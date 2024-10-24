<?php

namespace App\Api\V1\Controllers;

use JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\DB;
use App\Query\QueryProjectCost;
use Carbon\Carbon;

class ApiAccountingController extends Controller
{
    public function __construct()
    {
        $currentUser = JWTAuth::parseToken()->authenticate();

        $this->user_id = Auth::guard()->user()->id;
        $this->user_name = Auth::guard()->user()->name;
        $this->user_old_id = Auth::guard()->user()->old_id;
        $this->user_level = Auth::guard()->user()->approval_level;
        $this->user_division = Auth::guard()->user()->division_id;
        $this->user_person_id = Auth::guard()->user()->person_id;
        $this->user_emp_id = Auth::guard()->user()->emp_id;
    }
    public function get_ap_journal(Request $request)
    {
        $response = [];
        $response['invoice'] = [];
        $response['journal'] = [];
        $reference = $request->reference;
        $inv_info = DB::table('0_supp_trans')->where('type', 20)->where('ov_amount', '>', 0)->where('reference', $reference)->first();
        $trans_no = $inv_info->trans_no;
        $invoice = DB::table('0_supp_trans as st')
            ->Join('0_supp_invoice_items as si', 'si.supp_trans_no', '=', 'st.trans_no')
            ->leftJoin('0_purch_order_details as pod', 'pod.po_detail_item', '=', 'si.po_detail_item_id')
            ->leftJoin('0_purch_orders as po', 'pod.order_no', '=', 'po.order_no')
            ->where('st.type', 20)->where('st.trans_no', $trans_no)
            ->select(
                'st.trans_no',
                'st.trans_no',
                'st.ov_amount',
                'st.ov_gst',
                'st.rate',
                'st.pph_rate',
                'st.pph_amount',
                'po.curr_code'
            )
            ->groupBy('st.trans_no')
            ->get();
        $gl = DB::table('0_gl_trans')->where('type', 20)->where('type_no', $trans_no)->get();

        foreach ($invoice as $inv) {
            $item = [];
            $item['trans_no'] = $inv->trans_no;
            $item['reference'] = $reference;
            $item['currency'] = $inv->curr_code;
            $item['dpp'] = $inv->ov_amount;
            $item['rate'] = $inv->rate;
            $item['ppn'] = $inv->ov_gst;
            $item['pph_rate'] = $inv->pph_rate;
            $item['pph_amount'] = $inv->pph_amount;
            $item['total_in_idr'] = ($inv->ov_amount * $inv->rate) + $inv->ov_gst;

            array_push($response['invoice'], $item);
        }
        foreach ($gl as $ledger) {
            $journal = [];
            $journal['counter'] = $ledger->counter;
            $journal['type'] = $ledger->type;
            $journal['type_no'] = $ledger->type_no;
            $journal['tran_date'] = $ledger->tran_date;
            $journal['account'] = $ledger->account;
            $journal['memo_'] = $ledger->memo_;
            $journal['amount'] = $ledger->amount;
            $journal['counter'] = $ledger->counter;
            $journal['counter'] = $ledger->counter;
            $journal['counter'] = $ledger->counter;
            array_push($response['journal'], $journal);
        }
        return $response;
    }
    public function update_pph_ap(Request $request)
    {
        $invoice = $request->data['invoice'];
        $journal = $request->data['journal'];
        $totalAmount = 0;
        $reference = $request->reference;
        $inv_info = DB::table('0_supp_trans')->where('type', 20)->where('ov_amount', '>', 0)->where('reference', $reference)->first();
        $trans_no = $inv_info->trans_no;

        $gl = DB::table('0_gl_trans')->where('type', 20)->where('type_no', $trans_no)->where('amount', '>', 0)->first();

        foreach ($journal as $entry) {
            $amount = $entry['amount'];
            if ($amount > 0) {
                $totalAmount += $amount;
            } elseif ($amount < 0) {
                $totalAmount -= abs($amount);
            }
        }
        // if ($totalAmount != 0) {
        //     return response()->json([
        //         'error' => array(
        //             'message' => 'Journal not balance!',
        //             'status_code' => 403
        //         )
        //     ], 403);
        // }
        DB::beginTransaction();
        try {

            foreach ($invoice as $inv) {
                DB::table('0_supp_trans')->updateOrInsert(
                    ['type' => 20, 'trans_no' => $trans_no],
                    [
                        'pph_rate' => $inv['pph_rate'],
                        'pph_amount' => $inv['pph_amount']
                    ]
                );
            }

            foreach ($journal as $ledger) {
                DB::table('0_gl_trans')->updateOrInsert(
                    ['counter' => $ledger['counter']],
                    [
                        'type' => $ledger['type'],
                        'type_no' => $trans_no,
                        'tran_date' => $gl->tran_date,
                        'account' => $ledger['account'],
                        'person_type_id' => $gl->person_type_id,
                        'person_id' => $gl->person_id,
                        'vat_no' => $gl->vat_no,
                        'vat_amount' => $gl->vat_amount,
                        'verified' => $gl->verified,
                        'project_code' => $gl->project_code,
                        'amount' => $ledger['amount'],
                        'memo_' => $ledger['memo_']
                    ]
                );
            }

            // Commit Transaction
            DB::commit();

            return response()->json([
                'success' => true
            ]);
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }
}
