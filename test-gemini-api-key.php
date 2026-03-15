#!/usr/bin/env php
<?php

/**
 * Test Gemini API Key and List Available Models
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔑 Testing Gemini API Key\n";
echo str_repeat('=', 50) . "\n\n";

$apiKey = config('services.gemini.api_key');
$baseUrl = config('services.gemini.base_url');

if (empty($apiKey)) {
    echo "❌ No API key configured\n";
    exit(1);
}

echo "API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "Base URL: {$baseUrl}\n\n";

// Test 1: List available models
echo "1. Listing available models...\n";
try {
    $response = \Illuminate\Support\Facades\Http::get("{$baseUrl}/models?key={$apiKey}");
    
    if ($response->successful()) {
        $data = $response->json();
        
        if (isset($data['models'])) {
            echo "✅ API Key is valid!\n\n";
            echo "Available models:\n";
            foreach ($data['models'] as $model) {
                $name = $model['name'] ?? 'unknown';
                $displayName = $model['displayName'] ?? '';
                $supportedMethods = $model['supportedGenerationMethods'] ?? [];
                
                if (in_array('generateContent', $supportedMethods)) {
                    echo "  ✅ {$name}\n";
                    if ($displayName) {
                        echo "     Display: {$displayName}\n";
                    }
                }
            }
        } else {
            echo "⚠️  Unexpected response format\n";
            echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "❌ API request failed\n";
        echo "Status: " . $response->status() . "\n";
        echo "Response: " . $response->body() . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Test 2: Try a simple generation with gemini-pro
echo "\n2. Testing content generation with gemini-pro...\n";
try {
    $response = \Illuminate\Support\Facades\Http::timeout(30)->post(
        "{$baseUrl}/models/gemini-pro:generateContent?key={$apiKey}",
        [
            'contents' => [
                [
                    'parts' => [
                        ['text' => 'Say "Hello, IELTS!" in exactly 3 words.']
                    ]
                ]
            ]
        ]
    );
    
    if ($response->successful()) {
        $data = $response->json();
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $text = $data['candidates'][0]['content']['parts'][0]['text'];
            echo "✅ Generation successful!\n";
            echo "Response: {$text}\n";
        } else {
            echo "⚠️  Unexpected response structure\n";
            echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "❌ Generation failed\n";
        echo "Status: " . $response->status() . "\n";
        echo "Response: " . $response->body() . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "Test complete!\n";
