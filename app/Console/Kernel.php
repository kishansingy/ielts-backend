<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Send daily vocabulary notification at 9:00 AM every day
        $schedule->command('vocabulary:send-daily')
                 ->dailyAt('09:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/vocabulary-notifications.log'));

        // Generate daily AI questions at 2:00 AM every day (Free tier friendly)
        $schedule->command('ai:generate-daily-questions --module=all --band=all --count=5')
                 ->dailyAt('02:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/ai-question-generation.log'));

        // Generate 1 complete mock test for ONE band per day at 3:00 AM
        // Rotates through bands: Mon=band6, Tue=band7, Wed=band8, Thu=band9, repeats
        // Uses only 4 Gemini API calls per day — safe for free tier
        $bandByDay = ['band6', 'band7', 'band8', 'band9'];
        $todayBand = $bandByDay[now()->dayOfWeek % 4]; // 0=Sun,1=Mon...
        $schedule->command("mocktests:generate-daily --band={$todayBand}")
                 ->dailyAt('03:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/mock-test-generation.log'));

        // Cleanup old notification devices (inactive for 30+ days)
        $schedule->call(function () {
            \App\Models\NotificationDevice::where('is_active', false)
                ->where('updated_at', '<', now()->subDays(30))
                ->delete();
        })->weekly();

        // Generate weekly vocabulary statistics
        $schedule->call(function () {
            // You can add vocabulary statistics generation here
            \Log::info('Weekly vocabulary statistics generated');
        })->weeklyOn(1, '08:00'); // Every Monday at 8 AM
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
