<?php

namespace App\Services;

use App\Models\Question;
use App\Models\User;
use App\Models\MockTest;
use App\Services\AIQuestionGeneratorService;
use App\Services\MockAIQuestionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class SmartAIQuestionService
{
    private $aiService;
    private $mockService;
    private $useOpenAI;

    public function __construct()
    {
        $this->aiService = new AIQuestionGeneratorService();
        $this->mockService = new MockAIQuestionService();
        $this->useOpenAI = $this->checkOpenAIAvailability();
    }

    /**
     * Generate questions intelligently - use OpenAI if available, fallback to mock
     */
    public function generateQuestionsForUser(User $user, MockTest $mockTest, string $moduleType, string $ieltsLevel, int $questionsNeeded = 10)
    {
        try {
            if ($this->useOpenAI) {
                Log::info("Attempting OpenAI generation for user {$user->id}");
                return $this->aiService->generateQuestionsForUser($user, $mockTest, $moduleType, $ieltsLevel, $questionsNeeded);
            } else {
                Log::info("Using mock AI generation for user {$user->id}");
                return $this->generateWithMockService($user, $mockTest, $moduleType, $ieltsLevel, $questionsNeeded);
            }
        } catch (Exception $e) {
            Log::warning("AI generation failed, falling back to mock: " . $e->getMessage());
            
            // Mark OpenAI as unavailable for 1 hour
            Cache::put('openai_unavailable', true, 3600);
            $this->useOpenAI = false;
            
            return $this->generateWithMockService($user, $mockTest, $moduleType, $ieltsLevel, $questionsNeeded);
        }
    }

    /**
     * Generate questions using mock service with user tracking
     */
    private function generateWithMockService(User $user, MockTest $mockTest, string $moduleType, string $ieltsLevel, int $questionsNeeded)
    {
        // Check what questions user has already used
        $usedQuestionIds = $this->getUserUsedQuestions($user->id, $moduleType, $ieltsLevel);
        
        // Get available unused questions first
        $availableQuestions = $this->getAvailableQuestions($moduleType, $ieltsLevel, $usedQuestionIds, $questionsNeeded);
        
        $questionsToGenerate = $questionsNeeded - $availableQuestions->count();
        
        if ($questionsToGenerate > 0) {
            // Generate new mock questions
            $newQuestions = $this->mockService->generateMockQuestions($moduleType, $ieltsLevel, $questionsToGenerate);
            
            // Log the generation
            $this->logGeneration($user, $mockTest, $moduleType, $ieltsLevel, $questionsToGenerate, $newQuestions->count());
            
            // Combine available and new questions
            $allQuestions = $availableQuestions->merge($newQuestions);
        } else {
            $allQuestions = $availableQuestions;
        }
        
        return $allQuestions->take($questionsNeeded);
    }

    /**
     * Check if OpenAI is available
     */
    private function checkOpenAIAvailability()
    {
        // Check if OpenAI was recently marked as unavailable
        if (Cache::get('openai_unavailable')) {
            return false;
        }

        // Check if API key is configured
        $apiKey = config('services.openai.api_key');
        if (!$apiKey || $apiKey === 'your_openai_api_key_here') {
            return false;
        }

        return true;
    }

    /**
     * Get questions user has already used
     */
    private function getUserUsedQuestions(int $userId, string $moduleType, string $ieltsLevel)
    {
        return \App\Models\QuestionUsageTracking::where('user_id', $userId)
            ->whereHas('question', function($query) use ($moduleType, $ieltsLevel) {
                $query->where('question_type', $this->mapModuleToQuestionType($moduleType))
                      ->where('ielts_band_level', $ieltsLevel);
            })
            ->pluck('question_id')
            ->toArray();
    }

    /**
     * Get available unused questions
     */
    private function getAvailableQuestions(string $moduleType, string $ieltsLevel, array $excludeIds, int $limit)
    {
        return Question::where('question_type', $this->mapModuleToQuestionType($moduleType))
            ->where('ielts_band_level', $ieltsLevel)
            ->where('is_retired', false)
            ->whereNotIn('id', $excludeIds)
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * Map module type to question type
     */
    private function mapModuleToQuestionType(string $moduleType)
    {
        $mapping = [
            'reading' => 'multiple_choice',
            'listening' => 'fill_blank', 
            'writing' => 'essay',
            'speaking' => 'part2'
        ];

        return $mapping[$moduleType] ?? 'multiple_choice';
    }

    /**
     * Log AI generation activity
     */
    private function logGeneration(User $user, MockTest $mockTest, string $moduleType, string $ieltsLevel, int $requested, int $generated)
    {
        \App\Models\AiQuestionGenerationLog::create([
            'user_id' => $user->id,
            'mock_test_id' => $mockTest->id,
            'module_type' => $moduleType,
            'ielts_band_level' => $ieltsLevel,
            'questions_requested' => $requested,
            'questions_generated' => $generated,
            'generation_metadata' => [
                'service_used' => $this->useOpenAI ? 'openai' : 'mock',
                'generated_at' => now()
            ],
            'generated_at' => now()
        ]);
    }

    /**
     * Mark questions as used by a user
     */
    public function markQuestionsAsUsed(User $user, $questions, int $mockTestAttemptId)
    {
        foreach ($questions as $question) {
            \App\Models\QuestionUsageTracking::create([
                'question_id' => $question->id,
                'user_id' => $user->id,
                'mock_test_attempt_id' => $mockTestAttemptId,
                'used_at' => now()
            ]);

            // Update question usage count
            $question->increment('usage_count');
            $question->update(['last_used_at' => now()]);
        }
    }

    /**
     * Get system status
     */
    public function getSystemStatus()
    {
        return [
            'openai_available' => $this->useOpenAI,
            'openai_configured' => config('services.openai.api_key') !== 'your_openai_api_key_here',
            'mock_service_available' => true,
            'current_service' => $this->useOpenAI ? 'OpenAI' : 'Mock Service',
            'total_questions' => Question::where('is_ai_generated', true)->count(),
            'openai_cache_expires' => Cache::get('openai_unavailable') ? 'In 1 hour' : 'N/A'
        ];
    }

    /**
     * Force retry OpenAI (clear cache)
     */
    public function retryOpenAI()
    {
        Cache::forget('openai_unavailable');
        $this->useOpenAI = $this->checkOpenAIAvailability();
        return $this->useOpenAI;
    }
}