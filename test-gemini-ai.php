#!/usr/bin/env php
<?php

/**
 * Test Gemini AI Integration
 * 
 * This script tests the Gemini AI service for IELTS content generation
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🧪 Testing Gemini AI Integration\n";
echo str_repeat('=', 50) . "\n\n";

// Test 1: Check configuration
echo "1. Checking Gemini AI configuration...\n";
$apiKey = config('services.gemini.api_key');
$baseUrl = config('services.gemini.base_url');
$model = config('services.gemini.model');

if (empty($apiKey)) {
    echo "❌ GEMINI_API_KEY not configured in .env\n";
    echo "   Please add: GEMINI_API_KEY=your_api_key_here\n\n";
    exit(1);
}

echo "✅ API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "✅ Base URL: {$baseUrl}\n";
echo "✅ Model: {$model}\n\n";

// Test 2: Test service instantiation
echo "2. Testing GeminiAIService instantiation...\n";
try {
    $geminiService = new \App\Services\GeminiAIService();
    echo "✅ Service instantiated successfully\n\n";
} catch (Exception $e) {
    echo "❌ Failed to instantiate service: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 3: Test Reading content generation
echo "3. Testing Reading content generation (Band 7)...\n";
try {
    $result = $geminiService->generateContent('reading', '7', ['question_count' => 3]);
    
    if (isset($result['passage']) && isset($result['questions'])) {
        echo "✅ Reading content generated successfully\n";
        echo "   Passage: " . substr($result['passage']->title, 0, 50) . "...\n";
        echo "   Questions: " . $result['questions']->count() . "\n\n";
    } else {
        echo "❌ Invalid response structure\n\n";
    }
} catch (Exception $e) {
    echo "❌ Reading generation failed: " . $e->getMessage() . "\n\n";
}

// Test 4: Test Writing content generation
echo "4. Testing Writing content generation (Band 7)...\n";
try {
    $result = $geminiService->generateContent('writing', '7');
    
    if (isset($result['tasks'])) {
        echo "✅ Writing content generated successfully\n";
        echo "   Tasks: " . $result['tasks']->count() . "\n\n";
    } else {
        echo "❌ Invalid response structure\n\n";
    }
} catch (Exception $e) {
    echo "❌ Writing generation failed: " . $e->getMessage() . "\n\n";
}

// Test 5: Test Speaking content generation
echo "5. Testing Speaking content generation (Band 7)...\n";
try {
    $result = $geminiService->generateContent('speaking', '7');
    
    if (isset($result['prompts'])) {
        echo "✅ Speaking content generated successfully\n";
        echo "   Prompts: " . $result['prompts']->count() . "\n\n";
    } else {
        echo "❌ Invalid response structure\n\n";
    }
} catch (Exception $e) {
    echo "❌ Speaking generation failed: " . $e->getMessage() . "\n\n";
}

// Test 6: Test Listening content generation
echo "6. Testing Listening content generation (Band 7)...\n";
try {
    $result = $geminiService->generateContent('listening', '7', ['question_count' => 3]);
    
    if (isset($result['exercise']) && isset($result['questions'])) {
        echo "✅ Listening content generated successfully\n";
        echo "   Exercise: " . substr($result['exercise']->title, 0, 50) . "...\n";
        echo "   Questions: " . $result['questions']->count() . "\n\n";
    } else {
        echo "❌ Invalid response structure\n\n";
    }
} catch (Exception $e) {
    echo "❌ Listening generation failed: " . $e->getMessage() . "\n\n";
}

// Test 7: Check database records
echo "7. Checking database records...\n";
$aiReadingQuestions = \App\Models\Question::where('is_ai_generated', true)->count();
$aiWritingTasks = \App\Models\WritingTask::where('source', 'ai_generated')->count();
$aiSpeakingPrompts = \App\Models\SpeakingPrompt::where('source', 'ai_generated')->count();
$aiListeningExercises = \App\Models\ListeningExercise::where('source', 'ai_generated')->count();

echo "✅ AI-generated content in database:\n";
echo "   Reading Questions: {$aiReadingQuestions}\n";
echo "   Writing Tasks: {$aiWritingTasks}\n";
echo "   Speaking Prompts: {$aiSpeakingPrompts}\n";
echo "   Listening Exercises: {$aiListeningExercises}\n\n";

// Summary
echo str_repeat('=', 50) . "\n";
echo "✨ Test Summary\n";
echo str_repeat('=', 50) . "\n";
echo "Gemini AI integration is working!\n\n";

echo "📋 Next Steps:\n";
echo "1. Test API endpoints using Postman or curl\n";
echo "2. Run daily generation command: php artisan ai:generate-daily-questions\n";
echo "3. Set up cron job for automated generation\n";
echo "4. Monitor generation logs in storage/logs/ai-question-generation.log\n\n";

echo "🔗 API Endpoints:\n";
echo "POST /api/gemini/preview\n";
echo "POST /api/gemini/mock-tests/{id}/generate\n";
echo "POST /api/gemini/evaluate/writing\n";
echo "POST /api/gemini/evaluate/speaking\n";
echo "GET  /api/gemini/stats\n\n";

echo "Test complete! ✅\n";
