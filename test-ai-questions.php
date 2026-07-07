<?php

require_once 'vendor/autoload.php';

use Illuminate\Http\Request;
use App\Services\AIQuestionGeneratorService;
use App\Models\User;
use App\Models\MockTest;

// Simple test script to verify AI question generation
echo "Testing AI Question Generation System...\n\n";

try {
    // Test 1: Check if service can be instantiated
    echo "1. Testing service instantiation...\n";
    $aiService = new AIQuestionGeneratorService();
    echo "✓ AIQuestionGeneratorService created successfully\n\n";

    // Test 2: Check database connection and models
    echo "2. Testing database models...\n";
    
    $userCount = User::count();
    echo "✓ Users in database: {$userCount}\n";
    
    $mockTestCount = MockTest::count();
    echo "✓ Mock tests in database: {$mockTestCount}\n";
    
    // Test 3: Check if we have the required environment variables
    echo "\n3. Checking environment configuration...\n";
    
    $openaiKey = config('services.openai.api_key');
    if ($openaiKey && $openaiKey !== 'your_openai_api_key_here') {
        echo "✓ OpenAI API key is configured\n";
    } else {
        echo "⚠ OpenAI API key not configured or using default value\n";
        echo "  Please set OPENAI_API_KEY in your .env file\n";
    }
    
    $baseUrl = config('services.openai.base_url');
    echo "✓ OpenAI base URL: {$baseUrl}\n";
    
    $model = config('services.openai.model');
    echo "✓ OpenAI model: {$model}\n";

    // Test 4: Check database tables
    echo "\n4. Checking database tables...\n";
    
    try {
        \DB::table('questions')->count();
        echo "✓ questions table exists\n";
    } catch (Exception $e) {
        echo "✗ questions table missing or inaccessible\n";
    }
    
    try {
        \DB::table('question_usage_tracking')->count();
        echo "✓ question_usage_tracking table exists\n";
    } catch (Exception $e) {
        echo "✗ question_usage_tracking table missing - run migrations\n";
    }
    
    try {
        \DB::table('ai_question_generation_log')->count();
        echo "✓ ai_question_generation_log table exists\n";
    } catch (Exception $e) {
        echo "✗ ai_question_generation_log table missing - run migrations\n";
    }

    // Test 5: Test question retrieval methods
    echo "\n5. Testing question retrieval...\n";
    
    $availableQuestions = \App\Models\Question::where('is_retired', false)->count();
    echo "✓ Available questions: {$availableQuestions}\n";
    
    $aiGeneratedQuestions = \App\Models\Question::where('is_ai_generated', true)->count();
    echo "✓ AI generated questions: {$aiGeneratedQuestions}\n";

    echo "\n=== Test Summary ===\n";
    echo "✓ Basic system components are working\n";
    
    if ($openaiKey && $openaiKey !== 'your_openai_api_key_here') {
        echo "✓ Ready for AI question generation\n";
        echo "\nNext steps:\n";
        echo "1. Test the API endpoints using Postman or curl\n";
        echo "2. Try generating questions through the frontend\n";
        echo "3. Monitor the ai_question_generation_log table for activity\n";
    } else {
        echo "⚠ Configure OpenAI API key to enable AI generation\n";
        echo "\nTo complete setup:\n";
        echo "1. Add OPENAI_API_KEY=your_actual_key to .env file\n";
        echo "2. Run: php artisan config:clear\n";
        echo "3. Test API endpoints\n";
    }

} catch (Exception $e) {
    echo "✗ Error during testing: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== API Endpoints to Test ===\n";
echo "POST /api/ai-questions/preview\n";
echo "POST /api/ai-questions/mock-tests/{id}/generate\n";
echo "POST /api/ai-questions/mock-tests/{id}/start-with-questions\n";
echo "GET  /api/ai-questions/stats\n";
echo "GET  /api/ai-questions/generation-history\n";

echo "\nTest complete!\n";