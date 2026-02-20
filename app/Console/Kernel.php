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
        $schedule->command('inventory:delta')
            ->everySixHours()
            ->withoutOverlapping()
            ->name('inventory-global-lock');

        $schedule->command('inventory:full')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->name('inventory-global-lock');
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
