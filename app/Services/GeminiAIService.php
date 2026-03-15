<?php

namespace App\Services;

use App\Models\Question;
use App\Models\ReadingPassage;
use App\Models\WritingTask;
use App\Models\SpeakingPrompt;
use App\Models\ListeningExercise;
use App\Models\ListeningQuestion;
use App\Models\User;
use App\Models\MockTest;
use App\Models\AiQuestionGenerationLog;
use App\Models\GeminiUsageTracking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GeminiAIService
{
    private $apiKey;
    private $baseUrl;
    private $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->baseUrl = config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
        $this->model = config('services.gemini.model', 'gemini-1.5-flash');
    }

    /**
     * Generate IELTS content based on module type
     */
    public function generateContent(string $module, string $bandLevel, array $options = [])
    {
        $prompt = $this->buildPrompt($module, $bandLevel, $options);
        
        try {
            $apiResponse = $this->callGeminiAPI($prompt, $options['temperature'] ?? 0.4);
            $result = $this->parseResponse($apiResponse['text'], $module, $bandLevel);
            
            // Track usage
            $this->trackUsage($module, $bandLevel, $apiResponse, true);
            
            return $result;
        } catch (Exception $e) {
            // Track failed request
            $this->trackUsage($module, $bandLevel, null, false, $e->getMessage());
            
            Log::error("Gemini AI Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Call Gemini API
     */
    private function callGeminiAPI(string $prompt, float $temperature = 0.4)
    {
        // Use available models from the API (v1beta)
        $modelNames = [
            'gemini-2.5-flash',      // Latest and fastest
            'gemini-flash-latest',   // Fallback
            'gemini-2.0-flash',      // Alternative
            'gemini-pro-latest'      // Last resort
        ];
        
        $lastError = null;
        $startTime = microtime(true);
        
        foreach ($modelNames as $modelName) {
            try {
                $url = "{$this->baseUrl}/models/{$modelName}:generateContent?key={$this->apiKey}";
                
                $response = Http::timeout(60)->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => $temperature,
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 8192,
                    ]
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                        $responseTime = microtime(true) - $startTime;
                        
                        // Extract usage metadata
                        $usageMetadata = $data['usageMetadata'] ?? [];
                        $promptTokens = $usageMetadata['promptTokenCount'] ?? null;
                        $completionTokens = $usageMetadata['candidatesTokenCount'] ?? null;
                        $totalTokens = $usageMetadata['totalTokenCount'] ?? null;
                        
                        Log::info("Successfully used Gemini model: {$modelName}", [
                            'response_time' => $responseTime,
                            'prompt_tokens' => $promptTokens,
                            'completion_tokens' => $completionTokens,
                            'total_tokens' => $totalTokens
                        ]);
                        
                        return [
                            'text' => $data['candidates'][0]['content']['parts'][0]['text'],
                            'model' => $modelName,
                            'usage' => [
                                'prompt_tokens' => $promptTokens,
                                'completion_tokens' => $completionTokens,
                                'total_tokens' => $totalTokens
                            ]
                        ];
                    }
                }
                
                $lastError = $response->body();
            } catch (Exception $e) {
                $lastError = $e->getMessage();
                continue;
            }
        }
        
        throw new Exception('Gemini API request failed with all models. Last error: ' . $lastError);
    }

    /**
     * Build prompt based on module type
     */
    private function buildPrompt(string $module, string $bandLevel, array $options = [])
    {
        $systemPrompt = "You are an IELTS exam content generator and certified IELTS evaluator trained on IDP and British Council standards. ";
        $systemPrompt .= "Generate high-quality IELTS mock test content that strictly follows the official IELTS exam format, difficulty distribution, timing, and band descriptors. ";
        $systemPrompt .= "Return output ONLY in valid JSON format. ";
        
        switch ($module) {
            case 'reading':
                return $this->buildReadingPrompt($bandLevel, $systemPrompt, $options);
            case 'writing':
                return $this->buildWritingPrompt($bandLevel, $systemPrompt, $options);
            case 'speaking':
                return $this->buildSpeakingPrompt($bandLevel, $systemPrompt, $options);
            case 'listening':
                return $this->buildListeningPrompt($bandLevel, $systemPrompt, $options);
            default:
                throw new Exception("Unsupported module: {$module}");
        }
    }

    /**
     * Build Reading Module Prompt
     */
    private function buildReadingPrompt(string $bandLevel, string $systemPrompt, array $options)
    {
        $questionCount = $options['question_count'] ?? 10;
        
        return $systemPrompt . "

Generate a full IELTS Reading passage with questions.

Requirements:
- Band Level: {$bandLevel}
- Word count: 900-1100 words
- Academic topic
- Number of questions: {$questionCount}

Question types to include (use ONLY these types):
- multiple_choice (with 4 options A, B, C, D)
- true_false (True/False questions)
- fill_blank (sentence completion, summary completion)

Return ONLY valid JSON in this exact structure:
{
  \"module\": \"reading\",
  \"band_level\": \"{$bandLevel}\",
  \"passage_title\": \"Title here\",
  \"passage_text\": \"Full passage text here (900-1100 words)\",
  \"questions\": [
    {
      \"type\": \"multiple_choice\",
      \"question\": \"Question text\",
      \"options\": [\"A\", \"B\", \"C\", \"D\"],
      \"correct_answer\": \"A\"
    },
    {
      \"type\": \"true_false\",
      \"question\": \"Statement to verify\",
      \"options\": null,
      \"correct_answer\": \"TRUE\"
    },
    {
      \"type\": \"fill_blank\",
      \"question\": \"Complete the sentence: The main idea is ___\",
      \"options\": null,
      \"correct_answer\": \"the answer\"
    }
  ]
}

Important:
- Use ONLY the three question types listed above
- For true_false, correct_answer must be \"TRUE\" or \"FALSE\"
- For fill_blank, provide the exact answer text
- Questions follow real IELTS exam structure
- Match real exam difficulty progression for band {$bandLevel}
- Be original but realistic
- Follow official IELTS style wording
- Answer keys are accurate";
    }

    /**
     * Build Writing Module Prompt
     */
    private function buildWritingPrompt(string $bandLevel, string $systemPrompt, array $options)
    {
        return $systemPrompt . "

Generate IELTS Writing tasks with evaluation samples.

Requirements:
- Band Level: {$bandLevel}
- Task 1: Graph / Chart / Process / Map
- Task 2: Opinion / Discussion / Problem-Solution essay

Return ONLY valid JSON in this exact structure:
{
  \"module\": \"writing\",
  \"band_level\": \"{$bandLevel}\",
  \"tasks\": [
    {
      \"task_type\": \"task_1\",
      \"question\": \"Task 1 question with chart/graph description\",
      \"word_count\": 150,
      \"time_limit\": 20,
      \"model_answer\": \"Band 9 sample answer\",
      \"band_6_sample\": \"Band 6 level sample\",
      \"band_7_sample\": \"Band 7 level sample\",
      \"band_8_sample\": \"Band 8 level sample\"
    },
    {
      \"task_type\": \"task_2\",
      \"question\": \"Task 2 essay question\",
      \"word_count\": 250,
      \"time_limit\": 40,
      \"model_answer\": \"Band 9 sample answer\",
      \"band_6_sample\": \"Band 6 level sample\",
      \"band_7_sample\": \"Band 7 level sample\",
      \"band_8_sample\": \"Band 8 level sample\"
    }
  ]
}

Ensure authentic IELTS writing task format and appropriate difficulty for band {$bandLevel}.";
    }

    /**
     * Build Speaking Module Prompt
     */
    private function buildSpeakingPrompt(string $bandLevel, string $systemPrompt, array $options)
    {
        return $systemPrompt . "

Generate IELTS Speaking test questions.

Structure:
- Part 1 (Introduction & familiar topics) - 4-5 questions
- Part 2 (Cue card with 1-minute prep topic) - 1 cue card with follow-ups
- Part 3 (Discussion questions) - 4-5 questions

Band Level: {$bandLevel}

Return ONLY valid JSON in this exact structure:
{
  \"module\": \"speaking\",
  \"band_level\": \"{$bandLevel}\",
  \"part_1\": [
    \"Question 1\",
    \"Question 2\",
    \"Question 3\",
    \"Question 4\"
  ],
  \"part_2\": {
    \"cue_card\": \"Describe a time when... You should say: - point 1 - point 2 - point 3\",
    \"follow_up_questions\": [
      \"Follow-up 1\",
      \"Follow-up 2\"
    ]
  },
  \"part_3\": [
    \"Discussion question 1\",
    \"Discussion question 2\",
    \"Discussion question 3\",
    \"Discussion question 4\"
  ]
}

Ensure questions are appropriate for band {$bandLevel} difficulty.";
    }

    /**
     * Build Listening Module Prompt
     */
    private function buildListeningPrompt(string $bandLevel, string $systemPrompt, array $options)
    {
        $questionCount = $options['question_count'] ?? 10;
        
        return $systemPrompt . "

Generate IELTS Listening test content.

Band Level: {$bandLevel}
Number of questions: {$questionCount}

Include:
- Dialogue / Monologue format
- Question types (use ONLY these types):
  * multiple_choice (with 4 options A, B, C, D)
  * true_false (True/False questions)
  * fill_blank (form completion, sentence completion, note completion)

Return ONLY valid JSON in this exact structure:
{
  \"module\": \"listening\",
  \"band_level\": \"{$bandLevel}\",
  \"title\": \"Listening exercise title\",
  \"audio_script\": \"Full transcript of what would be heard\",
  \"questions\": [
    {
      \"type\": \"multiple_choice\",
      \"question\": \"Question text\",
      \"options\": [\"A\", \"B\", \"C\", \"D\"],
      \"correct_answer\": \"A\"
    },
    {
      \"type\": \"fill_blank\",
      \"question\": \"Complete: The student's name is ___\",
      \"options\": null,
      \"correct_answer\": \"the answer\"
    }
  ]
}

Important:
- Use ONLY the three question types listed above
- For fill_blank, provide the exact answer text
- Ensure authentic IELTS listening format
- Appropriate difficulty for band {$bandLevel}";
    }

    /**
     * Parse and save response based on module
     */
    private function parseResponse(string $response, string $module, string $bandLevel)
    {
        // Extract JSON from response
        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}') + 1;
        
        if ($jsonStart === false || $jsonEnd === false) {
            throw new Exception('No valid JSON found in Gemini response');
        }
        
        $jsonContent = substr($response, $jsonStart, $jsonEnd - $jsonStart);
        $data = json_decode($jsonContent, true);
        
        if (!$data) {
            throw new Exception('Failed to parse Gemini response JSON: ' . json_last_error_msg());
        }

        switch ($module) {
            case 'reading':
                return $this->saveReadingContent($data, $bandLevel);
            case 'writing':
                return $this->saveWritingContent($data, $bandLevel);
            case 'speaking':
                return $this->saveSpeakingContent($data, $bandLevel);
            case 'listening':
                return $this->saveListeningContent($data, $bandLevel);
            default:
                throw new Exception("Unsupported module: {$module}");
        }
    }

    /**
     * Save Reading content to database
     */
    private function saveReadingContent(array $data, string $bandLevel)
    {
        // Create passage
        $passage = ReadingPassage::create([
            'title' => $data['passage_title'] ?? 'AI Generated Passage - Band ' . $bandLevel,
            'content' => $data['passage_text'],
            'difficulty_level' => $this->mapBandToLevel($bandLevel),
            'band_level' => 'band' . $bandLevel,
            'time_limit' => 20,
            'created_by' => 1, // System user
            'source' => 'ai_generated'
        ]);

        // Create questions
        $questions = collect();
        foreach ($data['questions'] as $questionData) {
            // Map AI question types to database enum values
            $questionType = $this->mapQuestionType($questionData['type']);
            
            $question = Question::create([
                'passage_id' => $passage->id,
                'question_text' => $questionData['question'],
                'question_type' => $questionType,
                'correct_answer' => $questionData['correct_answer'],
                'options' => $questionData['options'] ?? null,
                'points' => $this->getPointsByLevel($bandLevel),
                'ielts_band_level' => $bandLevel,
                'is_ai_generated' => true,
                'ai_metadata' => [
                    'generated_at' => now(),
                    'model_used' => $this->model,
                    'module_type' => 'reading',
                    'source' => 'gemini',
                    'original_type' => $questionData['type']
                ]
            ]);
            $questions->push($question);
        }

        return [
            'passage' => $passage,
            'questions' => $questions
        ];
    }

    /**
     * Save Writing content to database
     */
    private function saveWritingContent(array $data, string $bandLevel)
    {
        $tasks = collect();
        
        foreach ($data['tasks'] as $taskData) {
            $task = WritingTask::create([
                'title' => 'AI Generated ' . ucfirst($taskData['task_type']) . ' - Band ' . $bandLevel,
                'task_type' => str_replace('_', '', $taskData['task_type']), // task_1 -> task1
                'band_level' => 'band' . $bandLevel,
                'prompt' => $taskData['question'],
                'instructions' => 'Complete this writing task according to IELTS standards.',
                'word_limit' => $taskData['word_count'] ?? 250,
                'time_limit' => $taskData['time_limit'] ?? 40,
                'created_by' => 1, // System user
                'model_answer' => $taskData['model_answer'] ?? null,
                'evaluation_criteria' => [
                    'band_6_sample' => $taskData['band_6_sample'] ?? null,
                    'band_7_sample' => $taskData['band_7_sample'] ?? null,
                    'band_8_sample' => $taskData['band_8_sample'] ?? null,
                ],
                'source' => 'ai_generated'
            ]);
            $tasks->push($task);
        }

        return ['tasks' => $tasks];
    }

    /**
     * Save Speaking content to database
     */
    private function saveSpeakingContent(array $data, string $bandLevel)
    {
        $prompts = collect();
        
        // Part 1 questions
        foreach ($data['part_1'] as $index => $question) {
            $prompt = SpeakingPrompt::create([
                'title' => "AI Generated Part 1 Q" . ($index + 1) . " - Band " . $bandLevel,
                'prompt_text' => $question,
                'preparation_time' => 0,
                'response_time' => 60,
                'difficulty_level' => $this->mapBandToLevel($bandLevel),
                'band_level' => 'band' . $bandLevel,
                'created_by' => 1,
                'follow_up_questions' => [],
                'source' => 'ai_generated'
            ]);
            $prompts->push($prompt);
        }

        // Part 2 cue card
        $prompt = SpeakingPrompt::create([
            'title' => "AI Generated Part 2 Cue Card - Band " . $bandLevel,
            'prompt_text' => $data['part_2']['cue_card'],
            'preparation_time' => 60,
            'response_time' => 120,
            'difficulty_level' => $this->mapBandToLevel($bandLevel),
            'band_level' => 'band' . $bandLevel,
            'created_by' => 1,
            'follow_up_questions' => $data['part_2']['follow_up_questions'] ?? [],
            'source' => 'ai_generated'
        ]);
        $prompts->push($prompt);

        // Part 3 questions
        foreach ($data['part_3'] as $index => $question) {
            $prompt = SpeakingPrompt::create([
                'title' => "AI Generated Part 3 Q" . ($index + 1) . " - Band " . $bandLevel,
                'prompt_text' => $question,
                'preparation_time' => 0,
                'response_time' => 120,
                'difficulty_level' => $this->mapBandToLevel($bandLevel),
                'band_level' => 'band' . $bandLevel,
                'created_by' => 1,
                'follow_up_questions' => [],
                'source' => 'ai_generated'
            ]);
            $prompts->push($prompt);
        }

        return ['prompts' => $prompts];
    }

    /**
     * Save Listening content to database
     */
    private function saveListeningContent(array $data, string $bandLevel)
    {
        // Create listening exercise
        $exercise = ListeningExercise::create([
            'title' => $data['title'] ?? 'AI Generated Listening - Band ' . $bandLevel,
            'audio_file_path' => 'ai-generated/placeholder.mp3', // Placeholder - needs TTS integration
            'transcript' => $data['audio_script'],
            'duration' => 300, // 5 minutes default
            'difficulty_level' => $this->mapBandToLevel($bandLevel),
            'band_level' => 'band' . $bandLevel,
            'created_by' => 1,
            'source' => 'ai_generated'
        ]);

        // Create questions
        $questions = collect();
        foreach ($data['questions'] as $questionData) {
            // Map AI question types to database enum values
            $questionType = $this->mapQuestionType($questionData['type']);
            
            $question = ListeningQuestion::create([
                'listening_exercise_id' => $exercise->id,
                'question_text' => $questionData['question'],
                'question_type' => $questionType,
                'correct_answer' => $questionData['correct_answer'],
                'options' => $questionData['options'] ?? null,
                'points' => $this->getPointsByLevel($bandLevel),
                'order' => $questions->count() + 1
            ]);
            $questions->push($question);
        }

        return [
            'exercise' => $exercise,
            'questions' => $questions
        ];
    }

    /**
     * Evaluate Writing submission
     */
    public function evaluateWriting(string $essayText, string $taskType, string $bandLevel)
    {
        $prompt = "Evaluate this IELTS Writing response according to official IELTS band descriptors from IDP / British Council.

Task Type: {$taskType}
Target Band Level: {$bandLevel}

Essay:
{$essayText}

Score separately for:
- Task Achievement
- Coherence & Cohesion
- Lexical Resource
- Grammatical Range & Accuracy

Provide detailed feedback and improvement suggestions.

Return ONLY valid JSON in this exact structure:
{
  \"task_achievement\": { \"band\": \"7.0\", \"feedback\": \"Detailed feedback\" },
  \"coherence_cohesion\": { \"band\": \"7.0\", \"feedback\": \"Detailed feedback\" },
  \"lexical_resource\": { \"band\": \"7.0\", \"feedback\": \"Detailed feedback\" },
  \"grammar\": { \"band\": \"7.0\", \"feedback\": \"Detailed feedback\" },
  \"overall_band\": \"7.0\",
  \"improvement_suggestions\": [\"Suggestion 1\", \"Suggestion 2\"]
}";

        try {
            $response = $this->callGeminiAPI($prompt, 0.3);
            
            // Extract JSON
            $jsonStart = strpos($response, '{');
            $jsonEnd = strrpos($response, '}') + 1;
            $jsonContent = substr($response, $jsonStart, $jsonEnd - $jsonStart);
            
            return json_decode($jsonContent, true);
        } catch (Exception $e) {
            Log::error("Gemini Writing Evaluation Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Evaluate Speaking submission
     */
    public function evaluateSpeaking(string $transcriptText, int $partNumber, string $bandLevel)
    {
        $prompt = "Evaluate this IELTS Speaking response using official IELTS band descriptors.

Part: {$partNumber}
Target Band Level: {$bandLevel}

Transcript:
{$transcriptText}

Score:
- Fluency & Coherence
- Lexical Resource
- Grammatical Range & Accuracy
- Pronunciation

Return ONLY valid JSON in this exact structure:
{
  \"fluency_coherence\": { \"band\": \"7.0\", \"feedback\": \"Detailed feedback\" },
  \"lexical_resource\": { \"band\": \"7.0\", \"feedback\": \"Detailed feedback\" },
  \"grammar\": { \"band\": \"7.0\", \"feedback\": \"Detailed feedback\" },
  \"pronunciation\": { \"band\": \"7.0\", \"feedback\": \"Detailed feedback\" },
  \"overall_band\": \"7.0\",
  \"improvement_suggestions\": [\"Suggestion 1\", \"Suggestion 2\"]
}";

        try {
            $response = $this->callGeminiAPI($prompt, 0.3);
            
            // Extract JSON
            $jsonStart = strpos($response, '{');
            $jsonEnd = strrpos($response, '}') + 1;
            $jsonContent = substr($response, $jsonStart, $jsonEnd - $jsonStart);
            
            return json_decode($jsonContent, true);
        } catch (Exception $e) {
            Log::error("Gemini Speaking Evaluation Error: " . $e->getMessage());
            throw $e;
        }
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
     * Map band level to difficulty level
     */
    private function mapBandToLevel(string $band)
    {
        $mapping = [
            '6' => 'intermediate',
            '7' => 'intermediate',
            '8' => 'advanced',
            '9' => 'advanced'
        ];

        return $mapping[$band] ?? 'intermediate';
    }

    /**
     * Map AI question types to database enum values
     */
    private function mapQuestionType(string $aiType)
    {
        // Database enum: 'multiple_choice','true_false','fill_blank'
        $mapping = [
            'multiple_choice' => 'multiple_choice',
            'true_false' => 'true_false',
            'true_false_not_given' => 'true_false',
            'fill_blank' => 'fill_blank',
            'sentence_completion' => 'fill_blank',
            'summary_completion' => 'fill_blank',
            'matching_headings' => 'multiple_choice',
            'short_answer' => 'fill_blank'
        ];

        return $mapping[strtolower($aiType)] ?? 'multiple_choice';
    }

    /**
     * Log generation activity
     */
    public function logGeneration(User $user, MockTest $mockTest, string $moduleType, string $bandLevel, int $generated)
    {
        AiQuestionGenerationLog::create([
            'user_id' => $user->id,
            'mock_test_id' => $mockTest->id,
            'module_type' => $moduleType,
            'ielts_band_level' => $bandLevel,
            'questions_requested' => $generated,
            'questions_generated' => $generated,
            'generation_metadata' => [
                'service_used' => 'gemini',
                'model' => $this->model,
                'generated_at' => now()
            ],
            'generated_at' => now()
        ]);
    }

    /**
     * Track API usage for monitoring
     */
    private function trackUsage(string $module, string $bandLevel, $apiResponse, bool $success, string $errorMessage = null)
    {
        $usage = $apiResponse['usage'] ?? [];
        
        GeminiUsageTracking::create([
            'model_used' => $apiResponse['model'] ?? $this->model,
            'module_type' => $module,
            'band_level' => $bandLevel,
            'prompt_tokens' => $usage['prompt_tokens'] ?? null,
            'completion_tokens' => $usage['completion_tokens'] ?? null,
            'total_tokens' => $usage['total_tokens'] ?? null,
            'estimated_cost' => 0, // Gemini free tier
            'request_type' => 'generation',
            'success' => $success,
            'error_message' => $errorMessage,
            'requested_at' => now()
        ]);
    }

    /**
     * Get usage statistics
     */
    public function getUsageStats($period = 'today')
    {
        switch ($period) {
            case 'today':
                return GeminiUsageTracking::getDailyStats();
            case 'month':
                return GeminiUsageTracking::getMonthlyStats();
            case 'limit':
                return GeminiUsageTracking::isApproachingDailyLimit();
            default:
                return [];
        }
    }

    /**
     * Free-form chat with Gemini AI
     */
    public function chat(string $userMessage, array $history = [])
    {
        // Build system context + conversation
        $systemContext = "You are an IELTS expert AI assistant. Help students with IELTS preparation, grammar, vocabulary, writing tips, speaking practice, reading strategies, and listening techniques. Be concise, friendly, and educational.";

        $fullPrompt = $systemContext . "\n\n";

        foreach ($history as $msg) {
            $role = $msg['role'] === 'user' ? 'Student' : 'Assistant';
            $fullPrompt .= "{$role}: {$msg['content']}\n";
        }

        $fullPrompt .= "Student: {$userMessage}\nAssistant:";

        $result = $this->callGeminiAPI($fullPrompt, 0.7);

        return trim($result['text']);
    }
}
