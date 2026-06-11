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
        // $schedule->command('inspire')->hourly();
        $schedule->command('database:autobackup')->daily();
        $schedule->command('invoice:generate')->daily('13:00');
        $schedule->command('shipments:detect-abnormal')->hourly();
        $schedule->command('wms:sla-check')->everyThirtyMinutes();
        $schedule->command('wms:min-stock-check')->dailyAt('07:00');
        $schedule->command('wms:expiry-alert')->dailyAt('08:00');
        $schedule->command('wms:auto-fulfillment')->everyFifteenMinutes();
        $schedule->command('aramex:sync-tracking')->everyFifteenMinutes()->withoutOverlapping();
        $schedule->command('jet:sync-tracking')->everyFifteenMinutes()->withoutOverlapping();
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
