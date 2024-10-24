<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\CompareCostOver;
use App\Console\Commands\GenerateExitClearences;
use App\Console\Commands\UploadDataCaAllToDiskstation;
use App\Console\Commands\CompareRabvsCost;
use App\Console\Commands\ValidateGrnTmp;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        CompareCostOver::class,
        GenerateExitClearences::class,
        CompareRabvsCost::class,
        UploadDataCaAllToDiskstation::class,
        ValidateGrnTmp::class

    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('compare:cost')->everyThirtyMinutes();
        $schedule->command('compare:rab')->everyThirtyMinutes();
        $schedule->command('generate:ec')->everyMinute();
        $schedule->command('project:duration')->everyMinute();
        $schedule->command('check:grn')->everyMinute();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
