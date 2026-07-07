<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Exception;

class TestOpenAIConnection extends Command
{
    protected $signature = 'ai:test-openai';
    protected $description = 'Test OpenAI API connection and generate a sample question';

    public function handle()
    {
        $this->info('Testing OpenAI API Connection...');
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

        try {
            // First, let's check available models
            $this->info('📋 Checking available models...');
            
            $modelsResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->timeout(10)->get($baseUrl . '/models');
            
            if ($modelsResponse->successful()) {
                $models = $modelsResponse->json();
                $availableModels = collect($models['data'])->pluck('id')->filter(function($model) {
                    return str_contains($model, 'gpt');
                })->take(5)->toArray();
                
                $this->info('✅ Available GPT models: ' . implode(', ', $availableModels));
            } else {
                $this->warn('⚠️ Could not fetch available models, proceeding with configured model');
            }
            
            $this->newLine();
            $this->info('📡 Testing API connection...');
            
            $configuredModel = config('services.openai.model', 'gpt-3.5-turbo');
            $this->info('🤖 Using model: ' . $configuredModel);
            
            // Simple test prompt
            $prompt = "Generate 1 simple IELTS Reading question for band 6 level. 
            
            Format as JSON with these fields:
            - question_text: The question
            - question_type: 'multiple_choice'
            - correct_answer: The correct answer
            - options: Array of 4 options
            - passage_text: A short reading passage (1 paragraph)
            
            Return only the JSON, no other text.";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($baseUrl . '/chat/completions', [
                'model' => $configuredModel,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert IELTS question generator. Generate high-quality, authentic IELTS questions.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 1000
            ]);

            if ($response->successful()) {
                $this->info('✅ API connection successful!');
                
                $data = $response->json();
                $generatedContent = $data['choices'][0]['message']['content'] ?? '';
                
                $this->newLine();
                $this->info('📝 Generated Content:');
                $this->line($generatedContent);
                
                // Try to parse as JSON
                $this->newLine();
                $this->info('🔍 Parsing JSON...');
                
                // Extract JSON from response
                $jsonStart = strpos($generatedContent, '{');
                $jsonEnd = strrpos($generatedContent, '}') + 1;
                
                if ($jsonStart !== false && $jsonEnd !== false) {
                    $jsonContent = substr($generatedContent, $jsonStart, $jsonEnd - $jsonStart);
                    $questionData = json_decode($jsonContent, true);
                    
                    if ($questionData) {
                        $this->info('✅ JSON parsing successful!');
                        $this->newLine();
                        $this->info('📋 Parsed Question Data:');
                        $this->line('Question: ' . ($questionData['question_text'] ?? 'N/A'));
                        $this->line('Type: ' . ($questionData['question_type'] ?? 'N/A'));
                        $this->line('Answer: ' . ($questionData['correct_answer'] ?? 'N/A'));
                        
                        if (isset($questionData['options']) && is_array($questionData['options'])) {
                            $this->line('Options: ' . implode(', ', $questionData['options']));
                        }
                    } else {
                        $this->warn('⚠️ JSON parsing failed, but API call was successful');
                    }
                } else {
                    $this->warn('⚠️ No JSON found in response, but API call was successful');
                }
                
                $this->newLine();
                $this->info('💰 Usage Info:');
                $usage = $data['usage'] ?? [];
                $this->line('Prompt tokens: ' . ($usage['prompt_tokens'] ?? 'N/A'));
                $this->line('Completion tokens: ' . ($usage['completion_tokens'] ?? 'N/A'));
                $this->line('Total tokens: ' . ($usage['total_tokens'] ?? 'N/A'));
                
            } else {
                $this->error('❌ API call failed');
                $this->error('Status: ' . $response->status());
                $this->error('Response: ' . $response->body());
                return 1;
            }

        } catch (Exception $e) {
            $this->error('❌ Error testing OpenAI connection: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->info('🎉 OpenAI integration test completed successfully!');
        $this->info('Your system is ready to generate AI questions.');
        
        return 0;
    }
}