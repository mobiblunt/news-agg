<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
       $schedule->command('fetch:news')
                 ->hourly()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->onSuccess(function () {
                     \Log::info('News fetch completed successfully');
                 })
                 ->onFailure(function () {
                     \Log::error('News fetch failed');
                 });

        // Clean up old articles (older than 30 days) - runs daily at 2 AM
        $schedule->call(function () {
            \App\Models\Article::where('published_at', '<', now()->subDays(30))->delete();
            \Log::info('Old articles cleaned up');
        })->dailyAt('02:00');

        // Optional: Generate daily report
        $schedule->call(function () {
            $stats = [
                'total_articles' => \App\Models\Article::count(),
                'today_articles' => \App\Models\Article::whereDate('created_at', today())->count(),
                'sources' => \App\Models\Article::distinct()->pluck('source')->toArray(),
            ];
            \Log::info('Daily Stats', $stats);
        })->dailyAt('23:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
