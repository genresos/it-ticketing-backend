<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\FinanceController;

class ExportCA extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:ca';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup database daily';

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
        $command     = FinanceController::export_ca();
        $return      = NULL;
        $output      = NULL;
        exec($command, $output, $return);
    }
}
