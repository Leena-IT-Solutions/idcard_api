<?php

namespace App\Services;

use App\Models\Student;
use App\Models\School;
use Spatie\Browsershot\Browsershot;

class CardRenderService
{
    /**
     * Render the front-side HTML of an ID card using the Blade component.
     */
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

    /**
     * Create a fully-configured Browsershot instance with HTML set.
     * Sets tempPath BEFORE setHtml so the temp file is written to a
     * directory accessible by both PHP-FPM and Chrome.
     */
    protected function createBrowsershot(string $html): Browsershot
    {
        $b = (new Browsershot())
            ->setHtml($html);

        return $this->configureBrowsershot($b);
    }

    /**
     * Apply all environment-specific configuration to a Browsershot instance.
     * Detects Chrome, Node, NPM, and node_modules paths automatically,
     * with env() overrides for each.
     */
    protected function configureBrowsershot(Browsershot $browsershot): Browsershot
    {
        // --- Inject /usr/local/bin into PATH for www-data / PHP-FPM ---
        $currentPath = getenv('PATH') ?: '/usr/bin';
        $extraPaths = ['/usr/local/bin', '/usr/bin', '/bin'];
        foreach ($extraPaths as $ep) {
            if (strpos($currentPath, $ep) === false) {
                $currentPath = $ep . ':' . $currentPath;
            }
        }
        putenv("PATH={$currentPath}");

        // --- Puppeteer cache directory ---
        $cacheDir = env('PUPPETEER_CACHE_DIR');
        if (!$cacheDir || !is_dir($cacheDir)) {
            $cacheDir = storage_path('app/puppeteer');
        }
        if (!file_exists($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        putenv("PUPPETEER_CACHE_DIR={$cacheDir}");

        // --- Chrome user data dir (writable by www-data) ---
        $chromeDataDir = storage_path('app/chrome-data');
        if (!file_exists($chromeDataDir)) {
            @mkdir($chromeDataDir, 0755, true);
        }
        $crashDumpsDir = storage_path('app/chrome-crashes');
        if (!file_exists($crashDumpsDir)) {
            @mkdir($crashDumpsDir, 0755, true);
        }

        // --- Set HOME to writable dir for www-data (Chrome needs this) ---
        $home = getenv('HOME');
        if (!$home || !is_writable($home)) {
            putenv("HOME={$chromeDataDir}");
        }
        putenv("XDG_CONFIG_HOME={$chromeDataDir}");
        putenv("XDG_CACHE_HOME={$chromeDataDir}");

        // --- Chrome args ---
        $browsershot->setOption('args', [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--headless=new',
            '--allow-file-access-from-files',
            '--disable-web-security',
            '--disable-dev-shm-usage',
            '--disable-gpu',
            '--user-data-dir=' . $chromeDataDir,
            '--crash-dumps-dir=' . $crashDumpsDir,
            '--disable-crash-reporter',
            '--disable-breakpad',
            '--no-first-run',
            '--disable-extensions',
            '--disable-component-update',
            '--disable-default-apps',
        ]);

        // --- Chrome binary ---
        $chromePath = $this->resolveFromEnvOrPaths('CHROME_PATH', [
            '/usr/bin/google-chrome',
            '/usr/bin/google-chrome-stable',
            '/usr/bin/chromium-browser',
            '/usr/bin/chromium',
            '/usr/local/bin/google-chrome',
            '/usr/local/bin/chromium',
            '/opt/google/chrome/chrome',
        ]);
        if ($chromePath) {
            $browsershot->setChromePath($chromePath);
        }

        // --- Node binary (prioritise /usr/local/bin for `n` installs) ---
        $nodeBinary = $this->resolveFromEnvOrPaths('NODE_BINARY', [
            '/usr/local/bin/node',
            '/usr/bin/node',
            '/bin/node',
        ]);
        if ($nodeBinary) {
            $browsershot->setNodeBinary($nodeBinary);
        }

        // --- NPM binary ---
        $npmBinary = $this->resolveFromEnvOrPaths('NPM_BINARY', [
            '/usr/local/bin/npm',
            '/usr/bin/npm',
            '/bin/npm',
        ]);
        if ($npmBinary) {
            $browsershot->setNpmBinary($npmBinary);
        }

        // --- node_modules path ---
        $nodeModulePath = env('NODE_MODULES_PATH');
        if (!$nodeModulePath && file_exists(base_path('node_modules'))) {
            $nodeModulePath = base_path('node_modules');
        }
        if ($nodeModulePath) {
            $browsershot->setNodeModulePath($nodeModulePath);
        }

        return $browsershot;
    }

    /**
     * Resolve a binary path: check env() first, then iterate candidate paths.
     */
    protected function resolveFromEnvOrPaths(string $envKey, array $candidates): ?string
    {
        $fromEnv = env($envKey);
        if ($fromEnv && file_exists($fromEnv)) {
            return $fromEnv;
        }

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Render HTML to a PNG screenshot.
     */
    public function toPng(string $html, int $widthPx, int $heightPx): string
    {
        return $this->createBrowsershot($html)
            ->windowSize($widthPx, $heightPx)
            ->screenshot();
    }

    /**
     * Render HTML to a PDF document.
     */
    public function toPdf(string $html, float $widthMm, float $heightMm): string
    {
        return $this->createBrowsershot($html)
            ->showBackground()
            ->paperSize($widthMm, $heightMm, 'mm')
            ->margins(0, 0, 0, 0)
            ->pdf();
    }

    /**
     * Static diagnostic method — call from `php artisan tinker` or the
     * `browsershot:diagnose` command to see exactly what is detected.
     */
    public static function diagnose(): array
    {
        $service = new static();
        $results = [];

        // PATH
        $results['system_path'] = getenv('PATH') ?: '(not set)';

        // Node
        $nodeCandidates = ['/usr/local/bin/node', '/usr/bin/node', '/bin/node'];
        $nodeResolved = $service->resolveFromEnvOrPaths('NODE_BINARY', $nodeCandidates);
        $results['node_binary'] = $nodeResolved ?: '❌ NOT FOUND';
        if ($nodeResolved) {
            $results['node_version'] = trim(shell_exec($nodeResolved . ' -v 2>&1') ?? 'unknown');
        }

        // NPM
        $npmCandidates = ['/usr/local/bin/npm', '/usr/bin/npm', '/bin/npm'];
        $npmResolved = $service->resolveFromEnvOrPaths('NPM_BINARY', $npmCandidates);
        $results['npm_binary'] = $npmResolved ?: '❌ NOT FOUND';

        // Chrome
        $chromeCandidates = [
            '/usr/bin/google-chrome', '/usr/bin/google-chrome-stable',
            '/usr/bin/chromium-browser', '/usr/bin/chromium',
            '/usr/local/bin/google-chrome', '/usr/local/bin/chromium',
            '/opt/google/chrome/chrome',
        ];
        $chromeResolved = $service->resolveFromEnvOrPaths('CHROME_PATH', $chromeCandidates);
        $results['chrome_binary'] = $chromeResolved ?: '❌ NOT FOUND';
        if ($chromeResolved) {
            $results['chrome_version'] = trim(shell_exec($chromeResolved . ' --version 2>&1') ?? 'unknown');
        }

        // node_modules/puppeteer
        $nodeModulesPath = env('NODE_MODULES_PATH') ?: base_path('node_modules');
        $puppeteerExists = file_exists($nodeModulesPath . '/puppeteer/package.json');
        $results['node_modules_path'] = $nodeModulesPath;
        $results['puppeteer_installed'] = $puppeteerExists ? '✅ YES' : '❌ NO';

        // Puppeteer cache
        $cacheDir = env('PUPPETEER_CACHE_DIR');
        if (!$cacheDir || !is_dir($cacheDir)) {
            $cacheDir = storage_path('app/puppeteer');
        }
        $results['puppeteer_cache_dir'] = $cacheDir;
        $results['puppeteer_cache_exists'] = is_dir($cacheDir) ? '✅ YES' : '❌ NO';

        // Temp path
        $results['temp_path'] = storage_path('app/temp');
        $results['temp_writable'] = is_writable(storage_path('app')) ? '✅ YES' : '❌ NO';

        // Env overrides
        $results['env_CHROME_PATH'] = env('CHROME_PATH') ?: '(not set)';
        $results['env_NODE_BINARY'] = env('NODE_BINARY') ?: '(not set)';
        $results['env_NPM_BINARY'] = env('NPM_BINARY') ?: '(not set)';
        $results['env_NODE_MODULES_PATH'] = env('NODE_MODULES_PATH') ?: '(not set)';
        $results['env_PUPPETEER_CACHE_DIR'] = env('PUPPETEER_CACHE_DIR') ?: '(not set)';

        // Quick render test
        try {
            $pdf = $service->toPdf('<h1 style="color:green">Browsershot PDF OK</h1>', 85.6, 54.0);
            $png = $service->toPng('<h1 style="color:green">Browsershot PNG OK</h1>', 1011, 638);
            $results['test_render'] = '✅ SUCCESS (PDF: ' . strlen($pdf) . ' bytes, PNG: ' . strlen($png) . ' bytes)';
        } catch (\Throwable $e) {
            $results['test_render'] = '❌ FAILED: ' . $e->getMessage();
        }

        return $results;
    }
}
