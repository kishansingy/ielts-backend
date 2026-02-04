<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\WritingTask;
use App\Models\User;

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 Testing Writing API...\n\n";

try {
    // Test 1: Check if writing tasks exist
    $tasks = WritingTask::all();
    echo "📝 Total writing tasks in database: {$tasks->count()}\n";
    
    if ($tasks->count() > 0) {
        echo "✅ Writing tasks found:\n";
        foreach ($tasks->take(5) as $task) {
            echo "  - ID: {$task->id}, Title: {$task->title}, Type: {$task->task_type}\n";
        }
    } else {
        echo "❌ No writing tasks found in database\n";
    }
    
    echo "\n";
    
    // Test 2: Check user authentication
    $user = User::where('email', 'student1@test.com')->first();
    if ($user) {
        echo "✅ User found: {$user->email} (Role: {$user->role})\n";
        
        // Test 3: Simulate the controller logic
        echo "\n🔍 Testing controller logic...\n";
        
        $query = WritingTask::query();
        $tasksForUser = $query->orderBy('created_at', 'desc')->get();
        
        echo "📊 Tasks available for user: {$tasksForUser->count()}\n";
        
        // Add user submission data
        $userId = $user->id;
        $tasksForUser->each(function ($task) use ($userId) {
            $task->user_submissions_count = $task->submissions()
                ->where('user_id', $userId)
                ->count();
                
            $task->latest_submission = $task->submissions()
                ->where('user_id', $userId)
                ->latest('submitted_at')
                ->first();
                
            // Add review data if submissions exist
            if ($task->user_submissions_count > 0) {
                $task->can_review = true;
                $task->latest_submission_date = $task->latest_submission?->submitted_at;
                $task->latest_score = $task->latest_submission?->score;
            }
        });
        
        echo "\n📋 Tasks with user data:\n";
        foreach ($tasksForUser->take(3) as $task) {
            echo "  - {$task->title}\n";
            echo "    Submissions: {$task->user_submissions_count}\n";
            echo "    Can review: " . ($task->can_review ?? false ? 'YES' : 'NO') . "\n";
            echo "    Latest score: " . ($task->latest_score ?? 'N/A') . "\n";
            echo "\n";
        }
        
    } else {
        echo "❌ User not found\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}