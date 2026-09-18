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
        $schedule->command('debugbar:clear')->weekly();
        $schedule->command('queue:retry all')->daily();
        $schedule->command('queue:work --stop-when-empty')
            ->everyFiveMinutes()
            ->withoutOverlapping();

        // The platform writes transactions with the query builder, so there are no
        // model events to hook and the investor ledger has to pull. Every minute
        // keeps it close enough that a reconciliation failure means a real problem
        // rather than ordinary lag.
        $schedule->command('ledger:sync --quiet-success')
            ->everyMinute()
            ->withoutOverlapping();
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
