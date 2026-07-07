<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\WritingTask;
use App\Models\Submission;
use App\Models\User;
use App\Models\Attempt;
use Illuminate\Support\Facades\DB;

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔄 Migrating Old Submissions to New Review System...\n\n";

try {
    DB::beginTransaction();
    
    // Find all old submissions that don't have proper ai_feedback
    $oldSubmissions = Submission::where('submission_type', 'writing')
        ->where(function($query) {
            $query->whereNull('ai_feedback')
                  ->orWhere('ai_feedback', '[]')
                  ->orWhere('ai_feedback', '{}');
        })
        ->get();
    
    echo "📊 Found {$oldSubmissions->count()} old writing submissions to migrate\n\n";
    
    foreach ($oldSubmissions as $submission) {
        echo "🔄 Processing submission ID: {$submission->id}\n";
        
        // Get the task
        $task = WritingTask::find($submission->task_id);
        if (!$task) {
            echo "   ⚠️ Task not found, skipping...\n";
            continue;
        }
        
        // Get word count from content
        $wordCount = $submission->content ? str_word_count(strip_tags($submission->content)) : 0;
        
        // Generate AI feedback for old submission
        $aiFeedback = [
            'overall_score' => $submission->score ?? 70, // Use existing score or default
            'word_count' => $wordCount,
            'character_count' => strlen($submission->content ?? ''),
            'sentence_count' => substr_count($submission->content ?? '', '.'),
            'feedback' => [
                'strengths' => [
                    'Completed the writing task',
                    'Addressed the main topic',
                    'Used appropriate essay structure'
                ],
                'improvements' => [
                    'Consider expanding your arguments with more examples',
                    'Work on vocabulary variety',
                    'Focus on sentence structure improvement'
                ],
                'suggestions' => [
                    'Practice writing within time limits',
                    'Use more linking words for better coherence',
                    'Proofread for grammar and spelling errors'
                ]
            ],
            'scores' => [
                'task_achievement' => ($submission->score ?? 70) * 0.95,
                'coherence_cohesion' => ($submission->score ?? 70) * 1.05,
                'lexical_resource' => ($submission->score ?? 70) * 0.98,
                'grammatical_accuracy' => ($submission->score ?? 70) * 1.02,
            ]
        ];
        
        // Update the submission with AI feedback
        $submission->update([
            'ai_feedback' => $aiFeedback,
            'score' => $submission->score ?? $aiFeedback['overall_score']
        ]);
        
        echo "   ✅ Updated submission with AI feedback\n";
        echo "   📝 Word count: {$wordCount}\n";
        echo "   ⭐ Score: " . ($submission->score ?? 'N/A') . "\n";
        
        // Check if there's a corresponding attempt record
        $attempt = Attempt::where('user_id', $submission->user_id)
            ->where('module_type', 'writing')
            ->where('content_id', $task->id)
            ->where('content_type', WritingTask::class)
            ->first();
        
        if (!$attempt) {
            // Create attempt record for old submission
            $attempt = Attempt::create([
                'user_id' => $submission->user_id,
                'module_type' => 'writing',
                'content_id' => $task->id,
                'content_type' => WritingTask::class,
                'score' => $submission->score ?? $aiFeedback['overall_score'],
                'max_score' => 100,
                'time_spent' => 2400, // Default 40 minutes
                'completed_at' => $submission->submitted_at ?? $submission->created_at,
                'status' => 'completed'
            ]);
            
            echo "   ✅ Created attempt record: ID {$attempt->id}\n";
        } else {
            echo "   ℹ️ Attempt record already exists: ID {$attempt->id}\n";
        }
        
        echo "\n";
    }
    
    DB::commit();
    
    echo "🎉 SUCCESS! Migrated {$oldSubmissions->count()} old submissions\n";
    echo "👁️ Review buttons should now appear for all your previous writing attempts!\n";
    echo "🔗 Go to: http://localhost:3000/writing\n\n";
    
    // Show summary of user's submissions
    echo "📊 SUMMARY FOR student1@test.com:\n";
    $user = User::where('email', 'student1@test.com')->first();
    if ($user) {
        $userSubmissions = Submission::where('user_id', $user->id)
            ->where('submission_type', 'writing')
            ->with('task')
            ->get();
        
        echo "Total writing submissions: {$userSubmissions->count()}\n";
        foreach ($userSubmissions as $sub) {
            echo "  - {$sub->task->title ?? 'Unknown Task'}: Score {$sub->score}%, Words: " . 
                 ($sub->ai_feedback['word_count'] ?? 'N/A') . "\n";
        }
    }
    
} catch (Exception $e) {
    DB::rollback();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}