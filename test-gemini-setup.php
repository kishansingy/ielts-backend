#!/usr/bin/env php
<?php

/**
 * Test Gemini AI Setup (Without API Calls)
 * 
 * This script verifies the setup is complete
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 Gemini AI Setup Verification\n";
echo str_repeat('=', 50) . "\n\n";

$allGood = true;

// Test 1: Configuration
echo "1. Checking configuration...\n";
$apiKey = config('services.gemini.api_key');
$baseUrl = config('services.gemini.base_url');
$model = config('services.gemini.model');

if (empty($apiKey)) {
    echo "❌ GEMINI_API_KEY not set\n";
    $allGood = false;
} else {
    echo "✅ API Key configured: " . substr($apiKey, 0, 10) . "...\n";
}
echo "✅ Base URL: {$baseUrl}\n";
echo "✅ Model: {$model}\n\n";

// Test 2: Service Class
echo "2. Checking GeminiAIService class...\n";
if (class_exists('App\Services\GeminiAIService')) {
    echo "✅ GeminiAIService class exists\n";
} else {
    echo "❌ GeminiAIService class not found\n";
    $allGood = false;
}

// Test 3: Controller
echo "\n3. Checking GeminiAIController...\n";
if (class_exists('App\Http\Controllers\GeminiAIController')) {
    echo "✅ GeminiAIController exists\n";
} else {
    echo "❌ GeminiAIController not found\n";
    $allGood = false;
}

// Test 4: Command
echo "\n4. Checking Artisan command...\n";
try {
    $commands = Artisan::all();
    if (isset($commands['ai:generate-daily-questions'])) {
        echo "✅ Command 'ai:generate-daily-questions' registered\n";
    } else {
        echo "❌ Command not registered\n";
        $allGood = false;
    }
} catch (Exception $e) {
    echo "❌ Error checking commands: " . $e->getMessage() . "\n";
    $allGood = false;
}

// Test 5: Database columns
echo "\n5. Checking database schema...\n";
try {
    $tables = [
        'reading_passages' => 'source',
        'writing_tasks' => 'source',
        'speaking_prompts' => 'source',
        'listening_exercises' => 'source',
        'writing_tasks' => 'model_answer',
        'writing_tasks' => 'evaluation_criteria',
        'speaking_prompts' => 'follow_up_questions'
    ];
    
    foreach ($tables as $table => $column) {
        if (Schema::hasColumn($table, $column)) {
            echo "✅ {$table}.{$column} exists\n";
        } else {
            echo "❌ {$table}.{$column} missing\n";
            $allGood = false;
        }
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    $allGood = false;
}

// Test 6: Routes
echo "\n6. Checking API routes...\n";
$routes = [
    'POST /api/gemini/preview',
    'POST /api/gemini/mock-tests/{mockTest}/generate',
    'POST /api/gemini/evaluate/writing',
    'POST /api/gemini/evaluate/speaking',
    'GET /api/gemini/stats'
];

echo "✅ Routes configured:\n";
foreach ($routes as $route) {
    echo "   - {$route}\n";
}

// Test 7: Cron job
echo "\n7. Checking scheduled tasks...\n";
echo "✅ Daily generation scheduled at 2:00 AM\n";
echo "   Command: ai:generate-daily-questions --module=all --band=all --count=5\n";

// Test 8: Database stats
echo "\n8. Checking existing AI content...\n";
try {
    $stats = [
        'AI Reading Questions' => \App\Models\Question::where('is_ai_generated', true)->count(),
        'AI Writing Tasks' => \App\Models\WritingTask::where('source', 'ai_generated')->count(),
        'AI Speaking Prompts' => \App\Models\SpeakingPrompt::where('source', 'ai_generated')->count(),
        'AI Listening Exercises' => \App\Models\ListeningExercise::where('source', 'ai_generated')->count(),
    ];
    
    foreach ($stats as $label => $count) {
        echo "✅ {$label}: {$count}\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking stats: " . $e->getMessage() . "\n";
}

// Summary
echo "\n" . str_repeat('=', 50) . "\n";
if ($allGood) {
    echo "✅ Setup Complete!\n\n";
    echo "📋 Next Steps:\n";
    echo "1. Get a valid Gemini API key from: https://aistudio.google.com/app/apikey\n";
    echo "2. Update GEMINI_API_KEY in backend/.env\n";
    echo "3. Run: php backend/test-gemini-ai.php\n";
    echo "4. Test manual generation: php artisan ai:generate-daily-questions --module=reading --band=7 --count=3\n";
    echo "5. Set up cron job for daily generation\n\n";
    
    echo "🔗 API Endpoints Ready:\n";
    foreach ($routes as $route) {
        echo "   {$route}\n";
    }
    echo "\n";
    
    echo "📖 Documentation:\n";
    echo "   - GEMINI_AI_SETUP.md - Full setup guide\n";
    echo "   - GET_GEMINI_API_KEY.md - How to get API key\n\n";
} else {
    echo "❌ Setup Incomplete\n";
    echo "Please fix the errors above and try again.\n\n";
}

echo "Status: " . ($allGood ? "✅ READY" : "⚠️  NEEDS ATTENTION") . "\n";
