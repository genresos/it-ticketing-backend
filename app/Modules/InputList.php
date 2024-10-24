<?php

namespace App\Modules;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class InputList
{
    public static function division_list_row($show_project_only)
    {
        $response = [];
        $sql = "SELECT division_id,name FROM 0_hrm_divisions WHERE inactive=0";
        if ($show_project_only != 0) {
            $sql .= " AND is_project=1 ";
        }

        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {
            $tmp = [];
            $tmp['division_id'] = $data->division_id;
            $tmp['name'] = $data->name;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function project_type_list_row($project_type_id, $division_id)
    {
        $response = [];
        $sql = "SELECT project_type_id, name FROM 0_project_types WHERE project_type_id != -1";

        if ($division_id != 0) {
            $sql .= " AND division_id=$division_id";
        }
        if ($project_type_id != 0) {
            $sql .= " AND project_type_id=$project_type_id";
        }
        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {
            $tmp = [];
            $tmp['project_type_id'] = $data->project_type_id;
            $tmp['name'] = $data->name;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function customer_list_row($debtor_no)
    {
        $response = [];
        $sql = "SELECT debtor_no, debtor_ref, curr_code, inactive FROM 0_debtors_master ";

        if ($debtor_no != 0) {
            $sql .= " WHERE debtor_no=$debtor_no";
        }
        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {
            $tmp = [];
            $tmp['debtor_no'] = $data->debtor_no;
            $tmp['debtor_ref'] = $data->debtor_ref;
            $tmp['curr_code'] = $data->curr_code;
            $tmp['inactive'] = $data->inactive;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function sow_list_row($sow_id)
    {
        $response = [];
        $sql = "SELECT sow_id,name FROM 0_project_sow";

        if ($sow_id != 0) {
            $sql .= " WHERE sow_id=$sow_id";
        }

        $sql .= " GROUP BY name ORDER BY sow_id ASC";
        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {
            $tmp = [];
            $tmp['sow_id'] = $data->sow_id;
            $tmp['name'] = $data->name;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function po_category_list_row($po_category_id)
    {
        $response = [];
        $sql = "SELECT po_category_id, description FROM 0_po_category";

        if ($po_category_id != 0) {
            $sql .= " WHERE po_category_id=$po_category_id";
        }
        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {
            $tmp = [];
            $tmp['po_category_id'] = $data->po_category_id;
            $tmp['description'] = $data->description;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function site_list_row($site_no, $site_name)
    {
        $response = [];

        $sql = "SELECT ps.site_no,ps.site_id, ps.name, ps.latitude, ps.longitude FROM 0_project_site ps WHERE ps.site_id is not null";

        if ($site_no != 0) {
            $sql .= " AND ps.site_no = '$site_no'";
        }
        if ($site_name != '') {
            $sql .= " AND ps.name LIKE '%$site_name%' or ps.site_id LIKE '%$site_name%'";
        }

        $sql .= " ORDER BY ps.site_no DESC LIMIT 1000";

        $project_site = DB::select(DB::raw($sql));
        foreach ($project_site as $data) {

            $tmp = [];
            $tmp['site_no'] = $data->site_no;
            $tmp['site_id'] = $data->site_id;
            $tmp['site_name'] = $data->name;
            $tmp['lat'] = $data->latitude;
            $tmp['long'] = $data->longitude;


            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function site_list_office_row($site_no, $site_name)
    {
        $response = [];

        $sql = "SELECT ps.site_no,ps.site_id, ps.name, ps.latitude, ps.longitude FROM 0_project_site ps WHERE ps.is_office = 1";

        if ($site_no != 0) {
            $sql .= " AND ps.site_no = '$site_no'";
        }
        if ($site_name != '') {
            $sql .= " AND ps.name LIKE '%$site_name%' or ps.site_id LIKE '%$site_name%'";
        }

        $sql .= " ORDER BY ps.site_no DESC LIMIT 1000";

        $project_site = DB::select(DB::raw($sql));
        foreach ($project_site as $data) {

            $tmp = [];
            $tmp['site_no'] = $data->site_no;
            $tmp['site_id'] = $data->site_id;
            $tmp['site_name'] = $data->name;
            $tmp['lat'] = $data->latitude;
            $tmp['long'] = $data->longitude;


            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function project_manager_list_row($person_id)
    {
        $response = [];
        $sql = "SELECT person_id, name FROM 0_members WHERE inactive=0";

        if ($person_id != 0) {
            $sql .= " AND person_id=$person_id";
        }
        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {
            $tmp = [];
            $tmp['person_id'] = $data->person_id;
            $tmp['name'] = $data->name;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function project_area_list_row($area_id)
    {
        $response = [];
        $sql = "SELECT area_id, name FROM 0_project_area";

        if ($area_id != 0) {
            $sql .= " WHERE area_id=$area_id";
        }
        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {
            $tmp = [];
            $tmp['area_id'] = $data->area_id;
            $tmp['name'] = $data->name;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function project_payment_term_list_row($term_id)
    {
        $response = [];
        $sql = "SELECT term_id, name FROM 0_project_term";

        if ($term_id != 0) {
            $sql .= " WHERE term_id=$term_id";
        }
        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {
            $tmp = [];
            $tmp['term_id'] = $data->term_id;
            $tmp['name'] = $data->name;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function currencies_list_row($curr_abrev)
    {
        $response = [];
        $sql = "SELECT curr_abrev, currency, inactive FROM 0_currencies";

        if ($curr_abrev != '') {
            $sql .= " WHERE curr_abrev='$curr_abrev'";
        }
        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {
            $tmp = [];
            $tmp['curr_abrev'] = $data->curr_abrev;
            $tmp['currency'] = $data->currency;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function project_po_status_list_row($status_id)
    {
        $response = [];
        $sql = "SELECT status_id, name FROM 0_project_po_status";

        if ($status_id != 0) {
            $sql .= " WHERE status_id=$status_id";
        }
        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {
            $tmp = [];
            $tmp['status_id'] = $data->status_id;
            $tmp['name'] = $data->name;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function project_status_list_row($status_id)
    {
        $response = [];
        $sql = "SELECT status_id, name FROM 0_project_status WHERE type_id=1 AND status_id < 4";

        if ($status_id != 0) {
            $sql .= " AND status_id=$status_id";
        }
        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {
            $tmp = [];
            $tmp['status_id'] = $data->status_id;
            $tmp['name'] = $data->name;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function project_parent_list_row($code)
    {
        $response = [];
        $sql = "SELECT code, name FROM 0_projects WHERE is_bucket=1";

        if ($code != 0) {
            $sql .= " AND code='$code'";
        }
        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {
            $tmp = [];
            $tmp['code'] = $data->code;
            $tmp['name'] = $data->name;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function emp_list_row($emp_no)
    {
        $response = [];
        $sql = "SELECT e.*, ed.name as division_name
                FROM 0_hrm_employees e
                LEFT OUTER JOIN 0_hrm_divisions ed ON (e.division_id = ed.division_id)";

        if ($emp_no != 0) {
            $sql .= " AND id=$emp_no";
        }
        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {
            $tmp = [];
            $tmp['emp_no'] = $data->id;
            $tmp['emp_id'] = $data->emp_id;
            $tmp['name'] = $data->name;
            $tmp['division'] = $data->division_name;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function project_task_list_row($task_id)
    {
        $response = [];
        $sql = "SELECT pt.*, p.code AS project_code FROM 0_project_task pt
                LEFT OUTER JOIN 0_projects p ON (pt.project_no = p.project_no)
                WHERE pt.status!=3";

        if ($task_id != 0) {
            $sql .= " AND pt.id=$task_id";
        }
        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {
            $tmp = [];
            $tmp['id'] = $data->id;
            $tmp['title'] = $data->title;
            $tmp['project_code'] = $data->project_code;
            $tmp['date'] = date('d-m-Y', strtotime($data->date));

            array_push($response, $tmp);
        }

        return $response;
    }
    public static function company_list_row($client_id)
    {
        $response = [];
        $sql = "SELECT client_id, name FROM 0_clients";

        if ($client_id != 0) {
            $sql .= " WHERE client_id=$client_id";
        }
        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {
            $tmp = [];
            $tmp['client_id'] = $data->client_id;
            $tmp['name'] = $data->name;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function employee_level_list_row($level_id)
    {
        $response = [];
        $sql = "SELECT level_id, name FROM 0_hrm_employee_levels WHERE inactive=0";

        if ($level_id != 0) {
            $sql .= " AND level_id=$level_id";
        }
        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {
            $tmp = [];
            $tmp['level_id'] = $data->level_id;
            $tmp['name'] = $data->name;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function employee_type_list_row($employee_type_id)
    {
        $response = [];
        $sql = "SELECT employee_type_id, name FROM 0_hrm_employee_types";

        if ($employee_type_id != 0) {
            $sql .= " WHERE employee_type_id=$employee_type_id";
        }
        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {
            $tmp = [];
            $tmp['employee_type_id'] = $data->employee_type_id;
            $tmp['name'] = $data->name;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function employee_status_list_row($status_id)
    {
        $response = [];
        $sql = "SELECT status_id, name FROM 0_hrm_employee_status";


        if ($status_id != 0) {
            $sql .= " WHERE status_id=$status_id";
        }
        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {
            $tmp = [];
            $tmp['status_id'] = $data->status_id;
            $tmp['name'] = $data->name;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function attendance_type($id)
    {
        $response = [];
        $sql = "SELECT id,code, name FROM 0_attendance_type";


        // if ($id != 0) {
        //     $sql .= " AND id=$id";
        // }
        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {
            $tmp = [];
            $tmp['id'] = $data->id;
            $tmp['code'] = $data->code;
            $tmp['name'] = $data->name;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function project_management_fee($fee_id)
    {
        $response = [];
        $sql = "SELECT id, name FROM 0_project_management_fee";

        if ($fee_id != 0) {
            $sql .= " WHERE id=$fee_id";
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


    public static function list_budget_by_project_no($project_no)
    {
        $response = [];
        $sql = "SELECT project_budget_id, budget_name 
                FROM 0_project_budgets
                WHERE project_no = $project_no";
        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {
            $tmp = [];
            $tmp['budget_id'] = $data->project_budget_id;
            $tmp['budget_name'] = $data->budget_name;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }
}
