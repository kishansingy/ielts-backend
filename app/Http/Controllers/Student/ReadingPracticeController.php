<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ReadingPassage;
use App\Models\Attempt;
use App\Models\UserAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReadingPracticeController extends Controller
{
    /**
     * Get available reading passages for practice
     */
    public function index(Request $request)
    {
        $query = ReadingPassage::with(['questions' => function($q) {
            // Hide correct answers in the list view
            $q->select('id', 'passage_id', 'question_text', 'question_type', 'options', 'points');
        }]);
        
        // Filter by difficulty if provided
        if ($request->has('difficulty')) {
            $query->byDifficulty($request->difficulty);
        }
        
        $passages = $query->orderBy('created_at', 'desc')->get();
        
        // Add user's attempt count for each passage
        $userId = Auth::id();
        $passages->each(function ($passage) use ($userId) {
            $passage->user_attempts_count = $passage->attempts()
                ->where('user_id', $userId)
                ->completed()
                ->count();
                
            $passage->best_score = $passage->attempts()
                ->where('user_id', $userId)
                ->completed()
                ->max('score');
        });
        
        return response()->json($passages);
    }

    /**
     * Start a reading practice session
     */
    public function start($passageId)
    {
        $passage = ReadingPassage::with(['questions'])->findOrFail($passageId);
        
        // Create a new attempt
        $attempt = Attempt::create([
            'user_id' => Auth::id(),
            'module_type' => 'reading',
            'content_id' => $passage->id,
            'content_type' => ReadingPassage::class,
            'score' => 0,
            'max_score' => $passage->questions->sum('points'),
            'time_spent' => 0,
        ]);
        
        // Return passage with questions but hide correct answers
        $passageData = $passage->toArray();
        $passageData['questions'] = $passage->questions->map(function ($question) {
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
            'passage' => $passageData,
            'time_limit' => $passage->time_limit * 60, // Convert to seconds
        ]);
    }

    /**
     * Submit answers for a reading practice session
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
            'answers.*.question_id' => 'required|integer|exists:questions,id',
            'answers.*.user_answer' => 'required|string',
            'time_spent' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();
        
        try {
            // Get passage and questions
            $passage = ReadingPassage::with('questions')->find($attempt->content_id);
            $questions = $passage->questions->keyBy('id');
            
            // Prepare data for enhanced evaluation
            $userAnswers = [];
            $questionData = [];
            
            foreach ($request->answers as $answerData) {
                $question = $questions->get($answerData['question_id']);
                if (!$question) continue;
                
                $userAnswers[] = $answerData['user_answer'];
                $questionData[] = [
                    'id' => $question->id,
                    'type' => $question->question_type,
                    'correct_answers' => is_array($question->correct_answer) 
                        ? $question->correct_answer 
                        : [$question->correct_answer],
                    'points' => $question->points ?? 1
                ];
            }
            
            // Use Enhanced Evaluation Service
            $evaluationService = new \App\Services\EnhancedEvaluationService();
            $evaluation = $evaluationService->evaluateReading($userAnswers, $questionData);
            
            $totalScore = 0;
            $results = [];
            
            // Process each answer with enhanced evaluation results
            foreach ($request->answers as $index => $answerData) {
                $question = $questions->get($answerData['question_id']);
                if (!$question) continue;
                
                $evaluationResult = $evaluation['detailed_results'][$index] ?? null;
                $isCorrect = $evaluationResult['is_correct'] ?? false;
                $pointsEarned = $isCorrect ? ($question->points ?? 1) : 0;
                $totalScore += $pointsEarned;
                
                // Save user answer with enhanced evaluation
                UserAnswer::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'question_type' => 'reading',
                    'user_answer' => $answerData['user_answer'],
                    'is_correct' => $isCorrect,
                    'points_earned' => $pointsEarned,
                ]);
                
                $results[] = [
                    'question_id' => $question->id,
                    'user_answer' => $answerData['user_answer'],
                    'correct_answers' => $evaluationResult['correct_answers'] ?? [],
                    'is_correct' => $isCorrect,
                    'points_earned' => $pointsEarned,
                    'points_possible' => $question->points ?? 1,
                    'explanation' => $evaluationResult['explanation'] ?? '',
                    'evaluation_method' => 'enhanced_fuzzy_matching'
                ];
            }
            
            // Update attempt with enhanced evaluation results
            $attempt->update([
                'score' => $totalScore,
                'band_score' => $evaluation['band_score'],
                'time_spent' => $request->time_spent,
                'completed_at' => now(),
                'evaluation_details' => $evaluation['detailed_results']
            ]);
            
            DB::commit();
            
            return response()->json([
                'message' => 'Reading practice completed successfully',
                'results' => [
                    'total_score' => $totalScore,
                    'max_score' => $attempt->max_score,
                    'percentage' => $evaluation['accuracy_percentage'],
                    'band_score' => $evaluation['band_score'],
                    'time_spent' => $request->time_spent,
                    'questions' => $results,
                    'evaluation_summary' => [
                        'total_questions' => $evaluation['total_questions'],
                        'correct_answers' => $evaluation['correct_answers'],
                        'accuracy_percentage' => $evaluation['accuracy_percentage'],
                        'evaluation_method' => 'Enhanced Fuzzy Matching v2.0'
                    ]
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
     * Get user's reading practice history
     */
    public function history()
    {
        $attempts = Attempt::with(['content'])
            ->byUser(Auth::id())
            ->byModule('reading')
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