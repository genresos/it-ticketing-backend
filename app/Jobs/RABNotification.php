<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class RABNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $rab_no;
    private $approval_level;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($rab_no, $approval_level)
    {
        $this->rab_no = $rab_no;
        $this->approval_level = $approval_level;
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
                'title' => '[RAB] - RAB Approval',
                'body' => 'RAB No. : [' . $doc_no . '] need your approval!',
            ])
            ->send();
    }
    public function handle()
    {
        $rab_info = DB::table('0_project_submission_rab')->where('trans_no', $this->rab_no)->first();
        $project_info = DB::table('0_projects')->where('project_no', $rab_info->project_no)->first();

        if ($this->approval_level == 2) {
            $get_user = DB::table('0_user_dept')->where('division_id', $project_info->division_id)->pluck('user_id')->toArray();

            $recipients = DB::table('users')
                ->where('approval_level', 2)
                ->whereIn(
                    'old_id',
                    $get_user
                )->pluck('firebase_token')->toArray();
        } else if ($this->approval_level == 3) {
            $get_user = DB::table('0_user_dept')->where('division_id', $project_info->division_id)->pluck('user_id')->toArray();

            $recipients = DB::table('users')
                ->where('approval_level', 3)
                ->whereIn(
                    'old_id',
                    $get_user
                )->pluck('firebase_token')->toArray();
        } else if ($this->approval_level == 4) {
            $get_user = DB::table('0_user_project_control')->where('division_id', $project_info->division_id)->pluck('user_id')->toArray();

            $recipients = DB::table('users')
                ->where('approval_level', 4)
                ->whereIn(
                    'old_id',
                    $get_user
                )->pluck('firebase_token')->toArray();
        } else if ($this->approval_level == 41) {
            $recipients = DB::table('users')
                ->where('approval_level', 41)->pluck('firebase_token')->toArray();
        } else if ($this->approval_level == 42) {
            $recipients = DB::table('users')
                ->where('approval_level', 42)->pluck('firebase_token')->toArray();
        } else {
            $recipients = DB::table('users')
                ->where('approval_level', 999)->pluck('firebase_token')->toArray();
        }


        $this->send_notif($recipients, $rab_info->reference);
    }
}
