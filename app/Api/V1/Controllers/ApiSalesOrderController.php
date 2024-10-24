<?php

namespace App\Api\V1\Controllers;

use JWTAuth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\SalesOrderController;
use Illuminate\Http\Request;
use Auth;
use App\Modules\PaginationArr;
use App\Modules\InputList;
use Carbon\Carbon;

class ApiSalesOrderController extends Controller
{

    public function __construct()
    {
        $currentUser = JWTAuth::parseToken()->authenticate();

        $this->user_id = Auth::guard()->user()->id;
        $this->old_id = Auth::guard()->user()->old_id;
        $this->user_level = Auth::guard()->user()->approval_level;
        $this->user_division = Auth::guard()->user()->division_id;
        $this->user_person_id = Auth::guard()->user()->person_id;
        $this->user_name = Auth::guard()->user()->name;
    }

    public function get_so_by_project(Request $request)
    {
        $project_code = $request->project_code;
        $myArray = SalesOrderController::get_sql_for_sales_orders_detail_by_project($project_code);
        $myPage = $request->page;
        $myUrl = $request->url();
        $query = $request->query();

        if (empty($request->perpage)) {
            $perPage = 10;
        } else {
            $perPage = $request->perpage;
        }

        return PaginationArr::arr_pagination(
            $myArray,
            $myPage,
            $perPage,
            $myUrl,
            $query
        );
    }
    public function upload_order(Request $request)
    {
        $data = $request->data;
        DB::beginTransaction();
        try {
            foreach ($data as $val) {
                $check_site = DB::table('0_project_site')->where('site_id', $val['site_id'])->first();
                if (!empty($check_site)) {
                    $site_no = $check_site->site_no;
                } else if (empty($check_site)) {
                    DB::table('0_project_site')
                        ->insert(array(
                            'site_id' => $val['site_id'],
                            'name' => $val['site_name'],
                            'site_code' => ''
                        ));
                    $lastest = DB::table('0_project_site')->where('site_id', $val['site_id'])->first();
                    $site_no = $lastest->site_no;
                }
                DB::table('0_customer_orders')
                    ->insert(array(
                        'customer_po_id' => $val['customer_po_id'],
                        'change_history' => 0,
                        'customer' => $val['customer'],
                        'project_name' => $val['project_name'],
                        'site_no' => $site_no,
                        'site_code' => $val['site_code'],
                        'site_name' => $val['site_name'],
                        'site_id' => $val['site_id'],
                        'sub_contract_no' => $val['sub_contract_no'],
                        'pr_no' => $val['pr_no'], //prNumber
                        'po_status' => $val['po_status'], //shipmentStatus
                        'po_no' => $val['po_no'],
                        'po_line_no' => $val['po_line_no'],
                        'shipment_no' => $val['shipment_no'],
                        'version_no' => 0,
                        'item_code' => $val['item_code'],
                        'item_description' => $val['item_description'],
                        'unit_price' => $val['unit_price'],
                        'requested_qty' => $val['requested_qty'],
                        'due_qty' => $val['requested_qty'],
                        'line_amount' => $val['line_amount'],
                        'unit' => $val['uom'],
                        'payment_terms' => $val['payment_terms'], //taxRateText
                        'start_date' => $val['start_date'],
                        'end_date' => $val['end_date'],
                        'note_to_receiver' => $val['publish_date'],
                        'created_date' => Carbon::now(),
                        'created_by' => Auth::guard()->user()->id
                    ));
            }
            DB::commit();

            return response()->json([
                'success' => true
            ]);
        } catch (Exception $e) {
            // ROLLBACK TRANSACTION
            DB::rollBack();
        }
    }
}
