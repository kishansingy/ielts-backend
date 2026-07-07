<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Submission;
use App\Models\User;
use App\Models\Attempt;

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 Checking All Submissions in Database...\n\n";

try {
    $user = User::where('email', 'student1@test.com')->first();
    if (!$user) {
        echo "❌ User student1@test.com not found\n";
        exit;
    }
    
    echo "✅ User found: {$user->email} (ID: {$user->id})\n\n";
    
    // Check all submissions (not just writing)
    $allSubmissions = Submission::where('user_id', $user->id)->get();
    echo "📊 Total submissions by user: {$allSubmissions->count()}\n\n";
    
    if ($allSubmissions->count() > 0) {
        echo "📝 SUBMISSION DETAILS:\n";
        foreach ($allSubmissions as $submission) {
            echo "  ID: {$submission->id}\n";
            echo "  Type: {$submission->submission_type}\n";
            echo "  Task ID: {$submission->task_id}\n";
            echo "  Content length: " . strlen($submission->content ?? '') . " chars\n";
            echo "  Score: " . ($submission->score ?? 'No score') . "\n";
            echo "  Has AI feedback: " . ($submission->ai_feedback ? 'YES' : 'NO') . "\n";
            echo "  Submitted at: {$submission->submitted_at}\n";
            echo "  Created at: {$submission->created_at}\n";
            echo "  ---\n";
        }
    }
    
    // Check attempts
    $allAttempts = Attempt::where('user_id', $user->id)->get();
    echo "\n🎯 Total attempts by user: {$allAttempts->count()}\n\n";
    
    if ($allAttempts->count() > 0) {
        echo "🎯 ATTEMPT DETAILS:\n";
        foreach ($allAttempts as $attempt) {
            echo "  ID: {$attempt->id}\n";
            echo "  Module: {$attempt->module_type}\n";
            echo "  Content ID: {$attempt->content_id}\n";
            echo "  Score: {$attempt->score}/{$attempt->max_score}\n";
            echo "  Status: {$attempt->status}\n";
            echo "  Completed: {$attempt->completed_at}\n";
            echo "  ---\n";
        }
    }
    
    // Check if there are submissions in other tables
    echo "\n🔍 Checking other possible submission tables...\n";
    
    // Check if there are any records in user_answers table
    try {
        $userAnswers = DB::table('user_answers')->where('user_id', $user->id)->count();
        echo "User answers: {$userAnswers}\n";
    } catch (Exception $e) {
        echo "User answers table: Not found or error\n";
    }
    
    // Check attempts table
    try {
        $attempts = DB::table('attempts')->where('user_id', $user->id)->count();
        echo "Attempts: {$attempts}\n";
    } catch (Exception $e) {
        echo "Attempts table: Not found or error\n";
    }
    
    // Check mock_test_attempts table
    try {
        $mockAttempts = DB::table('mock_test_attempts')->where('user_id', $user->id)->count();
        echo "Mock test attempts: {$mockAttempts}\n";
    } catch (Exception $e) {
        echo "Mock test attempts table: Not found or error\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}