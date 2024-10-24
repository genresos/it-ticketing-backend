<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ProjectBudgetController;

class ProjectBudgetUpdateCost extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updatecost:budget';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Job Update Project Budget used amount';

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
            ProjectBudgetController::update_cost_budget_jobs();
        $return      = NULL;
        $output      = NULL;
        exec($command, $output, $return);
    }
}
