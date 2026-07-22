<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');
Route::view('privacy-policy', 'privacy')->name('privacy');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('users', function () {
    if (! auth()->user()->hasRole('saas_admin')) {
        abort(403);
    }
    return view('users');
})->middleware(['auth'])->name('users.index');

Route::get('schools', function () {
    $user = auth()->user();
    $activeSchoolId = session('active_school_id');
    
    $isSaasAdmin = $user->hasRole('saas_admin');
    
    $isSchoolAdmin = $activeSchoolId && \App\Models\SchoolUserRole::where('user_id', $user->id)
        ->where('school_id', $activeSchoolId)
        ->whereHas('role', function($q) { $q->where('slug', 'school_admin'); })
        ->exists();

    $hasAccess = $isSaasAdmin || $isSchoolAdmin || (!$activeSchoolId && $user->hasRole('school_admin'));

    if (!$hasAccess) {
        abort(403);
    }
    return view('schools');
})->middleware(['auth'])->name('schools');

Route::get('user-roles', function () {
    $user = auth()->user();
    $activeSchoolId = session('active_school_id');
    $isSchoolAdmin = $activeSchoolId && \App\Models\SchoolUserRole::where('user_id', $user->id)
        ->where('school_id', $activeSchoolId)
        ->whereHas('role', function($q) { $q->where('slug', 'school_admin'); })
        ->exists();

    if (!$user->hasRole('saas_admin') && !$isSchoolAdmin) {
        abort(403);
    }
    return view('user-roles');
})->middleware(['auth'])->name('user-roles');

Route::get('templates', function () {
    $user = auth()->user();
    $activeSchoolId = session('active_school_id');
    $isSchoolAdmin = $activeSchoolId && \App\Models\SchoolUserRole::where('user_id', $user->id)
        ->where('school_id', $activeSchoolId)
        ->whereHas('role', function($q) { $q->where('slug', 'school_admin'); })
        ->exists();

    if (!$user->hasRole('saas_admin') && !$isSchoolAdmin) {
        abort(403);
    }
    return view('templates');
})->middleware(['auth'])->name('templates');

Route::get('campaigns', function () {
    $user = auth()->user();
    $activeSchoolId = session('active_school_id');
    $isSchoolAdmin = $activeSchoolId && \App\Models\SchoolUserRole::where('user_id', $user->id)
        ->where('school_id', $activeSchoolId)
        ->whereHas('role', function($q) { $q->where('slug', 'school_admin'); })
        ->exists();

    if (!$user->hasRole('saas_admin') && !$isSchoolAdmin) {
        abort(403);
    }
    return view('campaigns');
})->middleware(['auth'])->name('campaigns');

Route::get('parent-access', function () {
    $user = auth()->user();
    $activeSchoolId = session('active_school_id');
    $isSchoolAdmin = $activeSchoolId && \App\Models\SchoolUserRole::where('user_id', $user->id)
        ->where('school_id', $activeSchoolId)
        ->whereHas('role', function($q) { $q->where('slug', 'school_admin'); })
        ->exists();

    if (!$user->hasRole('saas_admin') && !$isSchoolAdmin) {
        abort(403);
    }
    return view('parent-access');
})->middleware(['auth'])->name('parent-access');

Route::get('grades-divisions', function () {
    $user = auth()->user();
    $activeSchoolId = session('active_school_id');
    $hasAccess = $activeSchoolId && \App\Models\SchoolUserRole::where('user_id', $user->id)
        ->where('school_id', $activeSchoolId)
        ->whereHas('role', function($q) { $q->where('slug', 'school_admin'); })
        ->exists();

    if (!$user->hasRole('saas_admin') && !$hasAccess) {
        abort(403);
    }
    return view('grades-divisions');
})->middleware(['auth'])->name('grades-divisions');

Route::get('students', function () {
    $user = auth()->user();
    $activeSchoolId = session('active_school_id');
    $hasAccess = $activeSchoolId && \App\Models\SchoolUserRole::where('user_id', $user->id)
        ->where('school_id', $activeSchoolId)
        ->whereHas('role', function($q) { $q->whereIn('slug', ['school_admin', 'teacher']); })
        ->exists();

    if (!$user->hasRole('saas_admin') && !$hasAccess) {
        abort(403);
    }
    return view('students');
})->middleware(['auth'])->name('students');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('update-system', function () {
    if (! auth()->user()->hasRole('saas_admin')) {
        abort(403);
    }
    return view('update-system');
})->middleware(['auth'])->name('update-system');

Route::middleware(['auth'])->group(function () {
    Route::get('/git-info', function () {
        if (! auth()->user()->hasAnyRole(['saas_admin', 'school_admin'])) {
            abort(403);
        }
        try {
            $basePath = base_path();
            $currentUser = trim(shell_exec('whoami') ?? 'unknown');
            $gitDir = $basePath . '/.git';
            $gitExists = file_exists($gitDir);
            $gitReadable = $gitExists ? is_readable($gitDir) : false;

            $commitHash = trim(shell_exec('git -c safe.directory="' . $basePath . '" rev-parse --short HEAD') ?? '');
            $commitMessage = trim(shell_exec('git -c safe.directory="' . $basePath . '" log -1 --pretty=%B') ?? '');
            $branch = trim(shell_exec('git -c safe.directory="' . $basePath . '" rev-parse --abbrev-ref HEAD') ?? '');
            $commitDate = trim(shell_exec('git -c safe.directory="' . $basePath . '" log -1 --date=format:"%Y-%m-%d %H:%M:%S" --pretty=%cd') ?? '');
            $commitRelative = trim(shell_exec('git -c safe.directory="' . $basePath . '" log -1 --date=relative --pretty=%cd') ?? '');
            $remotes = trim(shell_exec('git -c safe.directory="' . $basePath . '" remote -v') ?? 'None');

            return response()->json([
                'success' => true,
                'branch' => ($branch && $branch !== 'HEAD') ? $branch : 'main',
                'commit_hash' => $commitHash ?: 'N/A',
                'commit_message' => $commitMessage ? strtok($commitMessage, "\n") : 'Git not initialized or not accessible',
                'commit_date' => $commitDate ?: 'N/A',
                'commit_relative' => $commitRelative ?: 'N/A',
                'diagnostics' => [
                    'php_user' => $currentUser,
                    'git_dir_exists' => $gitExists,
                    'git_dir_readable' => $gitReadable,
                    'remotes' => $remotes
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    })->name('git.info');

    Route::post('/git-update', function () {
        if (! auth()->user()->hasAnyRole(['saas_admin', 'school_admin'])) {
            abort(403);
        }
        $basePath = base_path();
        
        $branch = trim(shell_exec('git -c safe.directory="' . $basePath . '" rev-parse --abbrev-ref HEAD') ?? 'main');
        if ($branch === 'HEAD' || empty($branch) || $branch === 'Unknown') {
            $branch = 'main';
        }
        
        $commands = [
            'git -c safe.directory="' . $basePath . '" fetch origin ' . $branch . ' 2>&1',
            'git -c safe.directory="' . $basePath . '" reset --hard origin/' . $branch . ' 2>&1',
            'composer install --no-interaction --prefer-dist --optimize-autoloader 2>&1',
            'php artisan migrate --force 2>&1',
            'php artisan optimize:clear 2>&1',
        ];

        $output = ["Starting update process on branch '{$branch}'...\n"];
        $success = true;

        foreach ($commands as $command) {
            $output[] = "$ " . $command;
            $cmdOutput = [];
            $status = null;
            exec("cd " . $basePath . " && " . $command, $cmdOutput, $status);
            $output[] = implode("\n", $cmdOutput);
            $output[] = "Exit Code: " . $status . "\n";
            if ($status !== 0) {
                $success = false;
            }
        }

        if (function_exists('opcache_reset')) {
            opcache_reset();
            $output[] = "OPcache reset successfully.\n";
        }

        return response()->json([
            'success' => $success,
            'output' => implode("\n", $output),
        ]);
    })->name('git.update');

    Route::post('/artisan-run', function (\Illuminate\Http\Request $request) {
        if (! auth()->user()->hasAnyRole(['saas_admin', 'school_admin'])) {
            abort(403);
        }
        $commandKey = $request->input('command');
        $success = true;
        $output = '';

        try {
            switch ($commandKey) {
                case 'migrate':
                    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                    $output = \Illuminate\Support\Facades\Artisan::output();
                    break;
                case 'migrate-fresh':
                    \Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
                    $output = \Illuminate\Support\Facades\Artisan::output();
                    break;
                case 'seed':
                    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
                    $output = \Illuminate\Support\Facades\Artisan::output();
                    break;
                case 'clear-cache':
                    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
                    $output = \Illuminate\Support\Facades\Artisan::output();
                    
                    // Clear the Livewire Volt compiled classes folder
                    $livewireCachePath = storage_path('framework/cache/livewire');
                    if (\Illuminate\Support\Facades\File::exists($livewireCachePath)) {
                        \Illuminate\Support\Facades\File::cleanDirectory($livewireCachePath);
                        $output .= "\nLivewire/Volt cache directory cleared successfully.";
                    }

                    if (function_exists('opcache_reset')) {
                        opcache_reset();
                        $output .= "\nOPcache memory reset successfully.";
                    }
                    break;
                case 'optimize':
                    \Illuminate\Support\Facades\Artisan::call('optimize');
                    $output = \Illuminate\Support\Facades\Artisan::output();
                    break;
                case 'composer-install':
                    $basePath = base_path();
                    $cmdOutput = [];
                    $status = null;
                    exec("cd " . $basePath . " && COMPOSER_HOME=" . $basePath . "/.composer composer install --no-dev --optimize-autoloader 2>&1", $cmdOutput, $status);
                    $output = implode("\n", $cmdOutput) . "\nExit Code: " . $status;
                    $success = ($status === 0);
                    break;
                case 'fix-permissions':
                    $basePath = base_path();
                    $cmdOutput = [];
                    $status = null;
                    exec("chmod -R 777 " . $basePath . " 2>&1", $cmdOutput, $status);
                    $output = implode("\n", $cmdOutput) . "\nExit Code: " . $status;
                    $success = ($status === 0);
                    break;
                default:
                    return response()->json([
                        'success' => false,
                        'output' => 'Invalid command request.',
                    ], 400);
            }
        } catch (\Exception $e) {
            $success = false;
            $output = $e->getMessage();
        }

        return response()->json([
            'success' => $success,
            'output' => $output,
        ]);
    })->name('artisan.run');
});

require __DIR__.'/auth.php';
