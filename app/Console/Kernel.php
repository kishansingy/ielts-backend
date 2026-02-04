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
