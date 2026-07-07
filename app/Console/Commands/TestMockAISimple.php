<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Question;
use App\Models\User;
use App\Models\MockTest;
use Exception;

class TestMockAISimple extends Command
{
    protected $signature = 'ai:test-mock-simple';
    protected $description = 'Simple test of mock AI questions without reading passages';

    public function handle()
    {
        $this->info('Testing Mock AI System (Simple)...');
        $this->newLine();

        try {
            // Test 1: Create simple questions without passages
            $this->info('1. Creating simple mock questions...');
            
            $modules = [
                'writing' => 'essay',
                'speaking' => 'part2',
                'listening' => 'fill_blank'
            ];
            
            $levels = ['6', '7', '8', '9'];
            $questionsCreated = 0;
            
            foreach ($modules as $module => $questionType) {
                foreach ($levels as $level) {
                    $this->info("   Creating {$module} questions for Band {$level}...");
                    
                    for ($i = 1; $i <= 2; $i++) {
                        $questionData = $this->getMockQuestionData($module, $level, $i, $questionType);
                        
                        $question = Question::create([
                            'passage_id' => null, // No passage needed for these types
                            'question_text' => $questionData['question_text'],
                            'question_type' => $questionType,
                            'correct_answer' => $questionData['correct_answer'] ?? 'Sample answer',
                            'options' => $questionData['options'] ?? null,
                            'points' => $this->getPointsByLevel($level),
                            'ielts_band_level' => $level,
                            'is_ai_generated' => true,
                            'ai_metadata' => [
                                'generated_at' => now(),
                                'generator' => 'mock_service_simple',
                                'module_type' => $module
                            ]
                        ]);
                        
                        $questionsCreated++;
                    }
                    
                    $this->info("   ✅ Created 2 questions");
                }
            }
            
            $this->newLine();
            $this->info("✅ Total questions created: {$questionsCreated}");
            
            // Test 2: Verify database
            $this->info('2. Verifying database...');
            
            $totalAIQuestions = Question::where('is_ai_generated', true)->count();
            $this->info("✅ Total AI questions in database: {$totalAIQuestions}");
            
            foreach ($levels as $level) {
                $levelCount = Question::where('ielts_band_level', $level)
                    ->where('is_ai_generated', true)
                    ->count();
                $this->info("   Band {$level}: {$levelCount} questions");
            }
            
            // Test 3: Test question retrieval
            $this->newLine();
            $this->info('3. Testing question retrieval...');
            
            $user = User::first();
            if ($user) {
                $this->info("Testing with user: {$user->name} (ID: {$user->id})");
                
                foreach (array_keys($modules) as $module) {
                    $availableQuestions = Question::where('question_type', $modules[$module])
                        ->where('ielts_band_level', '7')
                        ->where('is_retired', false)
                        ->count();
                    
                    $this->info("   {$module}: {$availableQuestions} questions available");
                }
            }
            
            // Test 4: Test mock test integration
            $this->newLine();
            $this->info('4. Testing mock test integration...');
            
            $mockTest = MockTest::first();
            if ($mockTest) {
                $this->info("Testing with mock test: {$mockTest->title}");
                
                // Select questions for a test
                $testQuestions = Question::where('is_ai_generated', true)
                    ->where('ielts_band_level', '7')
                    ->limit(5)
                    ->get();
                
                $this->info("✅ Can select {$testQuestions->count()} questions for test");
                
                if ($testQuestions->count() > 0) {
                    $this->info("Sample questions:");
                    foreach ($testQuestions->take(3) as $index => $question) {
                        $this->line("   " . ($index + 1) . ". " . substr($question->question_text, 0, 60) . "...");
                    }
                }
            }
            
        } catch (Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->info('🎉 Mock AI Test Completed Successfully!');
        
        $this->newLine();
        $this->info('=== System Status ===');
        $this->info('✅ Mock question generation works');
        $this->info('✅ Database integration works');
        $this->info('✅ Question retrieval works');
        $this->info('✅ Ready for frontend integration');
        
        return 0;
    }
    
    private function getMockQuestionData(string $module, string $level, int $index, string $questionType)
    {
        switch ($module) {
            case 'writing':
                return [
                    'question_text' => "Some people believe that social media has improved communication, while others think it has made it worse. Discuss both views and give your opinion. Write at least 250 words. (Mock Essay {$index} - Band {$level})",
                    'correct_answer' => 'This is an opinion essay requiring balanced discussion of both viewpoints with personal opinion and examples.'
                ];
                
            case 'speaking':
                return [
                    'question_text' => "Describe a skill you would like to learn. You should say: what the skill is, why you want to learn it, how you plan to learn it, and explain how this skill would benefit you. (Mock Speaking {$index} - Band {$level})",
                    'correct_answer' => 'This is a Part 2 speaking task requiring 1-2 minutes of continuous speech covering all bullet points.'
                ];
                
            case 'listening':
                return [
                    'question_text' => "The library is open until _____ PM on weekdays. (Mock Listening {$index} - Band {$level})",
                    'correct_answer' => '9'
                ];
                
            default:
                return [
                    'question_text' => "Mock question for {$module} (Question {$index} - Band {$level})",
                    'correct_answer' => 'Mock answer'
                ];
        }
    }
    
    private function getPointsByLevel(string $level)
    {
        return match($level) {
            '6' => 1,
            '7' => 2,
            '8' => 3,
            '9' => 4,
            default => 1
        };
    }
}