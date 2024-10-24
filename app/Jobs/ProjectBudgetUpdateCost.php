<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ProjectBudgetController;

class ProjectBudgetUpdateCost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $project_budget_id;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($project_budget_id)
    {
        $this->project_budget_id = $project_budget_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */

    private function calculation_cost()
    {
        DB::beginTransaction();
        try {


            $po_balance = ProjectBudgetController::budget_use_po($this->project_budget_id);
            $ca_balance = ProjectBudgetController::budget_use_ca($this->project_budget_id);
            $gl_balance = ProjectBudgetController::budget_use_gl($this->project_budget_id);
            $salary_balance = ProjectBudgetController::budget_use_salary($this->project_budget_id);
            $gl_tmp_balance = ProjectBudgetController::budget_use_gl_tmp($this->project_budget_id);
            $budget_reverse = ProjectBudgetController::budget_reverse($this->project_budget_id);
            $used_amount = $po_balance + $ca_balance + $gl_balance + $gl_tmp_balance + $salary_balance - $budget_reverse;

            DB::table('0_project_budgets')->where('project_budget_id', $this->project_budget_id)
                ->update(array('used_amount' => $used_amount));

            // Commit Transaction
            DB::commit();
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }
    public function handle()
    {
        $this->calculation_cost();
    }
}
