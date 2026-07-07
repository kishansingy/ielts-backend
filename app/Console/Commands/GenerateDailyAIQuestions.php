<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GeminiAIService;
use App\Models\User;
use App\Models\MockTest;
use Illuminate\Support\Facades\Log;

class GenerateDailyAIQuestions extends Command
{
    protected $signature = 'ai:generate-daily-questions 
                            {--module=all : Module to generate (reading, writing, speaking, listening, or all)}
                            {--band=all : Band level (6, 7, 8, 9, or all)}
                            {--count=5 : Number of questions per module per band}';

    protected $description = 'Generate daily AI questions for IELTS mock tests using Gemini AI (Free tier friendly)';

    private $geminiService;

    public function __construct(GeminiAIService $geminiService)
    {
        parent::__construct();
        $this->geminiService = $geminiService;
    }

    public function handle()
    {
        // Check if daily generation is enabled
        $enabled = \App\Models\AiGenerationSetting::get('daily_generation_enabled', true);
        
        if (!$enabled) {
            $this->warn('⚠️  Daily generation is disabled in settings');
            return 0;
        }

        // Check daily limit
        $dailyLimit = \App\Models\AiGenerationSetting::get('daily_request_limit', 750);
        $limitStatus = \App\Models\GeminiUsageTracking::isApproachingDailyLimit($dailyLimit);
        
        if ($limitStatus['remaining'] <= 0) {
            $this->error('❌ Daily request limit reached');
            return 1;
        }

        $this->info('🚀 Starting Daily AI Question Generation...');
        $this->info('Using Gemini AI Free Tier');
        $this->info("Remaining requests today: {$limitStatus['remaining']}/{$dailyLimit}");
        $this->newLine();
        
        // Get settings
        $enabledModules = \App\Models\AiGenerationSetting::get('enabled_modules', ['reading', 'writing', 'speaking', 'listening']);
        $enabledBandLevels = \App\Models\AiGenerationSetting::get('enabled_band_levels', ['6', '7', '8', '9']);
        $generationPerModule = \App\Models\AiGenerationSetting::get('generation_per_module', [
            'reading' => 5,
            'writing' => 5,
            'speaking' => 5,
            'listening' => 5
        ]);
        $rateDelay = \App\Models\AiGenerationSetting::get('rate_limit_delay', 2);
        
        // Override with command options if provided
        $modules = $this->option('module') === 'all' 
            ? $enabledModules
            : [$this->option('module')];
            
        $bandLevels = $this->option('band') === 'all' 
            ? $enabledBandLevels
            : [$this->option('band')];
            
        $countPerSet = (int) $this->option('count');
        if ($countPerSet === 5) { // Default value, use settings
            $countPerSet = null;
        }
        
        $totalGenerated = 0;
        $errors = [];

        foreach ($modules as $module) {
            if (!in_array($module, $enabledModules)) {
                $this->warn("⚠️  Module '{$module}' is disabled in settings, skipping...");
                continue;
            }

            foreach ($bandLevels as $band) {
                if (!in_array($band, $enabledBandLevels)) {
                    $this->warn("⚠️  Band level '{$band}' is disabled in settings, skipping...");
                    continue;
                }

                // Check if we're approaching limit
                $currentStatus = \App\Models\GeminiUsageTracking::isApproachingDailyLimit($dailyLimit);
                if ($currentStatus['remaining'] <= 10) {
                    $this->warn("⚠️  Approaching daily limit, stopping generation");
                    break 2;
                }

                $count = $countPerSet ?? ($generationPerModule[$module] ?? 5);
                $this->info("\n📝 Generating {$module} questions for Band {$band}...");
                
                try {
                    $result = $this->geminiService->generateContent(
                        $module,
                        $band,
                        ['question_count' => $count]
                    );

                    $generated = $this->getGeneratedCount($result);
                    $totalGenerated += $generated;
                    
                    $this->info("✅ Generated {$generated} {$module} questions for Band {$band}");
                    
                    // Add delay to respect rate limits
                    sleep($rateDelay);
                    
                } catch (\Exception $e) {
                    $error = "❌ Failed to generate {$module} Band {$band}: " . $e->getMessage();
                    $this->error($error);
                    $errors[] = $error;
                    Log::error($error);
                }
            }
        }

        $this->info("\n" . str_repeat('=', 50));
        $this->info("📊 Generation Summary:");
        $this->info("Total questions generated: {$totalGenerated}");
        $this->info("Errors: " . count($errors));
        
        // Show final limit status
        $finalStatus = \App\Models\GeminiUsageTracking::isApproachingDailyLimit($dailyLimit);
        $this->info("Requests used today: {$finalStatus['count']}/{$dailyLimit}");
        $this->info("Remaining: {$finalStatus['remaining']}");
        
        if (count($errors) > 0) {
            $this->warn("\n⚠️  Errors encountered:");
            foreach ($errors as $error) {
                $this->warn($error);
            }
        }
        
        $this->info("\n✨ Daily generation complete!");
        
        return count($errors) === 0 ? 0 : 1;
    }

    private function getGeneratedCount($result)
    {
        if (isset($result['questions'])) {
            return $result['questions']->count();
        } elseif (isset($result['tasks'])) {
            return $result['tasks']->count();
        } elseif (isset($result['prompts'])) {
            return $result['prompts']->count();
        }
        return 0;
    }
}
