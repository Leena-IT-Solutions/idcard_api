<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::view('update-system', 'update-system')
    ->middleware(['auth'])
    ->name('update-system');

Route::middleware(['auth'])->group(function () {
    Route::get('/git-info', [\App\Http\Controllers\GitUpdaterController::class, 'info']);
    Route::post('/git-update', [\App\Http\Controllers\GitUpdaterController::class, 'update']);
});

require __DIR__.'/auth.php';
