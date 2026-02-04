<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\ReadingPassage;
use App\Models\ListeningExercise;
use App\Models\WritingTask;
use App\Models\SpeakingPrompt;
use App\Models\MockTest;
use App\Models\Question;
use App\Models\ListeningQuestion;

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🚀 Setting up Accuracy Test Data...\n\n";

try {
    DB::beginTransaction();

    // 1. Create test student user
    echo "1. Creating test student user...\n";
    $student = User::firstOrCreate(
        ['email' => 'student1@test.com'],
        [
            'name' => 'Test Student 1',
            'password' => bcrypt('password123'),
            'role' => 'student',
            'email_verified_at' => now()
        ]
    );
    echo "✅ Student created: {$student->email}\n\n";

    // 2. Create Reading Test Data
    echo "2. Creating Reading Test Data...\n";
    $readingPassage = ReadingPassage::create([
        'title' => 'Climate Change Impact - Accuracy Test',
        'content' => 'Climate change is one of the most pressing environmental issues of our time. Rising global temperatures have led to melting ice caps, rising sea levels, and extreme weather patterns. Scientists worldwide agree that human activities, particularly the burning of fossil fuels, are the primary cause of this phenomenon. The consequences affect not only the environment but also human societies, economies, and ecosystems globally.',
        'difficulty_level' => 'intermediate',
        'band_level' => 'band7',
        'time_limit' => 20,
        'is_active' => true,
        'created_by' => 1
    ]);

    // Reading Questions for Accuracy Testing
    $readingQuestions = [
        [
            'passage_id' => $readingPassage->id,
            'question_text' => 'What is the primary cause of climate change according to the passage?',
            'question_type' => 'multiple_choice',
            'correct_answer' => 'burning of fossil fuels',
            'options' => ['natural disasters', 'burning of fossil fuels', 'solar radiation', 'ocean currents'],
            'points' => 1
        ],
        [
            'passage_id' => $readingPassage->id,
            'question_text' => 'Complete the sentence: Rising global temperatures have led to melting ice caps, rising sea levels, and _______ weather patterns.',
            'question_type' => 'fill_blank',
            'correct_answer' => 'extreme',
            'options' => null,
            'points' => 1
        ],
        [
            'passage_id' => $readingPassage->id,
            'question_text' => 'Scientists worldwide disagree about the causes of climate change.',
            'question_type' => 'true_false',
            'correct_answer' => 'False',
            'options' => ['True', 'False', 'Not Given'],
            'points' => 1
        ],
        [
            'passage_id' => $readingPassage->id,
            'question_text' => 'What word in the passage means "worldwide"?',
            'question_type' => 'fill_blank',
            'correct_answer' => 'globally',
            'options' => null,
            'points' => 1
        ],
        [
            'passage_id' => $readingPassage->id,
            'question_text' => 'The passage mentions that climate change affects only the environment.',
            'question_type' => 'true_false',
            'correct_answer' => 'False',
            'options' => ['True', 'False', 'Not Given'],
            'points' => 1
        ]
    ];

    foreach ($readingQuestions as $questionData) {
        Question::create($questionData);
    }
    echo "✅ Reading passage with 5 test questions created\n\n";

    // 3. Create Listening Test Data
    echo "3. Creating Listening Test Data...\n";
    $listeningExercise = ListeningExercise::create([
        'title' => 'University Enrollment - Accuracy Test',
        'audio_file_path' => 'audio/test-enrollment.mp3',
        'transcript' => 'Good morning. I would like to enroll in the Computer Science program. My name is Sarah Johnson, and I am 22 years old. I completed my bachelor\'s degree in Mathematics last year. The enrollment fee is $500, and classes start on January 15th. My phone number is 555-0123.',
        'duration' => 120,
        'difficulty_level' => 'intermediate',
        'band_level' => 'band7',
        'is_active' => true,
        'created_by' => 1
    ]);

    // Listening Questions for Accuracy Testing
    $listeningQuestions = [
        [
            'listening_exercise_id' => $listeningExercise->id,
            'question_text' => 'What is the student\'s name?',
            'question_type' => 'fill_blank',
            'correct_answer' => 'Sarah Johnson',
            'options' => null,
            'points' => 1
        ],
        [
            'listening_exercise_id' => $listeningExercise->id,
            'question_text' => 'How old is the student?',
            'question_type' => 'fill_blank',
            'correct_answer' => '22',
            'options' => null,
            'points' => 1
        ],
        [
            'listening_exercise_id' => $listeningExercise->id,
            'question_text' => 'What is the enrollment fee?',
            'question_type' => 'fill_blank',
            'correct_answer' => '$500',
            'options' => null,
            'points' => 1
        ],
        [
            'listening_exercise_id' => $listeningExercise->id,
            'question_text' => 'When do classes start?',
            'question_type' => 'fill_blank',
            'correct_answer' => 'January 15th',
            'options' => null,
            'points' => 1
        ],
        [
            'listening_exercise_id' => $listeningExercise->id,
            'question_text' => 'What program does the student want to enroll in?',
            'question_type' => 'multiple_choice',
            'correct_answer' => 'Computer Science',
            'options' => ['Mathematics', 'Computer Science', 'Engineering', 'Business'],
            'points' => 1
        ]
    ];

    foreach ($listeningQuestions as $questionData) {
        ListeningQuestion::create($questionData);
    }
    echo "✅ Listening exercise with 5 test questions created\n\n";

    // 4. Create Writing Test Data
    echo "4. Creating Writing Test Data...\n";
    $writingTask = WritingTask::create([
        'title' => 'Technology in Education - Accuracy Test',
        'task_type' => 'task2',
        'prompt' => 'Some people believe that technology has made learning easier and more effective, while others think it has made students lazy and dependent. Discuss both views and give your own opinion.',
        'instructions' => 'Write at least 250 words. You should spend about 40 minutes on this task.',
        'word_limit' => 250,
        'time_limit' => 40,
        'band_level' => 'band7',
        'is_active' => true,
        'created_by' => 1
    ]);
    echo "✅ Writing task created\n\n";

    // 5. Create Speaking Test Data
    echo "5. Creating Speaking Test Data...\n";
    $speakingPrompt = SpeakingPrompt::create([
        'title' => 'Describe Your Hometown - Accuracy Test',
        'prompt_text' => 'Describe the town or city where you grew up. You should say: where it is located, what it is famous for, what you liked about living there, and explain whether you would recommend it as a place to visit.',
        'preparation_time' => 60,
        'response_time' => 120,
        'difficulty_level' => 'intermediate',
        'band_level' => 'band7',
        'is_active' => true,
        'created_by' => 1
    ]);
    echo "✅ Speaking prompt created\n\n";

    // 6. Create Mock Test
    echo "6. Creating Mock Test...\n";
    $mockTest = MockTest::create([
        'title' => 'Complete IELTS Accuracy Test',
        'description' => 'Full IELTS mock test for accuracy testing - includes all 4 modules',
        'duration_minutes' => 180, // 3 hours
        'is_active' => true
    ]);

    // Link components to mock test
    DB::table('mock_test_sections')->insert([
        [
            'mock_test_id' => $mockTest->id, 
            'module_type' => 'reading', 
            'content_id' => $readingPassage->id,
            'content_type' => 'App\\Models\\ReadingPassage',
            'order' => 1,
            'duration_minutes' => 60,
            'created_at' => now(),
            'updated_at' => now()
        ],
        [
            'mock_test_id' => $mockTest->id, 
            'module_type' => 'listening', 
            'content_id' => $listeningExercise->id,
            'content_type' => 'App\\Models\\ListeningExercise',
            'order' => 2,
            'duration_minutes' => 30,
            'created_at' => now(),
            'updated_at' => now()
        ],
        [
            'mock_test_id' => $mockTest->id, 
            'module_type' => 'writing', 
            'content_id' => $writingTask->id,
            'content_type' => 'App\\Models\\WritingTask',
            'order' => 3,
            'duration_minutes' => 60,
            'created_at' => now(),
            'updated_at' => now()
        ],
        [
            'mock_test_id' => $mockTest->id, 
            'module_type' => 'speaking', 
            'content_id' => $speakingPrompt->id,
            'content_type' => 'App\\Models\\SpeakingPrompt',
            'order' => 4,
            'duration_minutes' => 15,
            'created_at' => now(),
            'updated_at' => now()
        ]
    ]);
    echo "✅ Mock test with all 4 modules created\n\n";

    DB::commit();

    echo "🎉 SUCCESS! Accuracy test data setup complete!\n\n";
    echo "📋 TEST DATA SUMMARY:\n";
    echo "👤 Student Login: student1@test.com / password123\n";
    echo "📚 Reading Passage: '{$readingPassage->title}' (5 questions)\n";
    echo "🎧 Listening Exercise: '{$listeningExercise->title}' (5 questions)\n";
    echo "✍️ Writing Task: '{$writingTask->title}'\n";
    echo "🎤 Speaking Prompt: '{$speakingPrompt->title}'\n";
    echo "📝 Mock Test: '{$mockTest->title}' (ID: {$mockTest->id})\n\n";

    echo "🔗 TESTING URLS:\n";
    echo "Reading Practice: http://localhost:3000/reading-practice\n";
    echo "Listening Practice: http://localhost:3000/listening-practice\n";
    echo "Writing Practice: http://localhost:3000/writing-practice\n";
    echo "Speaking Practice: http://localhost:3000/speaking-practice\n";
    echo "Mock Test: http://localhost:3000/mock-tests/{$mockTest->id}\n";
    echo "AI Questions: http://localhost:3000/mock-tests/{$mockTest->id}/ai\n\n";

} catch (Exception $e) {
    DB::rollback();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}