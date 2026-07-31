<?php

namespace App\Console\Commands;

use App\Services\CardRenderService;
use Illuminate\Console\Command;

class DiagnoseBrowsershotCommand extends Command
{
    protected $signature = 'browsershot:diagnose';
    protected $description = 'Check Browsershot environment: Node, Chrome, Puppeteer, paths, and run a test render.';

    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════╗');
        $this->info('║   Browsershot Environment Diagnostic  ║');
        $this->info('╚══════════════════════════════════════╝');
        $this->newLine();

        $results = CardRenderService::diagnose();

        $rows = [];
        foreach ($results as $key => $value) {
            $label = str_replace('_', ' ', ucfirst($key));
            $rows[] = [$label, $value];
        }

        $this->table(['Check', 'Value'], $rows);

        $this->newLine();

        if (str_contains($results['test_render'] ?? '', '❌')) {
            $this->error('Test render FAILED — see error message above.');
            return Command::FAILURE;
        }

        $this->info('✅ All checks passed. Browsershot is ready to render PDFs and PNGs.');
        return Command::SUCCESS;
    }
}
