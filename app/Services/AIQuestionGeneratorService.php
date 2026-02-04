<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Module;
use App\Models\User;
use App\Models\MockTest;
use App\Models\QuestionUsageTracking;
use App\Models\AiQuestionGenerationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class AIQuestionGeneratorService
{
    private $openaiApiKey;
    private $openaiBaseUrl;

    public function __construct()
    {
        $this->openaiApiKey = config('services.openai.api_key');
        $this->openaiBaseUrl = config('services.openai.base_url', 'https://api.openai.com/v1');
    }

    /**
     * Generate questions for a user based on their level and unused questions
     */
    public function generateQuestionsForUser(User $user, MockTest $mockTest, string $moduleType, string $ieltsLevel, int $questionsNeeded = 10)
    {
        try {
            // Check what questions user has already used
            $usedQuestionIds = $this->getUserUsedQuestions($user->id, $moduleType, $ieltsLevel);
            
            // Get available unused questions first
            $availableQuestions = $this->getAvailableQuestions($moduleType, $ieltsLevel, $usedQuestionIds, $questionsNeeded);
            
            $questionsToGenerate = $questionsNeeded - $availableQuestions->count();
            
            if ($questionsToGenerate > 0) {
                // Generate new questions via AI
                $newQuestions = $this->generateNewQuestions($moduleType, $ieltsLevel, $questionsToGenerate);
                
                // Log the generation
                $this->logGeneration($user, $mockTest, $moduleType, $ieltsLevel, $questionsToGenerate, count($newQuestions));
                
                // Combine available and new questions
                $allQuestions = $availableQuestions->merge($newQuestions);
            } else {
                $allQuestions = $availableQuestions;
            }
            
            return $allQuestions->take($questionsNeeded);
            
        } catch (Exception $e) {
            Log::error('AI Question Generation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get questions user has already used
     */
    private function getUserUsedQuestions(int $userId, string $moduleType, string $ieltsLevel)
    {
        return QuestionUsageTracking::where('user_id', $userId)
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
     * Generate new questions using OpenAI (with fallback to mock service)
     */
    private function generateNewQuestions(string $moduleType, string $ieltsLevel, int $count)
    {
        // Try OpenAI first
        try {
            return $this->generateWithOpenAI($moduleType, $ieltsLevel, $count);
        } catch (Exception $e) {
            Log::warning('OpenAI generation failed, falling back to mock service: ' . $e->getMessage());
            
            // Fallback to mock service
            $mockService = new \App\Services\MockAIQuestionService();
            return $mockService->generateMockQuestions($moduleType, $ieltsLevel, $count);
        }
    }
    
    /**
     * Generate questions using OpenAI API
     */
    private function generateWithOpenAI(string $moduleType, string $ieltsLevel, int $count)
    {
        $prompt = $this->buildPrompt($moduleType, $ieltsLevel, $count);
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->openaiApiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post($this->openaiBaseUrl . '/chat/completions', [
            'model' => config('services.openai.model', 'gpt-3.5-turbo'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert IELTS question generator. Generate high-quality, authentic IELTS questions that match the specified band level difficulty.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 2000
        ]);

        if (!$response->successful()) {
            throw new Exception('OpenAI API request failed: ' . $response->body());
        }

        $aiResponse = $response->json();
        $generatedContent = $aiResponse['choices'][0]['message']['content'] ?? '';
        
        return $this->parseAndSaveQuestions($generatedContent, $moduleType, $ieltsLevel, $aiResponse);
    }

    /**
     * Build AI prompt based on module type and level
     */
    private function buildPrompt(string $moduleType, string $ieltsLevel, int $count)
    {
        $levelDescriptions = [
            '6' => 'intermediate level with moderate complexity',
            '7' => 'upper-intermediate level with good complexity', 
            '8' => 'advanced level with high complexity',
            '9' => 'expert level with maximum complexity'
        ];

        $levelDesc = $levelDescriptions[$ieltsLevel] ?? 'intermediate level';

        switch ($moduleType) {
            case 'reading':
                return "Generate {$count} IELTS Reading questions for band {$ieltsLevel} ({$levelDesc}). 
                
                Format each question as JSON with these fields:
                - question_text: The question
                - question_type: 'multiple_choice', 'true_false', or 'fill_blank'
                - correct_answer: The correct answer
                - options: Array of 4 options for multiple choice (null for others)
                - passage_text: A short reading passage (2-3 paragraphs)
                
                Make questions authentic IELTS style focusing on comprehension, inference, and detail recognition.
                
                Return as JSON array: [{question1}, {question2}, ...]";

            case 'listening':
                return "Generate {$count} IELTS Listening questions for band {$ieltsLevel} ({$levelDesc}).
                
                Format each question as JSON with these fields:
                - question_text: The question
                - question_type: 'multiple_choice', 'fill_blank', or 'short_answer'
                - correct_answer: The correct answer
                - options: Array of options for multiple choice (null for others)
                - audio_transcript: Transcript of what would be heard
                
                Focus on conversations, lectures, and announcements typical in IELTS.
                
                Return as JSON array: [{question1}, {question2}, ...]";

            case 'writing':
                return "Generate {$count} IELTS Writing prompts for band {$ieltsLevel} ({$levelDesc}).
                
                Format each prompt as JSON with these fields:
                - question_text: The writing prompt/task
                - question_type: 'essay' or 'letter'
                - task_requirements: Specific requirements (word count, format, etc.)
                - assessment_criteria: Key points for evaluation
                
                Include both Task 1 (letters/reports) and Task 2 (essays) variety.
                
                Return as JSON array: [{prompt1}, {prompt2}, ...]";

            case 'speaking':
                return "Generate {$count} IELTS Speaking questions for band {$ielts_level} ({$levelDesc}).
                
                Format each question as JSON with these fields:
                - question_text: The speaking prompt
                - question_type: 'part1', 'part2', or 'part3'
                - topic: Main topic area
                - follow_up_questions: Array of related follow-up questions
                
                Cover personal topics, describe/compare tasks, and abstract discussions.
                
                Return as JSON array: [{question1}, {question2}, ...]";

            default:
                throw new Exception("Unsupported module type: {$moduleType}");
        }
    }

    /**
     * Parse AI response and save questions to database
     */
    private function parseAndSaveQuestions(string $content, string $moduleType, string $ieltsLevel, array $aiMetadata)
    {
        try {
            // Extract JSON from the response
            $jsonStart = strpos($content, '[');
            $jsonEnd = strrpos($content, ']') + 1;
            
            if ($jsonStart === false || $jsonEnd === false) {
                throw new Exception('No valid JSON found in AI response');
            }
            
            $jsonContent = substr($content, $jsonStart, $jsonEnd - $jsonStart);
            $questionsData = json_decode($jsonContent, true);
            
            if (!$questionsData) {
                throw new Exception('Failed to parse AI response JSON');
            }

            $savedQuestions = collect();
            
            foreach ($questionsData as $questionData) {
                $question = $this->createQuestionFromAI($questionData, $moduleType, $ieltsLevel, $aiMetadata);
                if ($question) {
                    $savedQuestions->push($question);
                }
            }
            
            return $savedQuestions;
            
        } catch (Exception $e) {
            Log::error('Failed to parse AI questions: ' . $e->getMessage());
            Log::error('AI Response: ' . $content);
            throw $e;
        }
    }

    /**
     * Create question from AI data
     */
    private function createQuestionFromAI(array $data, string $moduleType, string $ieltsLevel, array $aiMetadata)
    {
        try {
            // For reading questions, we need to create a passage first
            $passageId = null;
            if ($moduleType === 'reading' && isset($data['passage_text'])) {
                $passageId = $this->createReadingPassage($data['passage_text'], $ieltsLevel);
            }

            $question = Question::create([
                'passage_id' => $passageId,
                'question_text' => $data['question_text'],
                'question_type' => $data['question_type'] ?? 'multiple_choice',
                'correct_answer' => $data['correct_answer'],
                'options' => $data['options'] ?? null,
                'points' => $this->getPointsByLevel($ieltsLevel),
                'ielts_band_level' => $ieltsLevel,
                'is_ai_generated' => true,
                'ai_metadata' => [
                    'generated_at' => now(),
                    'ai_response_id' => $aiMetadata['id'] ?? null,
                    'model_used' => $aiMetadata['model'] ?? 'gpt-4',
                    'module_type' => $moduleType,
                    'original_data' => $data
                ]
            ]);

            return $question;
            
        } catch (Exception $e) {
            Log::error('Failed to create question from AI data: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create reading passage for reading questions
     */
    private function createReadingPassage(string $passageText, string $ieltsLevel)
    {
        $passage = \App\Models\ReadingPassage::create([
            'title' => 'AI Generated Passage - Band ' . $ieltsLevel,
            'content' => $passageText,
            'difficulty_level' => $ieltsLevel,
            'is_active' => true
        ]);

        return $passage->id;
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

    /**
     * Get points based on IELTS level
     */
    private function getPointsByLevel(string $level)
    {
        $points = [
            '6' => 1,
            '7' => 2,
            '8' => 3,
            '9' => 4
        ];

        return $points[$level] ?? 1;
    }

    /**
     * Log AI generation activity
     */
    private function logGeneration(User $user, MockTest $mockTest, string $moduleType, string $ieltsLevel, int $requested, int $generated)
    {
        AiQuestionGenerationLog::create([
            'user_id' => $user->id,
            'mock_test_id' => $mockTest->id,
            'module_type' => $moduleType,
            'ielts_band_level' => $ieltsLevel,
            'questions_requested' => $requested,
            'questions_generated' => $generated,
            'generated_at' => now()
        ]);
    }

    /**
     * Mark questions as used by a user
     */
    public function markQuestionsAsUsed(User $user, $questions, int $mockTestAttemptId)
    {
        foreach ($questions as $question) {
            QuestionUsageTracking::create([
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
}