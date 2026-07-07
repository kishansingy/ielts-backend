<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\WritingTask;
use App\Models\Submission;
use App\Models\User;

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 Debugging Writing Submissions...\n\n";

try {
    $user = User::where('email', 'student1@test.com')->first();
    if (!$user) {
        echo "❌ User student1@test.com not found\n";
        exit;
    }
    
    echo "✅ User found: {$user->email} (ID: {$user->id})\n\n";
    
    $tasks = WritingTask::all();
    echo "📝 Total writing tasks: {$tasks->count()}\n\n";
    
    foreach ($tasks as $task) {
        echo "📋 Task: {$task->title}\n";
        echo "   ID: {$task->id}\n";
        echo "   Task Type: {$task->task_type}\n";
        
        $submissionCount = $task->submissions()->where('user_id', $user->id)->count();
        $latestSubmission = $task->submissions()->where('user_id', $user->id)->latest('submitted_at')->first();
        
        echo "   Submissions by user: {$submissionCount}\n";
        
        if ($latestSubmission) {
            echo "   Latest submission ID: {$latestSubmission->id}\n";
            echo "   Latest submission date: {$latestSubmission->submitted_at}\n";
            echo "   Latest submission score: " . ($latestSubmission->score ?? 'No score') . "\n";
        } else {
            echo "   Latest submission: None\n";
        }
        
        echo "   Can review: " . ($submissionCount > 0 ? 'YES' : 'NO') . "\n";
        echo "\n";
    }
    
    // Check all submissions for this user
    echo "🔍 All submissions by user:\n";
    $allSubmissions = Submission::where('user_id', $user->id)->get();
    echo "Total submissions: {$allSubmissions->count()}\n";
    
    foreach ($allSubmissions as $submission) {
        echo "  - ID: {$submission->id}, Type: {$submission->submission_type}, Task ID: {$submission->task_id}, Date: {$submission->submitted_at}\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}