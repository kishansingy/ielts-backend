<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GeminiUsageTracking;

class CheckGeminiUsage extends Command
{
    protected $signature = 'gemini:check-usage {--period=today : Period to check (today, month, limit)}';

    protected $description = 'Check Gemini API usage statistics';

    public function handle()
    {
        $period = $this->option('period');
        
        $this->info('📊 Gemini API Usage Statistics');
        $this->info(str_repeat('=', 50));
        $this->newLine();

        switch ($period) {
            case 'today':
                $this->showDailyStats();
                break;
            case 'month':
                $this->showMonthlyStats();
                break;
            case 'limit':
                $this->showLimitStatus();
                break;
            default:
                $this->error("Invalid period. Use: today, month, or limit");
                return 1;
        }

        return 0;
    }

    private function showDailyStats()
    {
        $stats = GeminiUsageTracking::getDailyStats();
        
        if ($stats->isEmpty()) {
            $this->warn('No usage data for today');
            return;
        }

        $this->info('📅 Today\'s Usage:');
        $this->newLine();

        $totalRequests = 0;
        $totalTokens = 0;

        foreach ($stats as $stat) {
            $this->line("Module: {$stat->module_type}");
            $this->line("Model: {$stat->model_used}");
            $this->line("Requests: {$stat->total_requests}");
            $this->line("Tokens: " . number_format($stat->total_tokens));
            $this->newLine();

            $totalRequests += $stat->total_requests;
            $totalTokens += $stat->total_tokens;
        }

        $this->info('Total Requests: ' . $totalRequests);
        $this->info('Total Tokens: ' . number_format($totalTokens));
    }

    private function showMonthlyStats()
    {
        $stats = GeminiUsageTracking::getMonthlyStats();
        
        if ($stats->isEmpty()) {
            $this->warn('No usage data for this month');
            return;
        }

        $this->info('📅 This Month\'s Usage:');
        $this->newLine();

        $headers = ['Date', 'Requests', 'Tokens'];
        $rows = [];

        $totalRequests = 0;
        $totalTokens = 0;

        foreach ($stats as $stat) {
            $rows[] = [
                $stat->date,
                $stat->total_requests,
                number_format($stat->total_tokens)
            ];

            $totalRequests += $stat->total_requests;
            $totalTokens += $stat->total_tokens;
        }

        $this->table($headers, $rows);
        $this->newLine();
        $this->info('Total Requests: ' . $totalRequests);
        $this->info('Total Tokens: ' . number_format($totalTokens));
    }

    private function showLimitStatus()
    {
        $status = GeminiUsageTracking::isApproachingDailyLimit();
        
        $this->info('🚦 Daily Limit Status:');
        $this->newLine();

        $this->line("Requests Today: {$status['count']}");
        $this->line("Daily Limit: {$status['limit']}");
        $this->line("Remaining: {$status['remaining']}");
        $this->line("Usage: " . number_format($status['percentage'], 2) . "%");
        $this->newLine();

        if ($status['approaching_limit']) {
            $this->warn('⚠️  Approaching daily limit (>80%)');
        } else {
            $this->info('✅ Within safe limits');
        }

        // Show progress bar
        $this->output->progressStart($status['limit']);
        $this->output->progressAdvance($status['count']);
        $this->output->progressFinish();
    }
}
