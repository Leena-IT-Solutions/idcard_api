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

    public function toPng(string $html, int $widthPx, int $heightPx): string
    {
        $cacheDir = env('PUPPETEER_CACHE_DIR', storage_path('app/puppeteer'));
        putenv("PUPPETEER_CACHE_DIR={$cacheDir}");

        return Browsershot::html($html)
            ->windowSize($widthPx, $heightPx)
            ->setOption('args', ['--no-sandbox'])
            ->screenshot();
    }

    public function toPdf(string $html, float $widthMm, float $heightMm): string
    {
        $cacheDir = env('PUPPETEER_CACHE_DIR', storage_path('app/puppeteer'));
        putenv("PUPPETEER_CACHE_DIR={$cacheDir}");

        return Browsershot::html($html)
            ->showBackground()
            ->setOption('args', ['--no-sandbox'])
            ->paperSize($widthMm, $heightMm, 'mm')
            ->margins(0, 0, 0, 0)
            ->pdf();
    }

}
