<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\WritingTask;
use App\Models\Submission;
use App\Models\Attempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WritingPracticeController extends Controller
{
    /**
     * Get available writing tasks for practice
     */
    public function index(Request $request)
    {
        $query = WritingTask::query();
        
        // Filter by task type if provided
        if ($request->has('task_type')) {
            $query->byType($request->task_type);
        }
        
        $tasks = $query->orderBy('created_at', 'desc')->get();
        
        // Add user's submission count for each task
        $userId = Auth::id();
        $tasks->each(function ($task) use ($userId) {
            $task->user_submissions_count = $task->submissions()
                ->where('user_id', $userId)
                ->count();
                
            $task->latest_submission = $task->submissions()
                ->where('user_id', $userId)
                ->latest('submitted_at')
                ->first();
        });
        
        return response()->json($tasks);
    }

    /**
     * Get a specific writing task for practice
     */
    public function show($taskId)
    {
        $task = WritingTask::findOrFail($taskId);
        
        return response()->json($task);
    }

    /**
     * Submit a writing response
     */
    public function submit(Request $request, $taskId)
    {
        $task = WritingTask::findOrFail($taskId);
        
        $request->validate([
            'content' => 'required|string|min:50',
            'time_spent' => 'required|integer|min:0',
        ]);

        // Check word count
        $wordCount = str_word_count(strip_tags($request->content));
        
        DB::beginTransaction();
        
        try {
            // Create submission
            $submission = Submission::create([
                'user_id' => Auth::id(),
                'task_id' => $task->id,
                'submission_type' => 'writing',
                'content' => $request->content,
                'submitted_at' => now(),
            ]);
            
            // Create attempt record for tracking
            $attempt = Attempt::create([
                'user_id' => Auth::id(),
                'module_type' => 'writing',
                'content_id' => $task->id,
                'content_type' => WritingTask::class,
                'score' => 0, // Will be updated when AI feedback is processed
                'max_score' => 100, // Standard writing score
                'time_spent' => $request->time_spent,
                'completed_at' => now(),
            ]);
            
            // TODO: Process AI feedback here
            $aiFeedback = $this->processAiFeedback($request->content, $task);
            
            if ($aiFeedback) {
                $submission->update([
                    'ai_feedback' => $aiFeedback,
                    'score' => $aiFeedback['overall_score'] ?? null,
                ]);
                
                $attempt->update([
                    'score' => $aiFeedback['overall_score'] ?? 0,
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Writing submission successful',
                'submission' => $submission->fresh(),
                'word_count' => $wordCount,
                'word_limit' => $task->word_limit,
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Error submitting writing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's writing submission history
     */
    public function history()
    {
        $submissions = Submission::with(['task'])
            ->byUser(Auth::id())
            ->byType('writing')
            ->orderBy('submitted_at', 'desc')
            ->paginate(10);
            
        return response()->json($submissions);
    }

    /**
     * Get detailed results for a specific submission
     */
    public function results($submissionId)
    {
        $submission = Submission::with(['task'])
            ->findOrFail($submissionId);
            
        // Verify this submission belongs to the authenticated user
        if ($submission->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return response()->json($submission);
    }

    /**
     * Process AI feedback for writing submission
     * This is a placeholder for AI integration
     */
    private function processAiFeedback($content, $task)
    {
        // Basic analysis without external AI service
        $wordCount = str_word_count(strip_tags($content));
        $characterCount = strlen($content);
        $sentenceCount = preg_match_all('/[.!?]+/', $content);
        
        // Basic scoring based on word count and task requirements
        $wordCountScore = min(100, ($wordCount / $task->word_limit) * 100);
        $lengthScore = $wordCount >= ($task->word_limit * 0.8) ? 80 : 60;
        
        // Simple grammar check (count of common errors)
        $grammarScore = 75; // Placeholder
        
        $overallScore = ($wordCountScore + $lengthScore + $grammarScore) / 3;
        
        return [
            'overall_score' => round($overallScore, 2),
            'word_count' => $wordCount,
            'character_count' => $characterCount,
            'sentence_count' => $sentenceCount,
            'feedback' => [
                'strengths' => [
                    'Good use of vocabulary',
                    'Clear structure'
                ],
                'improvements' => [
                    'Consider varying sentence length',
                    'Check for grammatical accuracy'
                ],
                'suggestions' => [
                    'Use more connecting words',
                    'Provide more specific examples'
                ]
            ],
            'scores' => [
                'task_achievement' => round($lengthScore, 2),
                'coherence_cohesion' => 70,
                'lexical_resource' => 75,
                'grammatical_accuracy' => round($grammarScore, 2),
            ]
        ];
    }
}