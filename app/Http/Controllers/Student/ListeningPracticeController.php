<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ListeningExercise;
use App\Models\Attempt;
use App\Models\UserAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ListeningPracticeController extends Controller
{
    /**
     * Get available listening exercises for practice
     */
    public function index(Request $request)
    {
        $query = ListeningExercise::with(['questions']);
        
        // Filter by difficulty if provided
        if ($request->has('difficulty')) {
            $query->byDifficulty($request->difficulty);
        }
        
        $exercises = $query->orderBy('created_at', 'desc')->get();
        
        // Add user's attempt count for each exercise
        $userId = Auth::id();
        $exercises->each(function ($exercise) use ($userId) {
            $exercise->user_attempts_count = $exercise->attempts()
                ->where('user_id', $userId)
                ->completed()
                ->count();
                
            $exercise->best_score = $exercise->attempts()
                ->where('user_id', $userId)
                ->completed()
                ->max('score');
                
            // Add audio URL
            $exercise->audio_url = $exercise->audio_url;
        });
        
        return response()->json($exercises);
    }

    /**
     * Start a listening practice session
     */
    public function start($exerciseId)
    {
        $exercise = ListeningExercise::with(['questions'])->findOrFail($exerciseId);
        
        // Create a new attempt
        $attempt = Attempt::create([
            'user_id' => Auth::id(),
            'module_type' => 'listening',
            'content_id' => $exercise->id,
            'content_type' => ListeningExercise::class,
            'score' => 0,
            'max_score' => $exercise->questions->sum('points'),
            'time_spent' => 0,
        ]);
        
        // Return exercise with questions but hide correct answers
        $exerciseData = $exercise->toArray();
        $exerciseData['questions'] = $exercise->questions->map(function ($question) {
            return [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'question_type' => $question->question_type,
                'options' => $question->options,
                'points' => $question->points,
            ];
        });
        
        return response()->json([
            'attempt_id' => $attempt->id,
            'exercise' => $exerciseData,
            'audio_url' => $exercise->audio_url,
            'duration' => $exercise->duration,
        ]);
    }

    /**
     * Submit answers for a listening practice session
     */
    public function submit(Request $request, $attemptId)
    {
        $attempt = Attempt::findOrFail($attemptId);
        
        // Verify this attempt belongs to the authenticated user
        if ($attempt->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Verify attempt is not already completed
        if ($attempt->isCompleted()) {
            return response()->json(['message' => 'Attempt already completed'], 400);
        }
        
        $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|integer|exists:listening_questions,id',
            'answers.*.user_answer' => 'required|string',
            'time_spent' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();
        
        try {
            $totalScore = 0;
            $results = [];
            
            foreach ($request->answers as $answerData) {
                $question = \App\Models\ListeningQuestion::find($answerData['question_id']);
                
                // Verify question belongs to the exercise in this attempt
                if ($question->listening_exercise_id !== $attempt->content_id) {
                    continue;
                }
                
                $isCorrect = $question->isCorrectAnswer($answerData['user_answer']);
                $pointsEarned = $isCorrect ? $question->points : 0;
                $totalScore += $pointsEarned;
                
                // Save user answer
                UserAnswer::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'question_type' => 'listening',
                    'user_answer' => $answerData['user_answer'],
                    'is_correct' => $isCorrect,
                    'points_earned' => $pointsEarned,
                ]);
                
                $results[] = [
                    'question_id' => $question->id,
                    'user_answer' => $answerData['user_answer'],
                    'correct_answer' => $question->correct_answer,
                    'is_correct' => $isCorrect,
                    'points_earned' => $pointsEarned,
                    'points_possible' => $question->points,
                ];
            }
            
            // Update attempt with final score and completion
            $attempt->update([
                'score' => $totalScore,
                'time_spent' => $request->time_spent,
                'completed_at' => now(),
            ]);
            
            DB::commit();
            
            return response()->json([
                'message' => 'Listening practice completed successfully',
                'results' => [
                    'total_score' => $totalScore,
                    'max_score' => $attempt->max_score,
                    'percentage' => $attempt->percentage,
                    'time_spent' => $request->time_spent,
                    'questions' => $results,
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Error submitting answers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's listening practice history
     */
    public function history()
    {
        $attempts = Attempt::with(['content'])
            ->byUser(Auth::id())
            ->byModule('listening')
            ->completed()
            ->orderBy('completed_at', 'desc')
            ->paginate(10);
            
        return response()->json($attempts);
    }

    /**
     * Get detailed results for a specific attempt
     */
    public function results($attemptId)
    {
        $attempt = Attempt::with(['content', 'userAnswers'])
            ->findOrFail($attemptId);
            
        // Verify this attempt belongs to the authenticated user
        if ($attempt->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return response()->json($attempt);
    }
}