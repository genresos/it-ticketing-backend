<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class BankPaymentNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $refs;
    private $fb_user;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($refs, $fb_user)
    {
        $this->refs = $refs;
        $this->fb_user = $fb_user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */

    private function send_notif($recipients)
    {
        return fcm()
            ->to($recipients)
            ->priority('high')
            ->timeToLive(0)
            ->data([
                'title' => '[BP] - Bank Payment',
                'body' => 'Document No : [' . $this->refs . '] need your approval!',
            ])
            ->send();
    }
    public function handle()
    {
        $this->send_notif($this->fb_user);
    }
}
