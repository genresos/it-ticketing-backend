<?php

namespace App\Api\V1\Controllers;

use JWTAuth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\AppVersionContoller;
use Illuminate\Http\Request;
use Auth;
use App\Modules\PaginationArr;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\InventoryMovementController;
use App\Http\Controllers\InventoryInternalUseController;

class ApiInventoryMoveController extends Controller
{

    public function __construct()
    {
        $currentUser = JWTAuth::parseToken()->authenticate();

        $this->user_id = Auth::guard()->user()->id;
        $this->user_old_id = Auth::guard()->user()->old_id;
        $this->user_level = Auth::guard()->user()->approval_level;
        $this->user_division = Auth::guard()->user()->division_id;
    }

    public function add_stock_transfer(Request $request)
    {
        $user_old_id = $this->user_old_id;
        $Items = $request->items;
        $location_from = $request->location_from;
        $location_to = $request->location_to;
        $date_ = $request->date;
        $type = 16;
        $last_ref = DB::table('0_sys_types')->where('type_id', 16)->pluck('next_reference');
        $reference = $last_ref[0];
        $project_code = empty($request->project_code) ? '' : $request->project_code;
        $last_trans_no = DB::table('0_stock_movements')->orderBy('trans_no', 'desc')->limit(1)->pluck('trans_no');
        $transfer_id = $last_trans_no[0] + 1;
        $remark = $request->remark;

        DB::beginTransaction();
        try {

            // add inventory movement 
            InventoryMovementController::add_inventory_move($transfer_id, $type, $reference, $date_, $location_from, $location_to, $project_code, $user_old_id);

            foreach ($Items as $key => $line_item) {
                $qoh = InventoryInternalUseController::get_qoh_on_date($line_item['stock_id'], $location_from, $date_, 0);
                if ($qoh - $line_item['quantity'] < 0) {
                    return response()->json([
                        'error' => array(
                            'message' => 'The quantity entered is greater than the available quantity for this item at the source location :' . '(' . $line_item['stock_id'] . ')',
                            'status_code' => 403
                        )
                    ], 403);
                }
                InventoryMovementController::add_inventory_move_details(
                    $transfer_id,
                    $line_item['stock_id'],
                    $line_item['quantity'],
                    $location_from,
                    $location_to,
                    empty($line_item['requisition_no']) ? 0 : $line_item['requisition_no'],
                    empty($line_item['requisition_line_id']) ? 0 : $line_item['requisition_line_id'],
                    $line_item['ext_description'],
                    $line_item['site_id']
                );

                InventoryMovementController::add_stock_transfer_item(
                    $transfer_id,
                    $line_item['stock_id'],
                    $location_from,
                    $location_to,
                    $date_,
                    $type,
                    $reference,
                    $line_item['quantity'],
                    $project_code
                );
            }
            // add comment
            DB::table('0_comments')
                ->insert(array(
                    'type' => $type,
                    'id' => $transfer_id,
                    'date_' => $date_,
                    'memo_' => $remark
                ));

            // update next refs;
            $next_ref = "$reference";
            DB::table('0_sys_types')->where('type_id', 16)
                ->update(array(
                    'next_reference' => ++$next_ref
                ));

            //audit trail
            $fiscal_year = DB::table('0_fiscal_year')->orderBy('id', 'desc')->first();

            DB::table('0_audit_trail')
                ->insert(array(
                    'type' => $type,
                    'trans_no' => $transfer_id,
                    'user' => $date_,
                    'fiscal_year' => $fiscal_year->id,
                    'gl_date' => $date_,
                    'description' => '',
                    'gl_seq' => 0

                ));

            // Commit Transaction
            DB::commit();

            return response()->json([
                'success' => true
            ]);
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();

            return $e;
        }
    }

    public function get_items_pr(Request $request)
    {
        $response = [];
        $sql =  DB::table('0_purch_requisitions as pr')
            ->Join('0_purch_requisition_details as prd', 'pr.order_no', '=', 'prd.order_no')
            ->where('pr.reference', $request->requisition_no)
            ->select('pr.reference', 'prd.*')
            ->get();


        $ref = DB::table('0_purch_requisitions as pr')->where('pr.reference', $request->requisition_no)->select('reference')->first();
        if (empty($ref)) {
            return response()->json([
                'success' => true,
                'data' => $response
            ]);
        }
        $response['requisition_no'] = empty($ref->reference) ? 0 : $ref->reference;
        $response['items'] = [];
        foreach ($sql as $data) {
            $tmp = [];
            $tmp['requisition_line_id'] = $data->pr_detail_item;
            $tmp['stock_id'] = $data->item_code;
            $tmp['ext_description'] = $data->description;
            $tmp['quantity'] = $data->qty;

            array_push($response['items'], $tmp);
        }


        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }
}
