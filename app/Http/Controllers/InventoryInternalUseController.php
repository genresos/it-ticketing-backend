<?php

namespace App\Http\Controllers;

use JWTAuth;
use Illuminate\Http\Request;
use Carbon\Carbon;
use SiteHelper;
use Auth;
use Illuminate\Support\Facades\DB;
use PDF;

class InventoryInternalUseController extends Controller
{
    public static function validate_mr($array, $user_id)
    {
        DB::beginTransaction();
        try {

            foreach ($array as $key) {
                $grn = DB::table('0_grn_batch')->where('id', $key['grn_batch_id'])->first();
                $grn_items = DB::table('0_grn_items')->where('grn_batch_id', $key['grn_batch_id'])
                    ->where(
                        'po_detail_item',
                        $key['po_detail_item']
                    )->first();

                $grn_tmp = DB::table('0_grn_items_tmp')->where('grn_batch_id', $key['grn_batch_id'])
                    ->where(
                        'po_detail_item',
                        $key['po_detail_item']
                    )->whereIn('counter', $key['counter'])
                    ->where('validated', 0)
                    ->select(
                        'grn_batch_id',
                        'line',
                        'po_detail_item',
                        'item_code',
                        'description',
                        DB::raw("SUM(qty_recd) as qty_recd"),
                        'quantity_inv',
                        'item_tax_type',
                        'discount_percent'
                    )
                    ->first();

                $items = DB::table('0_stock_master')->where('stock_id', $grn_tmp->item_code)->first();
                // //=====================================================================================================
                if (empty($grn_items)) {
                    DB::table('0_grn_items')
                        ->insert(array(
                            'grn_batch_id' => $grn_tmp->grn_batch_id,
                            'line' => $grn_tmp->line,
                            'po_detail_item' => $grn_tmp->po_detail_item,
                            'item_code' => $grn_tmp->item_code,
                            'description' => $grn_tmp->description,
                            'qty_recd' => $grn_tmp->qty_recd,
                            'quantity_inv' => $grn_tmp->quantity_inv,
                            'item_tax_type' => $grn_tmp->item_tax_type,
                            'discount_percent' => $grn_tmp->discount_percent
                        ));
                } else if (!empty($grn_items)) {
                    DB::table('0_grn_items')->where('id', $grn_items->id)
                        ->update(array(
                            'qty_recd' => DB::raw("qty_recd+$grn_tmp->qty_recd"),
                        ));
                }
                // /* cek trx kalau ada update
                // **/
                $stock_moves = DB::table('0_stock_moves')->where('type', 25)->where('trans_no', $grn_tmp->grn_batch_id)->where('stock_id', $grn_tmp->item_code)->first();

                if (empty($stock_moves)) {

                    DB::table('0_stock_moves')
                        ->insert(array(
                            'stock_id' => $grn_tmp->item_code,
                            'trans_no' => $grn_tmp->grn_batch_id,
                            'type' => 25,
                            'loc_code' => $grn->loc_code,
                            'tran_date' => $grn->delivery_date,
                            'person_id' => $grn->supplier_id,
                            'reference' => $grn->reference,
                            'qty' => $grn_tmp->qty_recd,
                            'standard_cost' => $items->material_cost,
                            'discount_percent' => $grn_tmp->discount_percent,
                            'visible' => 1

                        ));
                } else if (!empty($stock_moves)) {
                    DB::table('0_stock_moves')->where('trans_id', $stock_moves->trans_id)
                        ->update(array(
                            'stock_id' => $grn_tmp->item_code,
                            'trans_no' => $grn_tmp->grn_batch_id,
                            'type' => 25,
                            'loc_code' => $grn->loc_code,
                            'tran_date' => $grn->delivery_date,
                            'person_id' => $grn->supplier_id,
                            'reference' => $grn->reference,
                            'qty' =>  DB::raw("qty+$grn_tmp->qty_recd"),
                            'standard_cost' => $items->material_cost,
                            'discount_percent' => $grn_tmp->discount_percent,
                            'visible' => 1

                        ));
                }

                DB::table('0_grn_items_tmp')->where('grn_batch_id', $key['grn_batch_id'])
                    ->where(
                        'po_detail_item',
                        $key['po_detail_item']
                    )->whereIn('counter', $key['counter'])
                    ->update(array(
                        'validated' => 1,
                        'validated_by' => $user_id,
                        'validated_at' => Carbon::now()
                    ));
            }
            DB::commit();
            return response()->json([
                'success' => true
                // 'data' => $grn_tmp
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return $e;
        }
    }
    public static function add_inventory_trans($trans_no, $type, $reference, $tran_date, $loc_code, $user_id)
    {
        // $code = ($project_code == '') ? 0 : $project_code;
        // $project = DB::table('0_projects')->where('code', $code)->first();
        // $project_no = (empty($project) ? 0 : $project->project_no);

        DB::table('0_inventory_trans')
            ->insert(array(
                'trans_no' => $trans_no,
                'type' => $type,
                'reference' => $reference,
                'tran_date' => $tran_date,
                'loc_code' => $loc_code,
                'project_no' => 0,
                'created_date' => Carbon::now(),
                'created_by' => $user_id,
                'updated_date' => Carbon::now(),
                'updated_by' => $user_id
            ));
    }

    public static function add_stock_internal_use_item(
        $adj_id,
        $stock_id,
        $location,
        $date_,
        $type,
        $reference,
        $quantity,
        $standard_cost,
        $memo_,
        $project_code,
        $project_code_to,
        $budget_id,
        $units,
        $user_id
    ) {
        self::add_stock_move(
            15,
            $stock_id,
            $adj_id,
            $location,
            $date_,
            $reference,
            $quantity,
            $standard_cost,
            $type,
            null,
            0,
            0,
            null,
            $project_code,
            $project_code_to,
            $budget_id,
            $user_id
        );
    }

    public static function add_stock_move(
        $type,
        $stock_id,
        $trans_no,
        $location,
        $date_,
        $reference,
        $quantity,
        $std_cost,
        $person_id,
        $show_or_hide,
        $price,
        $discount_percent,
        $error_msg,
        $project_code,
        $to_project_code,
        $budget_id,
        $user_id

    ) {
        DB::table('0_stock_moves')
            ->insert(array(
                'stock_id' => $stock_id,
                'trans_no' => $trans_no,
                'type' => $type,
                'loc_code' => $location,
                'tran_date' => $date_,
                'person_id' => $user_id,
                'reference' => $reference,
                'qty' => $quantity,
                'standard_cost' => $std_cost,
                'visible' => $show_or_hide,
                'price' => $price,
                'discount_percent' => $discount_percent,
                'project_code' => $project_code,
                'to_project_code' => $to_project_code,
                'budget_id' => $budget_id,
                'visible' => 0

            ));
    }

    public static function add_inventory_trans_item($adj_id, $stock_id, $location, $quantity, $standard_cost, $remark, $units, $requisition_line_id)
    {
        $item_info = self::get_item_edit_info($stock_id);
        $act_units = $item_info;

        $devide = self::get_unit_devided_rate($stock_id, $act_units, $units);

        if (!empty($site_id))
            $site_info = explode("__", $site_id);

        if ($units != $act_units) {
            $qty = $quantity / $devide;
        } else {
            $qty = $quantity;
        }

        DB::table('0_inventory_trans_details')
            ->insert(array(
                'trans_no' => $adj_id,
                'stock_id' => $stock_id,
                'units' => $units,
                'act_units' => $act_units,
                'quantity' => $qty,
                'act_qty' => $quantity,
                'standard_cost' => $standard_cost,
                'loc_code' => $location,
                'remark' => $remark,
                'requisition_line_id' => $requisition_line_id
            ));
    }

    public static function get_item_edit_info($stock_id)
    {
        $sql = "SELECT s.description, (s.material_cost + s.labour_cost + s.overhead_cost) AS standard_cost, s.units, u.decimals, s.editable, s.category_id
		FROM 0_stock_master s 
		LEFT OUTER JOIN 0_item_units u ON (s.units = u.abbr)
		WHERE stock_id= '$stock_id'";
        $data = DB::connection('mysql')->select(DB::raw($sql));

        return $data[0]->units;
    }

    public static function get_unit_devided_rate($stock_id, $from_unit, $to_unit)
    {
        $sql = "SELECT devided_rate FROM 0_item_unit_conversion
			WHERE stock_id= '$stock_id' AND from_uom= '$from_unit' AND to_uom= '$to_unit'";
        $data = DB::connection('mysql')->select(DB::raw($sql));


        return (!empty($data) ? $data[0]->devided_rate : 0);
    }

    public static function get_qoh_on_date($stock_id, $location, $date_, $exclude)
    {
        if ($date_ == null) {
            $sql = "SELECT SUM(qty) AS qty FROM 0_stock_moves
            WHERE stock_id= '$stock_id";
        } else {
            $sql = "SELECT SUM(qty) AS qty FROM 0_stock_moves
            WHERE stock_id= '$stock_id'
            AND tran_date <= '$date_'";
        }
        if ($location != null)
            $sql .= " AND loc_code = '$location'";

        $result = DB::connection('mysql')->select(DB::raw($sql));
        if ($exclude > 0) {
            $sql1 = "SELECT SUM(qty) AS qty FROM 0_stock_moves
            WHERE stock_id= '$stock_id' AND type= $exclude AND tran_date = '$date_'";

            $result2 = DB::connection('mysql')->select(DB::raw($sql1));
            if ($result2 !== false)
                $result[0]->qty -= $result2[0]->qty;
        }

        $qoh =  $result[0]->qty;
        return $qoh ? $qoh : 0;
    }

    public static function location_list($loc_code, $user_id)
    {
        $sql = "SELECT loc_code, location_name, inactive FROM 0_locations WHERE inactive=0";

        if ($loc_code != '') {
            $sql .= " AND loc_code IN (SELECT loc_code FROM 0_user_location WHERE user_id=$user_id)";
        }

        $data = DB::connection('mysql')->select(DB::raw($sql));

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public static function validate_when_not_raw_material()
    {
        $grn_tmp = DB::table('0_grn_items_tmp as grn')
            ->leftJoin('0_purch_order_details as pod', 'grn.po_detail_item', '=', 'pod.po_detail_item')
            ->leftJoin('0_purch_orders as po', 'pod.order_no', '=', 'po.order_no')
            ->where('grn.validated', 0)
            ->where('po.doc_type_id', '!=', 4004)
            ->where('grn.id', '>', 4391)
            ->select(
                'grn.*',
                DB::raw('SUM(qty_recd) as qty_recd')
            )
            ->groupBy('grn.grn_batch_id')
            ->groupBy('grn.po_detail_item')
            ->get();

        try {
            foreach ($grn_tmp as $data) {
                $grn = DB::table('0_grn_batch')->where('id', $data->grn_batch_id)->first();
                $items = DB::table('0_stock_master')->where('stock_id', $data->item_code)->first();

                DB::table('0_grn_items')
                    ->insert(array(
                        'grn_batch_id' => $data->grn_batch_id,
                        'line' => $data->line,
                        'po_detail_item' => $data->po_detail_item,
                        'item_code' => $data->item_code,
                        'description' => $data->description,
                        'qty_recd' => $data->qty_recd,
                        'quantity_inv' => $data->quantity_inv,
                        'item_tax_type' => $data->item_tax_type,
                        'discount_percent' => $data->discount_percent
                    ));

                DB::table('0_stock_moves')
                    ->insert(array(
                        'stock_id' => $data->item_code,
                        'trans_no' => $data->grn_batch_id,
                        'type' => 25,
                        'loc_code' => $grn->loc_code,
                        'tran_date' => $grn->delivery_date,
                        'person_id' => $grn->supplier_id,
                        'reference' => $grn->reference,
                        'qty' => $data->qty_recd,
                        'standard_cost' => $items->material_cost,
                        'discount_percent' => $data->discount_percent,
                        'visible' => 1

                    ));
                DB::table('0_grn_items_tmp')->where('id', $data->id)
                    ->update(array(
                        'validated' => 1,
                        'validated_by' => 1,
                        'validated_at' => Carbon::now()
                    ));
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

    public static function download_qr_grn($grn, $old_receipt)
    {
        $response = [];


        if ($old_receipt == 0) {
            $sql = DB::table('0_grn_items_tmp')->where('grn_batch_id', $grn)->get();

            foreach ($sql as $data) {

                $po_detail = DB::table('0_purch_order_details')->where('po_detail_item', $data->po_detail_item)->first();
                $total_item = DB::table('0_grn_items_tmp')->where('grn_batch_id', $grn)->where('po_detail_item', $data->po_detail_item)->sum('qty_recd');
                $tmp = [];
                $tmp['item_code'] = $data->item_code;
                $tmp['grn_batch_id'] = $data->grn_batch_id;
                $tmp['po_detail_item'] = $data->po_detail_item;
                $tmp['project_code'] = $po_detail->project_code;
                $tmp['description'] = $data->description;
                $tmp['counter'] = $data->counter;
                $tmp['total_item'] = $total_item;

                array_push($response, $tmp);
            }
        } else {
            $sql = DB::table('0_grn_items')->where('grn_batch_id', $grn)->get();

            foreach ($sql as $data) {

                $po_detail = DB::table('0_purch_order_details')->where('po_detail_item', $data->po_detail_item)->first();
                $total_item = DB::table('0_grn_items')->where('grn_batch_id', $grn)->where('po_detail_item', $data->po_detail_item)->sum('qty_recd');

                for ($x = 1; $x < $total_item + 1; $x++) {

                    $tmp = [];
                    $tmp['item_code'] = $data->item_code;
                    $tmp['grn_batch_id'] = $data->grn_batch_id;
                    $tmp['po_detail_item'] = $data->po_detail_item;
                    $tmp['project_code'] = $po_detail->project_code;
                    $tmp['description'] = $data->description;
                    $tmp['counter'] = $x;
                    $tmp['total_item'] = $total_item;
                    array_push($response, $tmp);
                }
            }
        }
        // $sql = "SELECT 0_grn_items_tmp.*, 0_purch_order_details.project_code,0_purch_order_details.sales_order_no,0_purch_orders.doc_type_id
        //         FROM 0_grn_batch, 0_grn_items_tmp, 0_purch_order_details,0_purch_orders
        //         WHERE 0_grn_items_tmp.grn_batch_id=0_grn_batch.id AND 0_grn_items_tmp.po_detail_item=0_purch_order_details.po_detail_item
        //         AND 0_purch_order_details.order_no = 0_purch_orders.order_no
        //         AND 0_grn_batch.id = $grn
        //         AND 0_grn_items_tmp.grn_batch_id = $grn";
        // $exe = DB::select(DB::raw($sql));
        $customPaper = array(0, 0, 600, 400);
        $ref = DB::table('0_refs')->where('type', 25)->where('id', $grn)->first();
        $filename = 'QR_' . $ref->reference;
        $pdf = PDF::loadView('create_grn_qr', compact('response'))->setPaper($customPaper)->download("$filename.pdf");
        return $pdf;
    }
}
