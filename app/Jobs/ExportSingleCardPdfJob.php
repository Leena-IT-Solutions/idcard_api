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

            $items = [];
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

                $items[] = [
                    'student' => $student,
                    'template' => $template,
                    'school' => $export->school,
                ];

                $itemIndex++;
                if (($itemIndex % 5) === 0 || $itemIndex === count($studentIds)) {
                    $export->update(['processed_items' => $itemIndex]);
                }
            }

            $isMirrored = (bool) ($export->params['mirror_print'] ?? false);

            $itemChunks = array_chunk($items, 30);
            $chunkPdfPaths = [];
            $tempDir = storage_path('app/temp');
            if (!file_exists($tempDir)) {
                @mkdir($tempDir, 0777, true);
            }

            foreach ($itemChunks as $chunkIdx => $itemChunk) {
                $html = view('exports.single-card-pdf', [
                    'cardWidthMm' => $cardWidthMm,
                    'cardHeightMm' => $cardHeightMm,
                    'items' => $itemChunk,
                    'isMirrored' => $isMirrored,
                ])->render();

                $chunkPdf = $renderer->toPdf($html, $cardWidthMm, $cardHeightMm);
                $chunkPath = $tempDir . "/exp_{$export->id}_single_chunk_{$chunkIdx}.pdf";
                file_put_contents($chunkPath, $chunkPdf);
                $chunkPdfPaths[] = $chunkPath;

                unset($html, $chunkPdf);
            }

            $pdfRelativePath = 'exports/' . $export->id . '/single_cards_printer.pdf';
            $fullPdfPath = storage_path('app/private/' . $pdfRelativePath);

            if (!file_exists(dirname($fullPdfPath))) {
                mkdir(dirname($fullPdfPath), 0755, true);
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
            ]);
        } catch (\Throwable $e) {
            $export->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
