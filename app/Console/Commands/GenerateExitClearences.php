<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\EmployeesController;

class GenerateExitClearences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:ec';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto Generate EC From Cron';

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
            EmployeesController::auto_generate_ec();
        $return      = NULL;
        $output      = NULL;
        exec($command, $output, $return);
    }
}
