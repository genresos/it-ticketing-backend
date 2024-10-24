<?php

namespace App\Api\V1\Controllers;

use JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Carbon\Carbon;
use Validator, Redirect, Response, File;
use Auth;
use App\CashAdvance;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\EmployeesController;
use Symfony\Component\HttpKernel\Exception\ValidationAmountCADHttpException;
use App\Modules\PaginationArr;
use App\Exports\AuditAssetExport;
use App\Exports\AuditMaterialExport;
use Maatwebsite\Excel\Facades\Excel;

class AssetController extends Controller
{
    //
    use Helpers;

    /*
     *
     * 
     */
    //==================================================================== FUNCTION CA LIST User =============================================================\\  
    public function car_list()
    {
        $currentUser = JWTAuth::parseToken()->authenticate();
        $user_id = Auth::guard()->user()->old_id;

        $response = [];

        $sql = "SELECT
                        a.asset_id,a.asset_name, g.name AS group_name, a.asset_model_name,
                        a.asset_model_number,a.asset_serial_number
                    FROM 0_am_assets a
                    LEFT OUTER JOIN 0_am_groups g ON (a.group_id = g.group_id)
                    WHERE a.inactive = 0 AND a.type_id=2 ";


        $petty_cash = DB::select(DB::raw($sql));



        foreach ($petty_cash as $data) {

            $tmp = [];
            $tmp['asset_name'] = $data->asset_name;
            $tmp['group_name'] = $data->group_name;


            array_push($response, $tmp);
        }
        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    //==================================================================== FUNCTION CARD TOL List =======================================================>
    public function tol_card_list()
    {

        $sql = "SELECT * FROM 0_am_tolcard WHERE inactive = 0";
        $response = [];
        $tolcard = DB::select(DB::raw($sql));

        foreach ($tolcard as $data) {
            $tmp = [];
            $tmp['id'] = $data->id;
            $tmp['card_no'] = $data->card_no;
            $tmp['balance'] = $data->balance;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }


    //==================================================================== FUNCTION CREATE Request Kendaraan =============================================================\\  
    public function request_kendaraan(Request $request)
    {

        $sql = "SELECT pdpl.*, u.name, u.emp_id AS emp_id FROM 0_pdp_log pdpl 
                        LEFT JOIN users u ON (pdpl.person_id = u.id)
                        WHERE pdpl.reference = '$request->doc_no_pdp' AND pdpl.approval = 2";

        $get_emp_id = DB::select(DB::raw($sql));
        foreach ($get_emp_id as $data) {

            $emp_id = $data->emp_id;

            $doc_no_pdp = $request->doc_no_pdp;
            $project_budget_id = $request->project_budget_id;
            $payment_type = $request->payment_type;
            $project_no = $request->project_no;
            // $site_no = $request->site_no;
            // $milestone_id = $request->milestone_no;
            $petty_cash = $request->bank_no;
            $amount_bbm = $request->amount_bbm;
            $if_tol = $request->if_tol;
            $amount_tol = $request->amount_tol;
            $remark_bbm = $request->remark_bbm;
            $bbm_doc_no = $request->bbm_doc_no;
            $remark_tol = $request->remark_tol;
            $tolcard_no = $request->tolcard_no;
            $vehicle_no = $request->vehicle_no;
            $vehicle_type_id = $request->vehicle_type;

            $aa = FinanceController::add_cashadvance(
                $doc_no_pdp,
                $emp_id,
                $project_budget_id,
                $project_no,
                $petty_cash,
                $payment_type,
                $vehicle_type_id,
                $amount_bbm,
                $bbm_doc_no,
                $if_tol,
                $amount_tol,
                $remark_bbm,
                $remark_tol,
                $tolcard_no,
                $vehicle_no
            );

            return $aa;
        }
    }

    public function asset_by_emp_list(Request $request)
    {
        $response = [];
        $emp_id = empty($request->emp_id) ? '' : $request->emp_id;
        $sql = "SELECT a.asset_id AS asset_no, CONCAT_WS('_', g.name, a.asset_name) AS asset_name
                FROM 0_am_issues i
                LEFT OUTER JOIN 0_am_assets a ON (i.object_id = a.asset_id)
                LEFT OUTER JOIN 0_am_groups g ON (a.group_id = g.group_id)
                WHERE i.approval_status = 1";

        if ($emp_id != '')
            $sql .= " AND i.issue_assignee = '$emp_id'";

        $sql .= " GROUP BY i.object_id";
        $sql .= " ORDER BY i.issue_id DESC";
        $get_emp_id = DB::select(DB::raw($sql));
        foreach ($get_emp_id as $data) {
            $tmp = [];
            $tmp['asset_name'] = $data->asset_name;
            $tmp['asset_no'] = $data->asset_no;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public function get_asset_type(Request $request)
    {
        $sql = DB::table('0_am_types')
            ->select(
                'type_id',
                'name'
            )
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sql
        ]);
    }

    public function get_asset_group(Request $request)
    {
        $type_id = $request->type_id;
        $name = $request->name;
        $sql = DB::table('0_am_groups')
            ->when($type_id != '', function ($query) use ($type_id) {
                $query->where('type_id', $type_id);
            })
            ->when($name != '', function ($query) use ($name) {
                $query->where('name', 'LIKE', '%' . $name . '%');
            })
            ->select(
                'group_id',
                'type_id',
                'name'
            )
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sql
        ]);
    }

    public function insert_audit_asset(Request $request)
    {
        $all_request = $request->data;

        DB::beginTransaction();
        try {
            foreach ($all_request as $data) {
                DB::table('0_audit_asset')
                    ->insert(array(
                        'type_id' => $data['type_id'],
                        'group_id' => $data['group_id'],
                        'asset_no' => $data['asset_no'],
                        'serial_no' => $data['serial_no'],
                        'qty' => $data['qty'],
                        'condition' => $data['condition'],
                        'location' => $data['location'],
                        'last_user_id' => $data['last_user_id'],
                        'last_user_name' => $data['last_user_name'],
                        'project_no' => $data['project_no'],
                        'created_by' => Auth::guard()->user()->id,
                        'created_at' => Carbon::now()
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

    public function get_audit_asset(Request $request)
    {

        $type_id = !empty($request->type_id) ? $request->type_id : '';
        $group_id = !empty($request->group_id) ? $request->group_id : '';
        $condition = !empty($request->condition) ? $request->condition : '';
        $loc_code = !empty($request->loc_code) ? $request->loc_code : '';
        $emp_id = !empty($request->emp_id) ? $request->emp_id : '';
        $project_no = !empty($request->project_no) ? $request->project_no : '';

        $sql = "SELECT aa.id,
                amt.name AS type_name,
                ag.name AS group_name,
                aa.asset_no,
                aa.serial_no,
                aa.qty,
                aa.condition AS asset_condition,
                loc.location_name,
                aa.last_user_id,
                aa.last_user_name,
                p.code AS project_code,
                u.name AS auditor,
                aa.created_at
                FROM 0_audit_asset aa
                INNER JOIN 0_am_types amt ON (aa.type_id = amt.type_id)
                INNER JOIN 0_am_groups ag ON (aa.group_id = ag.group_id)
                INNER JOIN 0_projects p ON (aa.project_no = p.project_no)
                INNER JOIN 0_locations loc ON (aa.location = loc.loc_code)
                INNER JOIN users u ON (aa.created_by = u.id)
                WHERE aa.id != -1";

        if ($type_id != '') {
            $sql .= " AND aa.type_id = $type_id";
        }

        if ($group_id != '') {
            $sql .= " AND aa.group_id = $group_id";
        }

        if ($condition != '') {
            $sql .= " AND aa.condition = '$condition'";
        }

        if ($loc_code != '') {
            $sql .= " AND aa.location = '$loc_code'";
        }

        if ($emp_id != '') {
            $sql .= " AND aa.last_user_id = '$emp_id'";
        }

        if ($project_no != '') {
            $sql .= " AND aa.project_no = $project_no";
        }

        $exe = DB::connection('mysql')->select(DB::raw($sql));

        $myPage = $request->page;
        $myUrl = $request->url();
        $query = $request->query();

        if (empty($request->perpage)) {
            $perPage = 10;
        } else {
            $perPage = $request->perpage;
        }

        return PaginationArr::arr_pagination(
            $exe,
            $myPage,
            $perPage,
            $myUrl,
            $query
        );
    }

    public function export_audit_asset()
    {
        $filename = "Audit Asset";
        return Excel::download(new AuditAssetExport, "$filename.xlsx");
    }

    public function insert_audit_material(Request $request)
    {
        $all_request = $request->data;

        DB::beginTransaction();
        try {
            foreach ($all_request as $data) {
                DB::table('0_audit_material')
                    ->insert(array(
                        'item_code' => $data['item_code'],
                        'description' => $data['description'],
                        'current_location' => $data['current_location'],
                        'origin_location' => $data['origin_location'],
                        'qty' => $data['qty'],
                        'uom' => $data['uom'],
                        'project_no' => $data['project_no'],
                        'owner' => $data['owner'],
                        'remark' => $data['remark'],
                        'created_by' => Auth::guard()->user()->id,
                        'created_at' => Carbon::now()
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

    public function get_audit_material(Request $request)
    {

        $item_code = !empty($request->item_code) ? $request->item_code : '';
        $curr_loc = !empty($request->curr_loc) ? $request->curr_loc : '';
        $origin_loc = !empty($request->origin_loc) ? $request->origin_loc : '';
        $owner = !empty($request->owner) ? $request->owner : '';
        $project_no = !empty($request->project_no) ? $request->project_no : '';
        $sql  = "SELECT am.id,
                am.item_code,
                am.description,
                (
                SELECT location_name FROM 0_locations loc
                WHERE loc_code = am.current_location
                ) current_location,
                (
                SELECT location_name FROM 0_locations loc
                WHERE loc_code = am.origin_location
                ) origin_location,
                am.qty,
                am.uom,
                p.code AS project_code,
                am.owner,
                am.remark,
                u.name AS auditor
                FROM 0_audit_material am
                INNER JOIN 0_projects p ON (am.project_no = p.project_no)
                INNER JOIN users u ON (u.id = am.created_by)
                WHERE am.id != -1";

        if ($item_code != '') {
            $sql .= " AND am.item_code = '$item_code'";
        }

        if ($curr_loc != '') {
            $sql .= " AND am.current_location = '$curr_loc'";
        }

        if ($origin_loc != '') {
            $sql .= " AND am.origin_location = '$origin_loc'";
        }

        if ($owner != '') {
            $sql .= " AND am.owner = '$owner'";
        }

        if ($project_no != '') {
            $sql .= " AND am.project_no = $project_no";
        }

        $exe = DB::connection('mysql')->select(DB::raw($sql));

        $myPage = $request->page;
        $myUrl = $request->url();
        $query = $request->query();

        if (empty($request->perpage)) {
            $perPage = 10;
        } else {
            $perPage = $request->perpage;
        }

        return PaginationArr::arr_pagination(
            $exe,
            $myPage,
            $perPage,
            $myUrl,
            $query
        );
    }

    public function export_audit_material()
    {
        $filename = "Audit Material";
        return Excel::download(new AuditMaterialExport, "$filename.xlsx");
    }
}
