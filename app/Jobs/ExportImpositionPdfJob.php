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
        ini_set('memory_limit', '1024M');
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

            $html = view('exports.imposition-sheet', [
                'layout' => $layout,
                'pages' => $pages,
                'isMirrored' => $isMirrored,
            ])->render();

            $pdf = $renderer->toPdf($html, $layout['page_width_mm'], $layout['page_height_mm']);

            $pdfRelativePath = 'exports/' . $export->id . '/imposition_print.pdf';
            $fullPdfPath = storage_path('app/private/' . $pdfRelativePath);

            if (!file_exists(dirname($fullPdfPath))) {
                mkdir(dirname($fullPdfPath), 0755, true);
            }

            file_put_contents($fullPdfPath, $pdf);

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
