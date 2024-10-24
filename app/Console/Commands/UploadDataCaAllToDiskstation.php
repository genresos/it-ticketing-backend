<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\EmployeesController;
use App\Api\V1\Controllers\FinanceController;

class UploadDataCaAllToDiskstation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uploadca:all';

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
            FinanceController::upload_ca_all_to_disk();
        $return      = NULL;
        $output      = NULL;
        exec($command, $output, $return);
    }
}
