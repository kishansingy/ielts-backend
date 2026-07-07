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

class ProductionIELTSQuestionsSeeder extends Seeder
{
    private $bandLevels = ['band6', 'band7', 'band8', 'band9'];
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create a system admin user for content creation
        $adminUser = \App\Models\User::firstOrCreate(
            ['email' => 'admin@ielts.com'],
            [
                'name' => 'System Admin',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );
        
        DB::transaction(function () use ($adminUser) {
            $this->seedReadingModules($adminUser);
            $this->seedListeningModules($adminUser);
            $this->seedWritingModules($adminUser);
            $this->seedSpeakingModules($adminUser);
        });
        
        $this->command->info('Production IELTS questions seeded successfully!');
    }

    /**
     * Seed Reading modules with real IELTS repeated questions
     */
    private function seedReadingModules($adminUser)
    {
        $this->command->info('Seeding Reading modules...');
        
        foreach ($this->bandLevels as $bandLevel) {
            // Create module
            $module = Module::firstOrCreate([
                'name' => 'reading',
                'module_type' => 'reading',
                'band_level' => $bandLevel,
            ], [
                'description' => "IELTS Reading practice for {$bandLevel}",
                'is_active' => true,
                'supports_ai_generation' => false,
            ]);

            // Create reading passages with real IELTS topics
            $passages = $this->getReadingPassages($bandLevel);
            
            foreach ($passages as $passageData) {
                $passage = ReadingPassage::create([
                    'title' => $passageData['title'],
                    'content' => $passageData['content'],
                    'difficulty_level' => $this->getDifficultyLevel($bandLevel),
                    'band_level' => $bandLevel,
                    'time_limit' => 20,
                    'created_by' => $adminUser->id,
                ]);

                // Create questions for this passage
                foreach ($passageData['questions'] as $index => $questionData) {
                    $question = Question::create([
                        'passage_id' => $passage->id,
                        'question_text' => $questionData['text'],
                        'question_type' => $questionData['type'],
                        'correct_answer' => $questionData['answer'],
                        'options' => $questionData['options'] ?? null,
                        'points' => 1,
                        'ielts_band_level' => str_replace('band', '', $bandLevel), // Convert band6 to 6
                        'is_ai_generated' => false,
                    ]);

                    // Link question to module
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
     * Seed Listening modules with real IELTS repeated questions
     */
    private function seedListeningModules($adminUser)
    {
        $this->command->info('Seeding Listening modules...');
        
        foreach ($this->bandLevels as $bandLevel) {
            $module = Module::firstOrCreate([
                'name' => 'listening',
                'module_type' => 'listening',
                'band_level' => $bandLevel,
            ], [
                'description' => "IELTS Listening practice for {$bandLevel}",
                'is_active' => true,
                'supports_ai_generation' => false,
            ]);

            $exercises = $this->getListeningExercises($bandLevel);
            
            foreach ($exercises as $exerciseData) {
                $exercise = ListeningExercise::create([
                    'title' => $exerciseData['title'],
                    'audio_file_path' => $exerciseData['audio_path'],
                    'transcript' => $exerciseData['transcript'],
                    'duration' => $exerciseData['duration'],
                    'difficulty_level' => $this->getDifficultyLevel($bandLevel),
                    'band_level' => $bandLevel,
                    'created_by' => $adminUser->id,
                ]);

                foreach ($exerciseData['questions'] as $index => $questionData) {
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
     * Seed Writing modules with real IELTS repeated tasks
     */
    private function seedWritingModules($adminUser)
    {
        $this->command->info('Seeding Writing modules...');
        
        foreach ($this->bandLevels as $bandLevel) {
            // Task 1 Module
            $moduleTask1 = Module::firstOrCreate([
                'name' => 'writing',
                'module_type' => 'writing',
                'band_level' => $bandLevel,
            ], [
                'description' => "IELTS Writing practice for {$bandLevel}",
                'is_active' => true,
                'supports_ai_generation' => false,
            ]);

            // Task 2 Module - same as Task 1 since name is enum
            $moduleTask2 = $moduleTask1;

            $tasks = $this->getWritingTasks($bandLevel);
            
            foreach ($tasks as $taskData) {
                WritingTask::create([
                    'title' => $taskData['title'],
                    'task_type' => $taskData['type'],
                    'prompt' => $taskData['prompt'],
                    'instructions' => $taskData['instructions'],
                    'time_limit' => $taskData['type'] === 'task1' ? 20 : 40,
                    'word_limit' => $taskData['type'] === 'task1' ? 150 : 250,
                    'band_level' => $bandLevel,
                    'created_by' => $adminUser->id,
                ]);
            }
        }
    }

    /**
     * Seed Speaking modules with real IELTS repeated prompts
     */
    private function seedSpeakingModules($adminUser)
    {
        $this->command->info('Seeding Speaking modules...');
        
        foreach ($this->bandLevels as $bandLevel) {
            $module = Module::firstOrCreate([
                'name' => 'speaking',
                'module_type' => 'speaking',
                'band_level' => $bandLevel,
            ], [
                'description' => "IELTS Speaking practice for {$bandLevel}",
                'is_active' => true,
                'supports_ai_generation' => false,
            ]);

            $prompts = $this->getSpeakingPrompts($bandLevel);
            
            foreach ($prompts as $promptData) {
                SpeakingPrompt::create([
                    'title' => $promptData['title'],
                    'prompt_text' => $promptData['prompt'],
                    'preparation_time' => 60,
                    'response_time' => 120,
                    'difficulty_level' => $this->getDifficultyLevel($bandLevel),
                    'band_level' => $bandLevel,
                    'created_by' => $adminUser->id,
                ]);
            }
        }
    }

    /**
     * Get difficulty level based on band
     */
    private function getDifficultyLevel($bandLevel)
    {
        return match ($bandLevel) {
            'band6' => 'beginner',
            'band7' => 'intermediate',
            'band8', 'band9' => 'advanced',
            default => 'intermediate',
        };
    }

    /**
     * Get reading passages with real IELTS repeated questions
     */
    private function getReadingPassages($bandLevel)
    {
        // Real IELTS repeated reading topics
        return [
            [
                'title' => 'The History of Chocolate',
                'content' => "Chocolate has a rich history dating back over 3,000 years. The ancient Mayans and Aztecs were among the first to cultivate cacao trees and create chocolate beverages. The word 'chocolate' comes from the Aztec word 'xocolatl', which means bitter water. When Spanish conquistadors brought chocolate to Europe in the 16th century, it quickly became popular among the wealthy elite. Initially consumed as a beverage, chocolate was later transformed into the solid form we know today through innovations in processing techniques during the Industrial Revolution.",
                'questions' => [
                    ['text' => 'How long has chocolate been in existence?', 'type' => 'fill_blank', 'answer' => 'over 3,000 years'],
                    ['text' => 'Which ancient civilizations first cultivated cacao?', 'type' => 'multiple_choice', 'answer' => 'Mayans and Aztecs', 'options' => ['Romans and Greeks', 'Mayans and Aztecs', 'Egyptians and Persians', 'Chinese and Japanese']],
                    ['text' => 'What does the Aztec word "xocolatl" mean?', 'type' => 'fill_blank', 'answer' => 'bitter water'],
                    ['text' => 'When was chocolate brought to Europe?', 'type' => 'fill_blank', 'answer' => '16th century'],
                    ['text' => 'Chocolate was initially consumed in which form?', 'type' => 'multiple_choice', 'answer' => 'beverage', 'options' => ['solid bar', 'beverage', 'powder', 'paste']],
                ]
            ],
            [
                'title' => 'Renewable Energy Sources',
                'content' => "The transition to renewable energy is crucial for combating climate change. Solar power harnesses energy from the sun using photovoltaic cells, while wind turbines convert kinetic energy from wind into electricity. Hydroelectric power uses flowing water to generate electricity, and geothermal energy taps into heat from beneath the Earth's surface. Each renewable source has advantages and limitations. Solar panels require significant initial investment but have low operating costs. Wind farms need consistent wind patterns and can impact local wildlife. Despite challenges, renewable energy capacity has grown exponentially in recent decades.",
                'questions' => [
                    ['text' => 'What do photovoltaic cells use to generate energy?', 'type' => 'fill_blank', 'answer' => 'sun'],
                    ['text' => 'Wind turbines convert what type of energy?', 'type' => 'multiple_choice', 'answer' => 'kinetic', 'options' => ['thermal', 'kinetic', 'chemical', 'nuclear']],
                    ['text' => 'What is a disadvantage of solar panels?', 'type' => 'fill_blank', 'answer' => 'significant initial investment'],
                    ['text' => 'Geothermal energy comes from where?', 'type' => 'fill_blank', 'answer' => "beneath the Earth's surface"],
                    ['text' => 'How has renewable energy capacity changed recently?', 'type' => 'multiple_choice', 'answer' => 'grown exponentially', 'options' => ['remained stable', 'decreased slightly', 'grown exponentially', 'fluctuated wildly']],
                ]
            ],
            [
                'title' => 'The Impact of Social Media',
                'content' => "Social media platforms have revolutionized communication in the 21st century. These digital networks enable instant connection across geographical boundaries, allowing people to share information, ideas, and experiences globally. However, research indicates both positive and negative effects. On the positive side, social media facilitates community building, enables social movements, and provides platforms for marginalized voices. Conversely, studies link excessive social media use to increased anxiety, depression, and reduced face-to-face interaction. The spread of misinformation and privacy concerns also pose significant challenges.",
                'questions' => [
                    ['text' => 'What have social media platforms revolutionized?', 'type' => 'fill_blank', 'answer' => 'communication'],
                    ['text' => 'Social media enables connection across what?', 'type' => 'multiple_choice', 'answer' => 'geographical boundaries', 'options' => ['time zones only', 'geographical boundaries', 'language barriers', 'age groups']],
                    ['text' => 'Name one positive effect of social media mentioned.', 'type' => 'fill_blank', 'answer' => 'community building'],
                    ['text' => 'Excessive social media use is linked to what?', 'type' => 'multiple_choice', 'answer' => 'increased anxiety', 'options' => ['better sleep', 'increased anxiety', 'improved memory', 'enhanced focus']],
                    ['text' => 'What are two challenges mentioned?', 'type' => 'fill_blank', 'answer' => 'misinformation and privacy concerns'],
                ]
            ],
            [
                'title' => 'Urban Planning and Sustainability',
                'content' => "Modern urban planning increasingly focuses on sustainability and livability. Green infrastructure, such as parks and green roofs, helps manage stormwater, reduce urban heat islands, and improve air quality. Mixed-use development combines residential, commercial, and recreational spaces, reducing the need for long commutes. Public transportation systems are being expanded and improved to decrease reliance on private vehicles. Smart city technologies use data and sensors to optimize resource use and improve services. However, implementing these changes requires significant investment and coordination among multiple stakeholders.",
                'questions' => [
                    ['text' => 'What does modern urban planning increasingly focus on?', 'type' => 'fill_blank', 'answer' => 'sustainability and livability'],
                    ['text' => 'Green infrastructure helps manage what?', 'type' => 'multiple_choice', 'answer' => 'stormwater', 'options' => ['traffic', 'stormwater', 'noise pollution', 'crime']],
                    ['text' => 'What does mixed-use development combine?', 'type' => 'fill_blank', 'answer' => 'residential, commercial, and recreational spaces'],
                    ['text' => 'Why are public transportation systems being improved?', 'type' => 'fill_blank', 'answer' => 'to decrease reliance on private vehicles'],
                    ['text' => 'What do smart city technologies use?', 'type' => 'multiple_choice', 'answer' => 'data and sensors', 'options' => ['solar panels', 'data and sensors', 'wind turbines', 'nuclear power']],
                ]
            ],
        ];
    }

    /**
     * Get listening exercises with real IELTS repeated topics
     */
    private function getListeningExercises($bandLevel)
    {
        return [
            [
                'title' => 'University Accommodation Inquiry',
                'audio_path' => 'listening/accommodation_inquiry.mp3',
                'transcript' => 'Student: Hello, I\'m calling about student accommodation for next semester. Staff: Yes, we have several options available. We have shared apartments and single rooms in halls of residence. Student: What\'s the price difference? Staff: Shared apartments are £120 per week, single rooms are £180 per week. Student: Are utilities included? Staff: Yes, all utilities and internet are included in both options.',
                'duration' => 180,
                'questions' => [
                    ['text' => 'What is the student inquiring about?', 'type' => 'fill_blank', 'answer' => 'student accommodation'],
                    ['text' => 'How much is a shared apartment per week?', 'type' => 'fill_blank', 'answer' => '£120'],
                    ['text' => 'How much is a single room per week?', 'type' => 'multiple_choice', 'answer' => '£180', 'options' => ['£120', '£150', '£180', '£200']],
                    ['text' => 'Are utilities included?', 'type' => 'true_false', 'answer' => 'true', 'options' => ['true', 'false']],
                    ['text' => 'What else is included besides utilities?', 'type' => 'fill_blank', 'answer' => 'internet'],
                ]
            ],
            [
                'title' => 'Library Tour and Services',
                'audio_path' => 'listening/library_tour.mp3',
                'transcript' => 'Guide: Welcome to the university library. On the ground floor, you\'ll find the circulation desk and computer lab. The second floor houses our reference collection and study rooms. The third floor is designated as a quiet study area. Our library is open 24 hours during exam periods. You can borrow up to 10 books at a time for three weeks. We also offer printing services at 10 pence per page.',
                'duration' => 200,
                'questions' => [
                    ['text' => 'What is on the ground floor?', 'type' => 'fill_blank', 'answer' => 'circulation desk and computer lab'],
                    ['text' => 'Where is the reference collection located?', 'type' => 'multiple_choice', 'answer' => 'second floor', 'options' => ['ground floor', 'second floor', 'third floor', 'basement']],
                    ['text' => 'What is the third floor designated as?', 'type' => 'fill_blank', 'answer' => 'quiet study area'],
                    ['text' => 'How many books can you borrow at once?', 'type' => 'fill_blank', 'answer' => '10'],
                    ['text' => 'How much does printing cost per page?', 'type' => 'multiple_choice', 'answer' => '10 pence', 'options' => ['5 pence', '10 pence', '15 pence', '20 pence']],
                ]
            ],
            [
                'title' => 'Job Interview at Marketing Firm',
                'audio_path' => 'listening/job_interview.mp3',
                'transcript' => 'Interviewer: Tell me about your experience in digital marketing. Candidate: I\'ve worked in digital marketing for five years, specializing in social media campaigns and SEO. Interviewer: What tools are you familiar with? Candidate: I\'m proficient in Google Analytics, Hootsuite, and Adobe Creative Suite. Interviewer: The position requires occasional travel. Is that acceptable? Candidate: Yes, I\'m comfortable with travel up to 25% of the time.',
                'duration' => 190,
                'questions' => [
                    ['text' => 'How many years of experience does the candidate have?', 'type' => 'fill_blank', 'answer' => 'five years'],
                    ['text' => 'What does the candidate specialize in?', 'type' => 'fill_blank', 'answer' => 'social media campaigns and SEO'],
                    ['text' => 'Which analytics tool is the candidate familiar with?', 'type' => 'multiple_choice', 'answer' => 'Google Analytics', 'options' => ['Facebook Insights', 'Google Analytics', 'Twitter Analytics', 'LinkedIn Analytics']],
                    ['text' => 'Does the position require travel?', 'type' => 'true_false', 'answer' => 'true', 'options' => ['true', 'false']],
                    ['text' => 'What percentage of travel time is the candidate comfortable with?', 'type' => 'fill_blank', 'answer' => '25%'],
                ]
            ],
            [
                'title' => 'Environmental Conservation Lecture',
                'audio_path' => 'listening/conservation_lecture.mp3',
                'transcript' => 'Professor: Today we\'ll discuss biodiversity conservation. Biodiversity refers to the variety of life on Earth, including species diversity, genetic diversity, and ecosystem diversity. Conservation efforts focus on protecting endangered species and their habitats. Key strategies include establishing protected areas, implementing sustainable resource management, and reducing pollution. Climate change poses the greatest threat to biodiversity, affecting migration patterns and habitat availability.',
                'duration' => 210,
                'questions' => [
                    ['text' => 'What does biodiversity refer to?', 'type' => 'fill_blank', 'answer' => 'variety of life on Earth'],
                    ['text' => 'Name one type of diversity mentioned.', 'type' => 'multiple_choice', 'answer' => 'species diversity', 'options' => ['cultural diversity', 'species diversity', 'linguistic diversity', 'economic diversity']],
                    ['text' => 'What do conservation efforts focus on?', 'type' => 'fill_blank', 'answer' => 'protecting endangered species and their habitats'],
                    ['text' => 'What is one conservation strategy mentioned?', 'type' => 'fill_blank', 'answer' => 'establishing protected areas'],
                    ['text' => 'What poses the greatest threat to biodiversity?', 'type' => 'multiple_choice', 'answer' => 'climate change', 'options' => ['deforestation', 'climate change', 'pollution', 'hunting']],
                ]
            ],
        ];
    }

    /**
     * Get writing tasks with real IELTS repeated topics
     */
    private function getWritingTasks($bandLevel)
    {
        return [
            // Task 1 prompts
            [
                'title' => 'Bar Chart: Internet Usage by Age Group',
                'type' => 'task1',
                'prompt' => 'The bar chart shows the percentage of internet usage across different age groups in 2020.',
                'instructions' => 'Summarize the information by selecting and reporting the main features, and make comparisons where relevant. Write at least 150 words.',
            ],
            [
                'title' => 'Line Graph: Temperature Changes',
                'type' => 'task1',
                'prompt' => 'The line graph illustrates average monthly temperatures in three cities over a year.',
                'instructions' => 'Summarize the information by selecting and reporting the main features, and make comparisons where relevant. Write at least 150 words.',
            ],
            [
                'title' => 'Process Diagram: Water Cycle',
                'type' => 'task1',
                'prompt' => 'The diagram shows the natural water cycle process.',
                'instructions' => 'Summarize the information by selecting and reporting the main features. Write at least 150 words.',
            ],
            [
                'title' => 'Table: Student Enrollment Statistics',
                'type' => 'task1',
                'prompt' => 'The table shows student enrollment numbers in different university departments from 2018 to 2022.',
                'instructions' => 'Summarize the information by selecting and reporting the main features, and make comparisons where relevant. Write at least 150 words.',
            ],
            [
                'title' => 'Pie Charts: Energy Consumption',
                'type' => 'task1',
                'prompt' => 'The pie charts compare household energy consumption in 2000 and 2020.',
                'instructions' => 'Summarize the information by selecting and reporting the main features, and make comparisons where relevant. Write at least 150 words.',
            ],
            
            // Task 2 prompts
            [
                'title' => 'Technology and Education',
                'type' => 'task2',
                'prompt' => 'Some people believe that technology has made learning easier and more accessible, while others think it has made students lazy and less focused. Discuss both views and give your own opinion.',
                'instructions' => 'Give reasons for your answer and include any relevant examples from your own knowledge or experience. Write at least 250 words.',
            ],
            [
                'title' => 'Environmental Protection',
                'type' => 'task2',
                'prompt' => 'Many people believe that protecting the environment is the responsibility of governments, while others think individuals should take action. Discuss both views and give your opinion.',
                'instructions' => 'Give reasons for your answer and include any relevant examples from your own knowledge or experience. Write at least 250 words.',
            ],
            [
                'title' => 'Work-Life Balance',
                'type' => 'task2',
                'prompt' => 'In many countries, people are working longer hours and have less free time. What are the causes of this? What are the effects on individuals and society?',
                'instructions' => 'Give reasons for your answer and include any relevant examples from your own knowledge or experience. Write at least 250 words.',
            ],
            [
                'title' => 'Public Transportation',
                'type' => 'task2',
                'prompt' => 'Some people think governments should invest more in public transportation, while others believe money should be spent on building more roads. Discuss both views and give your opinion.',
                'instructions' => 'Give reasons for your answer and include any relevant examples from your own knowledge or experience. Write at least 250 words.',
            ],
            [
                'title' => 'Traditional vs Modern Values',
                'type' => 'task2',
                'prompt' => 'As countries develop, their cultures become more similar. Is this a positive or negative development?',
                'instructions' => 'Give reasons for your answer and include any relevant examples from your own knowledge or experience. Write at least 250 words.',
            ],
            [
                'title' => 'Health and Lifestyle',
                'type' => 'task2',
                'prompt' => 'In many countries, the number of people suffering from obesity is increasing. What are the causes and what solutions can be implemented?',
                'instructions' => 'Give reasons for your answer and include any relevant examples from your own knowledge or experience. Write at least 250 words.',
            ],
            [
                'title' => 'Education System',
                'type' => 'task2',
                'prompt' => 'Some people think that schools should select students based on their academic abilities, while others believe students with different abilities should be educated together. Discuss both views and give your opinion.',
                'instructions' => 'Give reasons for your answer and include any relevant examples from your own knowledge or experience. Write at least 250 words.',
            ],
            [
                'title' => 'Globalization',
                'type' => 'task2',
                'prompt' => 'Globalization has both positive and negative effects on developing countries. What are these effects and which do you think is more significant?',
                'instructions' => 'Give reasons for your answer and include any relevant examples from your own knowledge or experience. Write at least 250 words.',
            ],
            [
                'title' => 'Crime and Punishment',
                'type' => 'task2',
                'prompt' => 'Some people believe that the best way to reduce crime is to give longer prison sentences. Others think there are better alternative ways. Discuss both views and give your opinion.',
                'instructions' => 'Give reasons for your answer and include any relevant examples from your own knowledge or experience. Write at least 250 words.',
            ],
            [
                'title' => 'Advertising',
                'type' => 'task2',
                'prompt' => 'Advertising influences people to buy things they do not really need. To what extent do you agree or disagree?',
                'instructions' => 'Give reasons for your answer and include any relevant examples from your own knowledge or experience. Write at least 250 words.',
            ],
        ];
    }

    /**
     * Get speaking prompts with real IELTS repeated topics
     */
    private function getSpeakingPrompts($bandLevel)
    {
        return [
            [
                'title' => 'Describe Your Hometown',
                'prompt' => 'Describe your hometown. You should say: where it is located, what it is famous for, what you like about it, and explain why it is special to you.',
            ],
            [
                'title' => 'A Memorable Journey',
                'prompt' => 'Describe a memorable journey you have taken. You should say: where you went, who you went with, what you did there, and explain why it was memorable.',
            ],
            [
                'title' => 'Your Favorite Book',
                'prompt' => 'Describe a book you have read that you found interesting. You should say: what the book is about, when you read it, why you chose to read it, and explain what you learned from it.',
            ],
            [
                'title' => 'An Important Decision',
                'prompt' => 'Describe an important decision you have made. You should say: what the decision was, when you made it, what factors influenced your decision, and explain why it was important.',
            ],
            [
                'title' => 'A Person Who Influenced You',
                'prompt' => 'Describe a person who has had a significant influence on your life. You should say: who this person is, how you know them, what they did to influence you, and explain why their influence was important.',
            ],
            [
                'title' => 'Your Ideal Job',
                'prompt' => 'Describe your ideal job. You should say: what the job is, what qualifications are needed, what the responsibilities would be, and explain why this would be your ideal job.',
            ],
            [
                'title' => 'A Festival or Celebration',
                'prompt' => 'Describe a festival or celebration in your country. You should say: what it is, when it takes place, how people celebrate it, and explain why it is important.',
            ],
            [
                'title' => 'A Skill You Would Like to Learn',
                'prompt' => 'Describe a skill you would like to learn. You should say: what the skill is, why you want to learn it, how you would learn it, and explain how it would benefit you.',
            ],
            [
                'title' => 'Your Favorite Restaurant',
                'prompt' => 'Describe your favorite restaurant. You should say: where it is located, what kind of food it serves, how often you go there, and explain why you like it.',
            ],
            [
                'title' => 'A Childhood Memory',
                'prompt' => 'Describe a happy memory from your childhood. You should say: what happened, when it happened, who was involved, and explain why this memory is special to you.',
            ],
            [
                'title' => 'Technology in Daily Life',
                'prompt' => 'Describe a piece of technology you use daily. You should say: what it is, how you use it, how long you have had it, and explain why it is important to you.',
            ],
            [
                'title' => 'An Environmental Problem',
                'prompt' => 'Describe an environmental problem in your area. You should say: what the problem is, what causes it, how it affects people, and explain what can be done to solve it.',
            ],
            [
                'title' => 'Your Study Habits',
                'prompt' => 'Describe your study habits. You should say: when and where you study, what methods you use, what challenges you face, and explain how effective your study habits are.',
            ],
            [
                'title' => 'A Historical Place',
                'prompt' => 'Describe a historical place you have visited. You should say: where it is, what its historical significance is, what you saw there, and explain what you learned from the visit.',
            ],
            [
                'title' => 'Your Future Plans',
                'prompt' => 'Describe your plans for the future. You should say: what you want to achieve, how you plan to achieve it, what challenges you might face, and explain why these goals are important to you.',
            ],
            [
                'title' => 'A Sport or Exercise',
                'prompt' => 'Describe a sport or exercise activity you enjoy. You should say: what it is, how often you do it, where you do it, and explain why you enjoy it.',
            ],
            [
                'title' => 'A Gift You Received',
                'prompt' => 'Describe a gift you received that was special to you. You should say: what it was, who gave it to you, when you received it, and explain why it was special.',
            ],
            [
                'title' => 'Your Daily Routine',
                'prompt' => 'Describe your typical daily routine. You should say: what you do in the morning, afternoon, and evening, what activities are most important, and explain how satisfied you are with your routine.',
            ],
            [
                'title' => 'A Problem You Solved',
                'prompt' => 'Describe a problem you successfully solved. You should say: what the problem was, how you solved it, who helped you, and explain what you learned from the experience.',
            ],
            [
                'title' => 'Your Learning Style',
                'prompt' => 'Describe how you prefer to learn new things. You should say: what methods work best for you, why you prefer these methods, what challenges you face, and explain how you overcome difficulties in learning.',
            ],
        ];
    }
}
