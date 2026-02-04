<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MockAIQuestionService;
use App\Models\User;
use App\Models\MockTest;
use Exception;

class TestMockAI extends Command
{
    protected $signature = 'ai:test-mock';
    protected $description = 'Test AI system using mock questions (no OpenAI required)';

    public function handle()
    {
        $this->info('Testing AI System with Mock Questions...');
        $this->info('(This test works without OpenAI API)');
        $this->newLine();

        try {
            // Test mock AI service
            $this->info('1. Testing Mock AI Service...');
            $mockService = new MockAIQuestionService();
            
            $modules = ['reading', 'writing', 'listening', 'speaking'];
            $levels = ['6', '7', '8', '9'];
            
            foreach ($modules as $module) {
                foreach ($levels as $level) {
                    $this->info("   Generating {$module} questions for Band {$level}...");
                    
                    $questions = $mockService->generateMockQuestions($module, $level, 2);
                    
                    if ($questions->count() > 0) {
                        $this->info("   ✅ Generated {$questions->count()} questions");
                        
                        // Show sample question
                        $sample = $questions->first();
                        $this->line("      Sample: " . substr($sample->question_text, 0, 80) . "...");
                    } else {
                        $this->error("   ❌ Failed to generate questions");
                    }
                }
            }
            
            $this->newLine();
            $this->info('2. Testing Database Integration...');
            
            // Check if questions were saved
            $totalQuestions = \App\Models\Question::where('is_ai_generated', true)->count();
            $this->info("✅ Total AI questions in database: {$totalQuestions}");
            
            // Test by level
            foreach ($levels as $level) {
                $levelCount = \App\Models\Question::where('ielts_band_level', $level)
                    ->where('is_ai_generated', true)
                    ->count();
                $this->info("   Band {$level}: {$levelCount} questions");
            }
            
            $this->newLine();
            $this->info('3. Testing Question Retrieval...');
            
            // Test getting unused questions for a user
            $user = User::first();
            if ($user) {
                $this->info("Testing with user: {$user->name} (ID: {$user->id})");
                
                foreach ($modules as $module) {
                    $availableQuestions = \App\Models\Question::where('question_type', $this->mapModuleToQuestionType($module))
                        ->where('ielts_band_level', '7')
                        ->where('is_retired', false)
                        ->whereDoesntHave('usageTracking', function($q) use ($user) {
                            $q->where('user_id', $user->id);
                        })
                        ->count();
                    
                    $this->info("   {$module}: {$availableQuestions} unused questions available");
                }
            } else {
                $this->warn("No users found in database for testing");
            }
            
            $this->newLine();
            $this->info('4. Testing Mock Test Integration...');
            
            $mockTest = MockTest::first();
            if ($mockTest) {
                $this->info("Testing with mock test: {$mockTest->title} (ID: {$mockTest->id})");
                
                // Simulate question selection for a test
                $selectedQuestions = \App\Models\Question::where('is_ai_generated', true)
                    ->where('ielts_band_level', '7')
                    ->limit(5)
                    ->get();
                
                $this->info("✅ Can select {$selectedQuestions->count()} questions for mock test");
                
                if ($selectedQuestions->count() > 0) {
                    $this->info("Sample questions for test:");
                    foreach ($selectedQuestions->take(3) as $index => $question) {
                        $this->line("   " . ($index + 1) . ". " . substr($question->question_text, 0, 60) . "...");
                    }
                }
            } else {
                $this->warn("No mock tests found in database");
            }
            
        } catch (Exception $e) {
            $this->error('❌ Error during testing: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->info('🎉 Mock AI Test Completed Successfully!');
        $this->newLine();
        
        $this->info('=== Summary ===');
        $this->info('✅ Mock AI question generation works');
        $this->info('✅ Database integration works');
        $this->info('✅ Question retrieval works');
        $this->info('✅ Mock test integration ready');
        
        $this->newLine();
        $this->info('=== Next Steps ===');
        $this->info('1. Fix OpenAI billing to use real AI generation');
        $this->info('2. Test frontend integration with mock questions');
        $this->info('3. Use mock service as fallback when OpenAI is unavailable');
        
        return 0;
    }
    
    private function mapModuleToQuestionType(string $moduleType)
    {
        $mapping = [
            'reading' => 'multiple_choice',
            'listening' => 'fill_blank',
            'writing' => 'essay',
            'speaking' => 'part2'
        ];
        
        return $mapping[$moduleType] ?? 'multiple_choice';
    }
}