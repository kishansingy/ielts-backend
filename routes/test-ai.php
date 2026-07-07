<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Services\AIQuestionGeneratorService;
use App\Models\User;
use App\Models\MockTest;

// Simple test route for AI question generation
Route::get('/test-ai-simple', function () {
    try {
        $service = new AIQuestionGeneratorService();
        
        // Get first user and mock test for testing
        $user = User::first();
        $mockTest = MockTest::first();
        
        if (!$user || !$mockTest) {
            return response()->json([
                'error' => 'Need at least 1 user and 1 mock test in database for testing'
            ], 400);
        }
        
        // Test generating questions
        $questions = $service->generateQuestionsForUser(
            $user, 
            $mockTest, 
            'reading', 
            '6', 
            2 // Just 2 questions for testing
        );
        
        return response()->json([
            'success' => true,
            'message' => 'AI question generation test successful',
            'user_id' => $user->id,
            'mock_test_id' => $mockTest->id,
            'questions_generated' => $questions->count(),
            'questions' => $questions->map(function($q) {
                return [
                    'id' => $q->id,
                    'question_text' => substr($q->question_text, 0, 100) . '...',
                    'question_type' => $q->question_type,
                    'ielts_band_level' => $q->ielts_band_level,
                    'is_ai_generated' => $q->is_ai_generated
                ];
            })
        ]);
        
    } catch (Exception $e) {
        return response()->json([
            'error' => 'AI generation failed: ' . $e->getMessage()
        ], 500);
    }
});

// Test route to check configuration
Route::get('/test-ai-config', function () {
    return response()->json([
        'openai_configured' => config('services.openai.api_key') !== 'your_openai_api_key_here',
        'openai_model' => config('services.openai.model'),
        'openai_base_url' => config('services.openai.base_url'),
        'database_tables' => [
            'questions' => \DB::table('questions')->count(),
            'question_usage_tracking' => \DB::table('question_usage_tracking')->count(),
            'ai_question_generation_log' => \DB::table('ai_question_generation_log')->count(),
        ],
        'users_count' => User::count(),
        'mock_tests_count' => MockTest::count(),
    ]);
});