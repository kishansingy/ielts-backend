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

// Test endpoint to verify backend is reachable
Route::get('test', function () {
    \Log::info('Test endpoint hit from: ' . request()->header('Origin'));
    return response()->json([
        'status' => 'success',
        'message' => 'Backend is reachable',
        'timestamp' => now(),
        'origin' => request()->header('Origin')
    ]);
});

// Welcome endpoint without authentication
Route::get('welcome', function () {
    \Log::info('Welcome endpoint hit from: ' . request()->header('Origin'));
    return response()->json([
        'status' => 'success',
        'message' => 'Welcome to IELTS Learning App!',
        'timestamp' => now(),
        'origin' => request()->header('Origin'),
        'note' => 'This endpoint works without authentication'
    ]);
});

// Handle OPTIONS preflight for simple-login
// Route::options('simple-login', function () {
//     \Log::info('OPTIONS request for simple-login from: ' . request()->header('Origin'));
//     return response()->json([], 200);
// });

// Simple login test without withCredentials
Route::post('simple-login', function () {
    return response()->json([
        'status' => 'ok',
        'data' => request()->all()
    ]);
    // \Log::info('=== SIMPLE LOGIN ATTEMPT ===');
    // \Log::info('Method: ' . request()->method());
    // \Log::info('Origin: ' . request()->header('Origin'));
    // \Log::info('Data: ' . json_encode(request()->all()));
    
    // $credentials = request()->only('email', 'password');
    
    // if (!Auth::attempt($credentials)) {
    //     \Log::error('Auth failed for: ' . request()->email);
    //     return response()->json(['error' => 'Invalid credentials'], 401);
    // }
    
    // $user = Auth::user();
    // $token = $user->createToken('auth-token')->plainTextToken;
    
    // \Log::info('Login successful for: ' . $user->email);
    
    // return response()->json([
    //     'user' => $user,
    //     'token' => $token
    // ]);
});

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('send-otp', [AuthController::class, 'sendOtp']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('check-availability', [AuthController::class, 'checkAvailability']);
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
            Route::get('/history', [App\Http\Controllers\Student\WritingPracticeController::class, 'history']);
            Route::get('/tips/improvement', [App\Http\Controllers\Student\WritingPracticeController::class, 'getImprovementTips']);
            Route::get('/submissions/{submission}/results', [App\Http\Controllers\Student\WritingPracticeController::class, 'results']);
            Route::get('/{task}', [App\Http\Controllers\Student\WritingPracticeController::class, 'show']);
            Route::post('/{task}/submit', [App\Http\Controllers\Student\WritingPracticeController::class, 'submit']);
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
        
        // Vocabulary routes
        Route::prefix('vocabulary')->group(function () {
            Route::get('/daily', [App\Http\Controllers\Student\VocabularyController::class, 'getDailyWord']);
            Route::get('/history', [App\Http\Controllers\Student\VocabularyController::class, 'getHistory']);
            Route::get('/{word}', [App\Http\Controllers\Student\VocabularyController::class, 'show']);
            Route::post('/{word}/interact', [App\Http\Controllers\Student\VocabularyController::class, 'recordInteraction']);
            Route::post('/bookmark/{word}', [App\Http\Controllers\Student\VocabularyController::class, 'bookmark']);
            Route::delete('/bookmark/{word}', [App\Http\Controllers\Student\VocabularyController::class, 'removeBookmark']);
        });

        // Notification device management
        Route::prefix('notifications')->group(function () {
            Route::post('/register-device', [App\Http\Controllers\Student\NotificationController::class, 'registerDevice']);
            Route::post('/unregister-device', [App\Http\Controllers\Student\NotificationController::class, 'unregisterDevice']);
            Route::get('/preferences', [App\Http\Controllers\Student\NotificationController::class, 'getPreferences']);
            Route::put('/preferences', [App\Http\Controllers\Student\NotificationController::class, 'updatePreferences']);
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
        
        // NEW: Attempt Review and Feedback Routes
        Route::prefix('attempts')->group(function () {
            Route::get('/', [App\Http\Controllers\Student\AttemptReviewController::class, 'getUserAttempts']);
            Route::get('/{id}/review', [App\Http\Controllers\Student\AttemptReviewController::class, 'getAttemptReview']);
            Route::get('/{id}/feedback', [App\Http\Controllers\Student\AttemptReviewController::class, 'getDetailedFeedback']);
        });
        
        Route::prefix('feedback')->group(function () {
            Route::get('/analytics', [App\Http\Controllers\Student\AttemptReviewController::class, 'getProgressAnalytics']);
            Route::get('/suggestions/{module}', [App\Http\Controllers\Student\AttemptReviewController::class, 'getModuleSuggestions']);
            Route::get('/study-plan', [App\Http\Controllers\Student\AttemptReviewController::class, 'getPersonalizedStudyPlan']);
        });
    });
    
    // Admin routes
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // Student management routes
        Route::prefix('students')->group(function () {
            Route::get('/', [App\Http\Controllers\AdminController::class, 'getStudents']);
            Route::post('/', [App\Http\Controllers\AdminController::class, 'createStudent']);
            Route::post('/link-user', [App\Http\Controllers\AdminController::class, 'linkUserToStudent']);
            Route::put('/{id}', [App\Http\Controllers\AdminController::class, 'updateStudent']);
            Route::patch('/{id}/password', [App\Http\Controllers\AdminController::class, 'updateStudentPassword']);
            Route::patch('/{id}/toggle-status', [App\Http\Controllers\AdminController::class, 'toggleStudentStatus']);
            Route::delete('/{id}', [App\Http\Controllers\AdminController::class, 'deleteStudent']);
            Route::get('/{id}/stats', [App\Http\Controllers\AdminController::class, 'getStudentStats']);
            Route::patch('/bulk-update-band-level', [App\Http\Controllers\AdminController::class, 'bulkUpdateBandLevel']);
        });
        
        // Get users without student profiles
        Route::get('/users/without-student-profile', [App\Http\Controllers\AdminController::class, 'getUsersWithoutStudentProfile']);
        
        // Test endpoint
        Route::get('/test-users', function() {
            return response()->json([
                'success' => true,
                'message' => 'Test endpoint working',
                'total_users' => \App\Models\User::count(),
                'student_users' => \App\Models\User::where('role', 'student')->count()
            ]);
        });
        
        // Dashboard stats
        Route::get('/dashboard-stats', [App\Http\Controllers\AdminController::class, 'getDashboardStats']);
        
        // Reading content management routes
        Route::apiResource('reading-passages', App\Http\Controllers\ReadingController::class);
        
        // Writing content management routes
        Route::apiResource('writing-tasks', App\Http\Controllers\WritingController::class);
        
        // Listening content management routes
        Route::apiResource('listening-exercises', App\Http\Controllers\ListeningController::class);
        
        // Speaking content management routes
        Route::apiResource('speaking-prompts', App\Http\Controllers\SpeakingController::class);
        
        // Vocabulary management routes
        Route::prefix('vocabulary')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\VocabularyController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Admin\VocabularyController::class, 'store']);
            Route::get('/{vocabularyWord}', [App\Http\Controllers\Admin\VocabularyController::class, 'show']);
            Route::put('/{vocabularyWord}', [App\Http\Controllers\Admin\VocabularyController::class, 'update']);
            Route::delete('/{vocabularyWord}', [App\Http\Controllers\Admin\VocabularyController::class, 'destroy']);
            Route::post('/bulk-import', [App\Http\Controllers\Admin\VocabularyController::class, 'bulkImport']);
            Route::get('/notifications/history', [App\Http\Controllers\Admin\VocabularyController::class, 'notificationHistory']);
            Route::post('/{vocabularyWord}/test-notification', [App\Http\Controllers\Admin\VocabularyController::class, 'sendTestNotification']);
        });
        
        // Mock Test management routes
        Route::prefix('mock-tests')->group(function () {
            Route::get('/content/available', [App\Http\Controllers\MockTestController::class, 'getAvailableContent']);
            Route::get('/', [App\Http\Controllers\MockTestController::class, 'index']);
            Route::post('/', [App\Http\Controllers\MockTestController::class, 'store']);
            Route::get('/{id}', [App\Http\Controllers\MockTestController::class, 'show']);
            Route::put('/{id}', [App\Http\Controllers\MockTestController::class, 'update']);
            Route::delete('/{id}', [App\Http\Controllers\MockTestController::class, 'destroy']);
        });
        
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
    
    // Mock Test routes for students
    Route::prefix('mock-tests')->group(function () {
        Route::get('/my-attempts', [App\Http\Controllers\MockTestController::class, 'myAttempts']);
        Route::get('/', [App\Http\Controllers\MockTestController::class, 'index']);
        Route::get('/{id}', [App\Http\Controllers\MockTestController::class, 'show']);
        Route::post('/{id}/start', [App\Http\Controllers\MockTestController::class, 'startAttempt']);
        Route::post('/attempts/{attemptId}/submit', [App\Http\Controllers\MockTestController::class, 'submitAttempt']);
        Route::get('/attempts/{attemptId}/results', [App\Http\Controllers\MockTestController::class, 'getAttemptResults']);
    });

    // AI Question Generation routes
    Route::prefix('ai-questions')->group(function () {
        Route::post('/mock-tests/{mockTest}/generate', [App\Http\Controllers\AIQuestionController::class, 'generateForMockTest']);
        Route::post('/mock-tests/{mockTest}/start-with-questions', [App\Http\Controllers\AIQuestionController::class, 'startMockTestWithQuestions']);
        Route::get('/preview', [App\Http\Controllers\AIQuestionController::class, 'previewQuestions']);
        Route::get('/stats', [App\Http\Controllers\AIQuestionController::class, 'getUserQuestionStats']);
        Route::get('/generation-history', [App\Http\Controllers\AIQuestionController::class, 'getGenerationHistory']);
        Route::get('/system-status', [App\Http\Controllers\AIQuestionController::class, 'getSystemStatus']);
        Route::post('/retry-openai', [App\Http\Controllers\AIQuestionController::class, 'retryOpenAI']);
    });

    // Band Level Management routes
    Route::prefix('band-levels')->group(function () {
        Route::get('/', [App\Http\Controllers\BandLevelController::class, 'getBandLevels']);
        Route::get('/stats', [App\Http\Controllers\BandLevelController::class, 'getBandLevelStats']);
        Route::get('/students', [App\Http\Controllers\BandLevelController::class, 'getStudentsByBandLevel']);
        
        // Admin only routes
        Route::middleware('role:admin')->group(function () {
            Route::post('/assign', [App\Http\Controllers\BandLevelController::class, 'assignBandLevel']);
            Route::put('/students/{userId}', [App\Http\Controllers\BandLevelController::class, 'updateBandLevel']);
            Route::patch('/students/{userId}/toggle-status', [App\Http\Controllers\BandLevelController::class, 'toggleStudentStatus']);
        });
    });

    // Test routes for AI system (remove in production)
    include __DIR__ . '/test-ai.php';
});
