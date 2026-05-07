<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\GeminiAIService;
use App\Models\MockTest;
use App\Models\MockTestSection;
use App\Models\ReadingPassage;
use App\Models\ListeningExercise;
use App\Models\WritingTask;
use App\Models\SpeakingPrompt;
use App\Models\Question;
use App\Models\ListeningQuestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Generates unique, band-appropriate mock test content via Gemini AI.
 *
 * Run AFTER ClearMockTestContentSeeder:
 *   php artisan db:seed --class=ClearMockTestContentSeeder
 *   php artisan db:seed --class=AIGeneratedMockTestSeeder
 *
 * Options (set in .env or pass via tinker):
 *   MOCK_TESTS_PER_BAND=5   (default: 5, max recommended: 20)
 */
class AIGeneratedMockTestSeeder extends Seeder
{
    private GeminiAIService $ai;
    private int $adminId = 1;

    // How many mock tests to generate per band (each needs unique content)
    private int $testsPerBand;

    // Band config: label used in prompts, numeric suffix
    private array $bands = [
        'band6' => ['label' => '6.5', 'numeric' => '6', 'difficulty' => 'beginner'],
        'band7' => ['label' => '7',   'numeric' => '7', 'difficulty' => 'intermediate'],
        'band8' => ['label' => '7.5', 'numeric' => '8', 'difficulty' => 'intermediate'],
        'band9' => ['label' => '8',   'numeric' => '9', 'difficulty' => 'advanced'],
    ];

    public function __construct()
    {
        $this->ai = new GeminiAIService();
        $this->testsPerBand = (int) env('MOCK_TESTS_PER_BAND', 5);
    }

    public function run(): void
    {
        $adminUser = \App\Models\User::where('role', 'admin')->first()
            ?? \App\Models\User::first();

        if (!$adminUser) {
            $this->command->error('No admin user found. Run UserSeeder first.');
            return;
        }

        $this->adminId = $adminUser->id;

        $this->command->info('');
        $this->command->info('=== AI Mock Test Content Generator ===');
        $this->command->info("Generating {$this->testsPerBand} tests per band × 4 bands = " . ($this->testsPerBand * 4) . " total tests");
        $this->command->info('Each test gets unique AI-generated reading, listening, writing, and speaking content.');
        $this->command->info('');

        foreach ($this->bands as $bandKey => $bandConfig) {
            $this->command->info("── Generating content for {$bandKey} (Band {$bandConfig['label']}) ──");
            $this->generateBandContent($bandKey, $bandConfig);
            $this->command->info('');
        }

        $total = MockTest::count();
        $this->command->info("Done. {$total} mock tests created with unique AI-generated content.");
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function generateBandContent(string $bandKey, array $bandConfig): void
    {
        for ($i = 1; $i <= $this->testsPerBand; $i++) {
            $this->command->line("  Test {$i}/{$this->testsPerBand}...");

            DB::beginTransaction();
            try {
                $mockTest = MockTest::create([
                    'title'            => "IELTS Mock Test {$i} - BAND" . strtoupper(str_replace('band', '', $bandKey)),
                    'description'      => "Complete IELTS practice test {$i} for band {$bandConfig['label']} level. Includes Reading, Listening, Writing, and Speaking sections.",
                    'band_level'       => $bandKey,
                    'duration_minutes' => 180,
                    'is_active'        => true,
                    'available_from'   => now(),
                    'available_until'  => null,
                ]);

                $order = 1;

                // Reading (1 passage, 10 questions)
                $order = $this->addReadingSection($mockTest, $bandKey, $bandConfig, $i, $order);
                sleep(15);

                // Listening (1 exercise, 10 questions)
                $order = $this->addListeningSection($mockTest, $bandKey, $bandConfig, $i, $order);
                sleep(15);

                // Writing (Task 1 + Task 2)
                $order = $this->addWritingSection($mockTest, $bandKey, $bandConfig, $i, $order);
                sleep(15);

                // Speaking (Part 1, 2, 3)
                $order = $this->addSpeakingSection($mockTest, $bandKey, $bandConfig, $i, $order);

                DB::commit();
                $this->command->line("    <fg=green>✓ Test {$i} created (ID: {$mockTest->id})</>");

            } catch (Exception $e) {
                DB::rollBack();
                $this->command->error("    ✗ Test {$i} failed: " . $e->getMessage());
                Log::error("AIGeneratedMockTestSeeder error: " . $e->getMessage());
            }

            // Pause between tests to respect Gemini rate limits (10 RPM on free tier)
            if ($i < $this->testsPerBand) {
                $this->command->line("    <fg=gray>Waiting 20s for rate limit...</>");
                sleep(20);
            }
        }
    }

    // ─── Reading ─────────────────────────────────────────────────────────────

    private function addReadingSection(MockTest $test, string $bandKey, array $bandConfig, int $testNum, int $order): int
    {
        $this->command->line("    Generating reading passage...");

        $prompt = $this->buildReadingPrompt($bandConfig, $testNum);
        $raw    = $this->callAI($prompt);
        $data   = $this->parseJson($raw);

        $passage = ReadingPassage::create([
            'title'            => $data['passage_title'] ?? "Reading Passage - Test {$testNum}",
            'content'          => $data['passage_text'],
            'difficulty_level' => $bandConfig['difficulty'],
            'band_level'       => $bandKey,
            'time_limit'       => 20,
            'created_by'       => $this->adminId,
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
                'ielts_band_level' => $bandConfig['numeric'],
                'is_ai_generated'  => true,
                'ai_metadata'      => ['band' => $bandKey, 'test_num' => $testNum, 'source' => 'gemini'],
            ]);
        }

        MockTestSection::create([
            'mock_test_id'     => $test->id,
            'module_type'      => 'reading',
            'content_id'       => $passage->id,
            'content_type'     => ReadingPassage::class,
            'order'            => $order,
            'duration_minutes' => 20,
        ]);

        return $order + 1;
    }

    // ─── Listening ───────────────────────────────────────────────────────────

    private function addListeningSection(MockTest $test, string $bandKey, array $bandConfig, int $testNum, int $order): int
    {
        $this->command->line("    Generating listening exercise...");

        $prompt   = $this->buildListeningPrompt($bandConfig, $testNum);
        $raw      = $this->callAI($prompt);
        $data     = $this->parseJson($raw);

        $exercise = ListeningExercise::create([
            'title'            => $data['title'] ?? "Listening Exercise - Test {$testNum}",
            'audio_file_path'  => 'ai-generated/placeholder.mp3',
            'transcript'       => $data['audio_script'],
            'duration'         => 300,
            'difficulty_level' => $bandConfig['difficulty'],
            'band_level'       => $bandKey,
            'created_by'       => $this->adminId,
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
            'mock_test_id'     => $test->id,
            'module_type'      => 'listening',
            'content_id'       => $exercise->id,
            'content_type'     => ListeningExercise::class,
            'order'            => $order,
            'duration_minutes' => 30,
        ]);

        return $order + 1;
    }

    // ─── Writing ─────────────────────────────────────────────────────────────

    private function addWritingSection(MockTest $test, string $bandKey, array $bandConfig, int $testNum, int $order): int
    {
        $this->command->line("    Generating writing tasks...");

        $prompt = $this->buildWritingPrompt($bandConfig, $testNum);
        $raw    = $this->callAI($prompt);
        $data   = $this->parseJson($raw);

        foreach ($data['tasks'] as $taskData) {
            $taskType = str_replace('_', '', $taskData['task_type']); // task_1 → task1

            $task = WritingTask::create([
                'title'        => "Writing {$taskData['task_type']} - Test {$testNum} ({$bandKey})",
                'task_type'    => $taskType,
                'prompt'       => $taskData['question'],
                'instructions' => $taskData['instructions'] ?? ($taskType === 'task1'
                    ? 'Summarize the information by selecting and reporting the main features. Write at least 150 words.'
                    : 'Give reasons for your answer and include relevant examples. Write at least 250 words.'),
                'time_limit'   => $taskType === 'task1' ? 20 : 40,
                'word_limit'   => $taskType === 'task1' ? 150 : 250,
                'band_level'   => $bandKey,
                'created_by'   => $this->adminId,
                'source'       => 'ai_generated',
            ]);

            MockTestSection::create([
                'mock_test_id'     => $test->id,
                'module_type'      => 'writing',
                'content_id'       => $task->id,
                'content_type'     => WritingTask::class,
                'order'            => $order,
                'duration_minutes' => $taskType === 'task1' ? 20 : 40,
            ]);

            $order++;
        }

        return $order;
    }

    // ─── Speaking ────────────────────────────────────────────────────────────

    private function addSpeakingSection(MockTest $test, string $bandKey, array $bandConfig, int $testNum, int $order): int
    {
        $this->command->line("    Generating speaking prompts...");

        $prompt = $this->buildSpeakingPrompt($bandConfig, $testNum);
        $raw    = $this->callAI($prompt);
        $data   = $this->parseJson($raw);

        // Part 1
        $part1Text = implode("\n", array_map(
            fn($q, $i) => ($i + 1) . ". {$q}",
            $data['part_1'],
            array_keys($data['part_1'])
        ));

        $p1 = SpeakingPrompt::create([
            'title'            => "Speaking Part 1 - Test {$testNum} ({$bandKey})",
            'prompt_text'      => $part1Text,
            'preparation_time' => 0,
            'response_time'    => 240,
            'difficulty_level' => $bandConfig['difficulty'],
            'band_level'       => $bandKey,
            'created_by'       => $this->adminId,
            'source'           => 'ai_generated',
        ]);

        MockTestSection::create([
            'mock_test_id' => $test->id, 'module_type' => 'speaking',
            'content_id' => $p1->id, 'content_type' => SpeakingPrompt::class,
            'order' => $order++, 'duration_minutes' => 4,
        ]);

        // Part 2
        $p2 = SpeakingPrompt::create([
            'title'            => "Speaking Part 2 - Test {$testNum} ({$bandKey})",
            'prompt_text'      => $data['part_2']['cue_card'],
            'preparation_time' => 60,
            'response_time'    => 120,
            'difficulty_level' => $bandConfig['difficulty'],
            'band_level'       => $bandKey,
            'created_by'       => $this->adminId,
            'source'           => 'ai_generated',
        ]);

        MockTestSection::create([
            'mock_test_id' => $test->id, 'module_type' => 'speaking',
            'content_id' => $p2->id, 'content_type' => SpeakingPrompt::class,
            'order' => $order++, 'duration_minutes' => 3,
        ]);

        // Part 3
        $part3Text = implode("\n", array_map(
            fn($q, $i) => ($i + 1) . ". {$q}",
            $data['part_3'],
            array_keys($data['part_3'])
        ));

        $p3 = SpeakingPrompt::create([
            'title'            => "Speaking Part 3 - Test {$testNum} ({$bandKey})",
            'prompt_text'      => $part3Text,
            'preparation_time' => 0,
            'response_time'    => 300,
            'difficulty_level' => $bandConfig['difficulty'],
            'band_level'       => $bandKey,
            'created_by'       => $this->adminId,
            'source'           => 'ai_generated',
        ]);

        MockTestSection::create([
            'mock_test_id' => $test->id, 'module_type' => 'speaking',
            'content_id' => $p3->id, 'content_type' => SpeakingPrompt::class,
            'order' => $order++, 'duration_minutes' => 5,
        ]);

        return $order;
    }

    // ─── Prompts ─────────────────────────────────────────────────────────────

    private function buildReadingPrompt(array $band, int $testNum): string
    {
        $label      = $band['label'];
        $difficulty = $band['difficulty'];

        // Rotate topics so each test gets a different subject area
        $topics = [
            'environmental science and climate change',
            'history of technology and industrial innovation',
            'psychology of human behaviour and decision-making',
            'economics and global trade patterns',
            'medical science and public health',
            'urban planning and sustainable cities',
            'linguistics and language acquisition',
            'astronomy and space exploration',
            'sociology and cultural identity',
            'artificial intelligence and ethics',
            'marine biology and ocean conservation',
            'political philosophy and governance',
            'nutrition science and dietary research',
            'architecture and heritage preservation',
            'renewable energy and engineering',
            'education systems and pedagogy',
            'migration and demographic change',
            'neuroscience and memory',
            'international law and human rights',
            'biodiversity and ecosystem services',
        ];

        $topic = $topics[($testNum - 1) % count($topics)];

        return "You are an IELTS exam content creator. Generate a UNIQUE IELTS Academic Reading passage on the topic: \"{$topic}\".

Band level: {$label} ({$difficulty})
Test number: {$testNum} — this passage must be completely different from any other passage.

Requirements:
- Passage: 700-900 words, academic register, factual and informative
- Exactly 10 questions covering different comprehension skills
- Mix of question types: at least 3 multiple_choice, at least 3 fill_blank, at least 2 true_false
- All 4 options for multiple_choice must be DISTINCT (no repeated or near-identical options)
- Correct answers must be directly supported by the passage text
- Difficulty must match band {$label}: vocabulary complexity, sentence length, and inference level

Return ONLY valid JSON:
{
  \"passage_title\": \"Unique title here\",
  \"passage_text\": \"Full passage text (700-900 words)\",
  \"questions\": [
    {
      \"type\": \"multiple_choice\",
      \"question\": \"Question text?\",
      \"options\": [\"Option A\", \"Option B\", \"Option C\", \"Option D\"],
      \"correct_answer\": \"Option A\"
    },
    {
      \"type\": \"true_false\",
      \"question\": \"Statement to verify.\",
      \"options\": null,
      \"correct_answer\": \"TRUE\"
    },
    {
      \"type\": \"fill_blank\",
      \"question\": \"Complete: The main cause is ___.\",
      \"options\": null,
      \"correct_answer\": \"exact answer from passage\"
    }
  ]
}";
    }

    private function buildListeningPrompt(array $band, int $testNum): string
    {
        $label      = $band['label'];
        $difficulty = $band['difficulty'];

        $scenarios = [
            'a student inquiring about university course registration',
            'a radio interview with a scientist about a recent discovery',
            'two colleagues discussing a workplace project proposal',
            'a tour guide explaining a historical site to visitors',
            'a customer service call about a product complaint',
            'a lecture on environmental conservation strategies',
            'a job interview at a technology company',
            'a community meeting about local infrastructure plans',
            'a podcast episode on health and nutrition trends',
            'a travel agent advising a client on holiday options',
            'a professor explaining research methodology to students',
            'a news report on economic policy changes',
            'a doctor consulting a patient about treatment options',
            'a library orientation for new university students',
            'a business negotiation between two companies',
            'a documentary narration about wildlife migration',
            'a language school enrolment conversation',
            'a city council debate on housing development',
            'a fitness instructor explaining an exercise programme',
            'a museum audio guide about an art exhibition',
        ];

        $scenario = $scenarios[($testNum - 1) % count($scenarios)];

        return "You are an IELTS exam content creator. Generate a UNIQUE IELTS Listening exercise based on this scenario: \"{$scenario}\".

Band level: {$label} ({$difficulty})
Test number: {$testNum} — this exercise must be completely different from any other.

Requirements:
- Audio script: 300-450 words, natural spoken English, realistic dialogue or monologue
- Exactly 10 questions
- Mix: at least 3 multiple_choice, at least 4 fill_blank, at least 2 true_false
- All 4 options for multiple_choice must be DISTINCT (no repeated or near-identical options)
- Answers must be explicitly stated in the audio script
- Difficulty matches band {$label}

Return ONLY valid JSON:
{
  \"title\": \"Descriptive exercise title\",
  \"audio_script\": \"Full transcript (300-450 words)\",
  \"questions\": [
    {
      \"type\": \"multiple_choice\",
      \"question\": \"Question text?\",
      \"options\": [\"Option A\", \"Option B\", \"Option C\", \"Option D\"],
      \"correct_answer\": \"Option A\"
    },
    {
      \"type\": \"fill_blank\",
      \"question\": \"The meeting is scheduled for ___.\",
      \"options\": null,
      \"correct_answer\": \"exact answer from script\"
    },
    {
      \"type\": \"true_false\",
      \"question\": \"Statement to verify.\",
      \"options\": null,
      \"correct_answer\": \"TRUE\"
    }
  ]
}";
    }

    private function buildWritingPrompt(array $band, int $testNum): string
    {
        $label      = $band['label'];
        $difficulty = $band['difficulty'];

        $task1Topics = [
            'a bar chart comparing energy consumption across five countries',
            'a line graph showing population growth in urban vs rural areas',
            'a pie chart illustrating household expenditure categories',
            'a table presenting university enrolment statistics by subject',
            'a process diagram showing how paper is recycled',
            'a map showing changes to a town centre over 20 years',
            'a bar chart comparing average salaries across different professions',
            'a line graph depicting CO2 emissions over three decades',
            'a diagram showing the stages of water treatment',
            'a pie chart comparing transport usage in two cities',
            'a table showing tourist arrivals in five countries',
            'a bar chart illustrating smartphone ownership by age group',
            'a flow chart showing the manufacturing process of glass',
            'a line graph comparing literacy rates across regions',
            'a map showing planned development of a coastal area',
            'a bar chart comparing working hours in different industries',
            'a pie chart showing sources of electricity generation',
            'a table presenting crime statistics across different categories',
            'a diagram illustrating the life cycle of a butterfly',
            'a line graph showing changes in average house prices',
        ];

        $task2Topics = [
            'whether governments should fund arts and culture',
            'the impact of social media on interpersonal relationships',
            'whether university education should be free for all students',
            'the advantages and disadvantages of remote working',
            'whether stricter environmental laws harm economic growth',
            'the role of technology in modern education',
            'whether capital punishment is ever justified',
            'the causes and effects of increasing obesity rates',
            'whether tourism does more harm than good to local communities',
            'the impact of globalisation on cultural diversity',
            'whether children should learn a second language from birth',
            'the advantages and disadvantages of living in a large city',
            'whether governments should regulate fast food advertising',
            'the causes of youth unemployment and possible solutions',
            'whether space exploration is worth the cost',
            'the impact of automation on employment',
            'whether competitive sport teaches valuable life skills',
            'the advantages and disadvantages of nuclear energy',
            'whether older generations have a responsibility to younger ones',
            'the causes and solutions to increasing levels of stress',
        ];

        $t1 = $task1Topics[($testNum - 1) % count($task1Topics)];
        $t2 = $task2Topics[($testNum - 1) % count($task2Topics)];

        return "You are an IELTS exam content creator. Generate UNIQUE IELTS Writing tasks.

Band level: {$label} ({$difficulty})
Test number: {$testNum}

Task 1 topic: {$t1}
Task 2 topic: {$t2}

Return ONLY valid JSON:
{
  \"tasks\": [
    {
      \"task_type\": \"task_1\",
      \"question\": \"The [chart/graph/diagram] below shows {$t1}. Summarise the information by selecting and reporting the main features, and make comparisons where relevant.\",
      \"instructions\": \"Write at least 150 words.\",
      \"word_count\": 150,
      \"time_limit\": 20
    },
    {
      \"task_type\": \"task_2\",
      \"question\": \"A unique, well-formed IELTS Task 2 essay question about: {$t2}. Discuss both views and give your own opinion.\",
      \"instructions\": \"Give reasons for your answer and include any relevant examples from your own knowledge or experience. Write at least 250 words.\",
      \"word_count\": 250,
      \"time_limit\": 40
    }
  ]
}";
    }

    private function buildSpeakingPrompt(array $band, int $testNum): string
    {
        $label      = $band['label'];
        $difficulty = $band['difficulty'];

        $themes = [
            'hobbies and leisure activities',
            'travel and tourism',
            'technology and daily life',
            'education and learning',
            'work and career',
            'food and culture',
            'environment and nature',
            'health and fitness',
            'family and relationships',
            'art and creativity',
            'sport and competition',
            'media and entertainment',
            'cities and communities',
            'language and communication',
            'money and success',
            'traditions and celebrations',
            'science and discovery',
            'fashion and identity',
            'volunteering and social responsibility',
            'dreams and ambitions',
        ];

        $theme = $themes[($testNum - 1) % count($themes)];

        return "You are an IELTS exam content creator. Generate a UNIQUE IELTS Speaking test on the theme: \"{$theme}\".

Band level: {$label} ({$difficulty})
Test number: {$testNum}

Return ONLY valid JSON:
{
  \"part_1\": [
    \"Personal question 1 about {$theme}?\",
    \"Personal question 2 about {$theme}?\",
    \"Personal question 3 about {$theme}?\",
    \"Personal question 4 about {$theme}?\"
  ],
  \"part_2\": {
    \"cue_card\": \"Describe [something related to {$theme}].\\nYou should say:\\n- what it is\\n- when/where you experienced it\\n- who was involved\\nand explain why it was significant to you.\",
    \"follow_up_questions\": [
      \"Follow-up question 1?\",
      \"Follow-up question 2?\"
    ]
  },
  \"part_3\": [
    \"Abstract discussion question 1 about {$theme} in society?\",
    \"Abstract discussion question 2 about {$theme} and the future?\",
    \"Abstract discussion question 3 comparing perspectives on {$theme}?\",
    \"Abstract discussion question 4 about challenges related to {$theme}?\"
  ]
}";
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function callAI(string $prompt): string
    {
        $apiKey  = config('services.gemini.api_key');
        $baseUrl = config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');

        $models = ['gemini-2.0-flash', 'gemini-2.5-flash'];

        $lastError = 'No models tried';

        foreach ($models as $model) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(120)->post(
                    "{$baseUrl}/models/{$model}:generateContent?key={$apiKey}",
                    [
                        'contents' => [['parts' => [['text' => $prompt]]]],
                        'generationConfig' => [
                            'temperature'     => 0.85,
                            'topK'            => 40,
                            'topP'            => 0.95,
                            'maxOutputTokens' => 8192,
                        ],
                    ]
                );

                if ($response->successful()) {
                    $text = $response->json('candidates.0.content.parts.0.text');
                    if ($text) {
                        $this->command->line("      <fg=gray>Model: {$model}</>");
                        return $text;
                    }
                    $lastError = "Empty response from {$model}: " . $response->body();
                } elseif ($response->status() === 429) {
                    // Parse retryDelay from response and wait
                    $body        = $response->json();
                    $retryDelay  = 0;
                    foreach ($body['error']['details'] ?? [] as $detail) {
                        if (isset($detail['retryDelay'])) {
                            $retryDelay = (int) $detail['retryDelay'];
                            break;
                        }
                    }
                    $wait = max($retryDelay + 5, 65); // always wait at least 65s on 429
                    $this->command->line("      <fg=yellow>Rate limited on {$model}, waiting {$wait}s...</>");
                    sleep($wait);
                    // Retry same model once after waiting
                    $retry = \Illuminate\Support\Facades\Http::timeout(120)->post(
                        "{$baseUrl}/models/{$model}:generateContent?key={$apiKey}",
                        [
                            'contents' => [['parts' => [['text' => $prompt]]]],
                            'generationConfig' => [
                                'temperature'     => 0.85,
                                'topK'            => 40,
                                'topP'            => 0.95,
                                'maxOutputTokens' => 8192,
                            ],
                        ]
                    );
                    if ($retry->successful()) {
                        $text = $retry->json('candidates.0.content.parts.0.text');
                        if ($text) {
                            $this->command->line("      <fg=gray>Model: {$model} (retry)</>");
                            return $text;
                        }
                    }
                    $lastError = "HTTP 429 from {$model} (retry also failed)";
                } else {
                    $lastError = "HTTP {$response->status()} from {$model}: " . $response->body();
                }
            } catch (Exception $e) {
                $lastError = "Exception on {$model}: " . $e->getMessage();
            }
        }

        throw new Exception('All Gemini models failed. Last error: ' . $lastError);
    }

    private function parseJson(string $raw): array
    {
        // Strip markdown code fences if present
        $raw = preg_replace('/```json\s*/i', '', $raw);
        $raw = preg_replace('/```\s*/i', '', $raw);

        $start = strpos($raw, '{');
        $end   = strrpos($raw, '}');

        if ($start === false || $end === false) {
            throw new Exception('No JSON object found in AI response.');
        }

        $json = substr($raw, $start, $end - $start + 1);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON parse error: ' . json_last_error_msg());
        }

        return $data;
    }

    private function mapType(string $type): string
    {
        return match (strtolower($type)) {
            'multiple_choice', 'mcq'         => 'multiple_choice',
            'true_false', 'true/false', 'tf' => 'true_false',
            default                           => 'fill_blank',
        };
    }

    /**
     * Ensure options array has no duplicates and contains the correct answer.
     */
    private function sanitizeOptions(?array $options, string $correctAnswer): ?array
    {
        if (!$options) return null;

        // Deduplicate (case-insensitive)
        $seen   = [];
        $unique = [];
        foreach ($options as $opt) {
            $key = strtolower(trim($opt));
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[]   = $opt;
            }
        }

        // Ensure correct answer is present
        $hasCorrect = false;
        foreach ($unique as $opt) {
            if (strtolower(trim($opt)) === strtolower(trim($correctAnswer))) {
                $hasCorrect = true;
                break;
            }
        }

        if (!$hasCorrect) {
            $unique[] = $correctAnswer;
        }

        // Shuffle so correct answer isn't always first
        shuffle($unique);

        return array_values($unique);
    }
}
