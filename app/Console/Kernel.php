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
        //$schedule->command('app:payment-send')->everyMinute()->runInBackground()->withoutOverlapping()->onOneServer();

        $schedule->command('model:prune --path=app')->dailyAt('00:00')->runInBackground()->onOneServer();
        $schedule->command('eloquent_file:on-zero --days=10')->dailyAt('00:30')->runInBackground()->onOneServer();

        //$schedule->command('ping:logs')->twiceDaily();
        $schedule->command('horizon:snapshot')->everyFiveMinutes()->runInBackground()->onOneServer();

        $schedule->command('auth:clear-resets')->daily()->runInBackground()->onOneServer();
        //$schedule->command('cache:prune-stale-tags')->daily()->runInBackground()->onOneServer();
        $schedule->command('sanctum:prune-expired --hours=24')->daily()->runInBackground()->onOneServer();
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
