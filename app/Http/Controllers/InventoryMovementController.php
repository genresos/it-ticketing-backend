<?php

namespace App\Http\Controllers;

use JWTAuth;
use Illuminate\Http\Request;
use Carbon\Carbon;
use SiteHelper;
use Auth;
use Illuminate\Support\Facades\DB;
use PDF;

class InventoryMovementController extends Controller
{
    public static function add_inventory_move($trans_no, $type, $reference, $tran_date, $from_loc_code, $to_loc_code, $project_code, $user_old_id)
    {

        $row = DB::table('0_projects')->where('code', $project_code)->first();
        $project_no = ($project_code = '') ? 0 : $row->project_no;

        DB::table('0_stock_movements')
            ->insert(array(
                'trans_no' => $trans_no,
                'type' => $type,
                'reference' => $reference,
                'tran_date' => $tran_date,
                'from_loc_code' => $from_loc_code,
                'to_loc_code' => $to_loc_code,
                'project_no' => $project_no,
                'created_date' => Carbon::now(),
                'created_by' => $user_old_id,
                'updated_date' => Carbon::now(),
                'updated_by' => $user_old_id

            ));
    }

    public static function add_inventory_move_details($trans_no, $stock_id, $qty_moved, $from_loc_code, $to_loc_code, $requisition_no, $requisition_line_id, $remark, $site_id)
    {

        DB::table('0_stock_movement_details')
            ->insert(array(
                'trans_no' => $trans_no,
                'stock_id' => $stock_id,
                'quantity_moved' => $qty_moved,
                'from_loc_code' => $from_loc_code,
                'to_loc_code' => $to_loc_code,
                'site_id' => $site_id,
                'requisition_no' => $requisition_no,
                'requisition_line_id' => $requisition_line_id,
                'remark' => $remark

            ));
    }

    public static function add_stock_transfer_item(
        $transfer_id,
        $stock_id,
        $location_from,
        $location_to,
        $date_,
        $type,
        $reference,
        $quantity,
        $project_code
    ) {
        DB::table('0_stock_moves')
            ->insert(array(
                'stock_id' => $stock_id,
                'trans_no' => $transfer_id,
                'type' => $type,
                'loc_code' => $location_from,
                'tran_date' => $date_,
                'reference' => $reference,
                'qty' => -$quantity,
                'project_code' => $project_code

            ));
        DB::table('0_stock_moves')
            ->insert(array(
                'stock_id' => $stock_id,
                'trans_no' => $transfer_id,
                'type' => $type,
                'loc_code' => $location_to,
                'tran_date' => $date_,
                'reference' => $reference,
                'qty' => $quantity,
                'project_code' => $project_code

            ));
    }
}
