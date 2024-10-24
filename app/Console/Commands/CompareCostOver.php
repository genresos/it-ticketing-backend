<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ProjectCostController;

class CompareCostOver extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'compare:cost';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compare Cost to Order Over';

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
        $command     = ProjectCostController::curdate_project_transaction();
        $return      = NULL;
        $output      = NULL;
        exec($command, $output, $return);
    }
}
