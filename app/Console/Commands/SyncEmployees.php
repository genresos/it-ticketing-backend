<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\EmployeesController;

class SyncEmployees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:employees';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto Sync data From Cron';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $command     =
            EmployeesController::sync_employees();
        $return      = NULL;
        $output      = NULL;
        exec($command, $output, $return);
    }
}
