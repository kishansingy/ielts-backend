<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use App\Models\ReadingPassage;
use App\Models\Question;
use App\Models\ListeningExercise;
use App\Models\ListeningQuestion;
use App\Models\WritingTask;
use App\Models\SpeakingPrompt;
use Illuminate\Support\Facades\DB;

class ComprehensiveIELTSContentSeeder extends Seeder
{
    private $bandLevels = ['band6', 'band7', 'band8', 'band9'];
    private $testsPerBand = 50; // 50 tests per band level
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting comprehensive IELTS content seeding...');
        
        // Get or create admin user
        $adminUser = \App\Models\User::firstOrCreate(
            ['email' => 'admin@ielts.com'],
            [
                'name' => 'System Admin',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'mobile' => '9999999999',
                'country_code' => '+91',
            ]
        );
        
        // Clear existing data
        $this->command->info('Clearing existing test data...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        DB::table('module_questions')->truncate();
        Question::truncate();
        ReadingPassage::truncate();
        ListeningQuestion::truncate();
        ListeningExercise::truncate();
        WritingTask::truncate();
        SpeakingPrompt::truncate();
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        DB::transaction(function () use ($adminUser) {
            $this->seedReadingModules($adminUser);
            $this->seedListeningModules($adminUser);
            $this->seedWritingModules($adminUser);
            $this->seedSpeakingModules($adminUser);
        });
        
        $this->command->info('Comprehensive IELTS content seeded successfully!');
        $this->command->info("Total: {$this->testsPerBand} tests per band × 4 bands × 4 modules = " . ($this->testsPerBand * 4 * 4) . " total tests");
    }

    /**
     * Seed Reading modules
     */
    private function seedReadingModules($adminUser)
    {
        $this->command->info('Seeding Reading modules...');
        
        $topics = $this->getReadingTopics();
        
        foreach ($this->bandLevels as $bandLevel) {
            $this->command->info("  Creating {$bandLevel} reading passages...");
            
            $module = Module::firstOrCreate([
                'name' => 'reading',
                'module_type' => 'reading',
                'band_level' => $bandLevel,
            ], [
                'description' => "IELTS Reading practice for {$bandLevel}",
                'is_active' => true,
                'supports_ai_generation' => false,
            ]);

            for ($i = 1; $i <= $this->testsPerBand; $i++) {
                $topicIndex = ($i - 1) % count($topics);
                $topic = $topics[$topicIndex];
                
                $passage = ReadingPassage::create([
                    'title' => "{$topic['title']} - Test {$i}",
                    'content' => $this->generateReadingContent($topic, $bandLevel, $i),
                    'difficulty_level' => $this->getDifficultyLevel($bandLevel),
                    'band_level' => $bandLevel,
                    'time_limit' => 20,
                    'created_by' => $adminUser->id,
                ]);

                // Create 5 unique questions per passage
                $questions = $this->generateReadingQuestions($topic, $bandLevel, $i);
                foreach ($questions as $index => $questionData) {
                    $question = Question::create([
                        'passage_id' => $passage->id,
                        'question_text' => $questionData['text'],
                        'question_type' => $questionData['type'],
                        'correct_answer' => $questionData['answer'],
                        'options' => $questionData['options'] ?? null,
                        'points' => 1,
                        'ielts_band_level' => str_replace('band', '', $bandLevel),
                        'is_ai_generated' => false,
                    ]);

                    DB::table('module_questions')->insert([
                        'module_id' => $module->id,
                        'question_id' => $question->id,
                        'order' => $index + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Seed Listening modules
     */
    private function seedListeningModules($adminUser)
    {
        $this->command->info('Seeding Listening modules...');
        
        $scenarios = $this->getListeningScenarios();
        
        foreach ($this->bandLevels as $bandLevel) {
            $this->command->info("  Creating {$bandLevel} listening exercises...");
            
            $module = Module::firstOrCreate([
                'name' => 'listening',
                'module_type' => 'listening',
                'band_level' => $bandLevel,
            ], [
                'description' => "IELTS Listening practice for {$bandLevel}",
                'is_active' => true,
                'supports_ai_generation' => false,
            ]);

            for ($i = 1; $i <= $this->testsPerBand; $i++) {
                $scenarioIndex = ($i - 1) % count($scenarios);
                $scenario = $scenarios[$scenarioIndex];
                
                $exercise = ListeningExercise::create([
                    'title' => "{$scenario['title']} - Test {$i}",
                    'audio_file_path' => "listening/{$bandLevel}_test_{$i}.mp3",
                    'transcript' => $this->generateListeningTranscript($scenario, $bandLevel, $i),
                    'duration' => 180 + ($i * 5), // Vary duration
                    'difficulty_level' => $this->getDifficultyLevel($bandLevel),
                    'band_level' => $bandLevel,
                    'created_by' => $adminUser->id,
                ]);

                $questions = $this->generateListeningQuestions($scenario, $bandLevel, $i);
                foreach ($questions as $questionData) {
                    ListeningQuestion::create([
                        'listening_exercise_id' => $exercise->id,
                        'question_text' => $questionData['text'],
                        'question_type' => $questionData['type'],
                        'correct_answer' => $questionData['answer'],
                        'options' => $questionData['options'] ?? null,
                        'points' => 1,
                    ]);
                }
            }
        }
    }

    /**
     * Seed Writing modules
     */
    private function seedWritingModules($adminUser)
    {
        $this->command->info('Seeding Writing modules...');
        
        $task1Topics = $this->getWritingTask1Topics();
        $task2Topics = $this->getWritingTask2Topics();
        
        foreach ($this->bandLevels as $bandLevel) {
            $this->command->info("  Creating {$bandLevel} writing tasks...");
            
            $module = Module::firstOrCreate([
                'name' => 'writing',
                'module_type' => 'writing',
                'band_level' => $bandLevel,
            ], [
                'description' => "IELTS Writing practice for {$bandLevel}",
                'is_active' => true,
                'supports_ai_generation' => false,
            ]);

            // Create Task 1 prompts (25 per band)
            for ($i = 1; $i <= 25; $i++) {
                $topicIndex = ($i - 1) % count($task1Topics);
                $topic = $task1Topics[$topicIndex];
                
                WritingTask::create([
                    'title' => "{$topic['type']}: {$topic['subject']} - Test {$i}",
                    'task_type' => 'task1',
                    'prompt' => $this->generateTask1Prompt($topic, $i),
                    'instructions' => 'Summarize the information by selecting and reporting the main features, and make comparisons where relevant. Write at least 150 words.',
                    'time_limit' => 20,
                    'word_limit' => 150,
                    'band_level' => $bandLevel,
                    'created_by' => $adminUser->id,
                ]);
            }

            // Create Task 2 prompts (25 per band)
            for ($i = 1; $i <= 25; $i++) {
                $topicIndex = ($i - 1) % count($task2Topics);
                $topic = $task2Topics[$topicIndex];
                
                WritingTask::create([
                    'title' => "{$topic['category']}: {$topic['subject']} - Test {$i}",
                    'task_type' => 'task2',
                    'prompt' => $this->generateTask2Prompt($topic, $i),
                    'instructions' => 'Give reasons for your answer and include any relevant examples from your own knowledge or experience. Write at least 250 words.',
                    'time_limit' => 40,
                    'word_limit' => 250,
                    'band_level' => $bandLevel,
                    'created_by' => $adminUser->id,
                ]);
            }
        }
    }

    /**
     * Seed Speaking modules
     */
    private function seedSpeakingModules($adminUser)
    {
        $this->command->info('Seeding Speaking modules...');
        
        $topics = $this->getSpeakingTopics();
        
        foreach ($this->bandLevels as $bandLevel) {
            $this->command->info("  Creating {$bandLevel} speaking prompts...");
            
            $module = Module::firstOrCreate([
                'name' => 'speaking',
                'module_type' => 'speaking',
                'band_level' => $bandLevel,
            ], [
                'description' => "IELTS Speaking practice for {$bandLevel}",
                'is_active' => true,
                'supports_ai_generation' => false,
            ]);

            for ($i = 1; $i <= $this->testsPerBand; $i++) {
                $topicIndex = ($i - 1) % count($topics);
                $topic = $topics[$topicIndex];
                
                SpeakingPrompt::create([
                    'title' => "{$topic['category']}: {$topic['subject']} - Test {$i}",
                    'prompt_text' => $this->generateSpeakingPrompt($topic, $i),
                    'preparation_time' => 60,
                    'response_time' => 120,
                    'difficulty_level' => $this->getDifficultyLevel($bandLevel),
                    'band_level' => $bandLevel,
                    'created_by' => $adminUser->id,
                ]);
            }
        }
    }

    // Helper methods for generating content
    
    private function getReadingTopics()
    {
        return [
            ['title' => 'Technology and Innovation', 'keywords' => ['artificial intelligence', 'automation', 'digital transformation']],
            ['title' => 'Environmental Conservation', 'keywords' => ['biodiversity', 'climate change', 'sustainability']],
            ['title' => 'Education Systems', 'keywords' => ['learning methods', 'curriculum', 'student development']],
            ['title' => 'Healthcare Advances', 'keywords' => ['medical research', 'treatment', 'public health']],
            ['title' => 'Urban Development', 'keywords' => ['city planning', 'infrastructure', 'smart cities']],
            ['title' => 'Cultural Heritage', 'keywords' => ['traditions', 'preservation', 'cultural identity']],
            ['title' => 'Economic Trends', 'keywords' => ['globalization', 'trade', 'economic growth']],
            ['title' => 'Social Media Impact', 'keywords' => ['communication', 'digital society', 'online behavior']],
            ['title' => 'Space Exploration', 'keywords' => ['astronomy', 'space missions', 'cosmic discoveries']],
            ['title' => 'Renewable Energy', 'keywords' => ['solar power', 'wind energy', 'clean technology']],
        ];
    }

    private function generateReadingContent($topic, $bandLevel, $testNumber)
    {
        $complexity = match($bandLevel) {
            'band6' => 'basic',
            'band7' => 'intermediate',
            'band8' => 'advanced',
            'band9' => 'expert',
        };
        
        $keyword = $topic['keywords'][($testNumber - 1) % count($topic['keywords'])];
        
        return "This passage explores {$keyword} in the context of {$topic['title']}. " .
               "[Test {$testNumber} for {$bandLevel}] " .
               "The {$complexity} level content discusses various aspects including research findings, " .
               "practical applications, and future implications. Scientists and experts in the field " .
               "have conducted extensive studies to understand the impact and significance. " .
               "Recent developments have shown promising results, leading to new opportunities " .
               "and challenges that require careful consideration and strategic planning. " .
               "The implications extend across multiple sectors, affecting both individuals and " .
               "organizations in meaningful ways. Understanding these concepts is crucial for " .
               "making informed decisions and contributing to progress in this important area.";
    }

    private function generateReadingQuestions($topic, $bandLevel, $testNumber)
    {
        $keyword = $topic['keywords'][($testNumber - 1) % count($topic['keywords'])];
        
        return [
            [
                'text' => "What is the main subject discussed in the passage?",
                'type' => 'fill_blank',
                'answer' => $keyword
            ],
            [
                'text' => "Which field does this passage primarily focus on?",
                'type' => 'multiple_choice',
                'answer' => $topic['title'],
                'options' => json_encode([$topic['title'], 'General Science', 'Historical Events', 'Literary Analysis'])
            ],
            [
                'text' => "What have scientists conducted to understand the impact?",
                'type' => 'fill_blank',
                'answer' => 'extensive studies'
            ],
            [
                'text' => "Recent developments have shown what kind of results?",
                'type' => 'multiple_choice',
                'answer' => 'promising results',
                'options' => json_encode(['disappointing outcomes', 'promising results', 'unclear findings', 'negative impacts'])
            ],
            [
                'text' => "Understanding these concepts is crucial for what?",
                'type' => 'fill_blank',
                'answer' => 'making informed decisions'
            ],
        ];
    }

    private function getListeningScenarios()
    {
        return [
            ['title' => 'University Accommodation', 'context' => 'student services'],
            ['title' => 'Job Interview', 'context' => 'employment'],
            ['title' => 'Library Services', 'context' => 'academic facilities'],
            ['title' => 'Travel Planning', 'context' => 'tourism'],
            ['title' => 'Medical Appointment', 'context' => 'healthcare'],
            ['title' => 'Course Registration', 'context' => 'education'],
            ['title' => 'Bank Services', 'context' => 'finance'],
            ['title' => 'Restaurant Reservation', 'context' => 'hospitality'],
            ['title' => 'Gym Membership', 'context' => 'fitness'],
            ['title' => 'Conference Registration', 'context' => 'professional development'],
        ];
    }

    private function generateListeningTranscript($scenario, $bandLevel, $testNumber)
    {
        return "Transcript for {$scenario['title']} - Test {$testNumber} ({$bandLevel}). " .
               "This conversation takes place in a {$scenario['context']} setting. " .
               "Speaker A: Hello, I'd like to inquire about the services available. " .
               "Speaker B: Certainly, we offer various options tailored to your needs. " .
               "The standard package includes basic features, while premium options provide " .
               "additional benefits. Pricing varies depending on your selection. " .
               "Speaker A: What are the main differences between the packages? " .
               "Speaker B: The premium package includes priority service, extended hours, " .
               "and additional resources. We also offer flexible payment plans.";
    }

    private function generateListeningQuestions($scenario, $bandLevel, $testNumber)
    {
        return [
            [
                'text' => "What is the main topic of this conversation?",
                'type' => 'fill_blank',
                'answer' => $scenario['title']
            ],
            [
                'text' => "Where does this conversation take place?",
                'type' => 'multiple_choice',
                'answer' => $scenario['context'],
                'options' => json_encode([$scenario['context'], 'office', 'home', 'park'])
            ],
            [
                'text' => "What does the standard package include?",
                'type' => 'fill_blank',
                'answer' => 'basic features'
            ],
            [
                'text' => "What additional benefit does the premium package offer?",
                'type' => 'multiple_choice',
                'answer' => 'priority service',
                'options' => json_encode(['free delivery', 'priority service', 'discount', 'warranty'])
            ],
            [
                'text' => "Are flexible payment plans available?",
                'type' => 'true_false',
                'answer' => 'true',
                'options' => json_encode(['true', 'false'])
            ],
        ];
    }

    private function getWritingTask1Topics()
    {
        return [
            ['type' => 'Bar Chart', 'subject' => 'Internet Usage Statistics'],
            ['type' => 'Line Graph', 'subject' => 'Temperature Variations'],
            ['type' => 'Pie Chart', 'subject' => 'Energy Consumption'],
            ['type' => 'Table', 'subject' => 'Student Enrollment Data'],
            ['type' => 'Process Diagram', 'subject' => 'Manufacturing Process'],
            ['type' => 'Map', 'subject' => 'Urban Development Changes'],
            ['type' => 'Mixed Charts', 'subject' => 'Economic Indicators'],
        ];
    }

    private function generateTask1Prompt($topic, $testNumber)
    {
        return "The {$topic['type']} shows information about {$topic['subject']} (Test {$testNumber}). " .
               "The data presents various trends and patterns that require analysis and comparison.";
    }

    private function getWritingTask2Topics()
    {
        return [
            ['category' => 'Education', 'subject' => 'Online Learning vs Traditional Education'],
            ['category' => 'Technology', 'subject' => 'Impact of Social Media on Society'],
            ['category' => 'Environment', 'subject' => 'Climate Change Solutions'],
            ['category' => 'Health', 'subject' => 'Public Health Policies'],
            ['category' => 'Work', 'subject' => 'Remote Work Benefits and Challenges'],
            ['category' => 'Society', 'subject' => 'Generation Gap Issues'],
            ['category' => 'Culture', 'subject' => 'Preserving Traditional Values'],
            ['category' => 'Economy', 'subject' => 'Globalization Effects'],
        ];
    }

    private function generateTask2Prompt($topic, $testNumber)
    {
        return "Some people believe that {$topic['subject']} has significant implications for modern society. " .
               "Others argue that the effects are overstated. (Test {$testNumber}) " .
               "Discuss both views and give your own opinion.";
    }

    private function getSpeakingTopics()
    {
        return [
            ['category' => 'Personal', 'subject' => 'Hobbies and Interests'],
            ['category' => 'Education', 'subject' => 'Learning Experiences'],
            ['category' => 'Work', 'subject' => 'Career Aspirations'],
            ['category' => 'Travel', 'subject' => 'Memorable Journeys'],
            ['category' => 'Technology', 'subject' => 'Digital Devices'],
            ['category' => 'Family', 'subject' => 'Family Traditions'],
            ['category' => 'Food', 'subject' => 'Culinary Preferences'],
            ['category' => 'Sports', 'subject' => 'Physical Activities'],
            ['category' => 'Entertainment', 'subject' => 'Leisure Activities'],
            ['category' => 'Environment', 'subject' => 'Environmental Concerns'],
        ];
    }

    private function generateSpeakingPrompt($topic, $testNumber)
    {
        return "Describe your experience with {$topic['subject']}. (Test {$testNumber}) " .
               "You should say: what it involves, when you started, why you find it interesting, " .
               "and explain how it has influenced your life.";
    }

    private function getDifficultyLevel($bandLevel)
    {
        return match ($bandLevel) {
            'band6' => 'beginner',
            'band7' => 'intermediate',
            'band8', 'band9' => 'advanced',
            default => 'intermediate',
        };
    }
}
