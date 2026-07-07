<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ReadingPassage;
use App\Models\Question;
use App\Models\ListeningQuestion;
use App\Models\WritingTask;
use App\Models\ListeningExercise;
use App\Models\SpeakingPrompt;
use App\Models\Attempt;
use App\Models\UserAnswer;
use App\Models\Submission;
use Illuminate\Support\Facades\Hash;

class ComprehensiveSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::updateOrCreate(
            ['email' => 'admin@ielts.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now()
            ]
        );

        // Create sample students
        $students = [];
        for ($i = 1; $i <= 10; $i++) {
            $students[] = User::updateOrCreate(
                ['email' => "student{$i}@example.com"],
                [
                    'name' => "Student {$i}",
                    'password' => Hash::make('password'),
                    'role' => 'student',
                    'email_verified_at' => now()
                ]
            );
        }

        // Seed Reading Content
        $this->seedReadingContent($admin);
        
        // Seed Writing Content
        $this->seedWritingContent($admin);
        
        // Seed Listening Content
        $this->seedListeningContent($admin);
        
        // Seed Speaking Content
        $this->seedSpeakingContent($admin);
        
        // Seed Practice Attempts
        $this->seedPracticeAttempts($students);
    }

    private function seedReadingContent($admin)
    {
        // Reading Passage 1
        $passage1 = ReadingPassage::create([
            'title' => 'The Impact of Climate Change on Arctic Wildlife',
            'content' => 'The Arctic region is experiencing rapid environmental changes due to global warming. These changes are having profound effects on the wildlife that calls this region home. Polar bears, for instance, depend on sea ice for hunting seals, their primary food source. As temperatures rise and ice melts earlier each year, polar bears are forced to travel greater distances to find food, leading to malnutrition and decreased reproduction rates.

Arctic foxes face similar challenges. Their white winter coats, which provide camouflage in snowy environments, become a disadvantage as snow cover decreases. This makes them more visible to predators and reduces their hunting success. Additionally, red foxes are moving northward into Arctic fox territory, creating increased competition for resources.

Marine mammals such as walruses and seals are also affected. Walruses use sea ice as platforms for resting and accessing feeding areas. With less stable ice, walruses are forced to crowd onto beaches, leading to dangerous stampedes and increased mortality rates among young calves.

The changes in the Arctic ecosystem create a cascade effect throughout the food chain. Reduced ice cover affects the growth of algae under the ice, which forms the base of the Arctic food web. This impacts fish populations, which in turn affects seabirds and marine mammals that depend on them for food.

Scientists are working to understand these complex interactions and develop conservation strategies. Some proposed solutions include creating protected areas, reducing greenhouse gas emissions globally, and developing early warning systems to help wildlife adapt to changing conditions. However, the scale and speed of change in the Arctic present unprecedented challenges for conservation efforts.',
            'difficulty_level' => 'intermediate',
            'time_limit' => 20,
            'created_by' => $admin->id
        ]);

        // Questions for Reading Passage 1
        $questions1 = [
            [
                'question_text' => 'What is the primary food source for polar bears?',
                'question_type' => 'multiple_choice',
                'options' => json_encode(['Fish', 'Seals', 'Arctic foxes', 'Algae']),
                'correct_answer' => 'Seals',
                'points' => 1
            ],
            [
                'question_text' => 'Why are Arctic foxes at a disadvantage when snow cover decreases?',
                'question_type' => 'multiple_choice',
                'options' => json_encode(['They cannot find food', 'Their white coats make them visible', 'They get too cold', 'They cannot reproduce']),
                'correct_answer' => 'Their white coats make them visible',
                'points' => 1
            ],
            [
                'question_text' => 'What happens when walruses are forced onto beaches?',
                'question_type' => 'multiple_choice',
                'options' => json_encode(['They find more food', 'Dangerous stampedes occur', 'They adapt quickly', 'They migrate south']),
                'correct_answer' => 'Dangerous stampedes occur',
                'points' => 1
            ],
            [
                'question_text' => 'According to the passage, reduced ice cover affects which part of the food chain first?',
                'question_type' => 'multiple_choice',
                'options' => json_encode(['Fish populations', 'Algae growth', 'Seabird populations', 'Marine mammals']),
                'correct_answer' => 'Algae growth',
                'points' => 1
            ],
            [
                'question_text' => 'Which conservation strategy is NOT mentioned in the passage?',
                'question_type' => 'multiple_choice',
                'options' => json_encode(['Creating protected areas', 'Reducing greenhouse gas emissions', 'Relocating wildlife', 'Developing early warning systems']),
                'correct_answer' => 'Relocating wildlife',
                'points' => 1
            ]
        ];

        foreach ($questions1 as $questionData) {
            Question::create(array_merge($questionData, [
                'passage_id' => $passage1->id
            ]));
        }

        // Reading Passage 2
        $passage2 = ReadingPassage::create([
            'title' => 'The Evolution of Urban Transportation',
            'content' => 'Urban transportation has undergone significant transformations throughout history, adapting to the changing needs of growing cities and advancing technology. In the early 19th century, most urban travel was accomplished on foot or by horse-drawn vehicles. The introduction of omnibuses in the 1820s marked the beginning of public transportation, providing shared rides along fixed routes.

The late 1800s saw the development of electric streetcars, which offered faster and more reliable service than horse-drawn vehicles. These systems expanded rapidly, with many cities building extensive networks of tracks. However, the flexibility of streetcars was limited by their dependence on fixed rails.

The 20th century brought the automobile revolution, fundamentally changing urban mobility. Cars provided unprecedented personal freedom and convenience, allowing people to travel directly from origin to destination without transfers or waiting for schedules. This led to suburban expansion and the development of car-centric urban planning.

Recognizing the limitations of automobile-dependent cities, including traffic congestion, air pollution, and social inequality, many urban planners began advocating for sustainable transportation alternatives. The late 20th and early 21st centuries have seen renewed interest in public transit, cycling infrastructure, and pedestrian-friendly urban design.

Modern cities are now experimenting with innovative transportation solutions. Electric buses reduce emissions while maintaining the flexibility of traditional bus systems. Bike-sharing programs provide convenient short-distance travel options. Some cities have implemented congestion pricing to discourage car use in busy areas while funding public transportation improvements.

The future of urban transportation likely involves integrated systems that combine multiple modes of travel. Smart technology enables real-time coordination between different transportation options, allowing users to plan efficient multi-modal journeys. Autonomous vehicles may eventually provide the convenience of personal transportation while reducing the need for private car ownership.',
            'difficulty_level' => 'advanced',
            'time_limit' => 25,
            'created_by' => $admin->id
        ]);

        // Questions for Reading Passage 2
        $questions2 = [
            [
                'question_text' => 'What marked the beginning of public transportation?',
                'question_type' => 'multiple_choice',
                'options' => json_encode(['Electric streetcars', 'Omnibuses', 'Automobiles', 'Horse-drawn vehicles']),
                'correct_answer' => 'Omnibuses',
                'points' => 1
            ],
            [
                'question_text' => 'What was a limitation of electric streetcars?',
                'question_type' => 'multiple_choice',
                'options' => json_encode(['They were too expensive', 'They were unreliable', 'They depended on fixed rails', 'They were too slow']),
                'correct_answer' => 'They depended on fixed rails',
                'points' => 1
            ],
            [
                'question_text' => 'Which of the following is NOT mentioned as a limitation of automobile-dependent cities?',
                'question_type' => 'multiple_choice',
                'options' => json_encode(['Traffic congestion', 'Air pollution', 'Social inequality', 'Noise pollution']),
                'correct_answer' => 'Noise pollution',
                'points' => 1
            ],
            [
                'question_text' => 'What is the purpose of congestion pricing?',
                'question_type' => 'multiple_choice',
                'options' => json_encode(['To increase government revenue', 'To discourage car use and fund public transit', 'To reduce road maintenance costs', 'To promote electric vehicles']),
                'correct_answer' => 'To discourage car use and fund public transit',
                'points' => 1
            ],
            [
                'question_text' => 'According to the passage, the future of urban transportation involves:',
                'question_type' => 'multiple_choice',
                'options' => json_encode(['Only autonomous vehicles', 'Integrated multi-modal systems', 'Return to horse-drawn vehicles', 'Elimination of public transport']),
                'correct_answer' => 'Integrated multi-modal systems',
                'points' => 1
            ]
        ];

        foreach ($questions2 as $questionData) {
            Question::create(array_merge($questionData, [
                'passage_id' => $passage2->id
            ]));
        }
    }

    private function seedWritingContent($admin)
    {
        
        // Writing Task 1 (Academic)
        WritingTask::create([
            'title' => 'Population Growth Chart Analysis',
            'task_type' => 'task1',
            'prompt' => 'The chart below shows the population growth in three major cities from 1990 to 2020.',
            'instructions' => 'Summarize the information by selecting and reporting the main features, and make comparisons where relevant. Write at least 150 words.',
            'time_limit' => 20,
            'word_limit' => 150,
            'created_by' => $admin->id
        ]);

        // Writing Task 1 (General)
        WritingTask::create([
            'title' => 'Complaint Letter to Hotel Manager',
            'task_type' => 'task1',
            'prompt' => 'You recently stayed at a hotel and were dissatisfied with the service.',
            'instructions' => 'Write a letter to the hotel manager. In your letter: explain what went wrong, describe how you felt about the service, suggest what the manager should do to improve the service. Write at least 150 words.',
            'time_limit' => 20,
            'word_limit' => 150,
            'created_by' => $admin->id
        ]);

        // Writing Task 2
        WritingTask::create([
            'title' => 'Technology and Social Interaction',
            'task_type' => 'task2',
            'prompt' => 'Some people believe that technology has made people less social, while others argue that it has enhanced social connections.',
            'instructions' => 'Discuss both views and give your own opinion. Give reasons for your answer and include any relevant examples from your own knowledge or experience. Write at least 250 words.',
            'time_limit' => 40,
            'word_limit' => 250,
            'created_by' => $admin->id
        ]);

        // Writing Task 2
        WritingTask::create([
            'title' => 'Environmental Protection vs Economic Development',
            'task_type' => 'task2',
            'prompt' => 'Many countries face a dilemma between protecting the environment and promoting economic development.',
            'instructions' => 'To what extent do you agree that environmental protection should be prioritized over economic development? Give reasons for your answer and include any relevant examples from your own knowledge or experience. Write at least 250 words.',
            'time_limit' => 40,
            'word_limit' => 250,
            'created_by' => $admin->id
        ]);
    }

    private function seedListeningContent($admin)
    {
        
        // Listening Exercise 1
        $listening1 = ListeningExercise::create([
            'title' => 'University Orientation Session',
            'audio_file_path' => '/audio/listening/university-orientation.mp3',
            'transcript' => 'Student: Hi, I\'m here for the orientation session. I\'m starting my first year next week.

Coordinator: Welcome! I\'m Sarah, and I\'ll be showing you around today. Let me start by giving you a map of the campus. The main library is located in the center of campus, and it\'s open 24 hours during exam periods.

Student: That\'s great! What about the dining facilities?

Coordinator: We have three main dining halls. The largest one is in the student union building, which also houses the bookstore and several study areas. There\'s also a smaller café in the science building that\'s popular with students.

Student: And what about sports facilities?

Coordinator: The sports complex includes a gym, swimming pool, and tennis courts. All students get free access with their student ID. We also have various clubs and societies you can join.

Student: How do I sign up for clubs?

Coordinator: There\'s a clubs fair next Friday in the main quad. You can meet representatives from all the different organizations and sign up for anything that interests you.',
            'duration' => 180,
            'difficulty_level' => 'beginner',
            'created_by' => $admin->id
        ]);

        // Questions for Listening Exercise 1
        $listeningQuestions1 = [
            [
                'question_text' => 'How long is the main library open during exam periods?',
                'question_type' => 'multiple_choice',
                'options' => json_encode(['12 hours', '18 hours', '24 hours', '20 hours']),
                'correct_answer' => '24 hours',
                'points' => 1
            ],
            [
                'question_text' => 'Where is the largest dining hall located?',
                'question_type' => 'multiple_choice',
                'options' => json_encode(['Science building', 'Student union building', 'Main library', 'Sports complex']),
                'correct_answer' => 'Student union building',
                'points' => 1
            ],
            [
                'question_text' => 'What do students need to access the sports facilities?',
                'question_type' => 'multiple_choice',
                'options' => json_encode(['Membership fee', 'Student ID', 'Sports pass', 'Registration form']),
                'correct_answer' => 'Student ID',
                'points' => 1
            ],
            [
                'question_text' => 'When is the clubs fair?',
                'question_type' => 'multiple_choice',
                'options' => json_encode(['Next Monday', 'Next Friday', 'This Friday', 'Next week']),
                'correct_answer' => 'Next Friday',
                'points' => 1
            ]
        ];

        foreach ($listeningQuestions1 as $questionData) {
            ListeningQuestion::create(array_merge($questionData, [
                'listening_exercise_id' => $listening1->id
            ]));
        }

        // Listening Exercise 2
        $listening2 = ListeningExercise::create([
            'title' => 'Job Interview - Marketing Position',
            'audio_file_path' => '/audio/listening/job-interview-marketing.mp3',
            'transcript' => 'Interviewer: Thank you for coming in today. Could you tell me about your experience in digital marketing?

Candidate: Certainly. I\'ve been working in digital marketing for five years, primarily focusing on social media campaigns and content creation. In my previous role at TechStart, I managed campaigns that increased our social media engagement by 150% over two years.

Interviewer: That\'s impressive. What tools and platforms are you most familiar with?

Candidate: I\'m proficient in Google Analytics, Facebook Ads Manager, and Hootsuite for social media scheduling. I also have experience with email marketing platforms like Mailchimp and have worked with graphic design tools such as Canva and Adobe Creative Suite.

Interviewer: Our company is launching a new product next quarter. How would you approach creating a marketing strategy for it?

Candidate: I\'d start by researching the target audience and analyzing competitor strategies. Then I\'d develop a multi-channel approach combining social media, email marketing, and content marketing. I believe in using data-driven decisions, so I\'d set up proper tracking and regularly analyze performance metrics to optimize the campaigns.

Interviewer: What do you see as the biggest challenge in digital marketing today?

Candidate: I think the biggest challenge is the constantly changing algorithms on social media platforms. What works today might not work tomorrow, so marketers need to be adaptable and always stay updated with the latest trends and platform changes.',
            'duration' => 240,
            'difficulty_level' => 'intermediate',
            'created_by' => $admin->id
        ]);

        // Questions for Listening Exercise 2
        $listeningQuestions2 = [
            [
                'question_text' => 'How long has the candidate been working in digital marketing?',
                'question_type' => 'multiple_choice',
                'options' => json_encode(['3 years', '5 years', '7 years', '10 years']),
                'correct_answer' => '5 years',
                'points' => 1
            ],
            [
                'question_text' => 'By what percentage did the candidate increase social media engagement at TechStart?',
                'question_type' => 'multiple_choice',
                'options' => json_encode(['100%', '120%', '150%', '200%']),
                'correct_answer' => '150%',
                'points' => 1
            ],
            [
                'question_text' => 'Which email marketing platform does the candidate have experience with?',
                'question_type' => 'multiple_choice',
                'options' => json_encode(['Constant Contact', 'Mailchimp', 'SendGrid', 'Campaign Monitor']),
                'correct_answer' => 'Mailchimp',
                'points' => 1
            ],
            [
                'question_text' => 'What does the candidate believe is the biggest challenge in digital marketing?',
                'question_type' => 'multiple_choice',
                'options' => json_encode(['Budget constraints', 'Changing algorithms', 'Competition', 'Technology updates']),
                'correct_answer' => 'Changing algorithms',
                'points' => 1
            ]
        ];

        foreach ($listeningQuestions2 as $questionData) {
            ListeningQuestion::create(array_merge($questionData, [
                'listening_exercise_id' => $listening2->id
            ]));
        }
    }

    private function seedSpeakingContent($admin)
    {
        
        // Speaking Prompt 1
        SpeakingPrompt::create([
            'title' => 'Describe Your Hometown',
            'prompt_text' => 'Describe the town or city where you grew up. You should say: where it is located, what it looks like, what you like most about it, and explain whether you would like to live there in the future.',
            'preparation_time' => 60,
            'response_time' => 120,
            'difficulty_level' => 'beginner',
            'created_by' => $admin->id
        ]);

        // Speaking Prompt 2
        SpeakingPrompt::create([
            'title' => 'Technology and Education',
            'prompt_text' => 'Some people believe that technology has improved education, while others think it has made learning more difficult. What is your opinion? Give reasons and examples to support your answer.',
            'preparation_time' => 60,
            'response_time' => 180,
            'difficulty_level' => 'intermediate',
            'created_by' => $admin->id
        ]);

        // Speaking Prompt 3
        SpeakingPrompt::create([
            'title' => 'Environmental Conservation',
            'prompt_text' => 'Discuss the role of individuals versus governments in environmental conservation. Consider: What can individuals do to protect the environment? What should governments prioritize? How can both work together effectively?',
            'preparation_time' => 60,
            'response_time' => 240,
            'difficulty_level' => 'advanced',
            'created_by' => $admin->id
        ]);

        // Speaking Prompt 4
        SpeakingPrompt::create([
            'title' => 'A Memorable Journey',
            'prompt_text' => 'Describe a memorable journey you have taken. You should say: where you went, who you went with, what made it memorable, and explain what you learned from this experience.',
            'preparation_time' => 60,
            'response_time' => 120,
            'difficulty_level' => 'intermediate',
            'created_by' => $admin->id
        ]);
    }

    private function seedPracticeAttempts($students)
    {
        $readingPassages = ReadingPassage::all();
        $writingTasks = WritingTask::all();
        $listeningExercises = ListeningExercise::all();
        $speakingPrompts = SpeakingPrompt::all();

        foreach ($students as $student) {
            // Create reading attempts
            foreach ($readingPassages->take(2) as $passage) {
                $attempt = Attempt::create([
                    'user_id' => $student->id,
                    'module_type' => 'reading',
                    'content_id' => $passage->id,
                    'content_type' => ReadingPassage::class,
                    'score' => rand(60, 95),
                    'max_score' => 100,
                    'time_spent' => rand(900, 1800), // 15-30 minutes
                    'completed_at' => now()->subDays(rand(1, 30))
                ]);

                // Create user answers
                foreach ($passage->questions as $question) {
                    $isCorrect = rand(0, 1) > 0.2; // 80% correct rate
                    UserAnswer::create([
                        'attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                        'question_type' => 'reading',
                        'user_answer' => $isCorrect ? $question->correct_answer : 'Wrong Answer',
                        'is_correct' => $isCorrect,
                        'points_earned' => $isCorrect ? $question->points : 0
                    ]);
                }
            }

            // Create writing submissions
            foreach ($writingTasks->take(2) as $task) {
                Submission::create([
                    'user_id' => $student->id,
                    'task_id' => $task->id,
                    'submission_type' => 'writing',
                    'content' => $this->generateSampleWritingResponse($task),
                    'score' => rand(65, 90),
                    'ai_feedback' => json_encode([
                        'overall_score' => rand(65, 90),
                        'grammar_score' => rand(70, 95),
                        'vocabulary_score' => rand(60, 85),
                        'structure_score' => rand(65, 90),
                        'suggestions' => ['Improve paragraph transitions', 'Use more varied vocabulary']
                    ]),
                    'submitted_at' => now()->subDays(rand(1, 30))
                ]);
            }

            // Create listening attempts
            foreach ($listeningExercises->take(2) as $exercise) {
                $attempt = Attempt::create([
                    'user_id' => $student->id,
                    'module_type' => 'listening',
                    'content_id' => $exercise->id,
                    'content_type' => ListeningExercise::class,
                    'score' => rand(55, 90),
                    'max_score' => 100,
                    'time_spent' => rand(600, 1200), // 10-20 minutes
                    'completed_at' => now()->subDays(rand(1, 30))
                ]);

                // Create user answers for listening questions
                $listeningQuestions = ListeningQuestion::where('listening_exercise_id', $exercise->id)->get();
                foreach ($listeningQuestions as $question) {
                    $isCorrect = rand(0, 1) > 0.3; // 70% correct rate
                    UserAnswer::create([
                        'attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                        'question_type' => 'listening',
                        'user_answer' => $isCorrect ? $question->correct_answer : 'Wrong Answer',
                        'is_correct' => $isCorrect,
                        'points_earned' => $isCorrect ? $question->points : 0
                    ]);
                }
            }

            // Create speaking submissions
            foreach ($speakingPrompts->take(2) as $prompt) {
                Submission::create([
                    'user_id' => $student->id,
                    'task_id' => $prompt->id,
                    'submission_type' => 'speaking',
                    'file_path' => '/audio/submissions/sample-speaking-' . $student->id . '-' . $prompt->id . '.mp3',
                    'score' => rand(60, 85),
                    'ai_feedback' => json_encode([
                        'overall_score' => rand(60, 85),
                        'fluency_score' => rand(65, 90),
                        'pronunciation_score' => rand(55, 80),
                        'vocabulary_score' => rand(60, 85),
                        'suggestions' => ['Work on pronunciation clarity', 'Improve fluency with practice']
                    ]),
                    'submitted_at' => now()->subDays(rand(1, 30))
                ]);
            }
        }
    }

    private function generateSampleWritingResponse($task)
    {
        if ($task->task_type === 'task1') {
            return "The chart illustrates the population growth in three major cities from 1990 to 2020. Overall, all three cities experienced significant population increases over the 30-year period, with City A showing the most dramatic growth.

In 1990, City A had a population of approximately 2 million people, which steadily increased to reach 5.5 million by 2020. This represents the largest absolute increase among the three cities. City B started with a similar population of 2.2 million in 1990 but grew more moderately to 3.8 million by 2020.

City C showed the most consistent growth pattern, beginning with 1.5 million residents in 1990 and reaching 3.2 million by 2020. The growth rate was particularly steep between 2000 and 2010 for all three cities, likely reflecting economic expansion during this period.

In conclusion, while all three cities experienced substantial population growth, City A demonstrated the most significant increase, more than doubling its population over the three-decade period.";
        } else {
            return "Technology has fundamentally transformed the way we interact with each other, and opinions are divided on whether this change has been beneficial or detrimental to social connections. While some argue that technology has made people less social, I believe that it has actually enhanced our ability to connect with others, though it has changed the nature of these connections.

Those who believe technology has reduced social interaction point to the decline in face-to-face conversations and the rise of superficial online relationships. They argue that people spend more time looking at screens than engaging with those around them, leading to weakened personal relationships and reduced empathy.

However, I would argue that technology has expanded our social horizons in unprecedented ways. Social media platforms, messaging apps, and video calling services have enabled us to maintain relationships across vast distances and connect with like-minded individuals worldwide. During the COVID-19 pandemic, technology proved essential in maintaining social connections when physical meetings were impossible.

Furthermore, technology has made communication more accessible for people with disabilities and social anxiety, providing alternative ways to express themselves and build relationships. Online communities have created support networks for individuals with shared interests or challenges that might not exist in their immediate physical environment.

In conclusion, while technology has certainly changed how we socialize, I believe it has ultimately enhanced rather than diminished our social connections by providing new avenues for communication and relationship building.";
        }
    }
}