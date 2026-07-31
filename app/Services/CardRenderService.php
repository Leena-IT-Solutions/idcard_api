<?php

namespace App\Services;

use App\Models\Student;
use App\Models\School;
use Spatie\Browsershot\Browsershot;

class CardRenderService
{
    public function renderFrontHtml($template, Student $student, School $school): string
    {
        return view('components.id-card-renderer', [
            'template' => $template,
            'student' => $student,
            'school' => $school,
            'scale' => 1.0,
            'previewMode' => false,
        ])->render();
    }

    protected function configureBrowsershot(Browsershot $browsershot): Browsershot
    {
        $cacheDir = env('PUPPETEER_CACHE_DIR');
        if (!$cacheDir || !file_exists(dirname($cacheDir))) {
            $cacheDir = storage_path('app/puppeteer');
        }
        if (!file_exists($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        putenv("PUPPETEER_CACHE_DIR={$cacheDir}");


        $tempPath = storage_path('app/temp');
        if (!file_exists($tempPath)) {
            @mkdir($tempPath, 0755, true);
        }

        $browsershot
            ->setTempPath($tempPath)
            ->setOption('args', [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--allow-file-access-from-files',
                '--disable-web-security',
                '--disable-dev-shm-usage',
            ]);


        $chromePath = env('CHROME_PATH');
        if (!$chromePath) {
            foreach ([
                '/usr/bin/google-chrome',
                '/usr/bin/google-chrome-stable',
                '/usr/bin/chromium-browser',
                '/usr/bin/chromium',
                '/usr/local/bin/chrome',
                '/opt/google/chrome/chrome',
            ] as $path) {
                if (file_exists($path)) {
                    $chromePath = $path;
                    break;
                }
            }
        }

        if ($chromePath) {
            $browsershot->setChromePath($chromePath);
        }

        $nodeModulePath = env('NODE_MODULES_PATH');
        if ($nodeModulePath) {
            $browsershot->setNodeModulePath($nodeModulePath);
        }

        return $browsershot;
    }

    protected function createBrowsershot(string $html): Browsershot
    {
        $tempPath = storage_path('app/temp');
        if (!file_exists($tempPath)) {
            @mkdir($tempPath, 0755, true);
        }

        $b = (new Browsershot())
            ->setTempPath($tempPath)
            ->setHtml($html);

        return $this->configureBrowsershot($b);
    }

    public function toPng(string $html, int $widthPx, int $heightPx): string
    {
        return $this->createBrowsershot($html)
            ->windowSize($widthPx, $heightPx)
            ->screenshot();
    }

    public function toPdf(string $html, float $widthMm, float $heightMm): string
    {
        return $this->createBrowsershot($html)
            ->showBackground()
            ->paperSize($widthMm, $heightMm, 'mm')
            ->margins(0, 0, 0, 0)
            ->pdf();
    }



}
