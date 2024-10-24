<?php

namespace App\Api\V1\Controllers;

use JWTAuth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\CashAdvanceController;
use Illuminate\Http\Request;
use Auth;
use App\Modules\PaginationArr;
use App\Modules\InputList;
use App\Jobs\CashAdvanceNotification;
use App\Jobs\BankPaymentNotification;

class ApiNotificationController extends Controller
{

    public function cashadvance_notification(Request $request)
    {
        $trans_no = $request->id;
        $approval = $request->approval;
        $ca_notif = new CashAdvanceNotification($trans_no, $approval);
        return $this->dispatch($ca_notif);
    }

    public function bp_notification(Request $request)
    {
        $arr = [];
        $refs = DB::table('0_refs')->where('type', 1)->where('id', $request->id)->first();
        $bp_notif = new BankPaymentNotification($refs->reference, $request->token);
        $this->dispatch($bp_notif);
    }
}
