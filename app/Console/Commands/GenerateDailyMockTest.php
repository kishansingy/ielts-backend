<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MockTest;
use App\Models\MockTestSection;
use App\Models\ReadingPassage;
use App\Models\ListeningExercise;
use App\Models\WritingTask;
use App\Models\SpeakingPrompt;
use App\Models\Question;
use App\Models\ListeningQuestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Generates 1 complete mock test per band level per day using Gemini free tier.
 * Each run makes exactly 4 API calls (one per section), staying within daily limits.
 *
 * Schedule: runs once daily at 2:00 AM
 * Cron: php artisan mocktests:generate-daily
 *
 * To run manually: php artisan mocktests:generate-daily
 * To run for a specific band: php artisan mocktests:generate-daily --band=band6
 */
class GenerateDailyMockTest extends Command
{
    protected $signature = 'mocktests:generate-daily
                            {--band= : Generate for a specific band only (band6|band7|band8|band9)}
                            {--dry-run : Show what would be generated without saving}';

    protected $description = 'Generate 1 AI mock test per band per day using Gemini free tier (4 API calls total)';

    private string $apiKey;
    private string $baseUrl;

    private array $bands = [
        'band6' => ['label' => '6.5', 'numeric' => '6', 'difficulty' => 'beginner'],
        'band7' => ['label' => '7',   'numeric' => '7', 'difficulty' => 'intermediate'],
        'band8' => ['label' => '7.5', 'numeric' => '8', 'difficulty' => 'intermediate'],
        'band9' => ['label' => '8',   'numeric' => '9', 'difficulty' => 'advanced'],
    ];

    public function handle(): int
    {
        $this->apiKey  = config('services.gemini.api_key');
        $this->baseUrl = config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');

        if (!$this->apiKey) {
            $this->error('GEMINI_API_KEY not set in .env');
            return 1;
        }

        $adminUser = \App\Models\User::where('role', 'admin')->first() ?? \App\Models\User::first();
        if (!$adminUser) {
            $this->error('No user found. Run migrations first.');
            return 1;
        }

        $bandFilter = $this->option('band');
        $bands      = $bandFilter ? [$bandFilter => $this->bands[$bandFilter]] : $this->bands;

        if ($bandFilter && !isset($this->bands[$bandFilter])) {
            $this->error("Invalid band: {$bandFilter}. Use band6, band7, band8, or band9.");
            return 1;
        }

        $this->info('');
        $this->info('=== Daily Mock Test Generator ===');
        $this->info('Generating 1 test per band (' . count($bands) . ' total) — 1 API call per section');
        $this->info('');

        $created = 0;
        $failed  = 0;

        foreach ($bands as $bandKey => $bandConfig) {
            $testNumber = MockTest::where('band_level', $bandKey)->count() + 1;
            $this->info("── {$bandKey} (Band {$bandConfig['label']}) — Test #{$testNumber} ──");

            if ($this->option('dry-run')) {
                $this->line("  [dry-run] Would generate test #{$testNumber} for {$bandKey}");
                continue;
            }

            $success = $this->generateOneTest($bandKey, $bandConfig, $testNumber, $adminUser->id);
            $success ? $created++ : $failed++;

            // 65s gap between bands to fully reset the per-minute quota
            if (count($bands) > 1 && $bandKey !== array_key_last($bands)) {
                $this->line("  Waiting 65s before next band...");
                sleep(65);
            }
        }

        $this->info('');
        $this->info("Done. Created: {$created} | Failed: {$failed}");

        return $failed > 0 ? 1 : 0;
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function generateOneTest(string $bandKey, array $bandConfig, int $testNumber, int $adminId): bool
    {
        DB::beginTransaction();
        try {
            $mockTest = MockTest::create([
                'title'            => "IELTS Mock Test {$testNumber} - BAND" . strtoupper(str_replace('band', '', $bandKey)),
                'description'      => "Complete IELTS practice test {$testNumber} for band {$bandConfig['label']} level.",
                'band_level'       => $bandKey,
                'duration_minutes' => 180,
                'is_active'        => true,
                'available_from'   => now(),
                'available_until'  => null,
            ]);

            $order = 1;

            // 1 API call — Reading
            $this->line("  [1/4] Reading...");
            $order = $this->addReading($mockTest, $bandKey, $bandConfig, $testNumber, $order, $adminId);
            $this->waitForRateLimit();

            // 1 API call — Listening
            $this->line("  [2/4] Listening...");
            $order = $this->addListening($mockTest, $bandKey, $bandConfig, $testNumber, $order, $adminId);
            $this->waitForRateLimit();

            // 1 API call — Writing
            $this->line("  [3/4] Writing...");
            $order = $this->addWriting($mockTest, $bandKey, $bandConfig, $testNumber, $order, $adminId);
            $this->waitForRateLimit();

            // 1 API call — Speaking
            $this->line("  [4/4] Speaking...");
            $this->addSpeaking($mockTest, $bandKey, $bandConfig, $testNumber, $order, $adminId);

            DB::commit();
            $this->line("  <fg=green>✓ Test #{$testNumber} created (ID: {$mockTest->id})</>");
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            $this->error("  ✗ Failed: " . $e->getMessage());
            Log::error("GenerateDailyMockTest failed for {$bandKey}: " . $e->getMessage());
            return false;
        }
    }

    // ─── Wait respecting free tier: 10 RPM = 6s minimum, use 8s to be safe ──

    private function waitForRateLimit(): void
    {
        sleep(8);
    }

    // ─── API Call with retry on 429 ──────────────────────────────────────────

    private function callAI(string $prompt): string
    {
        // Only use models with free tier quota — gemini-2.0-flash first, flash as fallback
        $models = ['gemini-2.0-flash', 'gemini-2.5-flash'];

        foreach ($models as $model) {
            $result = $this->tryModel($model, $prompt);
            if ($result !== null) return $result;
        }

        throw new Exception('Daily quota exhausted for all models. The scheduler will retry automatically tomorrow at 3:00 AM.');
    }

    private function tryModel(string $model, string $prompt, int $attempt = 1): ?string
    {
        try {
            $response = Http::timeout(120)->post(
                "{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}",
                [
                    'contents'         => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'temperature'     => 0.9,
                        'topK'            => 40,
                        'topP'            => 0.95,
                        'maxOutputTokens' => 8192,
                    ],
                ]
            );

            if ($response->successful()) {
                $text = $response->json('candidates.0.content.parts.0.text');
                return $text ?: null;
            }

            if ($response->status() === 429) {
                $body       = $response->json('error', []);
                $message    = $body['message'] ?? '';

                // If daily quota is exhausted (limit: 0), no point retrying today
                if (str_contains($message, 'limit: 0') || str_contains($message, 'PerDay')) {
                    $this->line("    <fg=red>Daily quota exhausted for {$model}. Try again tomorrow.</>");
                    return null; // skip to next model immediately
                }

                if ($attempt === 1) {
                    $retryDelay = 60;
                    foreach ($response->json('error.details', []) as $detail) {
                        if (isset($detail['retryDelay'])) {
                            $retryDelay = (int) $detail['retryDelay'] + 5;
                            break;
                        }
                    }
                    $this->line("    <fg=yellow>429 on {$model}, waiting {$retryDelay}s...</>");
                    sleep($retryDelay);
                    return $this->tryModel($model, $prompt, 2);
                }
            }

            return null;

        } catch (Exception $e) {
            return null;
        }
    }

    // ─── JSON Parser ─────────────────────────────────────────────────────────

    private function parseJson(string $raw): array
    {
        $raw   = preg_replace('/```json\s*/i', '', $raw);
        $raw   = preg_replace('/```\s*/i', '', $raw);
        $start = strpos($raw, '{');
        $end   = strrpos($raw, '}');

        if ($start === false || $end === false) {
            throw new Exception('No JSON found in AI response.');
        }

        $data = json_decode(substr($raw, $start, $end - $start + 1), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON parse error: ' . json_last_error_msg());
        }

        return $data;
    }

    private function sanitizeOptions(?array $options, string $correct): ?array
    {
        if (!$options) return null;
        $seen = $unique = [];
        foreach ($options as $opt) {
            $key = strtolower(trim($opt));
            if (!isset($seen[$key])) { $seen[$key] = true; $unique[] = $opt; }
        }
        $hasCorrect = false;
        foreach ($unique as $opt) {
            if (strtolower(trim($opt)) === strtolower(trim($correct))) { $hasCorrect = true; break; }
        }
        if (!$hasCorrect) $unique[] = $correct;
        shuffle($unique);
        return array_values($unique);
    }

    private function mapType(string $type): string
    {
        return match (strtolower($type)) {
            'multiple_choice', 'mcq'          => 'multiple_choice',
            'true_false', 'true/false', 'tf'  => 'true_false',
            default                            => 'fill_blank',
        };
    }

    // ─── Section Generators ──────────────────────────────────────────────────

    private function addReading(MockTest $test, string $bandKey, array $band, int $num, int $order, int $adminId): int
    {
        $topics = [
            'climate change and environmental policy',
            'history of scientific discovery',
            'psychology of decision making',
            'global economics and trade',
            'medical advances and public health',
            'urban planning and smart cities',
            'artificial intelligence and society',
            'marine biology and ocean conservation',
            'renewable energy technologies',
            'education systems around the world',
            'migration and cultural identity',
            'space exploration and astronomy',
            'biodiversity and ecosystem services',
            'digital technology and privacy',
            'nutrition science and diet',
            'architecture and heritage',
            'linguistics and language learning',
            'international law and human rights',
            'neuroscience and memory',
            'sustainable agriculture',
        ];
        $topic = $topics[($num - 1) % count($topics)];

        $prompt = "Generate a UNIQUE IELTS Academic Reading passage for Band {$band['label']} ({$band['difficulty']}) on: \"{$topic}\".
Test #{$num} — must be completely different from previous tests.

Rules:
- Passage: 600-800 words, academic register
- Exactly 10 questions: mix of multiple_choice (4 DISTINCT options), fill_blank, true_false
- Correct answers must be directly supported by the passage
- Vocabulary and complexity must match Band {$band['label']}

Return ONLY valid JSON:
{
  \"passage_title\": \"Title\",
  \"passage_text\": \"Full passage (600-800 words)\",
  \"questions\": [
    {\"type\": \"multiple_choice\", \"question\": \"?\", \"options\": [\"A\",\"B\",\"C\",\"D\"], \"correct_answer\": \"A\"},
    {\"type\": \"fill_blank\", \"question\": \"Complete: ___\", \"options\": null, \"correct_answer\": \"answer\"},
    {\"type\": \"true_false\", \"question\": \"Statement.\", \"options\": null, \"correct_answer\": \"TRUE\"}
  ]
}";

        $data    = $this->parseJson($this->callAI($prompt));
        $passage = ReadingPassage::create([
            'title'            => $data['passage_title'] ?? "Reading - Test {$num} ({$bandKey})",
            'content'          => $data['passage_text'],
            'difficulty_level' => $band['difficulty'],
            'band_level'       => $bandKey,
            'time_limit'       => 20,
            'created_by'       => $adminId,
            'source'           => 'ai_generated',
        ]);

        foreach ($data['questions'] as $q) {
            Question::create([
                'passage_id'       => $passage->id,
                'question_text'    => $q['question'],
                'question_type'    => $this->mapType($q['type']),
                'correct_answer'   => $q['correct_answer'],
                'options'          => $this->sanitizeOptions($q['options'] ?? null, $q['correct_answer']),
                'points'           => 1,
                'ielts_band_level' => $band['numeric'],
                'is_ai_generated'  => true,
            ]);
        }

        MockTestSection::create([
            'mock_test_id' => $test->id, 'module_type' => 'reading',
            'content_id' => $passage->id, 'content_type' => ReadingPassage::class,
            'order' => $order, 'duration_minutes' => 20,
        ]);

        return $order + 1;
    }

    private function addListening(MockTest $test, string $bandKey, array $band, int $num, int $order, int $adminId): int
    {
        $scenarios = [
            'a student asking about university registration',
            'a radio interview about a scientific discovery',
            'two colleagues planning a work project',
            'a tour guide at a historical site',
            'a doctor consulting a patient',
            'a lecture on environmental conservation',
            'a job interview at a company',
            'a community meeting about local plans',
            'a podcast on health and nutrition',
            'a travel agent advising a client',
            'a professor explaining research methods',
            'a library orientation for new students',
            'a business negotiation between companies',
            'a documentary about wildlife migration',
            'a language school enrolment conversation',
            'a fitness instructor explaining a programme',
            'a museum audio guide about an exhibition',
            'a news report on economic changes',
            'a city council debate on housing',
            'a customer service call about a product',
        ];
        $scenario = $scenarios[($num - 1) % count($scenarios)];

        $prompt = "Generate a UNIQUE IELTS Listening exercise for Band {$band['label']} ({$band['difficulty']}).
Scenario: \"{$scenario}\". Test #{$num}.

Rules:
- Audio script: 250-350 words, natural spoken English
- Exactly 10 questions: mix of multiple_choice (4 DISTINCT options), fill_blank, true_false
- Answers must be explicitly stated in the script

Return ONLY valid JSON:
{
  \"title\": \"Exercise title\",
  \"audio_script\": \"Full transcript\",
  \"questions\": [
    {\"type\": \"multiple_choice\", \"question\": \"?\", \"options\": [\"A\",\"B\",\"C\",\"D\"], \"correct_answer\": \"A\"},
    {\"type\": \"fill_blank\", \"question\": \"The meeting is at ___.\", \"options\": null, \"correct_answer\": \"answer\"},
    {\"type\": \"true_false\", \"question\": \"Statement.\", \"options\": null, \"correct_answer\": \"TRUE\"}
  ]
}";

        $data     = $this->parseJson($this->callAI($prompt));
        $exercise = ListeningExercise::create([
            'title'            => $data['title'] ?? "Listening - Test {$num} ({$bandKey})",
            'audio_file_path'  => 'ai-generated/placeholder.mp3',
            'transcript'       => $data['audio_script'],
            'duration'         => 300,
            'difficulty_level' => $band['difficulty'],
            'band_level'       => $bandKey,
            'created_by'       => $adminId,
            'source'           => 'ai_generated',
        ]);

        foreach ($data['questions'] as $q) {
            ListeningQuestion::create([
                'listening_exercise_id' => $exercise->id,
                'question_text'         => $q['question'],
                'question_type'         => $this->mapType($q['type']),
                'correct_answer'        => $q['correct_answer'],
                'options'               => $this->sanitizeOptions($q['options'] ?? null, $q['correct_answer']),
                'points'                => 1,
            ]);
        }

        MockTestSection::create([
            'mock_test_id' => $test->id, 'module_type' => 'listening',
            'content_id' => $exercise->id, 'content_type' => ListeningExercise::class,
            'order' => $order, 'duration_minutes' => 30,
        ]);

        return $order + 1;
    }

    private function addWriting(MockTest $test, string $bandKey, array $band, int $num, int $order, int $adminId): int
    {
        $t1Topics = ['bar chart comparing energy use', 'line graph showing population growth',
            'pie chart of household spending', 'table of university enrolment', 'process diagram of recycling',
            'map showing town changes', 'bar chart of average salaries', 'line graph of CO2 emissions',
            'diagram of water treatment', 'pie chart of transport usage', 'table of tourist arrivals',
            'bar chart of smartphone ownership', 'flow chart of glass manufacturing',
            'line graph of literacy rates', 'map of coastal development',
            'bar chart of working hours', 'pie chart of electricity sources',
            'table of crime statistics', 'diagram of butterfly life cycle', 'line graph of house prices'];

        $t2Topics = ['government funding of arts', 'social media and relationships',
            'free university education', 'remote working advantages', 'environmental laws vs growth',
            'technology in education', 'capital punishment debate', 'causes of obesity',
            'tourism harm to communities', 'globalisation and culture',
            'children learning second languages', 'living in large cities',
            'regulating fast food advertising', 'youth unemployment causes',
            'space exploration costs', 'automation and employment',
            'competitive sport and life skills', 'nuclear energy pros and cons',
            'older generations responsibility', 'causes of stress'];

        $t1 = $t1Topics[($num - 1) % count($t1Topics)];
        $t2 = $t2Topics[($num - 1) % count($t2Topics)];

        $prompt = "Generate IELTS Writing tasks for Band {$band['label']} ({$band['difficulty']}). Test #{$num}.
Task 1 topic: {$t1}. Task 2 topic: {$t2}.

Return ONLY valid JSON:
{
  \"tasks\": [
    {\"task_type\": \"task_1\", \"question\": \"The [chart] below shows {$t1}. Summarise the main features and make comparisons.\", \"instructions\": \"Write at least 150 words.\"},
    {\"task_type\": \"task_2\", \"question\": \"A well-formed IELTS essay question about: {$t2}. Discuss both views and give your opinion.\", \"instructions\": \"Write at least 250 words.\"}
  ]
}";

        $data = $this->parseJson($this->callAI($prompt));

        foreach ($data['tasks'] as $taskData) {
            $taskType = str_replace('_', '', $taskData['task_type']); // task_1 → task1
            $task     = WritingTask::create([
                'title'        => "Writing " . strtoupper($taskData['task_type']) . " - Test {$num} ({$bandKey})",
                'task_type'    => $taskType,
                'prompt'       => $taskData['question'],
                'instructions' => $taskData['instructions'],
                'time_limit'   => $taskType === 'task1' ? 20 : 40,
                'word_limit'   => $taskType === 'task1' ? 150 : 250,
                'band_level'   => $bandKey,
                'created_by'   => $adminId,
                'source'       => 'ai_generated',
            ]);

            MockTestSection::create([
                'mock_test_id' => $test->id, 'module_type' => 'writing',
                'content_id' => $task->id, 'content_type' => WritingTask::class,
                'order' => $order++, 'duration_minutes' => $taskType === 'task1' ? 20 : 40,
            ]);
        }

        return $order;
    }

    private function addSpeaking(MockTest $test, string $bandKey, array $band, int $num, int $order, int $adminId): void
    {
        $themes = ['hobbies', 'travel', 'technology', 'education', 'work', 'food and culture',
            'environment', 'health', 'family', 'art', 'sport', 'media', 'cities',
            'language learning', 'money', 'traditions', 'science', 'fashion',
            'volunteering', 'ambitions'];
        $theme = $themes[($num - 1) % count($themes)];

        $prompt = "Generate IELTS Speaking test for Band {$band['label']} on theme: \"{$theme}\". Test #{$num}.

Return ONLY valid JSON:
{
  \"part_1\": [\"Q1?\", \"Q2?\", \"Q3?\", \"Q4?\"],
  \"part_2\": {
    \"cue_card\": \"Describe something related to {$theme}.\\nYou should say:\\n- what it is\\n- when/where\\n- who was involved\\nand explain why it was significant.\",
    \"follow_up_questions\": [\"Follow-up 1?\", \"Follow-up 2?\"]
  },
  \"part_3\": [\"Abstract Q1?\", \"Abstract Q2?\", \"Abstract Q3?\", \"Abstract Q4?\"]
}";

        $data = $this->parseJson($this->callAI($prompt));

        $parts = [
            ['text' => implode("\n", array_map(fn($q, $i) => ($i+1).". {$q}", $data['part_1'], array_keys($data['part_1']))), 'prep' => 0,  'resp' => 240, 'label' => 'Part 1'],
            ['text' => $data['part_2']['cue_card'],                                                                                          'prep' => 60, 'resp' => 120, 'label' => 'Part 2'],
            ['text' => implode("\n", array_map(fn($q, $i) => ($i+1).". {$q}", $data['part_3'], array_keys($data['part_3']))), 'prep' => 0,  'resp' => 300, 'label' => 'Part 3'],
        ];

        foreach ($parts as $part) {
            $prompt = SpeakingPrompt::create([
                'title'            => "Speaking {$part['label']} - Test {$num} ({$bandKey})",
                'prompt_text'      => $part['text'],
                'preparation_time' => $part['prep'],
                'response_time'    => $part['resp'],
                'difficulty_level' => $band['difficulty'],
                'band_level'       => $bandKey,
                'created_by'       => $adminId,
                'source'           => 'ai_generated',
            ]);

            MockTestSection::create([
                'mock_test_id' => $test->id, 'module_type' => 'speaking',
                'content_id' => $prompt->id, 'content_type' => SpeakingPrompt::class,
                'order' => $order++,
                'duration_minutes' => (int) ceil(($part['prep'] + $part['resp']) / 60),
            ]);
        }
    }
}
