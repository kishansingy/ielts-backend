<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MockTest;
use App\Models\MockTestSection;
use App\Models\ReadingPassage;
use App\Models\ListeningExercise;
use App\Models\WritingTask;
use App\Models\SpeakingPrompt;
use Illuminate\Support\Facades\DB;

class ProductionMockTestsSeeder extends Seeder
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
            foreach ($this->bandLevels as $bandLevel) {
                $this->createMockTestsForBand($bandLevel, $adminUser);
            }
        });
        
        $this->command->info('Production Mock Tests seeded successfully!');
    }

    /**
     * Create 20 mock tests for each band level
     */
    private function createMockTestsForBand($bandLevel, $adminUser)
    {
        $this->command->info("Creating mock tests for {$bandLevel}...");
        
        for ($i = 1; $i <= 20; $i++) {
            $mockTest = MockTest::create([
                'title' => "IELTS Mock Test {$i} - " . strtoupper($bandLevel),
                'description' => "Complete IELTS practice test {$i} for {$bandLevel} level. Includes Reading, Listening, Writing, and Speaking sections.",
                'band_level' => $bandLevel,
                'duration_minutes' => 180, // 3 hours total
                'is_active' => true,
                'available_from' => now(),
                'available_until' => null,
            ]);

            // Add Reading Section
            $this->addReadingSection($mockTest, $bandLevel, $i, $adminUser);
            
            // Add Listening Section
            $this->addListeningSection($mockTest, $bandLevel, $i, $adminUser);
            
            // Add Writing Section
            $this->addWritingSection($mockTest, $bandLevel, $i, $adminUser);
            
            // Add Speaking Section
            $this->addSpeakingSection($mockTest, $bandLevel, $i, $adminUser);
        }
    }

    /**
     * Add Reading section to mock test
     */
    private function addReadingSection($mockTest, $bandLevel, $testNumber, $adminUser)
    {
        // Get or create reading passages for this test
        $passages = ReadingPassage::where('band_level', $bandLevel)
            ->skip(($testNumber - 1) * 3)
            ->take(3)
            ->get();

        if ($passages->count() < 3) {
            // Create additional passages if needed
            for ($i = $passages->count(); $i < 3; $i++) {
                $passage = ReadingPassage::create([
                    'title' => "Reading Passage " . ($i + 1) . " - Test {$testNumber}",
                    'content' => $this->getReadingContent($bandLevel, $testNumber, $i + 1),
                    'difficulty_level' => $this->getDifficultyLevel($bandLevel),
                    'band_level' => $bandLevel,
                    'time_limit' => 20,
                    'created_by' => $adminUser->id,
                ]);
                $passages->push($passage);
            }
        }

        $order = 1;
        foreach ($passages as $passage) {
            MockTestSection::create([
                'mock_test_id' => $mockTest->id,
                'module_type' => 'reading',
                'content_id' => $passage->id,
                'content_type' => ReadingPassage::class,
                'order' => $order++,
                'duration_minutes' => 20,
            ]);
        }
    }

    /**
     * Add Listening section to mock test
     */
    private function addListeningSection($mockTest, $bandLevel, $testNumber, $adminUser)
    {
        // Get or create listening exercises
        $exercises = ListeningExercise::where('band_level', $bandLevel)
            ->skip(($testNumber - 1) * 4)
            ->take(4)
            ->get();

        if ($exercises->count() < 4) {
            for ($i = $exercises->count(); $i < 4; $i++) {
                $exercise = ListeningExercise::create([
                    'title' => "Listening Section " . ($i + 1) . " - Test {$testNumber}",
                    'audio_file_path' => "listening/mock_test_{$testNumber}_section_" . ($i + 1) . ".mp3",
                    'transcript' => $this->getListeningTranscript($bandLevel, $testNumber, $i + 1),
                    'duration' => 180,
                    'difficulty_level' => $this->getDifficultyLevel($bandLevel),
                    'band_level' => $bandLevel,
                    'created_by' => $adminUser->id,
                ]);
                $exercises->push($exercise);
            }
        }

        $order = 4; // After reading sections
        foreach ($exercises as $exercise) {
            MockTestSection::create([
                'mock_test_id' => $mockTest->id,
                'module_type' => 'listening',
                'content_id' => $exercise->id,
                'content_type' => ListeningExercise::class,
                'order' => $order++,
                'duration_minutes' => 10,
            ]);
        }
    }

    /**
     * Add Writing section to mock test
     */
    private function addWritingSection($mockTest, $bandLevel, $testNumber, $adminUser)
    {
        // Task 1
        $task1 = WritingTask::create([
            'title' => "Writing Task 1 - Mock Test {$testNumber}",
            'task_type' => 'task1',
            'prompt' => $this->getWritingTask1Prompt($testNumber),
            'instructions' => 'Summarize the information by selecting and reporting the main features, and make comparisons where relevant. Write at least 150 words.',
            'time_limit' => 20,
            'word_limit' => 150,
            'band_level' => $bandLevel,
            'created_by' => $adminUser->id,
        ]);

        MockTestSection::create([
            'mock_test_id' => $mockTest->id,
            'module_type' => 'writing',
            'content_id' => $task1->id,
            'content_type' => WritingTask::class,
            'order' => 8,
            'duration_minutes' => 20,
        ]);

        // Task 2
        $task2 = WritingTask::create([
            'title' => "Writing Task 2 - Mock Test {$testNumber}",
            'task_type' => 'task2',
            'prompt' => $this->getWritingTask2Prompt($testNumber),
            'instructions' => 'Give reasons for your answer and include any relevant examples from your own knowledge or experience. Write at least 250 words.',
            'time_limit' => 40,
            'word_limit' => 250,
            'band_level' => $bandLevel,
            'created_by' => $adminUser->id,
        ]);

        MockTestSection::create([
            'mock_test_id' => $mockTest->id,
            'module_type' => 'writing',
            'content_id' => $task2->id,
            'content_type' => WritingTask::class,
            'order' => 9,
            'duration_minutes' => 40,
        ]);
    }

    /**
     * Add Speaking section to mock test
     */
    private function addSpeakingSection($mockTest, $bandLevel, $testNumber, $adminUser)
    {
        // Create 3 speaking prompts (Part 1, 2, 3)
        $prompts = [
            [
                'title' => "Speaking Part 1 - Mock Test {$testNumber}",
                'prompt' => $this->getSpeakingPart1Prompt($testNumber),
                'preparation_time' => 0,
                'response_time' => 240, // 4 minutes
            ],
            [
                'title' => "Speaking Part 2 - Mock Test {$testNumber}",
                'prompt' => $this->getSpeakingPart2Prompt($testNumber),
                'preparation_time' => 60,
                'response_time' => 120, // 2 minutes
            ],
            [
                'title' => "Speaking Part 3 - Mock Test {$testNumber}",
                'prompt' => $this->getSpeakingPart3Prompt($testNumber),
                'preparation_time' => 0,
                'response_time' => 300, // 5 minutes
            ],
        ];

        $order = 10;
        foreach ($prompts as $promptData) {
            $prompt = SpeakingPrompt::create([
                'title' => $promptData['title'],
                'prompt_text' => $promptData['prompt'],
                'preparation_time' => $promptData['preparation_time'],
                'response_time' => $promptData['response_time'],
                'difficulty_level' => $this->getDifficultyLevel($bandLevel),
                'band_level' => $bandLevel,
                'created_by' => $adminUser->id,
            ]);

            MockTestSection::create([
                'mock_test_id' => $mockTest->id,
                'module_type' => 'speaking',
                'content_id' => $prompt->id,
                'content_type' => SpeakingPrompt::class,
                'order' => $order++,
                'duration_minutes' => ($promptData['preparation_time'] + $promptData['response_time']) / 60,
            ]);
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
     * Generate reading content for mock tests
     */
    private function getReadingContent($bandLevel, $testNumber, $passageNumber)
    {
        $topics = [
            "The passage discusses the evolution of artificial intelligence and its impact on modern society. Machine learning algorithms have transformed industries from healthcare to finance, enabling more accurate predictions and automated decision-making processes.",
            "This text explores the importance of biodiversity in maintaining ecological balance. Scientists emphasize that protecting diverse ecosystems is crucial for human survival, as they provide essential services like clean air, water purification, and climate regulation.",
            "The article examines the psychological effects of social media on young adults. Research indicates that excessive use correlates with increased anxiety and depression, while moderate, purposeful engagement can enhance social connections and well-being.",
        ];
        
        return $topics[($passageNumber - 1) % count($topics)] . " [Test {$testNumber}, Passage {$passageNumber} for {$bandLevel}]";
    }

    /**
     * Generate listening transcript for mock tests
     */
    private function getListeningTranscript($bandLevel, $testNumber, $sectionNumber)
    {
        $transcripts = [
            "Narrator: You will hear a conversation between a student and a university administrator about course registration. Student: Hello, I need help registering for next semester's courses. Administrator: Of course, let me pull up your file. What program are you in?",
            "Narrator: You will hear a lecture about climate change and its effects on global weather patterns. Professor: Today we'll examine how rising temperatures are affecting precipitation patterns worldwide.",
            "Narrator: You will hear a discussion between two colleagues about a new project proposal. Person A: Have you reviewed the budget estimates for the marketing campaign? Person B: Yes, I think we need to allocate more resources to digital advertising.",
            "Narrator: You will hear a radio interview with an environmental scientist discussing renewable energy. Interviewer: What are the most promising developments in solar technology? Scientist: Recent advances in photovoltaic efficiency have been remarkable.",
        ];
        
        return $transcripts[($sectionNumber - 1) % count($transcripts)] . " [Test {$testNumber}, Section {$sectionNumber} for {$bandLevel}]";
    }

    /**
     * Generate Writing Task 1 prompts
     */
    private function getWritingTask1Prompt($testNumber)
    {
        $prompts = [
            "The bar chart shows the percentage of households with internet access in five countries between 2010 and 2020.",
            "The line graph illustrates changes in average monthly rainfall in three cities over a 12-month period.",
            "The pie charts compare the sources of energy production in a country in 2000 and 2020.",
            "The table shows the number of international students enrolled in universities across four countries from 2015 to 2020.",
            "The process diagram illustrates how coffee is produced from bean to cup.",
            "The bar chart compares the amount of time people spend on different leisure activities in two age groups.",
            "The line graph shows trends in unemployment rates in three regions over a decade.",
            "The pie charts display the distribution of household expenses in 2010 and 2020.",
            "The table presents data on tourism revenue in five countries over five years.",
            "The diagram shows the stages involved in recycling plastic bottles.",
            "The bar chart illustrates the number of books read per year by different age groups.",
            "The line graph depicts changes in life expectancy in four countries from 1990 to 2020.",
            "The pie charts show the proportion of different transportation methods used by commuters in two years.",
            "The table displays statistics on smartphone ownership across various demographics.",
            "The process diagram explains how solar panels convert sunlight into electricity.",
            "The bar chart compares weekly working hours in different professions.",
            "The line graph shows fluctuations in stock market indices over a year.",
            "The pie charts illustrate changes in diet composition between two generations.",
            "The table presents data on air quality measurements in major cities.",
            "The diagram demonstrates the water treatment process in urban areas.",
        ];
        
        return $prompts[($testNumber - 1) % count($prompts)];
    }

    /**
     * Generate Writing Task 2 prompts
     */
    private function getWritingTask2Prompt($testNumber)
    {
        $prompts = [
            "Some people think that universities should provide graduates with the knowledge and skills needed in the workplace. Others think that the true function of a university should be to give access to knowledge for its own sake. Discuss both views and give your own opinion.",
            "In many countries, the proportion of older people is steadily increasing. Does this trend have more positive or negative effects on society?",
            "Some people believe that unpaid community service should be a compulsory part of high school programs. To what extent do you agree or disagree?",
            "Many people prefer to watch foreign films rather than locally produced films. Why could this be? Should governments give more financial support to local film industries?",
            "Some experts believe that it is better for children to begin learning a foreign language at primary school rather than secondary school. Do the advantages of this outweigh the disadvantages?",
            "In some countries, young people are encouraged to work or travel for a year between finishing high school and starting university studies. Discuss the advantages and disadvantages for young people who decide to do this.",
            "Successful sports professionals can earn a great deal more money than people in other important professions. Some people think this is fully justified while others think it is unfair. Discuss both views and give your own opinion.",
            "Some people think that parents should teach children how to be good members of society. Others believe that school is the place to learn this. Discuss both views and give your own opinion.",
            "Many museums charge for admission while others are free. Do you think the advantages of charging people for admission to museums outweigh the disadvantages?",
            "Some people think that strict punishments for driving offences are the key to reducing traffic accidents. Others believe that other measures would be more effective. Discuss both views and give your own opinion.",
            "In many parts of the world, children and teenagers are committing more crimes. Why is this happening? How should they be punished?",
            "Some people think that instead of preventing climate change, we need to find a way to live with it. To what extent do you agree or disagree?",
            "Many people believe that social networking sites have had a huge negative impact on both individuals and society. To what extent do you agree or disagree?",
            "In some countries, owning a home rather than renting one is very important for people. Why might this be the case? Do you think this is a positive or negative situation?",
            "Some people say that advertising encourages us to buy things we really do not need. Others say that advertisements tell us about new products that may improve our lives. Which viewpoint do you agree with?",
            "In many countries, people are now living longer than ever before. Some people say an ageing population creates problems for governments. Other people think there are benefits if society has more elderly people. To what extent do you agree or disagree?",
            "Some people think that all university students should study whatever they like. Others believe that they should only be allowed to study subjects that will be useful in the future, such as those related to science and technology. Discuss both views and give your own opinion.",
            "In many countries, traditional foods are being replaced by international fast foods. This is having a negative effect on both families and societies. To what extent do you agree or disagree?",
            "Some people believe that it is best to accept a bad situation, such as an unsatisfactory job or shortage of money. Others argue that it is better to try and improve such situations. Discuss both views and give your own opinion.",
            "Some people think that the government is wasting money on the arts and that this money could be better spent elsewhere. To what extent do you agree with this view?",
        ];
        
        return $prompts[($testNumber - 1) % count($prompts)];
    }

    /**
     * Generate Speaking Part 1 prompts
     */
    private function getSpeakingPart1Prompt($testNumber)
    {
        $prompts = [
            "Let's talk about your hometown. Where are you from? What do you like about your hometown? Has your hometown changed much since you were a child?",
            "Let's talk about your work or studies. What do you do? Why did you choose this field? What do you find most interesting about your work/studies?",
            "Let's talk about your hobbies. What do you like to do in your free time? How long have you been interested in this hobby? Do you think hobbies are important?",
            "Let's talk about food. What is your favorite type of food? Do you enjoy cooking? Have your food preferences changed over time?",
            "Let's talk about technology. How often do you use technology? What technological device is most important to you? How has technology changed your life?",
            "Let's talk about travel. Do you enjoy traveling? Where have you traveled recently? What type of places do you prefer to visit?",
            "Let's talk about music. What kind of music do you like? Do you play any musical instruments? How does music make you feel?",
            "Let's talk about sports. Do you play any sports? What sports are popular in your country? Do you prefer watching or playing sports?",
            "Let's talk about reading. Do you enjoy reading? What types of books do you prefer? How often do you read?",
            "Let's talk about your daily routine. What does a typical day look like for you? What part of your day do you enjoy most? Would you like to change anything about your routine?",
            "Let's talk about friends. How do you usually spend time with friends? What qualities do you value in a friend? Have your friendships changed over the years?",
            "Let's talk about weather. What's the weather like in your country? What's your favorite season? How does weather affect your mood?",
            "Let's talk about shopping. Do you enjoy shopping? What do you usually shop for? Do you prefer shopping online or in stores?",
            "Let's talk about transportation. How do you usually get around? What's the transportation system like in your city? Have you ever had any interesting experiences while traveling?",
            "Let's talk about learning. What's the best way for you to learn new things? What have you learned recently? Do you enjoy learning new skills?",
            "Let's talk about your home. Can you describe where you live? What do you like about your home? Would you like to move somewhere else?",
            "Let's talk about celebrations. What celebrations are important in your culture? How do you usually celebrate special occasions? What was your most memorable celebration?",
            "Let's talk about nature. Do you spend much time in nature? What natural places do you enjoy visiting? Why is nature important to you?",
            "Let's talk about communication. How do you prefer to communicate with others? Has the way you communicate changed over time? What communication skills are most important?",
            "Let's talk about goals. What are your current goals? How do you work towards achieving your goals? What motivates you to pursue your goals?",
        ];
        
        return $prompts[($testNumber - 1) % count($prompts)];
    }

    /**
     * Generate Speaking Part 2 prompts
     */
    private function getSpeakingPart2Prompt($testNumber)
    {
        $prompts = [
            "Describe a person who has influenced you. You should say: who this person is, how you know them, what influence they have had on you, and explain why this person is important to you.",
            "Describe a place you would like to visit. You should say: where it is, what you know about it, why you want to visit it, and explain what you would do there.",
            "Describe an important event in your life. You should say: what the event was, when it happened, who was involved, and explain why it was important to you.",
            "Describe a skill you would like to learn. You should say: what the skill is, why you want to learn it, how you would learn it, and explain how it would benefit you.",
            "Describe a book or film that had a strong impact on you. You should say: what it was about, when you read/watched it, why it impacted you, and explain what you learned from it.",
            "Describe a challenge you have overcome. You should say: what the challenge was, when you faced it, how you overcame it, and explain what you learned from the experience.",
            "Describe your ideal job. You should say: what the job is, what it involves, why it appeals to you, and explain what you would need to do to get this job.",
            "Describe a tradition in your family or culture. You should say: what the tradition is, how long it has existed, how it is practiced, and explain why it is important.",
            "Describe a time when you helped someone. You should say: who you helped, what you did, why you helped them, and explain how you felt about helping them.",
            "Describe an object that is special to you. You should say: what it is, how you got it, what you use it for, and explain why it is special to you.",
            "Describe a memorable journey you have taken. You should say: where you went, who you went with, what you did, and explain why it was memorable.",
            "Describe a teacher who influenced you. You should say: who the teacher was, what subject they taught, how they influenced you, and explain why they were important to you.",
            "Describe a time when you tried something new. You should say: what you tried, when you tried it, how it went, and explain how you felt about the experience.",
            "Describe a goal you have achieved. You should say: what the goal was, how you achieved it, what challenges you faced, and explain how you felt when you achieved it.",
            "Describe a piece of advice you received. You should say: what the advice was, who gave it to you, when you received it, and explain how it helped you.",
            "Describe a festival or celebration you enjoy. You should say: what it is, when it takes place, how you celebrate it, and explain why you enjoy it.",
            "Describe a decision you made that changed your life. You should say: what the decision was, when you made it, what influenced your decision, and explain how it changed your life.",
            "Describe a hobby you enjoy. You should say: what the hobby is, how you started it, how often you do it, and explain why you enjoy it.",
            "Describe a time when you felt proud. You should say: what happened, when it happened, why you felt proud, and explain what this experience meant to you.",
            "Describe a change you would like to see in your community. You should say: what the change is, why it is needed, how it could be implemented, and explain how it would benefit the community.",
        ];
        
        return $prompts[($testNumber - 1) % count($prompts)];
    }

    /**
     * Generate Speaking Part 3 prompts
     */
    private function getSpeakingPart3Prompt($testNumber)
    {
        $prompts = [
            "Let's discuss education. How has education changed in recent years? What role should technology play in education? Do you think everyone should have access to higher education?",
            "Let's discuss work and careers. How has the nature of work changed? What skills will be most important in the future? Should people prioritize job satisfaction or salary?",
            "Let's discuss environmental issues. What are the biggest environmental challenges we face? Who is responsible for protecting the environment? How can individuals contribute to environmental protection?",
            "Let's discuss technology and society. How has technology changed the way we communicate? What are the benefits and drawbacks of social media? Will technology make life better or worse in the future?",
            "Let's discuss family and relationships. How have family structures changed? What makes a relationship successful? How important is family in modern society?",
            "Let's discuss health and lifestyle. What factors contribute to a healthy lifestyle? Should governments regulate unhealthy foods? How can people be encouraged to exercise more?",
            "Let's discuss culture and tradition. Why is it important to preserve cultural traditions? How does globalization affect local cultures? Can traditional and modern values coexist?",
            "Let's discuss cities and urban planning. What makes a city livable? Should cities prioritize cars or public transportation? How can cities become more sustainable?",
            "Let's discuss media and information. How has the internet changed access to information? What is the role of traditional media today? How can people identify reliable information?",
            "Let's discuss travel and tourism. How has tourism changed in recent years? What are the positive and negative effects of tourism? Should there be limits on tourism in certain places?",
            "Let's discuss crime and punishment. What are the main causes of crime? Is prison an effective punishment? How can society reduce crime rates?",
            "Let's discuss art and creativity. Why is art important in society? Should art be funded by governments? How does art reflect cultural values?",
            "Let's discuss sports and competition. What role does sport play in society? Should professional athletes be role models? Is competition always beneficial?",
            "Let's discuss money and success. How do people define success? Is money the most important factor in success? Has the definition of success changed over time?",
            "Let's discuss language and communication. Why is it important to learn foreign languages? Will English remain the global language? How has technology affected language?",
            "Let's discuss aging and generations. How should society support elderly people? What can younger generations learn from older ones? Are generational differences increasing?",
            "Let's discuss food and agriculture. How has food production changed? Should people eat less meat? What is the future of farming?",
            "Let's discuss privacy and security. How important is privacy in modern society? Should governments have access to personal data? How can people protect their privacy online?",
            "Let's discuss leadership and management. What makes a good leader? Are leaders born or made? How has leadership changed in modern organizations?",
            "Let's discuss innovation and change. Why is innovation important? How can societies encourage innovation? What are the risks of rapid change?",
        ];
        
        return $prompts[($testNumber - 1) % count($prompts)];
    }
}
