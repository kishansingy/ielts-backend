<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
    });
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Student routes
    Route::middleware('role:student')->prefix('student')->group(function () {
        // Reading practice routes
        Route::prefix('reading')->group(function () {
            Route::get('/', [App\Http\Controllers\Student\ReadingPracticeController::class, 'index']);
            Route::post('/{passage}/start', [App\Http\Controllers\Student\ReadingPracticeController::class, 'start']);
            Route::post('/attempts/{attempt}/submit', [App\Http\Controllers\Student\ReadingPracticeController::class, 'submit']);
            Route::get('/history', [App\Http\Controllers\Student\ReadingPracticeController::class, 'history']);
            Route::get('/attempts/{attempt}/results', [App\Http\Controllers\Student\ReadingPracticeController::class, 'results']);
        });
        
        // Writing practice routes
        Route::prefix('writing')->group(function () {
            Route::get('/', [App\Http\Controllers\Student\WritingPracticeController::class, 'index']);
            Route::get('/{task}', [App\Http\Controllers\Student\WritingPracticeController::class, 'show']);
            Route::post('/{task}/submit', [App\Http\Controllers\Student\WritingPracticeController::class, 'submit']);
            Route::get('/history', [App\Http\Controllers\Student\WritingPracticeController::class, 'history']);
            Route::get('/submissions/{submission}/results', [App\Http\Controllers\Student\WritingPracticeController::class, 'results']);
        });
        
        // Listening practice routes
        Route::prefix('listening')->group(function () {
            Route::get('/', [App\Http\Controllers\Student\ListeningPracticeController::class, 'index']);
            Route::post('/{exercise}/start', [App\Http\Controllers\Student\ListeningPracticeController::class, 'start']);
            Route::post('/attempts/{attempt}/submit', [App\Http\Controllers\Student\ListeningPracticeController::class, 'submit']);
            Route::get('/history', [App\Http\Controllers\Student\ListeningPracticeController::class, 'history']);
            Route::get('/attempts/{attempt}/results', [App\Http\Controllers\Student\ListeningPracticeController::class, 'results']);
        });
        
        // Speaking practice routes
        Route::prefix('speaking')->group(function () {
            Route::get('/', [App\Http\Controllers\Student\SpeakingPracticeController::class, 'index']);
            Route::get('/{prompt}', [App\Http\Controllers\Student\SpeakingPracticeController::class, 'show']);
            Route::post('/{prompt}/submit', [App\Http\Controllers\Student\SpeakingPracticeController::class, 'submit']);
            Route::get('/history', [App\Http\Controllers\Student\SpeakingPracticeController::class, 'history']);
            Route::get('/submissions/{submission}/results', [App\Http\Controllers\Student\SpeakingPracticeController::class, 'results']);
        });
        
        // Student dashboard routes
        Route::prefix('dashboard')->group(function () {
            Route::get('/', [App\Http\Controllers\Student\DashboardController::class, 'index']);
            Route::get('/progress', [App\Http\Controllers\Student\DashboardController::class, 'progress']);
            Route::get('/leaderboard', [App\Http\Controllers\Student\DashboardController::class, 'leaderboard']);
            Route::get('/trends', [App\Http\Controllers\Student\DashboardController::class, 'trends']);
            Route::get('/achievements', [App\Http\Controllers\Student\DashboardController::class, 'achievements']);
        });
        
        // Progress and analytics routes
        Route::get('/progress', [App\Http\Controllers\Student\ProgressController::class, 'index']);
        Route::get('/leaderboard', [App\Http\Controllers\Student\ProgressController::class, 'leaderboard']);
        Route::get('/progress/{module}', [App\Http\Controllers\Student\ProgressController::class, 'moduleProgress']);
        Route::get('/progress/chart-data', [App\Http\Controllers\Student\ProgressController::class, 'chartData']);
    });
    
    // Admin routes
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // Reading content management routes
        Route::apiResource('reading-passages', App\Http\Controllers\ReadingController::class);
        
        // Writing content management routes
        Route::apiResource('writing-tasks', App\Http\Controllers\WritingController::class);
        
        // Listening content management routes
        Route::apiResource('listening-exercises', App\Http\Controllers\ListeningController::class);
        
        // Speaking content management routes
        Route::apiResource('speaking-prompts', App\Http\Controllers\SpeakingController::class);
        
        // Admin dashboard routes
        Route::prefix('dashboard')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\DashboardController::class, 'index']);
            Route::get('/users', [App\Http\Controllers\Admin\DashboardController::class, 'userAnalytics']);
            Route::get('/content', [App\Http\Controllers\Admin\DashboardController::class, 'contentAnalytics']);
            Route::get('/performance', [App\Http\Controllers\Admin\DashboardController::class, 'performanceMetrics']);
        });
        
        // File management routes
        Route::prefix('files')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\FileManagementController::class, 'index']);
            Route::post('/upload', [App\Http\Controllers\Admin\FileManagementController::class, 'upload']);
            Route::delete('/delete', [App\Http\Controllers\Admin\FileManagementController::class, 'delete']);
            Route::delete('/delete-multiple', [App\Http\Controllers\Admin\FileManagementController::class, 'deleteMultiple']);
            Route::get('/info', [App\Http\Controllers\Admin\FileManagementController::class, 'fileInfo']);
            Route::post('/cleanup', [App\Http\Controllers\Admin\FileManagementController::class, 'cleanup']);
        });
    });
});
