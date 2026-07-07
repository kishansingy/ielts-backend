<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\WritingTask;
use App\Models\Submission;
use App\Models\User;
use App\Models\Attempt;

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🚀 Creating Test Writing Submission...\n\n";

try {
    $user = User::where('email', 'student1@test.com')->first();
    if (!$user) {
        echo "❌ User student1@test.com not found\n";
        exit;
    }
    
    echo "✅ User found: {$user->email} (ID: {$user->id})\n";
    
    // Find the "Technology in Education" task
    $task = WritingTask::where('title', 'Technology in Education - Accuracy Test')->first();
    if (!$task) {
        echo "❌ Technology in Education task not found\n";
        exit;
    }
    
    echo "✅ Task found: {$task->title} (ID: {$task->id})\n";
    
    // Create a sample essay
    $sampleEssay = "Technology has revolutionized the education sector in unprecedented ways, fundamentally changing how students learn and teachers deliver instruction. While some argue that technology has made learning more accessible and effective, others contend that it has created dependency and reduced critical thinking skills among students.

On the positive side, technology has democratized access to education. Online learning platforms, educational apps, and digital resources have made quality education available to students regardless of their geographical location or economic background. For instance, students in remote areas can now access the same educational content as their urban counterparts through internet-based learning systems. Additionally, interactive learning tools such as virtual reality and gamification have made complex subjects more engaging and easier to understand.

However, critics argue that excessive reliance on technology has made students lazy and dependent on digital devices for basic tasks. Many students now struggle with handwriting, mental arithmetic, and face-to-face communication skills. Furthermore, the constant availability of information through search engines has potentially reduced students' ability to memorize important facts and think critically about problems without external assistance.

In my opinion, while technology has undoubtedly transformed education for the better, it should be used as a tool to enhance traditional learning methods rather than replace them entirely. The key lies in finding the right balance between technological innovation and fundamental educational practices. Educational institutions should focus on teaching students how to use technology effectively while maintaining essential skills like critical thinking, problem-solving, and interpersonal communication.

In conclusion, technology in education is neither entirely beneficial nor completely detrimental. Its impact depends largely on how it is implemented and integrated into the learning process. When used thoughtfully and in moderation, technology can significantly enhance educational outcomes while preserving the essential human elements of learning.";

    // Create the submission
    $submission = Submission::create([
        'user_id' => $user->id,
        'task_id' => $task->id,
        'submission_type' => 'writing',
        'content' => $sampleEssay,
        'submitted_at' => now(),
        'score' => 75, // Sample score
        'ai_feedback' => [
            'overall_score' => 75.5,
            'word_count' => str_word_count($sampleEssay),
            'character_count' => strlen($sampleEssay),
            'sentence_count' => substr_count($sampleEssay, '.'),
            'feedback' => [
                'strengths' => [
                    'Clear essay structure with introduction, body paragraphs, and conclusion',
                    'Good use of linking words and transitions',
                    'Relevant examples provided to support arguments',
                    'Balanced discussion of both viewpoints'
                ],
                'improvements' => [
                    'Some sentences could be more concise',
                    'Consider using more varied vocabulary',
                    'Work on paragraph transitions',
                    'Strengthen the conclusion with more impact'
                ],
                'suggestions' => [
                    'Practice using more sophisticated vocabulary',
                    'Focus on sentence variety and complexity',
                    'Use more specific examples from real-world contexts',
                    'Work on creating stronger topic sentences'
                ]
            ],
            'scores' => [
                'task_achievement' => 76,
                'coherence_cohesion' => 78,
                'lexical_resource' => 72,
                'grammatical_accuracy' => 75,
            ]
        ]
    ]);
    
    echo "✅ Submission created: ID {$submission->id}\n";
    echo "   Word count: " . str_word_count($sampleEssay) . "\n";
    echo "   Score: {$submission->score}%\n";
    
    // Create corresponding attempt record
    $attempt = Attempt::create([
        'user_id' => $user->id,
        'module_type' => 'writing',
        'content_id' => $task->id,
        'content_type' => WritingTask::class,
        'score' => 75,
        'max_score' => 100,
        'time_spent' => 2400, // 40 minutes
        'completed_at' => now(),
        'status' => 'completed'
    ]);
    
    echo "✅ Attempt record created: ID {$attempt->id}\n";
    
    echo "\n🎉 SUCCESS! Test writing submission created.\n";
    echo "📝 You can now see the review button on the writing practice page!\n";
    echo "🔗 Go to: http://localhost:3000/writing\n";
    echo "👁️ Look for the 'Review' button on 'Technology in Education - Accuracy Test'\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}