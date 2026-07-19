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
    
    Route::post('/students/upload-photo', [StudentController::class, 'uploadPhoto']);
    Route::apiResource('students', StudentController::class);

    // Campaigns & Enrollments
    Route::get('/campaigns', [CampaignController::class, 'index']);
    Route::get('/campaigns/{id}/options', [CampaignController::class, 'options']);
    Route::post('/campaigns/enroll', [CampaignController::class, 'enroll']);
    Route::get('/enrollments', [CampaignController::class, 'enrollments']);

    // School Admin API routes
    Route::get('/school-admin/schools', [\App\Http\Controllers\Api\SchoolAdminController::class, 'schools']);
    Route::get('/school-admin/options', [\App\Http\Controllers\Api\SchoolAdminController::class, 'options']);
    Route::get('/school-admin/members', [\App\Http\Controllers\Api\SchoolAdminController::class, 'members']);
    Route::get('/school-admin/invitations', [\App\Http\Controllers\Api\SchoolAdminController::class, 'invitations']);
    Route::post('/school-admin/invitations', [\App\Http\Controllers\Api\SchoolAdminController::class, 'invite']);
    Route::put('/school-admin/members/{id}', [\App\Http\Controllers\Api\SchoolAdminController::class, 'updateMember']);
    Route::delete('/school-admin/members/{id}', [\App\Http\Controllers\Api\SchoolAdminController::class, 'deleteMember']);
    Route::delete('/school-admin/invitations/{id}', [\App\Http\Controllers\Api\SchoolAdminController::class, 'revokeInvitation']);
    Route::get('/school-admin/students', [\App\Http\Controllers\Api\SchoolAdminController::class, 'students']);
    Route::post('/school-admin/students', [\App\Http\Controllers\Api\SchoolAdminController::class, 'saveStudent']);
    Route::delete('/school-admin/students/{id}', [\App\Http\Controllers\Api\SchoolAdminController::class, 'deleteStudent']);
});
