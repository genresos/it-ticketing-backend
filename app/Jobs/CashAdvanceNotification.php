<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class CashAdvanceNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $trans_no;
    private $approval_position;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($trans_no, $approval_position)
    {
        $this->trans_no = $trans_no;
        $this->approval_position = $approval_position;
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
                'title' => '[CA] - Cash Advance',
                'body' => 'Document No : [' . $doc_no . '] need your approval!',
            ])
            ->send();
    }
    public function handle()
    {
        $trans_no = $this->trans_no;
        $approval_position = $this->approval_position;
        $cashadvance = DB::table('0_cashadvance')->where('trans_no', $trans_no)->first();
        $project_info = DB::table('0_projects')->select('division_id', 'person_id')->where('project_no', $cashadvance->project_no)->first();

        switch ($approval_position) {
            case 1: //Posisi PM
                $project_manager = DB::table('users')->where('person_id', $project_info->person_id)->first();
                $recipients = [
                    $project_manager->firebase_token
                ];
                $this->send_notif($recipients, $cashadvance->reference);
                break;
            case 4: //Posisi PC
                $project_control = DB::table('0_user_project_control AS pc')
                    ->leftJoin('0_users AS u', 'u.id', '=', 'pc.user_id')
                    ->select('pc.user_id')
                    ->where('pc.division_id', $project_info->division_id)
                    ->where('u.inactive', 0)->get();
                $user = array();
                foreach ($project_control as $data) {
                    $user[] = $data->user_id;
                }
                $user_token  = DB::table('users')->select('firebase_token')->whereIn('old_id', $user)->get();
                $recipients = array();
                foreach ($user_token as $key) {
                    $recipients[] = $key->firebase_token;
                }
                $this->send_notif($recipients, $cashadvance->reference);
                break;
            case 2: //Posisi DGM
                $dgm = DB::table('0_user_dept')->select('user_id')->where('division_id', $project_info->division_id)->get();
                $user = array();
                foreach ($dgm as $data) {
                    $user[] = $data->user_id;
                }
                $user_token  = DB::table('users')->select('firebase_token')->whereIn('old_id', $user)->get();
                $recipients = array();
                foreach ($user_token as $key) {
                    $recipients[] = $key->firebase_token;
                }
                $this->send_notif($recipients, $cashadvance->reference);
                break;
            case 3: //Posisi GM
                $gm = DB::table('0_user_divisions')->select('user_id')->where('division_id', $project_info->division_id)->get();
                $user = array();
                foreach ($gm as $data) {
                    $user[] = $data->user_id;
                }
                $user_token  = DB::table('users')->select('firebase_token')->whereIn('old_id', $user)->where('approval_level', 3)->get();
                $recipients = array();
                foreach ($user_token as $key) {
                    $recipients[] = $key->firebase_token;
                }
                $this->send_notif($recipients, $cashadvance->reference);
                break;
            case 41: //Posisi Dir.
                $user_token  = DB::table('users')->select('firebase_token')->where('approval_level', 41)->get();
                $recipients = array();
                foreach ($user_token as $key) {
                    $recipients[] = $key->firebase_token;
                }
                $this->send_notif($recipients, $cashadvance->reference);
                break;
            case 42: //Posisi Dir. Ops
                $user_token  = DB::table('users')->select('firebase_token')->where('approval_level', 42)->get();
                $recipients = array();
                foreach ($user_token as $key) {
                    $recipients[] = $key->firebase_token;
                }
                $this->send_notif($recipients, $cashadvance->reference);
                break;
            case 43: //Posisi Dir. Ops
                $user_token  = DB::table('users')->select('firebase_token')->where('approval_level', 42)->get();
                $recipients = array();
                foreach ($user_token as $key) {
                    $recipients[] = $key->firebase_token;
                }
                $this->send_notif($recipients, $cashadvance->reference);
                break;
            case 6: //Posisi Kasir
                return null;
                break;
            default:
                $user_token  = DB::table('users')->select('firebase_token')->where('id', 1)->get();
                $recipients = array();
                foreach ($user_token as $key) {
                    $recipients[] = $key->firebase_token;
                }
                $this->send_notif($recipients, $cashadvance->reference);
        }
    }
}
