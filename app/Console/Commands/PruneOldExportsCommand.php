<?php

namespace App\Console\Commands;

use App\Models\Export;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneOldExportsCommand extends Command
{
    protected $signature = 'exports:prune {--days=7 : Delete exports older than this number of days}';
    protected $description = 'Prune old exported files from local private storage and database';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $exports = Export::where('created_at', '<', $cutoff)->get();
        $count = 0;

        foreach ($exports as $export) {
            if ($export->file_path && Storage::disk('local')->exists($export->file_path)) {
                Storage::disk('local')->delete($export->file_path);
            }
            $export->delete();
            $count++;
        }

        $this->info("Pruned {$count} old export records and files older than {$days} days.");
        return Command::SUCCESS;
    }
}
