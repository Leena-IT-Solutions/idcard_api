<?php

namespace App\Console\Commands;

use App\Models\SchoolTemplate;
use App\Models\Template;
use App\Services\CardRenderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RasterizeTemplateBackgrounds extends Command
{
    protected $signature = 'templates:rasterize-backgrounds {--dry-run : List affected templates without changing anything}';

    protected $description = 'Convert SVG backgrounds uploaded via the Template Studio editor to PNG, '
        . 'since flutter_svg cannot render the <filter>/mask idiom common in design-tool-exported SVGs.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $renderer = new CardRenderService();
        $total = 0;
        $converted = 0;
        $failed = 0;

        foreach ([Template::class, SchoolTemplate::class] as $modelClass) {
            $rows = $modelClass::whereNotNull('background_image')
                ->where('background_image', 'like', 'templates/backgrounds/%')
                ->where('background_image', 'like', '%.svg')
                ->get();

            foreach ($rows as $row) {
                $total++;
                $path = $row->background_image;

                if (!Storage::disk('public')->exists($path)) {
                    $this->warn("[{$modelClass}#{$row->id}] Missing file, skipping: {$path}");
                    $failed++;
                    continue;
                }

                $this->line("[{$modelClass}#{$row->id}] \"{$row->name}\" -> rasterizing {$path}" . ($dryRun ? ' (dry run)' : ''));

                if ($dryRun) {
                    continue;
                }

                $widthPx = $row->orientation === 'portrait' ? 638 : 1011;
                $heightPx = $row->orientation === 'portrait' ? 1011 : 638;

                try {
                    $svgContents = Storage::disk('public')->get($path);
                    $png = $renderer->rasterizeSvgToPng($svgContents, $widthPx, $heightPx);
                    $newPath = 'templates/backgrounds/' . uniqid('bg_', true) . '.png';
                    Storage::disk('public')->put($newPath, $png);

                    $row->update(['background_image' => $newPath]);
                    Storage::disk('public')->delete($path);

                    $converted++;
                } catch (\Throwable $e) {
                    $this->error("[{$modelClass}#{$row->id}] Failed: {$e->getMessage()}");
                    $failed++;
                }
            }
        }

        $this->info("Done. {$total} SVG background(s) found, {$converted} converted, {$failed} failed.");

        return Command::SUCCESS;
    }
}
