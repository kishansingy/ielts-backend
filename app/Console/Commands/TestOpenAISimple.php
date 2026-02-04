<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Exception;

class TestOpenAISimple extends Command
{
    protected $signature = 'ai:test-simple';
    protected $description = 'Simple OpenAI API test without billing requirements';

    public function handle()
    {
        $this->info('Testing OpenAI API (Simple)...');
        $this->newLine();

        $apiKey = config('services.openai.api_key');
        $baseUrl = config('services.openai.base_url');

        if (!$apiKey || $apiKey === 'your_openai_api_key_here') {
            $this->error('❌ OpenAI API key not configured');
            $this->info('Please set OPENAI_API_KEY in your .env file');
            return 1;
        }

        $this->info('🔑 API Key: ' . substr($apiKey, 0, 10) . '...' . substr($apiKey, -4));
        $this->info('🌐 Base URL: ' . $baseUrl);
        $this->newLine();

        // Test 1: Check available models
        try {
            $this->info('📋 Fetching available models...');
            
            $modelsResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->timeout(10)->get($baseUrl . '/models');
            
            if ($modelsResponse->successful()) {
                $models = $modelsResponse->json();
                $gptModels = collect($models['data'])
                    ->pluck('id')
                    ->filter(function($model) {
                        return str_contains($model, 'gpt');
                    })
                    ->sort()
                    ->values()
                    ->toArray();
                
                $this->info('✅ Available GPT models:');
                foreach ($gptModels as $model) {
                    $this->line('  - ' . $model);
                }
                
                // Suggest best model to use
                $this->newLine();
                if (in_array('gpt-3.5-turbo', $gptModels)) {
                    $this->info('✅ Recommended: gpt-3.5-turbo (most compatible)');
                    $recommendedModel = 'gpt-3.5-turbo';
                } elseif (in_array('gpt-4o-mini', $gptModels)) {
                    $this->info('✅ Recommended: gpt-4o-mini (newer, efficient)');
                    $recommendedModel = 'gpt-4o-mini';
                } else {
                    $this->warn('⚠️ No standard GPT models found');
                    $recommendedModel = $gptModels[0] ?? 'gpt-3.5-turbo';
                }
                
            } else {
                $this->error('❌ Failed to fetch models');
                $this->error('Status: ' . $modelsResponse->status());
                $this->error('Response: ' . $modelsResponse->body());
                return 1;
            }
            
        } catch (Exception $e) {
            $this->error('❌ Error fetching models: ' . $e->getMessage());
            return 1;
        }

        // Test 2: Try a simple completion with the recommended model
        try {
            $this->newLine();
            $this->info('🧪 Testing simple completion with: ' . $recommendedModel);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($baseUrl . '/chat/completions', [
                'model' => $recommendedModel,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Say "Hello, IELTS system is working!" and nothing else.'
                    ]
                ],
                'max_tokens' => 20,
                'temperature' => 0
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? '';
                
                $this->info('✅ API call successful!');
                $this->info('📝 Response: ' . $content);
                
                $usage = $data['usage'] ?? [];
                $this->info('💰 Tokens used: ' . ($usage['total_tokens'] ?? 'N/A'));
                
            } else {
                $this->error('❌ API call failed');
                $this->error('Status: ' . $response->status());
                $this->error('Response: ' . $response->body());
                
                // Check if it's a billing issue
                $errorBody = $response->json();
                if (isset($errorBody['error']['code']) && $errorBody['error']['code'] === 'insufficient_quota') {
                    $this->newLine();
                    $this->warn('💳 This appears to be a billing/quota issue.');
                    $this->info('Solutions:');
                    $this->info('1. Add payment method to your OpenAI account');
                    $this->info('2. Check your usage limits');
                    $this->info('3. Verify your account has available credits');
                }
                
                return 1;
            }

        } catch (Exception $e) {
            $this->error('❌ Error testing completion: ' . $e->getMessage());
            return 1;
        }

        // Update .env recommendation
        $currentModel = config('services.openai.model');
        if ($currentModel !== $recommendedModel) {
            $this->newLine();
            $this->warn('💡 Recommendation: Update your .env file');
            $this->info('Current model: ' . $currentModel);
            $this->info('Recommended model: ' . $recommendedModel);
            $this->info('Change OPENAI_MODEL=' . $recommendedModel . ' in your .env file');
        }

        $this->newLine();
        $this->info('🎉 OpenAI API test completed successfully!');
        $this->info('Your system is ready for AI question generation.');
        
        return 0;
    }
}