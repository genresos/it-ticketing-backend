<?php

namespace App\Api\V1\Controllers;

use Illuminate\Http\Request;
use JWTAuth;
use Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Dingo\Api\Routing\Helpers;
use Carbon\Carbon;
use App\Employees;
use App\Query\QueryEmployees;
use URL;

class ApiPurchOrderController extends Controller
{
    public function __construct()
    {
        $currentUser = JWTAuth::parseToken()->authenticate();

        $this->user_id = Auth::guard()->user()->id;
    }

    public function po_need_approval(Request $request)
    {
        $response = array();
        $user_id = $this->user_id;
        if (!empty($request->doc_no)) {
            $doc_no = $request->doc_no;
        } else {
            $doc_no = '';
        }
        $sql = "SELECT 
				porder.order_no, 
                pt.type_name AS doc_type,		
				porder.reference, 
				porder.ord_date, 			
				supplier.supp_name, 
				porder.curr_code, 
				Sum(line.unit_price*line.quantity_ordered*(1-line.discount_percent)) AS OrderValue,
				users.real_name,
                (
				 SELECT h.status
				 FROM 0_purch_orders_history h
				 WHERE h.order_no = porder.order_no
				 ORDER BY h.id DESC LIMIT 1
				)AS po_status
				FROM 0_purch_orders AS porder 
				INNER JOIN 0_purch_order_details AS line ON (porder.order_no = line.order_no) 
                INNER JOIN 0_purch_order_types pt ON (porder.doc_type_id = pt.type_id)
				INNER JOIN 0_suppliers AS supplier ON (porder.supplier_id = supplier.supplier_id) 		
				INNER JOIN 0_users AS users ON (users.id = porder.created_by)";
        if ($user_id == 696)
            $sql .= " WHERE porder.approval = 0"; // pak suhadi

        else if ($user_id == 380)
            $sql .= " WHERE porder.approval = 1"; //pak ronny

        else if ($user_id == 1)

            $sql .= " WHERE porder.approval IN (0,1)"; //administrator

        else
            $sql .= " WHERE porder.order_no = -1";

        $sql .= " GROUP BY porder.order_no ORDER BY porder.order_no DESC";
        $exe = DB::select(DB::raw($sql));
        foreach ($exe as $data) {
            $tmp = [];
            $tmp['order_no'] = $data->order_no;
            $tmp['doc_type'] = $data->doc_type;
            $tmp['doc_no'] = $data->reference;
            $tmp['ord_date'] = $data->ord_date;
            $tmp['supp_name'] = $data->supp_name;
            $tmp['order_total'] = $data->OrderValue;
            $tmp['currency'] = $data->curr_code;
            $tmp['po_status'] = $data->po_status;
            $tmp['creator'] = $data->real_name;
            $tmp['details'] = DB::table('0_purch_order_details')->where('order_no', $data->order_no)
                ->select(
                    'line',
                    'item_code',
                    'quantity_ordered',
                    'unit_price'
                )->get();

            $tmp['history'] = DB::table('0_purch_orders_history AS h')
                ->leftJoin('users as u', 'h.user_id', '=', 'u.id')
                ->where('h.order_no', $data->order_no)
                ->select(
                    'h.id',
                    DB::raw("(CASE WHEN h.status = 3 THEN 'PENDING' ELSE 'NEW' END) AS status"),
                    'h.remark',
                    'u.name AS user',
                    'h.created_at'
                )->get();

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public function approve_po(Request $request)
    {
        $user_id = $this->user_id;

        $order_no = $request->order_no;
        $status = $request->status;
        $remark = $request->remark;

        DB::beginTransaction();
        try {
            $po_info = DB::table('0_purch_orders')->where('order_no', $order_no)->first();

            if ($status != 3) {
                $next_approval = $status == 1 ? $po_info->approval + 1 : 4;

                DB::table('0_purch_orders')->where('order_no', $order_no)
                    ->update(array(
                        'approval' => $next_approval
                    ));
            }

            if ($status == 2) { // kalau disapprove void po

                $po_detail_info = DB::table('0_purch_order_details')->where('order_no', $order_no)->get();

                foreach ($po_detail_info as $data_po) {
                    DB::table('0_purch_requisition_details')->where('pr_detail_item', $data_po->src_id)
                        ->update(array(
                            'qty_ordered' => 0
                        ));
                }

                DB::table('0_purch_orders')->where('order_no', $order_no)
                    ->update(array(
                        'status_id' => 1
                    ));

                DB::table('0_purch_order_details')->where('order_no', $order_no)
                    ->update(array(
                        'quantity_ordered' => 0, 'unit_price' => 0
                    ));

                DB::table('0_voided')
                    ->insert(array(
                        'type' => $po_info->doc_type_id,
                        'id' => $order_no,
                        'date_' => date('Y-m-d'),
                        'memo_' => $remark,
                        'created_by' => Carbon::now(),
                        'created_date' => date('Y-m-d')
                    ));
            }
            DB::table('0_purch_orders_history')
                ->insert(array(
                    'order_no' => $order_no,
                    'status' => $status,
                    'user_id' => $user_id,
                    'remark' => $remark,
                    'created_at' => Carbon::now(),
                ));

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
