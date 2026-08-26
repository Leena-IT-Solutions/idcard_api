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
            // Eager-load all students and their enrollments in 1 bulk query
            $studentsById = Student::whereIn('id', $studentIds)
                ->with(['campaignStudents' => function($q) use ($campaignId) {
                    $q->when($campaignId, fn($sq) => $sq->where('campaign_id', $campaignId))
                      ->with(['grade', 'division', 'verifier', 'campaign']);
                }])
                ->get()
                ->keyBy('id');

            $firstStudent = $studentsById->first();
            $firstEnrollment = $firstStudent?->campaignStudents?->first();
            $sampleTemplate = $templateResolver->getEffectiveTemplate($export->school_id, $firstEnrollment?->grade_id);

            $cardSize = $export->params['card_size'] ?? 'bleed';
            $isPunch = ($cardSize === 'punch');

            $orientation = $sampleTemplate->orientation ?? 'landscape';
            $isPortrait = $orientation === 'portrait';
            if ($isPunch) {
                $cardWidthMm = $isPortrait ? 54.0 : 86.0;
                $cardHeightMm = $isPortrait ? 86.0 : 54.0;
            } else {
                $cardWidthMm = $isPortrait ? 57.0 : 90.0;
                $cardHeightMm = $isPortrait ? 90.0 : 57.0;
            }
            $params['bleed_mm'] = 0.0;
            $params['margin_mm'] = 0.0;
            $layout = $layoutService->calculateLayout($params, $cardWidthMm, $cardHeightMm);

            $pages = [];
            $currentPageCards = [];
            $cols = $layout['cols'];
            $rows = $layout['rows'];
            $cardsPerPage = $layout['cards_per_page'];

            $isMirrored = (bool) ($export->params['mirror_print'] ?? false);

            $itemIndex = 0;
            foreach ($studentIds as $studentId) {
                $student = $studentsById->get($studentId);
                if (!$student) continue;

                $enrollment = $student->campaignStudents->first();
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

                if (count($currentPageCards) >= $cardsPerPage) {
                    $pages[] = $currentPageCards;
                    $currentPageCards = [];
                }
            }

            if (!empty($currentPageCards)) {
                $pages[] = $currentPageCards;
            }

            $totalPagesCount = count($pages);
            $pagesPerBatch = 3; // 3 imposition pages (~24 cards) per batch: renders in 5-8s with frequent live progress feedback
            $pageChunks = array_chunk($pages, $pagesPerBatch);
            $chunkPdfPaths = [];
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                @mkdir($tempDir, 0777, true);
            }

            $renderedCardsCount = 0;
            foreach ($pageChunks as $chunkIdx => $pageChunk) {
                // Abort immediately if the user deleted this export from the UI
                if (!Export::where('id', $this->exportId)->exists()) {
                    foreach ($chunkPdfPaths as $cp) { @unlink($cp); }
                    return;
                }

                $html = view('exports.imposition-sheet', [
                    'layout' => $layout,
                    'pages' => $pageChunk,
                    'isMirrored' => $isMirrored,
                    'cardSize' => $cardSize,
                ])->render();

                $chunkPdf = $renderer->toPdf($html, $layout['page_width_mm'], $layout['page_height_mm']);
                $chunkPath = $tempDir . "/exp_{$export->id}_imposition_chunk_{$chunkIdx}.pdf";
                file_put_contents($chunkPath, $chunkPdf);
                $chunkPdfPaths[] = $chunkPath;

                $chunkCardsCount = array_reduce($pageChunk, fn($carry, $page) => $carry + count($page), 0);
                $renderedCardsCount += $chunkCardsCount;
                $export->update(['processed_items' => min($renderedCardsCount, count($studentIds))]);

                unset($html, $chunkPdf);
                gc_collect_cycles();
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
