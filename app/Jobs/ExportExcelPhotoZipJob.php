<?php

namespace App\Jobs;

use App\Exports\StudentRosterExport;
use App\Models\Export;
use App\Models\Student;
use App\Models\CampaignStudent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use ZipArchive;

class ExportExcelPhotoZipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;

    public function __construct(public int $exportId) {}

    public function handle(): void
    {
        $export = Export::findOrFail($this->exportId);
        $export->update(['status' => 'processing']);

        try {
            $studentIds = $export->params['student_ids'] ?? [];
            $campaignId = $export->params['campaign_id'] ?? null;
            $export->update(['total_items' => count($studentIds)]);

            $tmpDir = storage_path('app/private/tmp/exports/' . $export->id);
            if (!file_exists($tmpDir . '/photos')) {
                mkdir($tmpDir . '/photos', 0755, true);
            }

            $exportRows = [];
            $schoolCode = preg_replace('/[^A-Za-z0-9_-]/', '', $export->school->school_code ?? $export->school->name ?? 'SCHOOL');


            foreach ($studentIds as $i => $studentId) {
                $student = Student::find($studentId);
                if (!$student) continue;

                $enrollment = CampaignStudent::where('student_id', $studentId)
                    ->when($campaignId, fn($q) => $q->where('campaign_id', $campaignId))
                    ->with(['grade', 'division', 'verifier', 'campaign'])
                    ->first();

                $exportRows[] = [
                    'student' => $student,
                    'enrollment' => $enrollment,
                ];

                // Copy photo if available
                if ($student->photo_path && Storage::disk('public')->exists($student->photo_path)) {
                    $gradeName = preg_replace('/[^A-Za-z0-9_-]/', '', $enrollment?->grade?->name ?? 'Grade');
                    $divName = preg_replace('/[^A-Za-z0-9_-]/', '', $enrollment?->division?->name ?? 'Div');
                    $roll = preg_replace('/[^A-Za-z0-9_-]/', '', $enrollment?->roll_no ?? '0');
                    $name = preg_replace('/[^A-Za-z0-9_-]/', '_', trim("{$student->first_name}_{$student->last_name}"));
                    $ext = pathinfo($student->photo_path, PATHINFO_EXTENSION) ?: 'jpg';

                    $photoFileName = "{$schoolCode}_{$gradeName}-{$divName}_roll{$roll}_{$name}.{$ext}";
                    $sourcePath = Storage::disk('public')->path($student->photo_path);
                    copy($sourcePath, $tmpDir . '/photos/' . $photoFileName);
                }

                if (($i + 1) % 10 === 0 || ($i + 1) === count($studentIds)) {
                    $export->update(['processed_items' => $i + 1]);
                }
            }

            // Export Excel Roster
            $excelPath = $tmpDir . '/roster.xlsx';
            Excel::store(new StudentRosterExport(collect($exportRows)), 'tmp/exports/' . $export->id . '/roster.xlsx', 'local');

            // Zip Excel + Photos
            $zipRelativePath = 'exports/' . $export->id . '/excel_photos.zip';
            $fullZipPath = storage_path('app/private/' . $zipRelativePath);

            if (!file_exists(dirname($fullZipPath))) {
                mkdir(dirname($fullZipPath), 0755, true);
            }

            $zip = new ZipArchive();
            if ($zip->open($fullZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                if (file_exists($excelPath)) {
                    $zip->addFile($excelPath, 'roster.xlsx');
                }

                $photos = glob($tmpDir . '/photos/*');
                foreach ($photos as $photoFile) {
                    if (is_file($photoFile)) {
                        $zip->addFile($photoFile, 'photos/' . basename($photoFile));
                    }
                }
                $zip->close();
            }

            // Cleanup temp dir
            $this->deleteDirectory($tmpDir);

            $export->update([
                'status' => 'completed',
                'file_path' => $zipRelativePath,
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
