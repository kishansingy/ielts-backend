<?php

namespace App\Http\Controllers;

use App\Services\GeminiAIService;
use App\Models\MockTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class GeminiAIController extends Controller
{
    private $geminiService;

    public function __construct(GeminiAIService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Generate content preview (no save)
     */
    public function preview(Request $request)
    {
        $request->validate([
            'module' => 'required|in:reading,writing,speaking,listening',
            'band_level' => 'required|in:6,7,8,9',
            'question_count' => 'sometimes|integer|min:1|max:20'
        ]);

        try {
            $result = $this->geminiService->generateContent(
                $request->module,
                $request->band_level,
                ['question_count' => $request->question_count ?? 10]
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Content generated successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Gemini preview error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate content: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate and save content for mock test
     */
    public function generateForMockTest(Request $request, $mockTestId)
    {
        $request->validate([
            'module' => 'required|in:reading,writing,speaking,listening',
            'band_level' => 'required|in:6,7,8,9',
            'question_count' => 'sometimes|integer|min:1|max:20'
        ]);

        try {
            $mockTest = MockTest::findOrFail($mockTestId);
            $user = $request->user();

            $result = $this->geminiService->generateContent(
                $request->module,
                $request->band_level,
                ['question_count' => $request->question_count ?? 10]
            );

            // Log the generation
            $questionCount = 0;
            if (isset($result['questions'])) {
                $questionCount = $result['questions']->count();
            } elseif (isset($result['tasks'])) {
                $questionCount = $result['tasks']->count();
            } elseif (isset($result['prompts'])) {
                $questionCount = $result['prompts']->count();
            }

            $this->geminiService->logGeneration(
                $user,
                $mockTest,
                $request->module,
                $request->band_level,
                $questionCount
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Content generated and saved successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Gemini mock test generation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate content: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Evaluate writing submission
     */
    public function evaluateWriting(Request $request)
    {
        $request->validate([
            'essay_text' => 'required|string|min:100',
            'task_type' => 'required|in:task_1,task_2',
            'band_level' => 'required|in:6,7,8,9'
        ]);

        try {
            $evaluation = $this->geminiService->evaluateWriting(
                $request->essay_text,
                $request->task_type,
                $request->band_level
            );

            return response()->json([
                'success' => true,
                'evaluation' => $evaluation,
                'message' => 'Writing evaluated successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Gemini writing evaluation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to evaluate writing: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Evaluate speaking submission
     */
    public function evaluateSpeaking(Request $request)
    {
        $request->validate([
            'transcript_text' => 'required|string|min:50',
            'part_number' => 'required|in:1,2,3',
            'band_level' => 'required|in:6,7,8,9'
        ]);

        try {
            $evaluation = $this->geminiService->evaluateSpeaking(
                $request->transcript_text,
                $request->part_number,
                $request->band_level
            );

            return response()->json([
                'success' => true,
                'evaluation' => $evaluation,
                'message' => 'Speaking evaluated successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Gemini speaking evaluation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to evaluate speaking: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get generation statistics
     */
    public function stats(Request $request)
    {
        $user = $request->user();
        
        $stats = [
            'total_generated' => \App\Models\AiQuestionGenerationLog::where('user_id', $user->id)->sum('questions_generated'),
            'by_module' => \App\Models\AiQuestionGenerationLog::where('user_id', $user->id)
                ->selectRaw('module_type, SUM(questions_generated) as total')
                ->groupBy('module_type')
                ->get(),
            'recent_generations' => \App\Models\AiQuestionGenerationLog::where('user_id', $user->id)
                ->orderBy('generated_at', 'desc')
                ->limit(10)
                ->get(),
            'ai_questions_available' => [
                'reading' => \App\Models\Question::where('is_ai_generated', true)->where('question_type', 'multiple_choice')->count(),
                'writing' => \App\Models\WritingTask::where('source', 'ai_generated')->count(),
                'speaking' => \App\Models\SpeakingPrompt::where('source', 'ai_generated')->count(),
                'listening' => \App\Models\ListeningExercise::where('source', 'ai_generated')->count(),
            ]
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Free-form AI chat
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'history' => 'sometimes|array|max:20',
            'history.*.role' => 'required|in:user,assistant',
            'history.*.content' => 'required|string',
        ]);

        try {
            $reply = $this->geminiService->chat(
                $request->message,
                $request->history ?? []
            );

            return response()->json([
                'success' => true,
                'reply' => $reply,
            ]);
        } catch (Exception $e) {
            Log::error('Gemini chat error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'AI is temporarily unavailable. Please try again.',
            ], 500);
        }
    }

    /**
     * Get Gemini API usage statistics
     */
    public function usageStats(Request $request)
    {
        $period = $request->get('period', 'today');
        
        $stats = $this->geminiService->getUsageStats($period);
        
        return response()->json([
            'success' => true,
            'period' => $period,
            'stats' => $stats
        ]);
    }
}