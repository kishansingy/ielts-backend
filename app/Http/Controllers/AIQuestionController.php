<?php

namespace App\Http\Controllers;

use App\Models\MockTest;
use App\Models\Module;
use App\Models\User;
use App\Models\MockTestAttempt;
use App\Services\AIQuestionGeneratorService;
use App\Services\SmartAIQuestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class AIQuestionController extends Controller
{
    private $aiQuestionService;
    private $smartAIService;

    public function __construct(AIQuestionGeneratorService $aiQuestionService, SmartAIQuestionService $smartAIService)
    {
        $this->aiQuestionService = $aiQuestionService;
        $this->smartAIService = $smartAIService;
    }

    /**
     * Generate questions for a mock test attempt
     */
    public function generateForMockTest(Request $request, MockTest $mockTest)
    {
        $request->validate([
            'ielts_band_level' => 'required|in:6,7,8,9',
            'modules' => 'required|array',
            'modules.*.type' => 'required|in:reading,writing,listening,speaking',
            'modules.*.questions_count' => 'required|integer|min:1|max:20'
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            $ieltsLevel = $request->ielts_band_level;
            $modules = $request->modules;

            // Check if user has already attempted this mock test
            $existingAttempt = MockTestAttempt::where('user_id', $user->id)
                ->where('mock_test_id', $mockTest->id)
                ->whereNotNull('completed_at')
                ->first();

            if ($existingAttempt) {
                return response()->json([
                    'error' => 'You have already completed this mock test. Questions cannot be regenerated.'
                ], 422);
            }

            $generatedQuestions = [];

            foreach ($modules as $moduleData) {
                $moduleType = $moduleData['type'];
                $questionsCount = $moduleData['questions_count'];

                // Generate questions for this module using smart service
                $questions = $this->smartAIService->generateQuestionsForUser(
                    $user, 
                    $mockTest, 
                    $moduleType, 
                    $ieltsLevel, 
                    $questionsCount
                );

                $generatedQuestions[$moduleType] = [
                    'questions' => $questions,
                    'count' => $questions->count(),
                    'requested' => $questionsCount
                ];
            }

            DB::commit();

            return response()->json([
                'message' => 'Questions generated successfully',
                'mock_test_id' => $mockTest->id,
                'ielts_band_level' => $ieltsLevel,
                'modules' => $generatedQuestions,
                'total_questions' => collect($generatedQuestions)->sum('count')
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'error' => 'Failed to generate questions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start mock test with generated questions
     */
    public function startMockTestWithQuestions(Request $request, MockTest $mockTest)
    {
        $request->validate([
            'ielts_band_level' => 'required|in:6,7,8,9',
            'modules' => 'required|array'
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            
            // Check for existing incomplete attempt
            $existingAttempt = MockTestAttempt::where('user_id', $user->id)
                ->where('mock_test_id', $mockTest->id)
                ->whereNull('completed_at')
                ->first();

            if ($existingAttempt) {
                return response()->json([
                    'error' => 'You have an incomplete attempt for this mock test. Please complete or abandon it first.'
                ], 422);
            }

            // Create new mock test attempt
            $attempt = MockTestAttempt::create([
                'user_id' => $user->id,
                'mock_test_id' => $mockTest->id,
                'started_at' => now(),
            ]);

            // Generate questions for each module
            $allQuestions = collect();
            foreach ($request->modules as $moduleData) {
                $questions = $this->smartAIService->generateQuestionsForUser(
                    $user,
                    $mockTest,
                    $moduleData['type'],
                    $request->ielts_band_level,
                    $moduleData['questions_count'] ?? 10
                );

                // Mark questions as used
                $this->smartAIService->markQuestionsAsUsed($user, $questions, $attempt->id);
                
                $allQuestions = $allQuestions->merge($questions);
            }

            DB::commit();

            return response()->json([
                'message' => 'Mock test started successfully',
                'attempt_id' => $attempt->id,
                'mock_test' => $mockTest,
                'questions' => $allQuestions,
                'started_at' => $attempt->started_at,
                'duration_minutes' => $mockTest->duration_minutes
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'error' => 'Failed to start mock test: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's question usage statistics
     */
    public function getUserQuestionStats(Request $request)
    {
        $user = Auth::user();
        
        $stats = DB::table('question_usage_tracking as qut')
            ->join('questions as q', 'qut.question_id', '=', 'q.id')
            ->where('qut.user_id', $user->id)
            ->select([
                'q.ielts_band_level',
                'q.question_type',
                DB::raw('COUNT(*) as used_count'),
                DB::raw('MAX(qut.used_at) as last_used')
            ])
            ->groupBy('q.ielts_band_level', 'q.question_type')
            ->get();

        $availableStats = DB::table('questions')
            ->select([
                'ielts_band_level',
                'question_type',
                DB::raw('COUNT(*) as total_available'),
                DB::raw('COUNT(CASE WHEN is_retired = false THEN 1 END) as active_available')
            ])
            ->whereNotNull('ielts_band_level')
            ->groupBy('ielts_band_level', 'question_type')
            ->get();

        return response()->json([
            'user_usage' => $stats,
            'available_questions' => $availableStats
        ]);
    }

    /**
     * Get AI generation history for user
     */
    public function getGenerationHistory(Request $request)
    {
        $user = Auth::user();
        
        $history = $user->aiGenerationLogs()
            ->with('mockTest:id,title')
            ->orderBy('generated_at', 'desc')
            ->paginate(20);

        return response()->json($history);
    }

    /**
     * Preview questions for a module and level (without marking as used)
     */
    public function previewQuestions(Request $request)
    {
        $request->validate([
            'module_type' => 'required|in:reading,writing,listening,speaking',
            'ielts_band_level' => 'required|in:6,7,8,9',
            'count' => 'integer|min:1|max:5'
        ]);

        try {
            $user = Auth::user();
            $moduleType = $request->module_type;
            $ieltsLevel = $request->ielts_band_level;
            $count = $request->count ?? 3;

            // Get available questions (don't mark as used)
            $usedQuestionIds = $this->aiQuestionService->getUserUsedQuestions($user->id, $moduleType, $ieltsLevel);
            
            $availableQuestions = Question::where('question_type', $this->mapModuleToQuestionType($moduleType))
                ->where('ielts_band_level', $ieltsLevel)
                ->where('is_retired', false)
                ->whereNotIn('id', $usedQuestionIds)
                ->inRandomOrder()
                ->limit($count)
                ->get();

            return response()->json([
                'questions' => $availableQuestions,
                'available_count' => $availableQuestions->count(),
                'total_used_by_user' => count($usedQuestionIds)
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to preview questions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get AI system status
     */
    public function getSystemStatus()
    {
        $status = $this->smartAIService->getSystemStatus();
        
        return response()->json([
            'status' => $status,
            'message' => $status['openai_available'] 
                ? 'OpenAI service is available' 
                : 'Using mock service (OpenAI unavailable)',
            'recommendations' => $this->getRecommendations($status)
        ]);
    }

    /**
     * Retry OpenAI connection
     */
    public function retryOpenAI()
    {
        $success = $this->smartAIService->retryOpenAI();
        
        return response()->json([
            'success' => $success,
            'message' => $success 
                ? 'OpenAI connection restored' 
                : 'OpenAI still unavailable',
            'status' => $this->smartAIService->getSystemStatus()
        ]);
    }

    /**
     * Get system recommendations
     */
    private function getRecommendations($status)
    {
        $recommendations = [];
        
        if (!$status['openai_configured']) {
            $recommendations[] = 'Configure OpenAI API key in .env file';
        }
        
        if (!$status['openai_available'] && $status['openai_configured']) {
            $recommendations[] = 'Add billing information to OpenAI account';
            $recommendations[] = 'Check OpenAI usage limits and quotas';
            $recommendations[] = 'Verify API key permissions';
        }
        
        if ($status['total_questions'] < 50) {
            $recommendations[] = 'Generate more questions to build question pool';
        }
        
        return $recommendations;
    }

    /**
     * Map module type to question type
     */
    private function mapModuleToQuestionType(string $moduleType)
    {
        $mapping = [
            'reading' => 'multiple_choice',
            'listening' => 'multiple_choice',
            'writing' => 'essay',
            'speaking' => 'speaking'
        ];

        return $mapping[$moduleType] ?? 'multiple_choice';
    }
}