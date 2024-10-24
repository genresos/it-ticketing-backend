<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class ProjectBudgetNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $project_budget_detail_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($project_budget_detail_id)
    {
        $this->project_budget_detail_id = $project_budget_detail_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */

    private function send_notif($recipients, $doc_no)
    {
        return fcm()
            ->to($recipients)
            ->priority('high')
            ->timeToLive(0)
            ->data([
                'title' => '[BUDGET] - Budget Approval',
                'body' => 'Budget detail No. : [' . $doc_no . '] need your approval!',
            ])
            ->send();
    }
    public function handle()
    {
        $project_budget_detail_id = $this->project_budget_detail_id;
        $budget_detail_info = DB::table('0_project_budget_details')->where('project_budget_detail_id', $project_budget_detail_id)->select('project_budget_id')->first();
        $budget_info = DB::table('0_project_budgets')->where('project_budget_id', $budget_detail_info->project_budget_id)->select('project_no')->first();
        $project_info = DB::table('0_projects')->where('project_no', $budget_info->project_no)->select('code')->first();

        if (str_contains($project_info->code, 'OFC')) {
            // kopro office --> bu ivonne 848
            $user = DB::table('users')->whereIn(
                'id',
                array(1, 848)
            )->select('firebase_token')->get();
            $recipients = [
                $user->firebase_token
            ];
            $this->send_notif($recipients, $project_budget_detail_id);
        } else {
            //kopro project --> pak moe

            $user = DB::table('users')->whereIn(
                'id',
                array(1, 50)
            )->select('firebase_token')->get();
            $recipients = array();

            foreach ($user as $data) {
                $recipients[] = $data->firebase_token;
            }
            $this->send_notif($recipients, $project_budget_detail_id);
        }
    }
}
