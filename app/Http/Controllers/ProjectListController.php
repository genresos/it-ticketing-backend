<?php

namespace App\Http\Controllers;

use JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Carbon\Carbon;
use Validator, Redirect, Response, File;
use Auth;
use URL;
use App\Exports\ListProjectExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpKernel\Exception\ProjectCodeExistHttpException;
use App\Query\QueryProjectList;
use App\Http\Controllers\ProjectBudgetController;
use App\Api\V1\Controllers\ApiOsproController;
use App\Api\V1\Controllers\ApiProjectOverviewController;

class ProjectListController extends Controller
{
    public static function list($inactive, $project_code, $realtime_cost)
    {
        $response = [];

        $sql = QueryProjectList::list($inactive, $project_code);

        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {
            $total_budget = ProjectBudgetController::sum_total_budget($data->project_no);
            $p_info = ApiProjectOverviewController::project_info($data->project_no);
            $project_value = ($p_info[0]->project_value == null) ? 0 : $p_info[0]->project_value;

            if($project_value == 0){
                $project_value = $data->project_value;
            }

            if ($realtime_cost == 1) {
                $get_cost = ApiOsproController::actual_cost($data->project_no);
                if ($get_cost['total_expense'] == 0 || $get_cost['total_expense'] == null) {
                    $total_cost = ProjectBudgetController::sum_total_cost_budget($data->project_no);
                } else {
                    $total_cost = $get_cost['total_expense'];
                }

                DB::table('0_project_cost_tmp')->updateOrInsert(
                    ['project_no' => $data->project_no, 'cost' => $total_cost],
                    ['project_no' => $data->project_no]
                );
                $cost = $total_cost;
            } else {
                $tmp_cost = DB::table('0_project_cost_tmp')->where('project_no', $data->project_no)->orderBy('id', 'desc')->first();
                $cost = (empty($tmp_cost) ? 0 : $tmp_cost->cost);
            }
            $total_rab = ProjectBudgetController::sum_total_rab($data->project_no);

            $info_so =  SalesOrderController::get_total_so($data->code,0);
            $info_so_waiting =  SalesOrderController::get_total_so($data->code,1);
            $total_so = $info_so[0]['amount'];
            $total_so_waiting = $info_so_waiting[0]['amount'];
            $curr_so = $info_so[0]['curr'];

            $tmp = [];
            $tmp['project_no'] = $data->project_no;
            $tmp['code'] = $data->code;
            $tmp['project_name'] = $data->name;
            $tmp['status'] = $data->inactive;
            $tmp['customer'] = $data->customer_name;
            $tmp['budget'] = $total_budget;
            $tmp['project_value'] = $project_value;
            $tmp['rab'] = $total_rab;
            $tmp['cost'] = $cost;
            $tmp['total_so'] = ($total_so == null) ? 0 : $total_so;
            $tmp['total_so_waiting'] = ($total_so_waiting == null) ? 0 : $total_so_waiting;
            $tmp['curr_so'] = $curr_so;
            $tmp['cust_po'] = $data->poreference;
            $tmp['reason_inactive'] = $data->cost_over;


            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function project($project_no)
    {
        $response = [];
        $sql = QueryProjectList::get_project($project_no);

        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {

            $tmp = [];
            $tmp['project_no'] = $data->project_no;
            $tmp['project_code'] = $data->code;
            $tmp['division_id'] = $data->division_id;
            $tmp['project_type_id'] = $data->project_type_id;
            $tmp['project_name'] = $data->project_name;
            $tmp['name_external'] = $data->name_external;
            $tmp['description'] = $data->description;
            $tmp['debtor_no'] = $data->debtor_no;
            $tmp['sow_id'] = $data->sow_id;
            $tmp['site_id'] = $data->site_id;
            $tmp['site_no'] = $data->site_no;
            $tmp['person_id'] = $data->person_id;
            $tmp['area_id'] = $data->area_id;
            $tmp['contract_no'] = $data->contract_no;
            $tmp['amandement_no'] = $data->amandement_no;
            $tmp['poreference'] = $data->poreference;
            $tmp['po_category_id'] = $data->po_category_id;
            $tmp['po_date'] = $data->po_date;
            $tmp['start_date'] = $data->start_date;
            $tmp['end_date'] = $data->end_date;
            $tmp['project_year'] = $data->project_year;
            $tmp['payment_term'] = $data->term_id;
            $tmp['curr_code'] = $data->curr_code;
            $tmp['amount'] = $data->amount;
            $tmp['po_version'] = $data->po_version;
            $tmp['revised_amount'] = $data->revised_amount;
            $tmp['final_non'] = $data->final_non;
            $tmp['po_status_id'] = $data->po_status_id;
            $tmp['project_status'] = $data->project_status_id;
            $tmp['is_project'] = $data->is_project;
            $tmp['is_site'] = $data->site_flag;
            $tmp['is_bucket'] = $data->is_bucket;
            $tmp['inactive'] = $data->inactive;
            $tmp['auto_inactive'] = $data->auto_inactive;
            $tmp['parent_project_code'] = $data->parent_project_code;
            $tmp['management_fee_id'] = $data->management_fee_id;
            $tmp['management_fee_rate'] = $data->mf_rate;
            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function project_code_exist($code)
    {
        $sql = "SELECT code FROM 0_projects WHERE code ='$code'";
        $query = DB::select(DB::raw($sql));

        foreach ($query as $data) {
            $is_exist = $data->code;
        }

        if (empty($is_exist)) {
            return 1;
        } else if (!empty($is_exist)) {
            return 0;
        }
    }

    public static function add_new_project($myArr)
    {
        $params = $myArr['params'];

        // $code = $params['code'];
        $description = $params['description'];
        $description_external = $params['description_external'];
        $long_description = $params['long_description'];
        $debtor_no = $params['debtor_no'];
        $po_reference = $params['po_reference'];
        $amandement_no = $params['amandement_no'];
        $term_id = $params['term_id'];
        $contract_no = $params['contract_no'];
        $po_date = $params['po_date'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $curr_code = $params['curr_code'];
        $amount = $params['amount'];
        $area_id = $params['area_id'];
        $person_id = $params['person_id'];
        $project_type_id = $params['project_type_id'];
        $site_id = $params['site_id'];
        $site_no = $params['site_no'];
        $po_status_id = $params['po_status_id'];
        $project_status_id = $params['project_status_id'];
        $p_year = $params['p_year'];
        $sow_id = $params['sow_id'];
        $po_category_id = $params['po_category_id'];
        $division_id = $params['division_id'];
        $site = $params['site'];
        $is_bucket = $params['is_bucket'];
        $is_project = $params['is_project'];
        $parent_project_code = $params['parent_project_code'];
        $fee_id = $params['fee_id'];
        $user_id = $myArr['user_id'];
        $deffered_cost = $params['deffered_cost'];

        // $code_exist = self::project_code_exist($code);

        //auto generate project code
        $year_x = $p_year;
        $prefix = '20';
        $str = $year_x;
        $str = preg_replace('/^' . preg_quote($prefix, '/') . '/', '', $str);
        $debtor_no_x = sprintf("%03d", $debtor_no);
        // $division_id_x = 1;
        $division_x =  DB::table('0_hrm_divisions')->where('division_id', $division_id)->first();
        $area_id_x = $area_id;
        $area_x =  DB::table('0_project_area')->where('area_id', $area_id_x)->first();
        $auto_number_x = sprintf("%02d", 1);
        $similar_code = $str . $division_x->abbr . $debtor_no_x . '-' . $area_x->code;

        // find similar code
        $similar = DB::table('0_projects')->where('code', 'like', '%' . $similar_code . '%')->orderBy('project_no', 'desc')->first();
        if (!empty($similar->code)) {
            $project_code = ++$similar->code;
        } else {
            $project_code = $str . $division_x->abbr . $debtor_no_x . '-' . $area_x->code . $auto_number_x;
        }

        if ($division_id == 1 || $division_id == 2 || $division_id == 16 || $division_id == 17) {

            DB::beginTransaction();

            try {

                DB::table('0_projects')
                    ->insert(array(
                        'code' => $project_code,
                        'name' => $description,
                        'name_external' => $description_external,
                        'description' => $long_description,
                        'debtor_no' => $debtor_no,
                        'poreference' => $po_reference,
                        'amandement_no' => $amandement_no,
                        'term_id' => $term_id,
                        'contract_no' => $contract_no,
                        'po_date' => $po_date,
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'curr_code' => $curr_code,
                        'amount' => $amount,
                        'area_id' => $area_id,
                        'person_id' => $person_id,
                        'project_type_id' => $project_type_id,
                        'site_id' => $site_id,
                        'site_no' => $site_no,
                        'po_status_id' => $po_status_id,
                        'project_status_id' => $project_status_id,
                        'project_year' => $p_year,
                        'sow_id' => $sow_id,
                        'po_category_id' => $po_category_id,
                        'division_id' => $division_id,
                        'site_flag' => $site,
                        'is_bucket' => $is_bucket,
                        'parent_project_code' => $parent_project_code,
                        'management_fee_id' => $fee_id,
                        'deffered_cost' => $deffered_cost,
                        'created_date' => Carbon::now(),
                        'created_by' => $user_id
                    ));

                // Commit Transaction
                DB::commit();

                // Semua proses benar

                return response()->json([
                    'success' => true,
                    'data' => $project_code
                ]);
            } catch (Exception $e) {
                // Rollback Transaction
                DB::rollback();
            }
        } else if ($division_id == 3) {
            // divisi TI

            DB::beginTransaction();

            try {

                DB::table('0_projects')
                    ->insert(array(
                        'code' => $project_code,
                        'name' => $description,
                        'name_external' => $description_external,
                        'description' => $long_description,
                        'debtor_no' => $debtor_no,
                        'division_id' => $division_id,
                        'project_type_id' => $project_type_id,
                        'area_id' => $area_id,
                        'person_id' => $person_id,
                        'project_status_id' => $project_status_id,
                        'site_flag' => $site,
                        'management_fee_id' => $fee_id,
                        'deffered_cost' => $deffered_cost,
                        'created_date' => Carbon::now(),
                        'created_by' => $user_id
                    ));

                // Commit Transaction
                DB::commit();

                // Semua proses benar

                return response()->json([
                    'success' => true,
                    'data' => $project_code
                ]);
            } catch (Exception $e) {
                // Rollback Transaction
                DB::rollback();
            }
        } else {
            DB::beginTransaction();

            try {

                DB::table('0_projects')
                    ->insert(array(
                        'code' => $project_code,
                        'name' => $description,
                        'name_external' => $description_external,
                        'description' => $long_description,
                        'debtor_no' => $debtor_no,
                        'division_id' => $division_id,
                        'area_id' => $area_id,
                        'person_id' => $person_id,
                        'project_status_id' => $project_status_id,
                        'is_project' => $is_project,
                        'site_flag' => $site,
                        'management_fee_id' => $fee_id,
                        'deffered_cost' => $deffered_cost,
                        'created_date' => Carbon::now(),
                        'created_by' => $user_id
                    ));

                // Commit Transaction
                DB::commit();

                // Semua proses benar

                return response()->json([
                    'success' => true,
                    'data' => $project_code
                ]);
            } catch (Exception $e) {
                // Rollback Transaction
                DB::rollback();
            }
        }
    }

    public static function update_project($myArr, $project_no)
    {
        $params = $myArr['params'];

        $code = $params['code'];
        $description = $params['description'];
        $description_external = $params['description_external'];
        $long_description = $params['long_description'];
        $debtor_no = $params['debtor_no'];
        $po_reference = $params['po_reference'];
        $amandement_no = $params['amandement_no'];
        $term_id = $params['term_id'];
        $contract_no = $params['contract_no'];
        $po_date = $params['po_date'];
        $start_date = $params['start_date'];
        $end_date = $params['end_date'];
        $curr_code = $params['curr_code'];
        $amount = $params['amount'];
        $area_id = $params['area_id'];
        $person_id = $params['person_id'];
        $project_type_id = $params['project_type_id'];
        $site_id = $params['site_id'];
        $site_no = $params['site_no'];
        $po_status_id = $params['po_status_id'];
        $project_status_id = $params['project_status_id'];
        $p_year = $params['p_year'];
        $sow_id = $params['sow_id'];
        $po_category_id = $params['po_category_id'];
        $po_version = $params['po_version'];
        $revised_amount = $params['revised_amount'];
        $final_non = $params['final_non'];
        $division_id = $params['division_id'];
        $site = $params['site'];
        $is_bucket = $params['is_bucket'];
        $parent_project_code = $params['parent_project_code'];
        $fee_id = $params['fee_id'];
        $user_id = $myArr['user_id'];
        $deffered_cost = $params['deffered_cost'];
        $inactive = ($params['project_status_id'] == 3) ? 1 : 0;



        if ($division_id == 1 || $division_id == 2 || $division_id == 16 || $division_id == 17) {
            DB::beginTransaction();

            try {

                DB::table('0_projects')->where('project_no', $project_no)
                    ->update(array(
                        'name' => $description,
                        'name_external' => $description_external,
                        'description' => $long_description,
                        'debtor_no' => $debtor_no,
                        'poreference' => $po_reference,
                        'amandement_no' => $amandement_no,
                        'term_id' => $term_id,
                        'contract_no' => $contract_no,
                        'po_date' => $po_date,
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'curr_code' => $curr_code,
                        'amount' => $amount,
                        'area_id' => $area_id,
                        'person_id' => $person_id,
                        'po_version' => $po_version,
                        'revised_amount' => $revised_amount,
                        'final_non' => $final_non,
                        'project_type_id' => $project_type_id,
                        'site_id' => $site_id,
                        'site_no' => $site_no,
                        'po_status_id' => $po_status_id,
                        'project_status_id' => $project_status_id,
                        'project_year' => $p_year,
                        'sow_id' => $sow_id,
                        'po_category_id' => $po_category_id,
                        'division_id' => $division_id,
                        'site_flag' => $site,
                        'is_bucket' => $is_bucket,
                        'parent_project_code' => $parent_project_code,
                        'management_fee_id' => $fee_id,
                        'inactive' => $inactive,
                        'deffered_cost' => $deffered_cost,
                        'updated_date' => Carbon::now(),
                        'updated_by' => $user_id
                    ));

                // Commit Transaction
                DB::commit();

                // Semua proses benar
                $msg = "Project_" . $code . "_Updated";
                return response()->json([
                    'success' => true,
                    'data' => $code
                ]);
            } catch (Exception $e) {
                // Rollback Transaction
                DB::rollback();
            }
        } else {
            DB::beginTransaction();

            try {

                DB::table('0_projects')->where('project_no', $project_no)
                    ->update(array(
                        'name' => $description,
                        'name_external' => $description_external,
                        'description' => $long_description,
                        'area_id' => $area_id,
                        'person_id' => $person_id,
                        'project_type_id' => $project_type_id,
                        'project_status_id' => $project_status_id,
                        'po_category_id' => $po_category_id,
                        'division_id' => $division_id,
                        'site_flag' => $site,
                        'management_fee_id' => $fee_id,
                        'inactive' => $inactive,
                        'deffered_cost' => $deffered_cost,
                        'updated_date' => Carbon::now(),
                        'updated_by' => $user_id
                    ));

                // Commit Transaction
                DB::commit();

                // Semua proses benar
                $msg = "Project_" . $code . "_Updated";
                return response()->json([
                    'success' => true,
                    'data' => $code
                ]);
            } catch (Exception $e) {
                // Rollback Transaction
                DB::rollback();
            }
        }
    }

    public static function delete_project($project_no)
    {
        DB::beginTransaction();

        try {

            DB::table('0_projects')->where('project_no', $project_no)->delete();

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

    public static function get_project_division_name($division_id)
    {
        $sql = DB::table('0_hrm_divisions')->where('division_id', $division_id)
            ->first();

        return $sql->name;
    }
    public static function get_project_info($code)
    {
        $sql = DB::table('0_projects')->where('code', $code)
            ->first();

        return $sql;
    }
    public static function get_id($emp_id)
    {
        $sql = DB::table('0_hrm_employees')->where('emp_id', $emp_id)
            ->first();

        return $sql;
    }

    public static function get_project_code($project_no)
    {
        $sql = DB::table('0_projects')->where('project_no', $project_no)
            ->first();

        return $sql->code;
    }

    public static function test($myArr, $project_no)
    {
        if ($myArr['params']['debtor_no'] == 27) {
            return "ok";
        } else if ($myArr['params']['debtor_no'] == 21) {
            return "ok2";
        } else {
            return "tidak ok";
        }
    }

    public static function export_project_list()
    {
        $filename = "Projects";
        return Excel::download(new ListProjectExport, "$filename.xlsx");
    }

    public static function project_manager_list($pm_name)
    {
        $response = [];

        $sql = QueryProjectList::get_pm_sql($pm_name);

        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {
            $tmp = [];
            $tmp['person_id'] = $data->person_id;
            $tmp['name'] = $data->name;
            $tmp['email'] = $data->email;
            $tmp['division_id'] = $data->division_id;
            $tmp['division_name'] = $data->division_name;
            $tmp['status_id'] = $data->inactive;
            $tmp['status_name'] = $data->status_name;
            array_push($response, $tmp);
        }

        return $response;
    }

    public static function add_pm($myArr)
    {
        $params = $myArr['params'];
        DB::beginTransaction();
        try {

            DB::table('0_members')
                ->insert(array(
                    'name' => $params['name'],
                    'email' => $params['email'],
                    'group_id' => 5,
                    'emp_id' => $params['emp_id'],
                    'division_id' => $params['division_id']
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

    public static function update_pm($myArr)
    {
        $params = $myArr['params'];
        DB::beginTransaction();
        try {

            DB::table('0_members')->where('person_id', $params['person_id'])
                ->update(array(
                    'name' => $params['name'],
                    'email' => $params['email'],
                    'group_id' => 5,
                    'emp_id' => $params['emp_id'],
                    'division_id' => $params['division_id'],
                    'inactive' => $params['inactive']
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
