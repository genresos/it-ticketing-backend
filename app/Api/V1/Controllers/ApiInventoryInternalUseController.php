<?php

namespace App\Api\V1\Controllers;

use JWTAuth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\AppVersionContoller;
use Illuminate\Http\Request;
use Auth;
use App\Modules\PaginationArr;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\InventoryInternalUseController;

class ApiInventoryInternalUseController extends Controller
{

    public function __construct()
    {
        $currentUser = JWTAuth::parseToken()->authenticate();

        $this->user_id = Auth::guard()->user()->id;
        $this->user_old_id = Auth::guard()->user()->old_id;
        $this->user_level = Auth::guard()->user()->approval_level;
        $this->user_division = Auth::guard()->user()->division_id;
    }

    public function get_items_mr_tmp(Request $request)
    {
        $data = $request->data;
        $response = [];
        foreach ($data as $key) {

            $grn_tmp = DB::table('0_grn_items_tmp')->where('grn_batch_id', $key['grn_batch_id'])->where('po_detail_item', $key['po_detail_item'])->where('validated', 0)->get();
            $grn_ref = DB::table('0_grn_batch')->where('id', $key['grn_batch_id'])->select('reference')->first();

            foreach ($grn_tmp as $items) {
                $tmp = [];
                $tmp['po_detail_item'] = $items->po_detail_item;
                $tmp['counter'] = $items->counter;
                // $tmp['document_no'] = $grn_ref->reference;
                $tmp['qty'] = $items->qty_recd;
                $tmp['validated'] = $items->validated == 0 ? false : true;
                // $tmp['qty'] = $items->qty_recd;
                array_push($response, $tmp);
            }
        }
        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }
    public function validation_material_receipt(Request $request)
    {
        $data = $request->data;
        $user_id = $this->user_id;
        return InventoryInternalUseController::validate_mr($data, $user_id);
    }

    public function get_data_item_from_qr(Request $request)
    {
        $response = [];

        $qr_code = $request->value;
        $explode = explode("_", $qr_code);
        $trans_no = $explode[1];
        $item_code = $explode[0];
        $project_code = $explode[2];
        $sql = "SELECT 0_grn_items.*, 0_purch_order_details.project_code,0_purch_order_details.sales_order_no,0_purch_orders.doc_type_id, 0_stock_master.material_cost, 0_stock_master.units
                FROM 0_grn_batch, 0_grn_items, 0_purch_order_details,0_purch_orders, 0_stock_master
                WHERE 0_grn_items.grn_batch_id=0_grn_batch.id AND 0_grn_items.po_detail_item=0_purch_order_details.po_detail_item
                AND 0_grn_items.item_code=0_stock_master.stock_id
                AND 0_purch_order_details.order_no = 0_purch_orders.order_no
                AND 0_grn_batch.id = $trans_no
                AND 0_grn_items.grn_batch_id = $trans_no AND 0_grn_items.item_code = '$item_code' AND 0_purch_order_details.project_code = '$project_code'";

        $data = DB::connection('mysql')->select(DB::raw($sql));

        foreach ($data as $value) {
            $tmp = [];
            $tmp['item_code'] = $value->item_code;
            $tmp['standart_cost'] = $value->material_cost;
            $tmp['project_id'] = $value->project_code;
            $tmp['units'] = $value->units;
            $tmp['quantity'] = null;
            $tmp['project_id_to'] = null;
            $tmp['budget_id'] = null;
            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }
    public function add_stock_internal_use(Request $request)
    {
        // return InventoryInternalUseController::get_qoh_on_date('ADP14AH00C200000830', 'FG', '2022-11-28', 0);
        $user_id = $this->user_old_id;
        $date_ = $request->date;
        $location = $request->location;
        $memo_ = $request->comment . ' (created from AdyawinsaApp)';
        $type = 15;
        $increase = 0;
        $items = $request->items;

        $post_id = DB::table('0_inventory_trans')->orderBy('trans_no', 'desc')->select('trans_no')->orderBy('trans_no', 'desc')->first();
        $adj_id = $post_id->trans_no;
        $curr_next_ref = DB::table('0_sys_types')->where('type_id', 15)->select('next_reference')->first();
        $reference_ = $curr_next_ref->next_reference;

        // --- check trans no ----------------------------
        $transno = DB::table('0_inventory_trans')->max('trans_no');
        if ($adj_id == $transno) {
            $adj_id = $transno + 1;
        } else {
            $adj_id = $transno + 1;
        }
        DB::beginTransaction();
        try {
            //-----------------------------------------------
            InventoryInternalUseController::add_inventory_trans($adj_id, 15, $reference_, $date_, $location, $user_id);
            foreach ($items as $key => $value) {

                $qoh = InventoryInternalUseController::get_qoh_on_date($value['item_code'], $location, $date_, 0);

                if ($qoh - $value['quantity'] < 0) {
                    return response()->json([
                        'error' => array(
                            'message' => 'The internal use cannot be processed because there is an insufficient quantity for this item ' . '(' . $value['item_code'] . ')',
                            'status_code' => 403
                        )
                    ], 403);
                }

                if (!$increase)
                    $value['quantity'] = -$value['quantity'];

                InventoryInternalUseController::add_stock_internal_use_item(
                    $adj_id,
                    $value['item_code'],
                    $location,
                    $date_,
                    $type,
                    $reference_,
                    $value['quantity'],
                    $value['standart_cost'],
                    $memo_,
                    $value['project_id'],
                    $value['project_id_to'],
                    $value['budget_id'],
                    $value['units'],
                    $user_id
                );

                InventoryInternalUseController::add_inventory_trans_item(
                    $adj_id,
                    $value['item_code'],
                    $location,
                    $value['quantity'],
                    $value['standart_cost'],
                    $memo_,
                    $value['units'],
                    0
                );
            }

            //--- add comments -----------
            DB::table('0_comments')
                ->insert(array(
                    'type' => 15,
                    'id' => $adj_id,
                    'date_' => $date_,
                    'memo_' => $memo_
                ));


            //--- save new Refs -----------

            DB::table('0_sys_types')->where('type_id', 15)
                ->update(array(
                    'next_reference' => ++$reference_
                ));

            //--- add audit trail -----------
            $fiscal_year = DB::table('0_fiscal_year')->orderBy('id', 'desc')->first();

            DB::table('0_audit_trail')
                ->insert(array(
                    'type' => 15,
                    'trans_no' => $adj_id,
                    'user' => $this->user_old_id,
                    'fiscal_year' => $fiscal_year->id,
                    'gl_date' => date('Y-m-d'),
                    'description' => '',
                    'gl_seq' => 0
                ));

            DB::commit();
            return response()->json([
                'success' => true,
                'data' => $reference_ . '' . ' has created'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            echo 'error';
        }
    }

    public function location_list_row(Request $request)
    {
        $user_id = $this->user_old_id;
        if (!empty($request->loc_code)) {
            $loc_code = $request->loc_code;
        } else {
            $loc_code = '';
        }

        return InventoryInternalUseController::location_list($loc_code, $user_id);
    }
}
