<?php

namespace App\Jobs;

use App\Models\Export;
use App\Models\Student;
use App\Models\CampaignStudent;
use App\Services\CardRenderService;
use App\Services\ImpositionLayoutService;
use App\Services\TemplateResolverService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExportImpositionPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;

    public function __construct(public int $exportId) {}

    public function handle(CardRenderService $renderer, ImpositionLayoutService $layoutService, TemplateResolverService $templateResolver): void
    {
        ini_set('memory_limit', '-1');
        set_time_limit(3600);

        $export = Export::findOrFail($this->exportId);
        $export->update(['status' => 'processing']);

        try {
            $studentIds = $export->params['student_ids'] ?? [];
            $campaignId = $export->params['campaign_id'] ?? null;
            $params = $export->params ?? [];
            $export->update(['total_items' => count($studentIds)]);

            // Get first student template to establish card orientation / size in layout calculation
            $firstStudent = !empty($studentIds) ? Student::find($studentIds[0]) : null;
            $firstEnrollment = $firstStudent ? CampaignStudent::where('student_id', $firstStudent->id)->first() : null;
            $sampleTemplate = $templateResolver->getEffectiveTemplate($export->school_id, $firstEnrollment?->grade_id);

            $orientation = $sampleTemplate->orientation ?? 'landscape';
            $isPortrait = $orientation === 'portrait';
            $cardWidthMm = $isPortrait ? 54.0 : 85.6;
            $cardHeightMm = $isPortrait ? 85.6 : 54.0;

            $layout = $layoutService->calculateLayout($params, $cardWidthMm, $cardHeightMm);

            $pages = [];
            $currentPageCards = [];
            $cols = $layout['cols'];
            $rows = $layout['rows'];
            $cardsPerPage = $layout['cards_per_page'];

            $isMirrored = (bool) ($export->params['mirror_print'] ?? false);

            $itemIndex = 0;
            foreach ($studentIds as $studentId) {
                $student = Student::find($studentId);
                if (!$student) continue;

                $enrollment = CampaignStudent::where('student_id', $studentId)
                    ->when($campaignId, fn($q) => $q->where('campaign_id', $campaignId))
                    ->with(['grade', 'division', 'verifier', 'campaign'])
                    ->first();

                if ($enrollment) {
                    $student->setRelation('campaignStudents', collect([$enrollment]));
                }

                $template = $templateResolver->getEffectiveTemplate($export->school_id, $enrollment?->grade_id);

                $cardIndexInPage = count($currentPageCards);
                $row = (int) floor($cardIndexInPage / $cols);
                $rawCol = $cardIndexInPage % $cols;
                $col = $isMirrored ? ($cols - 1 - $rawCol) : $rawCol;

                $currentPageCards[] = [
                    'row' => $row,
                    'col' => $col,
                    'student' => $student,
                    'template' => $template,
                    'school' => $export->school,
                ];

                $itemIndex++;
                if (($itemIndex % 5) === 0 || $itemIndex === count($studentIds)) {
                    $export->update(['processed_items' => $itemIndex]);
                }

                if (count($currentPageCards) >= $cardsPerPage) {
                    $pages[] = $currentPageCards;
                    $currentPageCards = [];
                }
            }

            if (!empty($currentPageCards)) {
                $pages[] = $currentPageCards;
            }

            $totalPagesCount = count($pages);
            $pagesPerBatch = 2; // 2 imposition pages per batch for fast sub-3s Chrome render speed and low memory footprint
            $pageChunks = array_chunk($pages, $pagesPerBatch);
            $chunkPdfPaths = [];
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                @mkdir($tempDir, 0777, true);
            }

            foreach ($pageChunks as $chunkIdx => $pageChunk) {
                $html = view('exports.imposition-sheet', [
                    'layout' => $layout,
                    'pages' => $pageChunk,
                    'isMirrored' => $isMirrored,
                ])->render();

                $chunkPdf = $renderer->toPdf($html, $layout['page_width_mm'], $layout['page_height_mm']);
                $chunkPath = $tempDir . "/exp_{$export->id}_imposition_chunk_{$chunkIdx}.pdf";
                file_put_contents($chunkPath, $chunkPdf);
                $chunkPdfPaths[] = $chunkPath;

                unset($html, $chunkPdf);
            }

            $pdfRelativePath = 'exports/' . $export->id . '/imposition_print.pdf';
            $fullPdfPath = storage_path('app/private/' . $pdfRelativePath);

            if (!file_exists(dirname($fullPdfPath))) {
                @mkdir(dirname($fullPdfPath), 0777, true);
            }

            if (count($chunkPdfPaths) === 1) {
                rename($chunkPdfPaths[0], $fullPdfPath);
            } else {
                $mergedPdf = $renderer->mergePdfs($chunkPdfPaths);
                file_put_contents($fullPdfPath, $mergedPdf);
                foreach ($chunkPdfPaths as $cp) {
                    @unlink($cp);
                }
            }

            $export->update([
                'status' => 'completed',
                'file_path' => $pdfRelativePath,
                'completed_at' => now(),
                'processed_items' => count($studentIds),
            ]);

            // Deduct credits from school wallet upon successful completion
            $school = $export->school;
            $neededCredits = count($studentIds);
            if ($school && $neededCredits > 0) {
                $school->deductCredits(
                    $neededCredits,
                    "Export: " . str_replace('_', ' ', strtoupper($export->type)) . " — {$neededCredits} student cards (Export #{$export->id})",
                    $export,
                    $export->user
                );
            }

            // Mark exported students as Sent for Printing if requested
            if (!empty($export->params['send_for_printing']) && !empty($studentIds)) {
                $campQuery = CampaignStudent::whereIn('student_id', $studentIds);
                if (!empty($export->params['campaign_id'])) {
                    $campQuery->where('campaign_id', $export->params['campaign_id']);
                }
                $campQuery->update([
                    'status' => CampaignStudent::STATUS_SENT_FOR_PRINTING,
                    'status_updated_at' => now(),
                    'status_updated_by' => $export->user_id,
                ]);
            }
        } catch (\Throwable $e) {
            $export->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            gc_collect_cycles();
        }
    }
}
