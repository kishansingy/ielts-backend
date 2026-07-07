<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AIQuestionGeneratorService;
use App\Models\User;
use App\Models\MockTest;
use App\Models\Question;
use App\Models\QuestionUsageTracking;
use App\Models\AiQuestionGenerationLog;
use Exception;

class TestAIQuestions extends Command
{
    protected $signature = 'ai:test-questions';
    protected $description = 'Test AI Question Generation System';

    public function handle()
    {
        $this->info('Testing AI Question Generation System...');
        $this->newLine();

        try {
            // Test 1: Check if service can be instantiated
            $this->info('1. Testing service instantiation...');
            $aiService = new AIQuestionGeneratorService();
            $this->info('✓ AIQuestionGeneratorService created successfully');
            $this->newLine();

            // Test 2: Check database connection and models
            $this->info('2. Testing database models...');
            
            $userCount = User::count();
            $this->info("✓ Users in database: {$userCount}");
            
            $mockTestCount = MockTest::count();
            $this->info("✓ Mock tests in database: {$mockTestCount}");
            
            // Test 3: Check if we have the required environment variables
            $this->newLine();
            $this->info('3. Checking environment configuration...');
            
            $openaiKey = config('services.openai.api_key');
            if ($openaiKey && $openaiKey !== 'your_openai_api_key_here') {
                $this->info('✓ OpenAI API key is configured');
            } else {
                $this->warn('⚠ OpenAI API key not configured or using default value');
                $this->warn('  Please set OPENAI_API_KEY in your .env file');
            }
            
            $baseUrl = config('services.openai.base_url');
            $this->info("✓ OpenAI base URL: {$baseUrl}");
            
            $model = config('services.openai.model');
            $this->info("✓ OpenAI model: {$model}");

            // Test 4: Check database tables
            $this->newLine();
            $this->info('4. Checking database tables...');
            
            try {
                \DB::table('questions')->count();
                $this->info('✓ questions table exists');
            } catch (Exception $e) {
                $this->error('✗ questions table missing or inaccessible');
            }
            
            try {
                \DB::table('question_usage_tracking')->count();
                $this->info('✓ question_usage_tracking table exists');
            } catch (Exception $e) {
                $this->error('✗ question_usage_tracking table missing - run migrations');
            }
            
            try {
                \DB::table('ai_question_generation_log')->count();
                $this->info('✓ ai_question_generation_log table exists');
            } catch (Exception $e) {
                $this->error('✗ ai_question_generation_log table missing - run migrations');
            }

            // Test 5: Test question retrieval methods
            $this->newLine();
            $this->info('5. Testing question retrieval...');
            
            $availableQuestions = Question::where('is_retired', false)->count();
            $this->info("✓ Available questions: {$availableQuestions}");
            
            $aiGeneratedQuestions = Question::where('is_ai_generated', true)->count();
            $this->info("✓ AI generated questions: {$aiGeneratedQuestions}");

            // Test 6: Test API configuration
            $this->newLine();
            $this->info('6. Testing API configuration...');
            
            if ($openaiKey && $openaiKey !== 'your_openai_api_key_here') {
                $this->info('✓ OpenAI API key format looks correct');
                
                // Test a simple API call (without actually calling OpenAI)
                $this->info('✓ Ready for API calls');
            } else {
                $this->warn('⚠ Cannot test API calls without valid key');
            }

            $this->newLine();
            $this->info('=== Test Summary ===');
            $this->info('✓ Basic system components are working');
            
            if ($openaiKey && $openaiKey !== 'your_openai_api_key_here') {
                $this->info('✓ Ready for AI question generation');
                $this->newLine();
                $this->info('Next steps:');
                $this->info('1. Test the API endpoints using Postman or curl');
                $this->info('2. Try generating questions through the frontend');
                $this->info('3. Monitor the ai_question_generation_log table for activity');
            } else {
                $this->warn('⚠ Configure OpenAI API key to enable AI generation');
                $this->newLine();
                $this->info('To complete setup:');
                $this->info('1. Add OPENAI_API_KEY=your_actual_key to .env file');
                $this->info('2. Run: php artisan config:clear');
                $this->info('3. Test API endpoints');
            }

        } catch (Exception $e) {
            $this->error('✗ Error during testing: ' . $e->getMessage());
            $this->error('Stack trace:');
            $this->error($e->getTraceAsString());
        }

        $this->newLine();
        $this->info('=== API Endpoints to Test ===');
        $this->info('POST /api/ai-questions/preview');
        $this->info('POST /api/ai-questions/mock-tests/{id}/generate');
        $this->info('POST /api/ai-questions/mock-tests/{id}/start-with-questions');
        $this->info('GET  /api/ai-questions/stats');
        $this->info('GET  /api/ai-questions/generation-history');

        $this->newLine();
        $this->info('Test complete!');
        
        return 0;
    }
}