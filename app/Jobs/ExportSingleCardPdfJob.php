<?php

namespace App\Jobs;

use App\Models\Export;
use App\Models\Student;
use App\Models\CampaignStudent;
use App\Services\CardRenderService;
use App\Services\TemplateResolverService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExportSingleCardPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;

    public function __construct(public int $exportId) {}

    public function handle(CardRenderService $renderer, TemplateResolverService $templateResolver): void
    {
        ini_set('memory_limit', '-1');
        set_time_limit(3600);

        $export = Export::findOrFail($this->exportId);
        $export->update(['status' => 'processing']);

        try {
            $studentIds = $export->params['student_ids'] ?? [];
            $campaignId = $export->params['campaign_id'] ?? null;
            $export->update(['total_items' => count($studentIds)]);

            // Get first student template to establish card orientation / physical dimensions (CR80 standard 85.6mm x 54mm)
            $firstStudent = !empty($studentIds) ? Student::find($studentIds[0]) : null;
            $firstEnrollment = $firstStudent ? CampaignStudent::where('student_id', $firstStudent->id)->first() : null;
            $sampleTemplate = $templateResolver->getEffectiveTemplate($export->school_id, $firstEnrollment?->grade_id);

            $orientation = $sampleTemplate->orientation ?? 'landscape';
            $isPortrait = $orientation === 'portrait';
            $cardWidthMm = $isPortrait ? 54.0 : 85.6;
            $cardHeightMm = $isPortrait ? 85.6 : 54.0;

            $studentsById = Student::whereIn('id', $studentIds)
                ->with(['campaignStudents' => function($q) use ($campaignId) {
                    $q->when($campaignId, fn($sq) => $sq->where('campaign_id', $campaignId))
                      ->with(['grade', 'division', 'verifier', 'campaign']);
                }])
                ->get()
                ->keyBy('id');

            $isMirrored = (bool) ($export->params['mirror_print'] ?? false);
            $studentIdChunks = array_chunk($studentIds, 60);
            $chunkPdfPaths = [];
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                @mkdir($tempDir, 0777, true);
            }

            $processedCount = 0;
            foreach ($studentIdChunks as $chunkIdx => $chunkIds) {
                $chunkItems = [];
                foreach ($chunkIds as $studentId) {
                    $student = $studentsById->get($studentId);
                    if (!$student) continue;

                    $enrollment = $student->campaignStudents->first();
                    $template = $templateResolver->getEffectiveTemplate($export->school_id, $enrollment?->grade_id);

                    $chunkItems[] = [
                        'student' => $student,
                        'template' => $template,
                        'school' => $export->school,
                    ];
                }

                $html = view('exports.single-card-pdf', [
                    'cardWidthMm' => $cardWidthMm,
                    'cardHeightMm' => $cardHeightMm,
                    'items' => $chunkItems,
                    'isMirrored' => $isMirrored,
                ])->render();

                $chunkPdf = $renderer->toPdf($html, $cardWidthMm, $cardHeightMm);
                $chunkPath = $tempDir . "/exp_{$export->id}_single_chunk_{$chunkIdx}.pdf";
                file_put_contents($chunkPath, $chunkPdf);
                $chunkPdfPaths[] = $chunkPath;

                $processedCount += count($chunkIds);
                $export->update(['processed_items' => $processedCount]);

                unset($chunkItems, $html, $chunkPdf);
                gc_collect_cycles();
            }

            $pdfRelativePath = 'exports/' . $export->id . '/single_cards_printer.pdf';
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
