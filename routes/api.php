<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\CampaignController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::put('/user/password', [AuthController::class, 'changePassword']);
    Route::delete('/user', [AuthController::class, 'deleteAccount']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::apiResource('students', StudentController::class);

    // Campaigns & Enrollments
    Route::get('/campaigns', [CampaignController::class, 'index']);
    Route::get('/campaigns/{id}/options', [CampaignController::class, 'options']);
    Route::post('/campaigns/enroll', [CampaignController::class, 'enroll']);
    Route::get('/enrollments', [CampaignController::class, 'enrollments']);
});
