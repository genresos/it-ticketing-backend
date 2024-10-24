<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use JWTAuth;
use Auth;
use Illuminate\Support\Facades\DB;
use Dingo\Api\Routing\Helpers;
use Carbon\Carbon;
use App\Employees;
use App\Query\QueryEmployees;
use URL;
use SiteHelper;

class EmployeesController extends Controller
{
    public static function get_employees($emp_id)
    {

        $sql = DB::table('0_hrm_employees')->where('emp_id', $emp_id)->first();

        return $sql->name;
    }

    public static function show_employees(
        $emp_id,
        $emp_name,
        $client_id,
        $division_id,
        $position_id,
        $location_id,
        $type_id,
        $status_id
    ) {
        $response = [];

        $sql = QueryEmployees::employees(
            $emp_id,
            $emp_name,
            $client_id,
            $division_id,
            $position_id,
            $location_id,
            $type_id,
            $status_id
        );

        $exec_sql = DB::select(DB::raw($sql));

        foreach ($exec_sql as $data) {

            $tmp = [];
            $tmp['company'] = $data->company_name;
            $tmp['emp_no'] = $data->id;
            $tmp['emp_id'] = $data->emp_id;
            $tmp['emp_name'] = $data->emp_name;
            $tmp['join_date'] = $data->join_date;
            $tmp['division_name'] = $data->division_name;
            $tmp['position_name'] = $data->position_name;
            $tmp['location_name'] = $data->location_name;
            $tmp['type_name'] = $data->type_name;
            $tmp['status'] = $data->inactive;

            array_push($response, $tmp);
        }

        return $response;
    }

    public static function show_employees_details(
        $emp_no
    ) {
        $response = [];

        $sql_emp = QueryEmployees::employee_details(
            $emp_no
        );
        $data_emp = DB::select(DB::raw($sql_emp));

        foreach ($data_emp as $emp) {

            $tmp = [];
            $tmp['company'] = $emp->company_name;
            $tmp['emp_id'] = $emp->emp_id;
            $tmp['emp_name'] = $emp->emp_name;
            $tmp['join_date'] = $emp->join_date;
            $tmp['division_name'] = $emp->division_name;
            $tmp['position_name'] = $emp->position_name;
            $tmp['location_name'] = $emp->location_name;
            $tmp['type_name'] = $emp->type_name;
            $tmp['status'] = $emp->inactive;

            $response['employee'] = $tmp;
        }

        $employee_hardware = self::show_employees_detail_hardware($emp_no);
        $response['employee_hardware'] = $employee_hardware;

        $employee_tools = self::show_employees_detail_tools($emp_no);
        $response['employee_tools'] = $employee_tools;

        $employee_cashadvance = self::show_employees_detail_ca($emp_no);
        $response['employee_cashadvance'] = $employee_cashadvance;

        return $response;
    }


    public static function show_employees_detail_hardware(
        $emp_no
    ) {
        $response = [];
        $info_emp = DB::table('0_hrm_employees')->where('id', $emp_no)->first();
        $emp_id = $info_emp->emp_id;
        $sql_emp_hardware = QueryEmployees::hardware_issue(
            $emp_id
        );

        $data_emp_hardware = DB::select(DB::raw($sql_emp_hardware));

        foreach ($data_emp_hardware as $emp_hardware) {

            $tmp = [];
            $tmp['#'] = $emp_hardware->issue_id;
            $tmp['doc_no'] = $emp_hardware->doc_no;
            $tmp['status'] = $emp_hardware->status_name;
            $tmp['trx_date'] = $emp_hardware->trx_date;
            $tmp['closing_date'] = $emp_hardware->close_date;
            $tmp['asset_no'] = $emp_hardware->asset_name;
            $tmp['asset_name'] = $emp_hardware->group_name;
            $tmp['description'] = $emp_hardware->issue_description;
            $tmp['accesories'] = $emp_hardware->accesories;
            $tmp['assignee_id'] = $emp_hardware->emp_id;
            $tmp['assignee_to'] = $emp_hardware->assignee;
            $tmp['project_code'] = $emp_hardware->project_code;
            $tmp['creator'] = $emp_hardware->real_name;

            array_push($response, $tmp);
        }
        return $response;
    }


    public static function show_employees_detail_tools(
        $emp_no
    ) {
        $response = [];
        $info_emp = DB::table('0_hrm_employees')->where('id', $emp_no)->first();
        $emp_id = $info_emp->emp_id;
        $sql_emp_tools = QueryEmployees::tools_issue(
            $emp_id
        );

        $data_emp_tools = DB::select(DB::raw($sql_emp_tools));

        foreach ($data_emp_tools as $emp_tools) {

            $tmp = [];
            $tmp['#'] = $emp_tools->issue_id;
            $tmp['doc_no'] = $emp_tools->doc_no;
            $tmp['status'] = $emp_tools->status_name;
            $tmp['trx_date'] = $emp_tools->trx_date;
            $tmp['closing_date'] = $emp_tools->close_date;
            $tmp['asset_no'] = $emp_tools->asset_name;
            $tmp['asset_name'] = $emp_tools->group_name;
            $tmp['description'] = $emp_tools->issue_description;
            $tmp['accesories'] = $emp_tools->accesories;
            $tmp['assignee_id'] = $emp_tools->emp_id;
            $tmp['assignee_to'] = $emp_tools->assignee;
            $tmp['project_code'] = $emp_tools->project_code;
            $tmp['creator'] = $emp_tools->real_name;

            array_push($response, $tmp);
        }
        return $response;
    }

    public static function show_employees_detail_ca(
        $emp_no
    ) {
        $response = [];
        $info_emp = DB::table('0_hrm_employees')->where('id', $emp_no)->first();
        $emp_id = $info_emp->emp_id;

        $sql_emp_ca = QueryEmployees::cashadvance_issue(
            $emp_id
        );

        $data_emp_ca = DB::select(DB::raw($sql_emp_ca));

        foreach ($data_emp_ca as $emp_ca) {

            $tmp = [];
            $tmp['#'] = $emp_ca->trans_no;
            $tmp['ca_type'] = $emp_ca->doc_type_name;
            $tmp['doc_no'] = $emp_ca->reference;
            $tmp['tran_date'] = $emp_ca->tran_date;
            $tmp['emp_id'] = $emp_ca->emp_id;
            $tmp['emp_name'] = $emp_ca->EmployeeName;
            $tmp['division'] = $emp_ca->division_name;
            $tmp['amount'] = $emp_ca->amount;
            $tmp['approved_amount'] = $emp_ca->approval_amount;
            $tmp['release_amount'] = $emp_ca->release_amount;
            $tmp['release_date'] = $emp_ca->release_date;
            $tmp['stl_doc_no'] = $emp_ca->stl_reference;
            $tmp['stl_date'] = $emp_ca->stl_date;
            $tmp['stl_amount'] = $emp_ca->settlement_amount;
            $tmp['stl_approved_amount'] = $emp_ca->settlement_approval_amount;
            $tmp['cash_in_out'] = $emp_ca->allocate_to_cash;
            $tmp['sal_deduction'] = $emp_ca->allocate_ear_amount;
            $tmp['slr_deduction_date'] = $emp_ca->allocate_ear_date;
            $tmp['ca_status'] = $emp_ca->approval;

            array_push($response, $tmp);
        }
        return $response;
    }

    public static function create_ec($myArr)
    {
        $params = $myArr['params'];
        $user_id = $myArr['user_id'];
        $detail_emp = DB::connection('pgsql')->table('hrd_employee')->where('id', $params['emp_no'])->first();
        $user_division = DB::table('0_hrm_employees')->where('id', $params['emp_no'])->first();
        DB::beginTransaction();
        try {

            DB::table('0_hrm_exit_clearences')
                ->insert(array(
                    'emp_name' => $detail_emp->employee_name,
                    'emp_id' => $detail_emp->employee_id,
                    'division_id' => $user_division->division_id,
                    'last_position' => $detail_emp->position_code,
                    'contract_type' => $params['contract_type'],
                    'join_date' => $detail_emp->join_date,
                    'due_date' => $detail_emp->due_date,
                    'last_date' => $params['last_date'],
                    'base' => $params['base'],
                    'created_by' => $user_id,
                    'created_at' => Carbon::now()
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

    public static function show_ec(
        $need_check,
        $status,
        $from_date,
        $to_date,
        $emp_id,
        $emp_name
    ) {
        $response = [];
        $sql = QueryEmployees::show_ec(
            $need_check,
            $status,
            $from_date,
            $to_date,
            $emp_id,
            $emp_name
        );

        $data = DB::select(DB::raw($sql));

        foreach ($data as $ec) {

            if ($ec->status_id == 3) {
                $deduction = $ec->deduction;
            } else {
                $deduction = 0;
            }

            $division_from_devosa =
                DB::connection('pgsql')->table('hrd_employee')->where('employee_id', $ec->emp_id)->first();

            $division = (empty($division_from_devosa) ? $ec->division_name : $division_from_devosa->division_code);
            $tmp = [];
            $tmp['id'] = $ec->id;
            $tmp['emp_id'] = $ec->emp_id;
            $tmp['emp_name'] = $ec->emp_name;
            $tmp['division_name'] = $division;
            $tmp['level_id'] = $ec->level_id;
            $tmp['level_name'] = $ec->level_name;
            $tmp['join_date'] = $ec->join_date;
            $tmp['due_date'] = $ec->due_date;
            $tmp['last_date'] = $ec->last_date;
            $tmp['reason_id'] = $ec->reason_id;
            $tmp['reason'] = $ec->reason;
            $tmp['pm_name'] = $ec->pm_name;
            $tmp['ec_status'] = $ec->ec_status;
            $tmp['ec_deduction'] = $deduction;
            $tmp['dept_terkait'] = ($ec->dept_terkait == null) ? 0 : $ec->dept_terkait;
            $tmp['dept_head_terkait'] = ($ec->dept_head_terkait == null) ? 0 : $ec->dept_head_terkait;
            $tmp['am_admin'] = ($ec->am_admin == null) ? 0 : $ec->am_admin;
            $tmp['ict_admin'] = ($ec->ict_admin == null) ? 0 : $ec->ict_admin;
            $tmp['ict_head'] = ($ec->ict_head == null) ? 0 : $ec->ict_head;
            $tmp['am_dept'] = ($ec->am_dept == null) ? 0 : $ec->am_dept;
            $tmp['ga_admin'] = ($ec->ga_admin == null) ? 0 : $ec->ga_admin;
            $tmp['ga_dept'] = ($ec->ga_dept == null) ? 0 : $ec->ga_dept;
            $tmp['fa_admin'] = ($ec->fa_admin == null) ? 0 : $ec->fa_admin;
            $tmp['fa_dept'] = ($ec->fa_dept == null) ? 0 : $ec->fa_dept;
            $tmp['pc_admin'] = ($ec->pc_admin == null) ? 0 : $ec->pc_admin;
            $tmp['pc_dept'] = ($ec->pc_dept == null) ? 0 : $ec->pc_dept;
            $tmp['hr_admin'] = ($ec->hr_admin == null) ? 0 : $ec->hr_admin;
            $tmp['hr_recruitment'] = ($ec->hr_rec == null) ? 0 : $ec->hr_rec;
            $tmp['hr_payroll'] = ($ec->hr_payroll == null) ? 0 : $ec->hr_payroll;
            $tmp['hr_dept'] = ($ec->hr_dept == null) ? 0 : $ec->hr_dept;
            $tmp['fa_dir'] = ($ec->fa_dir == null) ? 0 : $ec->fa_dir;
            $tmp['created_at'] = $ec->created_at;
            $tmp['created_by'] = $ec->created_by;

            array_push($response, $tmp);
        }
        return $response;
    }

    public static function summary_exit_clearences(
        $need_check,
        $status,
        $from_date,
        $to_date,
        $emp_id,
        $emp_name
    ) {
        $response = [];
        $sql = QueryEmployees::summary_exit_clearences(
            $need_check,
            $status,
            $from_date,
            $to_date,
            $emp_id,
            $emp_name
        );

        $data = DB::select(DB::raw($sql));

        foreach ($data as $ec) {

            if ($ec->status_id == 3) {
                $deduction = $ec->deduction;
            } else {
                $deduction = 0;
            }

            $division_from_devosa =
                DB::connection('pgsql')->table('hrd_employee')->where('employee_id', $ec->emp_id)->first();

            $division = (empty($division_from_devosa) ? $ec->division_name : $division_from_devosa->division_code);
            $tmp = [];
            $tmp['id'] = $ec->id;
            $tmp['emp_id'] = $ec->emp_id;
            $tmp['emp_name'] = $ec->emp_name;
            $tmp['division_name'] = $division;
            $tmp['level_id'] = $ec->level_id;
            $tmp['level_name'] = $ec->level_name;
            $tmp['join_date'] = $ec->join_date;
            $tmp['due_date'] = $ec->due_date;
            $tmp['last_date'] = $ec->last_date;
            $tmp['reason_id'] = $ec->reason_id;
            $tmp['reason'] = $ec->reason;
            $tmp['pm_name'] = $ec->pm_name;
            $tmp['ec_status'] = $ec->ec_status;
            $tmp['ec_deduction'] = $deduction;
            $tmp['dept_terkait'] = ($ec->dept_terkait == null) ? 0 : $ec->dept_terkait;
            $tmp['dept_head_terkait'] = ($ec->dept_head_terkait == null) ? 0 : $ec->dept_head_terkait;
            $tmp['am_admin'] = ($ec->am_admin == null) ? '' : $ec->am_admin;
            $tmp['am_dept'] = ($ec->am_dept == null) ? '' : $ec->am_dept;
            $tmp['ga_admin'] = ($ec->ga_admin == null) ? '' : $ec->ga_admin;
            $tmp['ga_dept'] = ($ec->ga_dept == null) ? '' : $ec->ga_dept;
            $tmp['fa_admin'] = ($ec->fa_admin == null) ? '' : $ec->fa_admin;
            $tmp['fa_dept'] = ($ec->fa_dept == null) ? '' : $ec->fa_dept;
            $tmp['pc_admin'] = ($ec->pc_admin == null) ? '' : $ec->pc_admin;
            $tmp['pc_dept'] = ($ec->pc_dept == null) ? '' : $ec->pc_dept;
            $tmp['hr_admin'] = ($ec->hr_admin == null) ? '' : $ec->hr_admin;
            $tmp['hr_recruitment'] = ($ec->hr_rec == null) ? '' : $ec->hr_rec;
            $tmp['hr_payroll'] = ($ec->hr_payroll == null) ? '' : $ec->hr_payroll;
            $tmp['hr_dept'] = ($ec->hr_dept == null) ? '' : $ec->hr_dept;
            $tmp['fa_dir'] = ($ec->fa_dir == null) ? '' : $ec->fa_dir;
            $tmp['created_at'] = $ec->created_at;
            $tmp['created_by'] = $ec->created_by;

            array_push($response, $tmp);
        }
        return $response;
    }

    public static function ec_need_approve(
        $user_level,
        $user_person_id,
        $user_division
    ) {
        $response = [];
        $sql = QueryEmployees::ec_need_approve(
            $user_level,
            $user_person_id,
            $user_division
        );

        $data = DB::select(DB::raw($sql));

        foreach ($data as $ec) {
            $division_from_devosa =
                DB::connection('pgsql')->table('hrd_employee')->where('employee_id', $ec->emp_id)->first();

            $division = (empty($division_from_devosa) ? $ec->division_name : $division_from_devosa->division_code);
            $department = (empty($division_from_devosa) ? $ec->division_name : $division_from_devosa->department_code);

            $tmp = [];
            $tmp['id'] = $ec->id;
            $tmp['emp_id'] = $ec->emp_id;
            $tmp['emp_name'] = $ec->emp_name;
            $tmp['division_name'] = $division;
            $tmp['department_name'] = $department;
            $tmp['project_manager'] = $ec->pm_name;
            $tmp['level_id'] = $ec->level_id;
            $tmp['level_name'] = $ec->level_name;
            $tmp['join_date'] = $ec->join_date;
            $tmp['due_date'] = $ec->due_date;
            $tmp['last_date'] = $ec->last_date;
            $tmp['reason_id'] = $ec->reason_id;
            $tmp['reason'] = $ec->reason;
            $tmp['dept_terkait'] = ($ec->dept_terkait == null) ? 0 : $ec->dept_terkait;
            $tmp['dept_head_terkait'] = ($ec->dept_head_terkait == null) ? 0 : $ec->dept_head_terkait;
            $tmp['am_admin'] = ($ec->am_admin == null) ? 0 : $ec->am_admin;
            $tmp['am_dept'] = ($ec->am_dept == null) ? 0 : $ec->am_dept;
            $tmp['ict_admin'] = ($ec->ict_admin == null) ? 0 : $ec->ict_admin;
            $tmp['ict_head'] = ($ec->ict_head == null) ? 0 : $ec->ict_head;
            $tmp['ga_admin'] = ($ec->ga_admin == null) ? 0 : $ec->ga_admin;
            $tmp['ga_dept'] = ($ec->ga_dept == null) ? 0 : $ec->ga_dept;
            $tmp['fa_admin'] = ($ec->fa_admin == null) ? 0 : $ec->fa_admin;
            $tmp['fa_dept'] = ($ec->fa_dept == null) ? 0 : $ec->fa_dept;
            $tmp['pc_admin'] = ($ec->pc_admin == null) ? 0 : $ec->pc_admin;
            $tmp['pc_dept'] = ($ec->pc_dept == null) ? 0 : $ec->pc_dept;
            $tmp['hr_admin'] = ($ec->hr_admin == null) ? 0 : $ec->hr_admin;
            $tmp['hr_recruitment'] = ($ec->hr_rec == null) ? 0 : $ec->hr_rec;
            $tmp['hr_payroll'] = ($ec->hr_payroll == null) ? 0 : $ec->hr_payroll;
            $tmp['hr_dept'] = ($ec->hr_dept == null) ? 0 : $ec->hr_dept;
            $tmp['fa_dir'] = ($ec->fa_dir == null) ? 0 : $ec->fa_dir;
            $total_deduction = DB::table('0_hrm_ec_history')->where('ec_id', $ec->id)->sum('deduction');
            $tmp['deduction'] = ($total_deduction == null) ? 0 : $total_deduction;
            $tmp['created_at'] = $ec->created_at;

            array_push($response, $tmp);
        }
        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public static function ec_approve($myArr)
    {
        $params = $myArr['params'];
        $ec_id = $params['id'];
        $deduction = empty($params['deduction ']) ? 0 : $params['deduction '];
        $user_id = $myArr['user_id'];
        $user_level = $myArr['user_level'];
        $person_id = $myArr['person_id'];
        $user_division = $myArr['division_id'];
        $pending_layer1 = ($params['status'] == 4) ? 0 : 1;
        $pending_layer2 = ($params['status'] == 4) ? 1 : 2;

        $info_ec =
            DB::table('0_hrm_exit_clearences as ec')
            ->leftJoin('0_hrm_divisions as d', 'd.division_id', '=', 'ec.division_id')
            ->where('ec.id', 3682)
            ->select('ec.dept_check', 'd.is_project', 'ec.person_id')
            ->first(); // Use first() to get a single record
        DB::beginTransaction();
        try {

            if ($user_level == 555 && $person_id == 0) {
                self::create_ec_history($params, 1, 1, $user_id);
                DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                    ->update(array(
                        'am_check' => $pending_layer1,
                        'status' => 1,
                        'deduction' => (empty($deduction)) ? 0 : $deduction,
                        'updated_by' => $user_id,
                        'updated_at' => Carbon::now()
                    ));
                self::create_ec_history($params, 10, 1, $user_id);
                DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                    ->update(array(
                        'ict_check' => $pending_layer1,
                        'status' => 1,
                        'deduction' => (empty($deduction)) ? 0 : $deduction,
                        'updated_by' => $user_id,
                        'updated_at' => Carbon::now()
                    ));
            } else if ($user_level == 777 && $person_id == 98) {
                self::create_ec_history($params, 10, 2, $user_id);
                DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                    ->update(array(
                        'ict_check' => $pending_layer2,
                        'status' => 1,
                        'deduction' => (empty($deduction)) ? 0 : $deduction,
                        'updated_by' => $user_id,
                        'updated_at' => Carbon::now()
                    ));
                /** KHUSUS OFFICE USER SEBAGAI ATASAN LANGSUNG */
                if ($info_ec->dept_check == 0 && $info_ec->is_project == 0 && $info_ec->person_id == $person_id) {
                    self::create_ec_history($params, 7, 1, $user_id);
                    DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                        ->update(array(
                            'dept_check' => $pending_layer1,
                            'status' => 1,
                            'deduction' => (empty($deduction)) ? 0 : $deduction,
                            'updated_by' => $user_id,
                            'updated_at' => Carbon::now()
                        ));
                    self::create_ec_history($params, 7, 2, $user_id);
                    DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                        ->update(array(
                            'dept_check' => $pending_layer2,
                            'status' => 1,
                            'deduction' => (empty($deduction)) ? 0 : $deduction,
                            'updated_by' => $user_id,
                            'updated_at' => Carbon::now()
                        ));
                }
                /**END */
            } else if ($user_level == 555 && $person_id == 207) {
                self::create_ec_history($params, 2, 2, $user_id);
                DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                    ->update(array(
                        'ga_check' => $pending_layer2,
                        'status' => 1,
                        'deduction' => (empty($deduction)) ? 0 : $deduction,
                        'updated_by' => $user_id,
                        'updated_at' => Carbon::now()
                    ));
            } else if ($user_level == 555 && $person_id == 158) {
                self::create_ec_history($params, 1, 2, $user_id);
                DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                    ->update(array(
                        'am_check' => $pending_layer2,
                        'status' => 1,
                        'deduction' => (empty($deduction)) ? 0 : $deduction,
                        'updated_by' => $user_id,
                        'updated_at' => Carbon::now()
                    ));
            } else if ($user_level == 111 && $person_id == 0 && $user_division == 0) {
                self::create_ec_history($params, 2, 1, $user_id);
                DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                    ->update(array(
                        'ga_check' => $pending_layer1,
                        'status' => 1,
                        'deduction' => (empty($deduction)) ? 0 : $deduction,
                        'updated_by' => $user_id,
                        'updated_at' => Carbon::now()
                    ));
            } else if ($user_level == 111 && $person_id == 0 && $user_division == 7) {
                self::create_ec_history($params, 3, 1, $user_id);
                DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                    ->update(array(
                        'fa_check' => $pending_layer1,
                        'status' => 1,
                        'deduction' => (empty($deduction)) ? 0 : $deduction,
                        'updated_by' => $user_id,
                        'updated_at' => Carbon::now()
                    ));
            } else if ($user_level == 4 && $person_id > 0 && $user_division == 7) {
                self::create_ec_history($params, 3, 2, $user_id);
                DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                    ->update(array(
                        'fa_check' => $pending_layer2,
                        'status' => 1,
                        'deduction' => (empty($deduction)) ? 0 : $deduction,
                        'updated_by' => $user_id,
                        'updated_at' => Carbon::now()
                    ));

                /** KHUSUS OFFICE USER SEBAGAI ATASAN LANGSUNG */
                if ($info_ec->dept_check == 0 && $info_ec->is_project == 0 && $info_ec->person_id == $person_id) {
                    self::create_ec_history($params, 7, 1, $user_id);
                    DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                        ->update(array(
                            'dept_check' => $pending_layer1,
                            'status' => 1,
                            'deduction' => (empty($deduction)) ? 0 : $deduction,
                            'updated_by' => $user_id,
                            'updated_at' => Carbon::now()
                        ));
                    self::create_ec_history($params, 7, 2, $user_id);
                    DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                        ->update(array(
                            'dept_check' => $pending_layer2,
                            'status' => 1,
                            'deduction' => (empty($deduction)) ? 0 : $deduction,
                            'updated_by' => $user_id,
                            'updated_at' => Carbon::now()
                        ));
                }
                /**END */
            } else if ($user_level == 4 && $person_id == 0 && $user_division == 25) {
                self::create_ec_history($params, 4, 1, $user_id);;
                DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                    ->update(array(
                        'pc_check' => $pending_layer1,
                        'status' => 1,
                        'deduction' => (empty($deduction)) ? 0 : $deduction,
                        'updated_by' => $user_id,
                        'updated_at' => Carbon::now()
                    ));
            } else if ($user_level == 4 && $person_id > 0 && $user_division == 25) {
                self::create_ec_history($params, 4, 2, $user_id);;
                DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                    ->update(array(
                        'pc_check' => $pending_layer2,
                        'status' => 1,
                        'deduction' => (empty($deduction)) ? 0 : $deduction,
                        'updated_by' => $user_id,
                        'updated_at' => Carbon::now()
                    ));
                /** KHUSUS OFFICE USER SEBAGAI ATASAN LANGSUNG */
                if ($info_ec->dept_check == 0 && $info_ec->is_project == 0 && $info_ec->person_id == $person_id) {
                    self::create_ec_history($params, 7, 1, $user_id);
                    DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                        ->update(array(
                            'dept_check' => $pending_layer1,
                            'status' => 1,
                            'deduction' => (empty($deduction)) ? 0 : $deduction,
                            'updated_by' => $user_id,
                            'updated_at' => Carbon::now()
                        ));
                    self::create_ec_history($params, 7, 2, $user_id);
                    DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                        ->update(array(
                            'dept_check' => $pending_layer2,
                            'status' => 1,
                            'deduction' => (empty($deduction)) ? 0 : $deduction,
                            'updated_by' => $user_id,
                            'updated_at' => Carbon::now()
                        ));
                }
                /**END */
            } else if ($user_level == 221 && $person_id == 0 && $user_division == 0) {
                self::create_ec_history($params, 9, 2, $user_id);
                DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                    ->update(array(
                        'rec_check' => $pending_layer2,
                        'status' => 1,
                        'deduction' => (empty($deduction)) ? 0 : $deduction,
                        'updated_by' => $user_id,
                        'updated_at' => Carbon::now()
                    ));
            } else if ($user_level == 222 && $person_id == 0 && $user_division == 0) {
                self::create_ec_history($params, 6, 1, $user_id);
                DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                    ->update(array(
                        'hr_check' => $pending_layer1,
                        'status' => 1,
                        'deduction' => (empty($deduction)) ? 0 : $deduction,
                        'updated_by' => $user_id,
                        'updated_at' => Carbon::now()
                    ));
            } else if ($user_level == 223 && $person_id == 0 && $user_division == 0) {
                self::create_ec_history($params, 8, 2, $user_id);
                DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                    ->update(array(
                        'payroll_check' => $pending_layer2,
                        'status' => 1,
                        'deduction' => (empty($deduction)) ? 0 : $deduction,
                        'updated_by' => $user_id,
                        'updated_at' => Carbon::now()
                    ));
            } else if ($user_level == 222 && $person_id > 0 && $user_division == 0) {

                self::create_ec_history($params, 6, 2, $user_id);
                DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                    ->update(array(
                        'dept_check' => $pending_layer2,
                        'status' => 1,
                        'deduction' => (empty($deduction)) ? 0 : $deduction,
                        'updated_by' => $user_id,
                        'updated_at' => Carbon::now()
                    ));
                /** KHUSUS OFFICE USER SEBAGAI ATASAN LANGSUNG */
                if ($info_ec->dept_check == 0 && $info_ec->is_project == 0 && $info_ec->person_id == $person_id) {
                    self::create_ec_history($params, 7, 1, $user_id);
                    DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                        ->update(array(
                            'dept_check' => $pending_layer1,
                            'status' => 1,
                            'deduction' => (empty($deduction)) ? 0 : $deduction,
                            'updated_by' => $user_id,
                            'updated_at' => Carbon::now()
                        ));
                    self::create_ec_history($params, 7, 2, $user_id);
                    DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                        ->update(array(
                            'dept_check' => $pending_layer2,
                            'status' => 1,
                            'deduction' => (empty($deduction)) ? 0 : $deduction,
                            'updated_by' => $user_id,
                            'updated_at' => Carbon::now()
                        ));
                }
                /**END */
            } else if ($user_level == 42) {

                /*
                *$user_level == 222 && $person_id > 0 && $user_division == 0
                *sementara pindah pak pandu (42)
                */
                self::create_ec_history($params, 7, 1, $user_id);
                self::create_ec_history($params, 7, 2, $user_id);
                DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                    ->update(array(
                        'dept_check' => $pending_layer2,
                        'status' => 1,
                        'deduction' => (empty($deduction)) ? 0 : $deduction,
                        'updated_by' => $user_id,
                        'updated_at' => Carbon::now()
                    ));
            } else if ($user_level == 1 && $person_id > 0) {
                self::create_ec_history($params, 7, 1, $user_id);
                DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                    ->update(array(
                        'dept_check' => $pending_layer1,
                        'status' => 1,
                        'deduction' => (empty($deduction)) ? 0 : $deduction,
                        'updated_by' => $user_id,
                        'updated_at' => Carbon::now()
                    ));

                /** KHUSUS OFFICE USER SEBAGAI ATASAN LANGSUNG */
                if ($info_ec->dept_check == 0 && $info_ec->is_project == 0 && $info_ec->person_id == $person_id) {
                    self::create_ec_history($params, 7, 2, $user_id);
                    DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                        ->update(array(
                            'dept_check' => $pending_layer2,
                            'status' => 1,
                            'deduction' => (empty($deduction)) ? 0 : $deduction,
                            'updated_by' => $user_id,
                            'updated_at' => Carbon::now()
                        ));
                }
                /**END */
            } else if ($user_level == 3 && $person_id > 0) {
                $pending_check = $info_ec->dept_check == 0 ? $pending_layer1 : $pending_layer2;
                self::create_ec_history($params, 7, $pending_check, $user_id);
                DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                    ->update(array(
                        'dept_check' => $pending_check,
                        'status' => 1,
                        'deduction' => (empty($deduction)) ? 0 : $deduction,
                        'updated_by' => $user_id,
                        'updated_at' => Carbon::now()
                    ));
            } else if ($user_level == 2 && $person_id > 0) {
                $pending_check = $info_ec->dept_check == 0 ? $pending_layer1 : $pending_layer2;
                self::create_ec_history($params, 7, $pending_check, $user_id);
                DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                    ->update(array(
                        'dept_check' => $pending_check,
                        'status' => 1,
                        'deduction' => (empty($deduction)) ? 0 : $deduction,
                        'updated_by' => $user_id,
                        'updated_at' => Carbon::now()
                    ));
            } else if ($user_level == 43) {
                // self::create_ec_history($params, 5, 2, $user_id);
                $total_deduction = DB::table('0_hrm_ec_history')->where('ec_id', $ec_id)->sum('deduction');
                DB::table('0_hrm_ec_history')
                    ->insert(array(
                        'ec_id' => $ec_id,
                        'type' => 5,
                        'pic' => 2,
                        'status' => $params['status'],
                        'remark' => $params['remark'],
                        'deduction' => (empty($deduction)) ? 0 : $deduction,
                        'user_id' => $user_id,
                        'created_at' => Carbon::now()
                    ));
                DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                    ->update(array(
                        'fa_dir_check' => ($params['status'] == 4) ? 0 : 2,
                        'status' => $params['status'],
                        'deduction' => (empty($deduction)) ? 0 : $deduction,
                        'updated_by' => $user_id,
                        'updated_at' => Carbon::now()
                    ));
            } else {
                return response()->json([
                    'msg' => 'Something wrong!'
                ], 403);
            }
            $total_deduction = DB::table('0_hrm_ec_history')->where('ec_id', $ec_id)->sum('deduction');

            DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                ->update(array(
                    'deduction' => $total_deduction
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

    protected static function create_ec_history($params, $type, $pic, $user_id)
    {
        DB::table('0_hrm_ec_history')
            ->insert(array(
                'ec_id' => $params['id'],
                'type' => $type,
                'pic' => $pic,
                'status' => $params['status'],
                'remark' => $params['remark'],
                'deduction' => $params['deduction'],
                'user_id' => $user_id,
                'created_at' => Carbon::now()
            ));
    }

    // protected static function update_ec_from_approval($id, $am_check, $ga_check, $fa_check, $pc_check, $hr_check, $fa_dir_check, $status, $deduction, $user_id)
    // {
    //     DB::table('0_hrm_exit_clearences')->where('id', $id)
    //         ->update(array(
    //             'status' => $status,
    //             'deduction' => $deduction,
    //             'updated_by' => $user_id,
    //             'updated_at' => Carbon::now()
    //         ));
    // }
    private static function history()
    {
        $array = array(
            "admin_am" => array(
                'name' => 'Admin AM',
                'type' => 1,
                'pic' => 1
            ),
            "dept_am" => array(
                'name' => 'Dept AM',
                'type' => 1,
                'pic' => 2
            ),
            "admin_ga" => array(
                'name' => 'Admin GA',
                'type' => 2,
                'pic' => 1
            ),
            "dept_ga" => array(
                'name' => 'Dept GA',
                'type' => 2,
                'pic' => 2
            ),
            "admin_fa" => array(
                'name' => 'Admin FA',
                'type' => 3,
                'pic' => 1
            ),
            "dept_fa" => array(
                'name' => 'Dept FA',
                'type' => 3,
                'pic' => 2
            ),
            "admin_pc" => array(
                'name' => 'Admin PC',
                'type' => 4,
                'pic' => 1
            ),
            "dept_pc" => array(
                'name' => 'Dept PC',
                'type' => 4,
                'pic' => 2
            ),
            "recruitment_hr" => array(
                'name' => 'HR Recruitment',
                'type' => 9,
                'pic' => 2
            ),
            "admin_hr" => array(
                'name' => 'Admin HR',
                'type' => 6,
                'pic' => 1
            ),
            "payroll_hr" => array(
                'name' => 'Payroll HR',
                'type' => 8,
                'pic' => 2
            ),
            "dept_hr" => array(
                'name' => 'Dept HR',
                'type' => 6,
                'pic' => 2
            ),
            "dept" => array(
                'name' => 'Dept Terkait',
                'type' => 7,
                'pic' => 1
            ),
            "dept_head" => array(
                'name' => 'Dept Head Terkait',
                'type' => 7,
                'pic' => 2
            ),
            "dir_fa" => array(
                'name' => 'Dir. Finance',
                'type' => 5,
                'pic' => 2
            )
        );

        return $array;
    }
    public static function ec_history($ec_id)
    {

        $response = [];
        $id = (empty($ec_id)) ? 0 : $ec_id;
        // $sql = QueryEmployees::sql_ec_history(
        //     $id
        // );

        foreach (self::history() as $ech => $val) {
            $sql = QueryEmployees::sql_ec_history(
                $id,
                $val['type'],
                $val['pic']
            );
            $tmp = [];
            $tmp['ec_id'] = $id;
            $tmp['pic_approval'] = $val['name'];
            if (!empty($sql)) {
                $tmp['pic_id'] = $sql->pic_id;
                $tmp['pic_name'] = $sql->name;
                $tmp['status'] = $sql->status;
                $tmp['remark'] = $sql->remark;
                $tmp['deduction'] = $sql->deduction;
                $tmp['approval_date'] = $sql->approval_date;
            } else {
                $tmp['pic_id'] = null;
                $tmp['pic_name'] = null;
                $tmp['status'] = null;
                $tmp['remark'] = null;
                $tmp['deduction'] = 0;
                $tmp['approval_date'] = null;
            }

            $get_attachment = DB::select(DB::raw(QueryEmployees::get_attachment_ec($id, $val['pic'])));
            if (empty($get_attachment)) {
                $tmp['attachments'] = null;
            }
            foreach ($get_attachment as $attachment) {
                $path = URL::to("/storage/hrm/ec/$attachment->filename");
                $attach = [];
                $attach['id'] = $attachment->id;
                $attach['path'] = $path;

                $tmp['attachments'][] = $attach;
            }
            array_push($response, $tmp);
        }

        $total_deduction = DB::table('0_hrm_ec_history')->where('ec_id', $ec_id)->sum('deduction');
        return response()->json([
            'success' => true,
            'data' => $response,
            'total_deduction' => $total_deduction

        ]);
    }


    public static function sync_employees()
    {
        $devosa =  DB::connection('pgsql')->table('hrd_employee')
            ->select(
                'id',
                'employee_id',
                'employee_id_2',
                'employee_name',
                'division_code',
                'join_date',
                'due_date',
                DB::raw("CASE
                    WHEN active = 1 THEN 0 ELSE 1 END AS active"),
                DB::raw("CASE
                    WHEN division_code = 'TI' THEN 2 
                    WHEN division_code = 'MS' THEN 3
                    WHEN division_code = 'CONST' THEN 1
                    WHEN division_code = 'SACME' THEN 1
                    WHEN division_code = 'HRGA' THEN 8 ELSE 0 END AS division_id")
            )
            ->orderBy('id', 'desc')
            ->get();

        DB::beginTransaction();
        try {

            foreach ($devosa as $data) {
                DB::table('0_hrm_employees')->updateOrInsert(
                    ['id' => $data->id],
                    array(
                        'id' => $data->id,
                        'emp_id' => $data->employee_id,
                        'name' => $data->employee_name,
                        'division_id' => $data->division_id,
                        'join_date' => $data->join_date,
                        'due_date' => $data->due_date,
                        'inactive' => $data->active
                    )
                );
            }
            // Commit Transaction
            DB::commit();

            return 'success';
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }

    public static function auto_generate_ec()
    {
        //-----------------sync name with Resign
        $query_resign = "UPDATE 0_hrm_exit_clearences SET emp_name = CONCAT(emp_name, '(Resign)')
                WHERE emp_name NOT LIKE '%Resign%'";
        DB::connection('mysql')->select(DB::raw($query_resign));
        //---------------------------------------------------------
        $sql = DB::connection('pgsql')->table('hrd_employee')->where('resign_date', '>', '2023-02-10')->orderBy('resign_date', 'desc')->get();
        foreach ($sql as $data) {

            $check_data = DB::table('0_hrm_exit_clearences')->where('emp_id', $data->employee_id)->where('deleted_by', 0)->first();
            $emp_division = DB::table('0_hrm_employees')->where('emp_id', $data->employee_id)->first();
            $divisi = empty($emp_division) ? 0 : $emp_division->division_id;
            $emp_contract_type = ($data->employee_status < 2) ? 'KONTRAK' : 'PERMANEN   ';
            if (empty($check_data) || $check_data == null) {
                DB::beginTransaction();
                try {
                    DB::table('0_hrm_exit_clearences')->insert(array(
                        'emp_name' => $data->employee_name,
                        'emp_id' => $data->employee_id,
                        'division_id' => $divisi,
                        'last_position' => $data->position_code,
                        'contract_type' => $emp_contract_type,
                        'join_date' => $data->join_date,
                        'due_date' => $data->due_date,
                        'last_date' => $data->resign_date,
                        'base' => $data->resign_reason,
                        'status' => 0,
                        'am_check' => 0,
                        'ga_check' => 0,
                        'fa_check' => 0,
                        'pc_check' => 0,
                        'hr_check' => 0,
                        'fa_dir_check' => 0,
                        'deduction' => 0,
                        'created_by' => 1,
                        'updated_by' => 1,
                        'updated_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ));
                    DB::commit();
                } catch (Exception $e) {
                    // Rollback Transaction
                    DB::rollback();
                }
            }
        }
        return response()->json([
            'success' => true
        ]);
    }

    public static function edit_pm_ec($myArr)
    {
        $params = $myArr['params'];
        $user_id = $myArr['user_id'];
        $info = DB::table('0_hrm_exit_clearences')->where('id', $params['id'])->first();
        DB::beginTransaction();
        try {

            if ($info->person_id == -1) { //untuk pertama kali assign pm
                DB::table('0_hrm_exit_clearences')->where('id', $params['id'])
                    ->update(array(
                        'person_id' => $params['person_id'],
                        'status' => 1,
                        'reason_id' => $params['reason_id'],
                        'updated_by' => $user_id,
                        'updated_at' => Carbon::now()
                    ));
            } else { //untuk edit pm
                DB::table('0_hrm_ec_history')->where('ec_id', $params['id'])->where('type', 7)->delete();
                DB::table('0_hrm_exit_clearences')->where('id', $params['id'])
                    ->update(array(
                        'person_id' => $params['person_id'],
                        'dept_check' => 0,
                        'status' => 1,
                        'reason_id' => $params['reason_id'],
                        'updated_by' => $user_id,
                        'updated_at' => Carbon::now()
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

    public static function reason_list($id)
    {
        $response = [];
        $sql = "SELECT id, name FROM 0_hrm_ec_reasons";

        if ($id != 0) {
            $sql .= " WHERE id = $id";
        }
        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {
            $tmp = [];
            $tmp['id'] = $data->id;
            $tmp['name'] = $data->name;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function export_ec($emp_id)
    {
        $response = [];
        $banned = array(' (Resign)');
        $exit_clearence = DB::table('0_hrm_exit_clearences')->where('emp_id', $emp_id)->where('person_id', '!=', '-1')->first();
        $emp_info = DB::connection('pgsql')->table('hrd_employee')->where('employee_id', $emp_id)->first();

        if ($emp_info->employee_status == 0) {
            $emp_status = 'Contract';
        } else if ($emp_info->employee_status == 1) {
            $emp_status = 'Contract';
        } else if ($emp_info->employee_status == 2) {
            $emp_status = 'Permanent';
        } else if ($emp_info->employee_status == 3) {
            $emp_status = 'Outsource';
        } else if ($emp_info->employee_status == 4) {
            $emp_status = 'Freelance';
        } else {
            $emp_status = '';
        }

        $emp = array(
            'employee_name' => str_ireplace($banned, '', $emp_info->employee_name),
            'employee_id' => $emp_info->employee_id,
            'branch_code' => $emp_info->branch_code,
            'division' => $emp_info->division_code . "/" . $emp_info->department_code,
            'last_position' => $emp_info->position_code,
            'working_status' => $emp_status,
            'join_date' => date_format(date_create($emp_info->join_date), "d-m-Y"),
            'due_date' => date_format(date_create($emp_info->due_date), "d-m-Y"),
            'resign_date' => date_format(date_create($emp_info->resign_date), "d-m-Y")
        );

        $response['employee_info'] = $emp;

        if ($emp_info->division_code == 'CONST') { // khusus construction ada brach manager
            $response['branch_manager'] = array(array(
                'date' => $exit_clearence->updated_at,
                'name' => 'YAYI TRISNAWATI',
                'remark' => '',
                'deduction' => 0,
                'signature_exist' => 1,
                'signature' => 'http://192.168.0.5/storage/profiles/signature/128.png'
            ));
        } else {
            $response['branch_manager'] = [];
        }
        $pm = DB::table('0_hrm_ec_history AS ech')
            ->leftJoin('users AS u', 'u.id', '=', 'ech.user_id')
            ->where('ech.ec_id', $exit_clearence->id)->where('ech.type', 7)->where('ech.pic', 1)
            ->select(
                'ech.created_at AS date',
                'ech.created_at AS date',
                'u.name',
                'ech.remark',
                'ech.deduction',
                'u.signature AS signature_exist',
                DB::raw("CONCAT('http://192.168.0.5/storage/profiles/signature/',u.id,'.png') AS signature")
            )
            ->orderBy('ech.id', 'desc')
            ->limit(1)
            ->get();

        foreach ($pm as $val => $key) {
            if ($key->signature_exist == 0) {
                return response()->json([
                    'error' => array(
                        'message' => $key->name . ' ' . "hasn't added a signature!",
                        'status_code' => 403
                    )
                ], 403);
            }
        }
        $response['dept_terkait'] = $pm;

        $ict_tools =
            DB::table('0_hrm_ec_history AS ech')
            ->leftJoin('users AS u', 'u.id', '=', 'ech.user_id')
            ->where('ech.ec_id', $exit_clearence->id)->where('ech.type', 1)->where('ech.pic', 1)
            ->select(
                'ech.created_at AS date',
                'u.name',
                'ech.remark',
                'ech.deduction',
                'u.signature AS signature_exist',
                DB::raw("CONCAT('http://192.168.0.5/storage/profiles/signature/',u.id,'.png') AS signature")
            )
            ->orderBy('ech.id', 'desc')
            ->limit(1)
            ->get();

        foreach ($ict_tools as $val => $key) {
            if ($key->signature_exist == 0) {
                return response()->json([
                    'error' => array(
                        'message' => $key->name . ' ' . "hasn't added a signature!",
                        'status_code' => 403
                    )
                ], 403);
            }
        }

        $response['ict_tools'] = $ict_tools;

        $ga =
            DB::table('0_hrm_ec_history AS ech')
            ->leftJoin('users AS u', 'u.id', '=', 'ech.user_id')
            ->where('ech.ec_id', $exit_clearence->id)->where('ech.type', 2)->where('ech.pic', 1)
            ->select(
                'ech.created_at AS date',
                'u.name',
                'ech.remark',
                'ech.deduction',
                'u.signature AS signature_exist',
                DB::raw("CONCAT('http://192.168.0.5/storage/profiles/signature/',u.id,'.png') AS signature")
            )
            ->orderBy('ech.id', 'desc')
            ->limit(1)
            ->get();

        foreach ($ga as $val => $key) {
            if ($key->signature_exist == 0) {
                return response()->json([
                    'error' => array(
                        'message' => $key->name . ' ' . "hasn't added a signature!",
                        'status_code' => 403
                    )
                ], 403);
            }
        }

        $response['ga'] = $ga;

        $asset_head = DB::table('0_hrm_ec_history AS ech')
            ->leftJoin('users AS u', 'u.id', '=', 'ech.user_id')
            ->where('ech.ec_id', $exit_clearence->id)->where('ech.type', 1)->where('ech.pic', 2)
            ->select(
                'ech.created_at AS date',
                'u.name',
                'ech.remark',
                'ech.deduction',
                'u.signature AS signature_exist',
                DB::raw("CONCAT('http://192.168.0.5/storage/profiles/signature/',u.id,'.png') AS signature")
            )
            ->orderBy('ech.id', 'desc')
            ->limit(1)
            ->get();

        foreach ($asset_head as $val => $key) {
            if ($key->signature_exist == 0) {
                return response()->json([
                    'error' => array(
                        'message' => $key->name . ' ' . "hasn't added a signature!",
                        'status_code' => 403
                    )
                ], 403);
            }
        }

        $response['asset_head'] = $asset_head;

        $ga_head = DB::table('0_hrm_ec_history AS ech')
            ->leftJoin('users AS u', 'u.id', '=', 'ech.user_id')
            ->where('ech.ec_id', $exit_clearence->id)->where('ech.type', 2)->where('ech.pic', 2)
            ->select(
                'ech.created_at AS date',
                'u.name',
                'ech.remark',
                'ech.deduction',
                'u.signature AS signature_exist',
                DB::raw("CONCAT('http://192.168.0.5/storage/profiles/signature/',u.id,'.png') AS signature")
            )
            ->orderBy('ech.id', 'desc')
            ->limit(1)
            ->get();

        foreach ($ga_head as $val => $key) {
            if ($key->signature_exist == 0) {
                return response()->json([
                    'error' => array(
                        'message' => $key->name . ' ' . "hasn't added a signature!",
                        'status_code' => 403
                    )
                ], 403);
            }
        }

        $response['ga_head'] = $ga_head;

        $ict_head = DB::table('0_hrm_ec_history AS ech')
            ->leftJoin('users AS u', 'u.id', '=', 'ech.user_id')
            ->where('ech.ec_id', $exit_clearence->id)->where('ech.type', 10)->where('ech.pic', 2)
            ->select(
                'ech.created_at AS date',
                'u.name',
                'ech.remark',
                'ech.deduction',
                'u.signature AS signature_exist',
                DB::raw("CONCAT('http://192.168.0.5/storage/profiles/signature/',u.id,'.png') AS signature")
            )
            ->orderBy('ech.id', 'desc')
            ->limit(1)
            ->get();

        foreach ($ict_head as $val => $key) {
            if ($key->signature_exist == 0) {
                return response()->json([
                    'error' => array(
                        'message' => $key->name . ' ' . "hasn't added a signature!",
                        'status_code' => 403
                    )
                ], 403);
            }
        }

        $response['ict_head'] = $ict_head;

        $finance_acc =
            DB::table('0_hrm_ec_history AS ech')
            ->leftJoin('users AS u', 'u.id', '=', 'ech.user_id')
            ->where('ech.ec_id', $exit_clearence->id)->where('ech.type', 3)->where('ech.pic', 1)
            ->select(
                'ech.created_at AS date',
                'u.name',
                'ech.remark',
                'ech.deduction',
                'u.signature AS signature_exist',
                DB::raw("CONCAT('http://192.168.0.5/storage/profiles/signature/',u.id,'.png') AS signature")
            )
            ->orderBy('ech.id', 'desc')
            ->limit(1)
            ->get();

        foreach ($finance_acc as $val => $key) {
            if ($key->signature_exist == 0) {
                return response()->json([
                    'error' => array(
                        'message' => $key->name . ' ' . "hasn't added a signature!",
                        'status_code' => 403
                    )
                ], 403);
            }
        }

        $response['finance_acc'] = $finance_acc;


        $finance_head = DB::table('0_hrm_ec_history AS ech')
            ->leftJoin('users AS u', 'u.id', '=', 'ech.user_id')
            ->where('ech.ec_id', $exit_clearence->id)->where('ech.type', 3)->where('ech.pic', 2)
            ->select(
                'ech.created_at AS date',
                'u.name',
                'ech.remark',
                'ech.deduction',
                'u.signature AS signature_exist',
                DB::raw("CONCAT('http://192.168.0.5/storage/profiles/signature/',u.id,'.png') AS signature")
            )
            ->orderBy('ech.id', 'desc')
            ->limit(1)
            ->get();

        foreach ($finance_head as $val => $key) {
            if ($key->signature_exist == 0) {
                return response()->json([
                    'error' => array(
                        'message' => $key->name . ' ' . "hasn't added a signature!",
                        'status_code' => 403
                    )
                ], 403);
            }
        }

        $response['finance_head'] = $finance_head;

        $dept_head =
            DB::table('0_hrm_ec_history AS ech')
            ->leftJoin('users AS u', 'u.id', '=', 'ech.user_id')
            ->where('ech.ec_id', $exit_clearence->id)->where('ech.type', 7)->where('ech.pic', 2)
            ->select(
                'ech.created_at AS date',
                'u.name',
                'ech.remark',
                'ech.deduction',
                'u.signature AS signature_exist',
                DB::raw("CONCAT('http://192.168.0.5/storage/profiles/signature/',u.id,'.png') AS signature")
            )
            ->orderBy('ech.id', 'desc')
            ->limit(1)
            ->get();

        foreach ($dept_head as $val => $key) {
            if ($key->signature_exist == 0) {
                return response()->json([
                    'error' => array(
                        'message' => $key->name . ' ' . "hasn't added a signature!",
                        'status_code' => 403
                    )
                ], 403);
            }
        }

        $response['dept_head'] = $dept_head;

        $hrd =
            DB::table('0_hrm_ec_history AS ech')
            ->leftJoin('users AS u', 'u.id', '=', 'ech.user_id')
            ->where('ech.ec_id', $exit_clearence->id)->where('ech.type', 6)->where('ech.pic', 1)
            ->select(
                'ech.created_at AS date',
                'u.name',
                'ech.remark',
                'ech.deduction',
                'u.signature AS signature_exist',
                DB::raw("CONCAT('http://192.168.0.5/storage/profiles/signature/',u.id,'.png') AS signature")
            )
            ->orderBy('ech.id', 'desc')
            ->limit(1)
            ->get();

        foreach ($hrd as $val => $key) {
            if ($key->signature_exist == 0) {
                return response()->json([
                    'error' => array(
                        'message' => $key->name . ' ' . "hasn't added a signature!",
                        'status_code' => 403
                    )
                ], 403);
            }
        }

        $response['hrd'] = $hrd;

        $hrd_recruitment =
            DB::table('0_hrm_ec_history AS ech')
            ->leftJoin('users AS u', 'u.id', '=', 'ech.user_id')
            ->where('ech.ec_id', $exit_clearence->id)->where('ech.type', 9)->where('ech.pic', 2)
            ->select(
                'ech.created_at AS date',
                'u.name',
                'ech.remark',
                'ech.deduction',
                'u.signature AS signature_exist',
                DB::raw("CONCAT('http://192.168.0.5/storage/profiles/signature/',u.id,'.png') AS signature")
            )
            ->orderBy('ech.id', 'desc')
            ->limit(1)
            ->get();

        foreach ($hrd_recruitment as $val => $key) {
            if ($key->signature_exist == 0) {
                return response()->json([
                    'error' => array(
                        'message' => $key->name . ' ' . "hasn't added a signature!",
                        'status_code' => 403
                    )
                ], 403);
            }
        }

        $response['hrd_recruitment'] = $hrd_recruitment;

        $hrd_payroll =
            DB::table('0_hrm_ec_history AS ech')
            ->leftJoin('users AS u', 'u.id', '=', 'ech.user_id')
            ->where('ech.ec_id', $exit_clearence->id)->where('ech.type', 8)->where('ech.pic', 2)
            ->select(
                'ech.created_at AS date',
                'u.name',
                'ech.remark',
                'ech.deduction',
                'u.signature AS signature_exist',
                DB::raw("CONCAT('http://192.168.0.5/storage/profiles/signature/',u.id,'.png') AS signature")
            )
            ->orderBy('ech.id', 'desc')
            ->limit(1)
            ->get();

        foreach ($hrd_payroll as $val => $key) {
            if ($key->signature_exist == 0) {
                return response()->json([
                    'error' => array(
                        'message' => $key->name . ' ' . "hasn't added a signature!",
                        'status_code' => 403
                    )
                ], 403);
            }
        }

        $response['hrd_payroll'] = $hrd_payroll;

        $hrd_head =
            DB::table('0_hrm_ec_history AS ech')
            ->leftJoin('users AS u', 'u.id', '=', 'ech.user_id')
            ->where('ech.ec_id', $exit_clearence->id)->where('ech.type', 6)->where('ech.pic', 2)
            ->select(
                'ech.created_at AS date',
                'u.name',
                'ech.remark',
                'ech.deduction',
                'u.signature AS signature_exist',
                DB::raw("CONCAT('http://192.168.0.5/storage/profiles/signature/',u.id,'.png') AS signature")
            )
            ->orderBy('ech.id', 'desc')
            ->limit(1)
            ->get();

        foreach ($hrd_head as $val => $key) {
            if ($key->signature_exist == 0) {
                return response()->json([
                    'error' => array(
                        'message' => $key->name . ' ' . "hasn't added a signature!",
                        'status_code' => 403
                    )
                ], 403);
            }
        }

        $response['hrd_head'] = $hrd_head;


        $pc_admin =
            DB::table('0_hrm_ec_history AS ech')
            ->leftJoin('users AS u', 'u.id', '=', 'ech.user_id')
            ->where('ech.ec_id', $exit_clearence->id)->where('ech.type', 4)->where('ech.pic', 1)
            ->select(
                'ech.created_at AS date',
                'u.name',
                'ech.remark',
                'ech.deduction',
                'u.signature AS signature_exist',
                DB::raw("CONCAT('http://192.168.0.5/storage/profiles/signature/',u.id,'.png') AS signature")
            )
            ->orderBy('ech.id', 'desc')
            ->limit(1)
            ->get();

        foreach ($pc_admin as $val => $key) {
            if ($key->signature_exist == 0) {
                return response()->json([
                    'error' => array(
                        'message' => $key->name . ' ' . "hasn't added a signature!",
                        'status_code' => 403
                    )
                ], 403);
            }
        }

        $response['pc_admin'] = $pc_admin;

        $pc_head =
            DB::table('0_hrm_ec_history AS ech')
            ->leftJoin('users AS u', 'u.id', '=', 'ech.user_id')
            ->where('ech.ec_id', $exit_clearence->id)->where('ech.type', 4)->where('ech.pic', 2)
            ->select(
                'ech.created_at AS date',
                'u.name',
                'ech.remark',
                'ech.deduction',
                'u.signature AS signature_exist',
                DB::raw("CONCAT('http://192.168.0.5/storage/profiles/signature/',u.id,'.png') AS signature")
            )
            ->orderBy('ech.id', 'desc')
            ->limit(1)
            ->get();

        foreach ($pc_head as $val => $key) {
            if ($key->signature_exist == 0) {
                return response()->json([
                    'error' => array(
                        'message' => $key->name . ' ' . "hasn't added a signature!",
                        'status_code' => 403
                    )
                ], 403);
            }
        }

        $response['pc_head'] = $pc_head;

        $dir_administration =
            DB::table('0_hrm_ec_history AS ech')
            ->leftJoin('users AS u', 'u.id', '=', 'ech.user_id')
            ->where('ech.ec_id', $exit_clearence->id)->where('ech.type', 5)->where('ech.pic', 2)
            ->select(
                'ech.created_at AS date',
                'u.name',
                'ech.remark',
                'ech.deduction',
                'u.signature AS signature_exist',
                DB::raw("CONCAT('http://192.168.0.5/storage/profiles/signature/',u.id,'.png') AS signature")
            )
            ->orderBy('ech.id', 'desc')
            ->limit(1)
            ->get();

        foreach ($dir_administration as $val => $key) {
            if ($key->signature_exist == 0) {
                return response()->json([
                    'error' => array(
                        'message' => $key->name . ' ' . "hasn't added a signature!",
                        'status_code' => 403
                    )
                ], 403);
            }
        }

        $response['dir_administration'] = $dir_administration;

        return $response;
    }

    public static function cancel_ec($ec_id, $user_id)
    {
        DB::beginTransaction();
        try {
            DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                ->update(array(
                    'deleted_by' => $user_id,
                    'deleted_at' => Carbon::now()
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

    public static function close_ec_manual($id)
    {
        $check = SiteHelper::ec_can_close_manualy($id);

        if ($check == true) {
            DB::beginTransaction();
            try {
                DB::table('0_hrm_exit_clearences')->where('id', $id)
                    ->update(array(
                        'status' => 2,
                        'fa_dir_check' => 2,
                        'updated_by' => Auth::guard()->user()->id,
                        'updated_at' => Carbon::now()
                    ));

                DB::table('0_hrm_ec_history')
                    ->insert(array(
                        'ec_id' => $id,
                        'type' => 5,
                        'pic' => 2,
                        'remark' => 'Close Manual',
                        'user_id' => 848,
                        'created_at' => Carbon::now()
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
        } else {
            return SiteHelper::error_msg(403, 'Approval Exit Clearence belum lengkap!');
        }
    }

    public static function edit_ec_history($myArr)
    {
        $params = $myArr['params'];
        $user_id = $myArr['user_id'];
        $ec_id = $params['ec_id'];
        $remark = $params['remark'];
        $status = $params['status'];
        $deduction = $params['deduction'];

        DB::beginTransaction();
        try {

            DB::table('0_hrm_ec_history')->where('ec_id', $ec_id)->where('user_id', $user_id)
                ->update(array(
                    'remark' => $remark,
                    'status' => $status,
                    'deduction' => $deduction,
                    'updated_at' => Carbon::now()
                ));

            $total_deduction = DB::table('0_hrm_ec_history')->where('ec_id', $ec_id)->sum('deduction');

            DB::table('0_hrm_exit_clearences')->where('id', $ec_id)
                ->update(array(
                    'deduction' => $total_deduction
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
