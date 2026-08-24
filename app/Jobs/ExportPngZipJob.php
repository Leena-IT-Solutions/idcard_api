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
use ZipArchive;

class ExportPngZipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;

    public function __construct(public int $exportId) {}

    public function handle(CardRenderService $renderer, TemplateResolverService $templateResolver): void
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(3600);

        $export = Export::findOrFail($this->exportId);
        $export->update(['status' => 'processing']);

        try {
            $studentIds = $export->params['student_ids'] ?? [];
            $campaignId = $export->params['campaign_id'] ?? null;
            $export->update(['total_items' => count($studentIds)]);

            $tmpDir = storage_path('app/private/tmp/exports/' . $export->id);
            if (!file_exists($tmpDir)) {
                @mkdir($tmpDir, 0777, true);
            }

            $isMirrored = (bool) ($export->params['mirror_print'] ?? false);
            $schoolCode = preg_replace('/[^A-Za-z0-9_-]/', '', $export->school->school_code ?? $export->school->name ?? 'SCHOOL');


            foreach ($studentIds as $i => $studentId) {
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

                $orientation = $template->orientation ?? 'landscape';
                $isPortrait = $orientation === 'portrait';
                $widthPx = $isPortrait ? 638 : 1011;
                $heightPx = $isPortrait ? 1011 : 638;

                $html = $renderer->renderFrontHtml($template, $student, $export->school, $isMirrored);
                $png = $renderer->toPng($html, $widthPx, $heightPx);

                $gradeName = preg_replace('/[^A-Za-z0-9_-]/', '', $enrollment?->grade?->name ?? 'Grade');
                $divName = preg_replace('/[^A-Za-z0-9_-]/', '', $enrollment?->division?->name ?? 'Div');
                $roll = preg_replace('/[^A-Za-z0-9_-]/', '', $enrollment?->roll_no ?? '0');
                $name = preg_replace('/[^A-Za-z0-9_-]/', '_', trim("{$student->first_name}_{$student->last_name}"));

                $filename = "{$schoolCode}_{$gradeName}-{$divName}_roll{$roll}_{$name}.png";
                file_put_contents($tmpDir . '/' . $filename, $png);

                if (($i + 1) % 5 === 0 || ($i + 1) === count($studentIds)) {
                    $export->update(['processed_items' => $i + 1]);
                    gc_collect_cycles();
                }
            }

            $zipRelativePath = 'exports/' . $export->id . '/id_cards_png.zip';
            $fullZipPath = storage_path('app/private/' . $zipRelativePath);

            if (!file_exists(dirname($fullZipPath))) {
                @mkdir(dirname($fullZipPath), 0777, true);
            }

            $zip = new ZipArchive();
            if ($zip->open($fullZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                foreach (glob($tmpDir . '/*.png') as $file) {
                    if (is_file($file)) {
                        $zip->addFile($file, basename($file));
                    }
                }
                $zip->close();
            }

            $this->deleteDirectory($tmpDir);

            $export->update([
                'status' => 'completed',
                'file_path' => $zipRelativePath,
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

    private function deleteDirectory($dir): void
    {
        if (!file_exists($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->deleteDirectory("$dir/$file") : unlink("$dir/$file");
        }
        rmdir($dir);
    }
}
