<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;

class GitUpdaterController extends Controller
{
    public function info()
    {
        $branch = Process::run('git rev-parse --abbrev-ref HEAD')->output();
        $commit = Process::run('git rev-parse --short HEAD')->output();
        $status = Process::run('git status --short')->output();

        return response()->json([
            'branch' => trim($branch),
            'commit' => trim($commit),
            'status' => trim($status) ?: 'Clean',
        ]);
    }

    public function update()
    {
        $output = [];
        $output[] = Process::run('git pull origin main')->output();
        $output[] = Process::run('composer install --no-interaction --prefer-dist --optimize-autoloader')->output();
        $output[] = Process::run('php artisan migrate --force')->output();
        $output[] = Process::run('php artisan optimize:clear')->output();

        return response()->json([
            'message' => 'Update completed successfully.',
            'log' => implode("\n", $output)
        ]);
    }
}
