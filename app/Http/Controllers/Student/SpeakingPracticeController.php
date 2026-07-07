<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\SpeakingPrompt;
use App\Models\Submission;
use App\Models\Attempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SpeakingPracticeController extends Controller
{
    /**
     * Get available speaking prompts for practice
     */
    public function index(Request $request)
    {
        $query = SpeakingPrompt::query();
        
        // Filter by difficulty if provided
        if ($request->has('difficulty')) {
            $query->byDifficulty($request->difficulty);
        }
        
        $prompts = $query->orderBy('created_at', 'desc')->get();
        
        // Add user's submission count for each prompt
        $userId = Auth::id();
        $prompts->each(function ($prompt) use ($userId) {
            $prompt->user_submissions_count = $prompt->submissions()
                ->where('user_id', $userId)
                ->count();
                
            $prompt->latest_submission = $prompt->submissions()
                ->where('user_id', $userId)
                ->latest('submitted_at')
                ->first();
        });
        
        return response()->json($prompts);
    }

    /**
     * Get a specific speaking prompt for practice
     */
    public function show($promptId)
    {
        $prompt = SpeakingPrompt::findOrFail($promptId);
        
        return response()->json($prompt);
    }

    /**
     * Submit a speaking response
     */
    public function submit(Request $request, $promptId)
    {
        $prompt = SpeakingPrompt::findOrFail($promptId);
        
        $request->validate([
            'audio_file' => 'required|file|mimes:mp3,wav,m4a,webm|max:10240', // 10MB max
            'time_spent' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();
        
        try {
            // Store the audio file
            $audioPath = $request->file('audio_file')->store('speaking/recordings', 'public');
            
            // Create submission
            $submission = Submission::create([
                'user_id' => Auth::id(),
                'task_id' => $prompt->id,
                'submission_type' => 'speaking',
                'file_path' => $audioPath,
                'submitted_at' => now(),
            ]);
            
            // Create attempt record for tracking
            $attempt = Attempt::create([
                'user_id' => Auth::id(),
                'module_type' => 'speaking',
                'content_id' => $prompt->id,
                'content_type' => SpeakingPrompt::class,
                'score' => 0, // Will be updated when AI feedback is processed
                'max_score' => 100, // Standard speaking score
                'time_spent' => $request->time_spent,
                'completed_at' => now(),
            ]);
            
            // TODO: Process AI feedback here
            $aiFeedback = $this->processAiFeedback($audioPath, $prompt);
            
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
                'message' => 'Speaking submission successful',
                'submission' => $submission->fresh(),
                'audio_url' => $submission->file_url,
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollback();
            
            // Clean up uploaded file if submission failed
            if (isset($audioPath)) {
                Storage::disk('public')->delete($audioPath);
            }
            
            return response()->json([
                'message' => 'Error submitting speaking response',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's speaking submission history
     */
    public function history()
    {
        $submissions = Submission::with(['task'])
            ->byUser(Auth::id())
            ->byType('speaking')
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
     * Process AI feedback for speaking submission
     * This is a placeholder for AI integration
     */
    private function processAiFeedback($audioPath, $prompt)
    {
        // Get audio file info
        $audioFile = Storage::disk('public')->path($audioPath);
        $audioSize = Storage::disk('public')->size($audioPath);
        
        // Basic analysis without external AI service
        // In a real implementation, this would send the audio to an AI service
        
        // Simulate processing time and basic scoring
        $duration = 60; // Placeholder - would get actual duration from audio file
        $fluencyScore = rand(60, 90);
        $pronunciationScore = rand(65, 85);
        $vocabularyScore = rand(70, 90);
        $grammarScore = rand(60, 85);
        
        $overallScore = ($fluencyScore + $pronunciationScore + $vocabularyScore + $grammarScore) / 4;
        
        return [
            'overall_score' => round($overallScore, 2),
            'duration' => $duration,
            'file_size' => $audioSize,
            'feedback' => [
                'strengths' => [
                    'Clear pronunciation in most parts',
                    'Good use of vocabulary',
                    'Appropriate response length'
                ],
                'improvements' => [
                    'Work on fluency and natural rhythm',
                    'Reduce hesitation and pauses',
                    'Improve grammatical accuracy'
                ],
                'suggestions' => [
                    'Practice speaking at a steady pace',
                    'Use more varied sentence structures',
                    'Focus on clear articulation'
                ]
            ],
            'scores' => [
                'fluency_coherence' => round($fluencyScore, 2),
                'lexical_resource' => round($vocabularyScore, 2),
                'grammatical_accuracy' => round($grammarScore, 2),
                'pronunciation' => round($pronunciationScore, 2),
            ],
            'analysis' => [
                'speaking_rate' => 'Normal',
                'pause_frequency' => 'Moderate',
                'volume_level' => 'Appropriate',
                'clarity' => 'Good'
            ]
        ];
    }
}