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
use Maatwebsite\Excel\Concerns\ToArray;
use URL;

class ProjectDailyPlanController extends Controller
{
    public static function need_approval_daily_plan($myArr)
    {

        $level = $myArr['level'];
        $person_id = $myArr['person_id'];
        $user_id = $myArr['user_id'];
        $emp_id = $myArr['emp_id'];
        $old_id = $myArr['user_old_id'];

        $qhse_emp = [
            '4821-0767',
            '4421-1039',
            '4822-0278',
            '4821-1186',
            '4021-1451',
            '4824-0363',
            '4821-1010'
        ];

        $string_qhse_emp = "'" . implode("','", $qhse_emp) . "'";

        $response = [];
        if ($level == 1) {
            $sql = "SELECT
                    pdp.id,
                    CASE
                    WHEN pdp.type = 1 THEN 'Jobs'
                    WHEN pdp.type = 2 THEN 'Luar Kota'
                    END AS type,
                    pdp.reference,
                    pdp.division,
                    pdp.project_code,
                    pdp.project_name,
                    pdp.plan_start,
                    pdp.plan_end,
                    pdp.du_id,
                    pdp.sow,
                    pdp.site_id,
                    pdp.daily_task,
                    pdp.address,
                    pdp.approval,
                    CASE
                    WHEN pdp.approval = 1 THEN 'PC'
                    WHEN pdp.approval = 2 THEN 'Employee'
                    WHEN pdp.approval = 3 THEN 'Dept.Head'
                    WHEN pdp.approval = 4 THEN 'GA'
                    END AS pic,
                    p.person_id
                FROM 0_project_daily_plan pdp
                LEFT OUTER JOIN 0_projects p ON (pdp.project_code = p.code)
                LEFT OUTER JOIN 0_members m ON (p.person_id = m.person_id)
                WHERE pdp.approval = 1 AND m.person_id = $person_id GROUP BY pdp.reference ORDER BY pdp.plan_start DESC";
        } else if ($level == 0) {
            $sql = "SELECT
                    pdp.id,
                    CASE
                    WHEN pdp.type = 1 THEN 'Jobs'
                    WHEN pdp.type = 2 THEN 'Luar Kota'
                    END AS type,
                    pdp.reference,
                    pdp.division,
                    pdp.project_code,
                    pdp.project_name,
                    pdp.plan_start,
                    pdp.plan_end,
                    pdp.du_id,
                    pdp.sow,
                    pdp.site_id,
                    pdp.daily_task,
                    pdp.address,
                    pdp.approval,
                    CASE
                    WHEN pdp.approval = 1 THEN 'PC'
                    WHEN pdp.approval = 2 THEN 'Employee'
                    WHEN pdp.approval = 3 THEN 'Dept.Head'
                    WHEN pdp.approval = 4 THEN 'GA'
                    END AS pic,
                    p.person_id
                FROM 0_project_daily_plan pdp
                LEFT OUTER JOIN 0_projects p ON (pdp.project_code = p.code)
                LEFT OUTER JOIN 0_members m ON (p.person_id = m.person_id)
                WHERE pdp.approval = 2 AND pdp.emp_id IN ('$emp_id') GROUP BY pdp.reference ORDER BY pdp.plan_start DESC";
        } else if ($level == 2) {
            if($person_id == 28) // khusus QHSE VIA PAK ROBERT
            {
                $ext_sql = " OR pdp.approval = 1 AND pdp.emp_id IN ($string_qhse_emp)";
            }else{
                $ext_sql = "";
            }
            $sql = "SELECT
                    pdp.id,
                    CASE
                    WHEN pdp.type = 1 THEN 'Jobs'
                    WHEN pdp.type = 2 THEN 'Luar Kota'
                    END AS type,
                    pdp.reference,
                    pdp.division,
                    pdp.project_code,
                    pdp.project_name,
                    pdp.plan_start,
                    pdp.plan_end,
                    pdp.du_id,
                    pdp.sow,
                    pdp.site_id,
                    pdp.daily_task,
                    pdp.address,
                    pdp.approval,
                    CASE
                    WHEN pdp.approval = 1 THEN 'PC'
                    WHEN pdp.approval = 2 THEN 'Employee'
                    WHEN pdp.approval = 3 THEN 'Dept.Head'
                    WHEN pdp.approval = 4 THEN 'GA'
                    END AS pic,
                    p.person_id,
                    p.division_id
                FROM 0_project_daily_plan pdp
                LEFT OUTER JOIN 0_projects p ON (pdp.project_code = p.code)
                LEFT OUTER JOIN 0_members m ON (p.person_id = m.person_id)
                WHERE pdp.approval = 3 AND pdp.status = 1 AND p.division_id IN 
                                                                                                            (
                                                                                                                SELECT division_id FROM 0_user_divisions
                                                                                                                WHERE user_id=$old_id
                                                                                                            )
                OR pdp.approval = 1 AND m.person_id = $person_id
                $ext_sql
                GROUP BY pdp.reference ORDER BY pdp.plan_start DESC";
        } else if ($level == 3) {
            $sql = "SELECT
                    pdp.id,
                    CASE
                    WHEN pdp.type = 1 THEN 'Jobs'
                    WHEN pdp.type = 2 THEN 'Luar Kota'
                    END AS type,
                    pdp.reference,
                    pdp.division,
                    pdp.project_code,
                    pdp.project_name,
                    pdp.plan_start,
                    pdp.plan_end,
                    pdp.du_id,
                    pdp.sow,
                    pdp.site_id,
                    pdp.daily_task,
                    pdp.address,
                    pdp.approval,
                    CASE
                    WHEN pdp.approval = 1 THEN 'PC'
                    WHEN pdp.approval = 2 THEN 'Employee'
                    WHEN pdp.approval = 3 THEN 'Dept.Head'
                    WHEN pdp.approval = 4 THEN 'GA'
                    END AS pic,
                    p.person_id,
                    p.division_id
                FROM 0_project_daily_plan pdp
                LEFT OUTER JOIN 0_projects p ON (pdp.project_code = p.code)
                LEFT OUTER JOIN 0_members m ON (p.person_id = m.person_id)
                WHERE pdp.approval = 3 AND pdp.status = 1 AND p.division_id IN 
                                                                                                            (
                                                                                                                SELECT division_id FROM 0_user_divisions
                                                                                                                WHERE user_id=$old_id
                                                                                                            )
                OR pdp.approval = 1 AND m.person_id = $person_id
                GROUP BY pdp.reference ORDER BY pdp.plan_start DESC";
        } else if ($level == 999) {
            $sql = "SELECT
                    pdp.id,
                    CASE
                    WHEN pdp.type = 1 THEN 'Jobs'
                    WHEN pdp.type = 2 THEN 'Luar Kota'
                    END AS type,
                    pdp.reference,
                    pdp.division,
                    pdp.project_code,
                    pdp.project_name,
                    pdp.plan_start,
                    pdp.plan_end,
                    pdp.du_id,
                    pdp.sow,
                    pdp.site_id,
                    pdp.daily_task,
                    pdp.address,
                    pdp.approval,
                    CASE
                    WHEN pdp.approval = 1 THEN 'PC'
                    WHEN pdp.approval = 2 THEN 'Employee'
                    WHEN pdp.approval = 3 THEN 'Dept.Head'
                    WHEN pdp.approval = 4 THEN 'GA'
                    END AS pic,
                    p.person_id
                FROM 0_project_daily_plan pdp
                LEFT OUTER JOIN 0_projects p ON (pdp.project_code = p.code)
                WHERE pdp.approval < 4 GROUP BY pdp.reference ORDER BY pdp.id DESC LIMIT 20";
        } else if ($level != 1 || $level != 999 || $level != 3 || $level != 0) {
            $sql = "SELECT * FROM 0_project_daily_plan WHERE id = 999999999999";
        }


        $get_daily_plan = DB::select(DB::raw($sql));

        foreach ($get_daily_plan as $data) {
            $tmp = [];
            $tmp['id'] = $data->id;
            $tmp['type'] = $data->type;
            $tmp['reference'] = $data->reference;
            $tmp['division'] = $data->division;
            $tmp['project_code'] = $data->project_code;
            $tmp['project_name'] = $data->project_name;
            $tmp['plan_start'] = $data->plan_start;
            $tmp['plan_end'] = $data->plan_end;
            $tmp['du_id'] = $data->du_id;
            $tmp['sow'] = $data->sow;
            $tmp['site_id'] = $data->site_id;
            $tmp['daily_task'] = $data->daily_task;
            $tmp['address'] = $data->address;
            $tmp['approval_position'] = $data->pic;


            $sql1 = "SELECT * FROM 0_project_daily_plan
                             WHERE reference = '$data->reference'";

            $get_member = DB::select(DB::raw($sql1));
            foreach ($get_member as $data) {
                $items = [];
                $items['emp_id'] = $data->emp_id;
                $items['emp_name'] = $data->emp_name;
                $items['phone'] = $data->phone_number;
                $items['remark'] = $data->remark;
                $tmp['member'][] = $items;
            }

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function get_project_daily_plan($myArr)
    {

        $level = $myArr['level'];
        $user_id = $myArr['user_id'];
        $emp_id = $myArr['emp_id'];

        $response = [];
        if ($level == 999) {
            $sql = "SELECT
                    pdp.id,
                    CASE
                    WHEN pdp.type = 1 THEN 'Jobs'
                    WHEN pdp.type = 2 THEN 'Luar Kota'
                    END AS type,
                    pdp.reference,
                    pdp.division,
                    pdp.project_code,
                    pdp.project_name,
                    pdp.plan_start,
                    pdp.plan_end,
                    pdp.du_id,
                    pdp.sow,
                    pdp.site_id,
                    pdp.daily_task,
		            pdp.vehicle_no,
                    pdp.approval,
                    pdp.address,
                    pdp.phone_number,
                    pdp.remark_security,
                    pdp.qrcode
                FROM 0_project_daily_plan pdp
                WHERE pdp.id != -1 AND pdp.approval IN (4,5) AND pdp.status = 1 GROUP BY pdp.reference ORDER BY pdp.plan_start DESC LIMIT 100";
        } else if ($level == 666) {
            $sql = "SELECT
                    pdp.id,
                    CASE
                    WHEN pdp.type = 1 THEN 'Jobs'
                    WHEN pdp.type = 2 THEN 'Luar Kota'
                    END AS type,
                    pdp.reference,
                    pdp.division,
                    pdp.project_code,
                    pdp.project_name,
                    pdp.plan_start,
                    pdp.plan_end,
                    pdp.du_id,
                    pdp.sow,
                    pdp.site_id,
                    pdp.daily_task,
		            pdp.vehicle_no,
                    pdp.approval,
                    pdp.address,
                    pdp.phone_number,
                    pdp.remark_security,
                    pdp.qrcode
                FROM 0_project_daily_plan pdp
                WHERE pdp.checked_by = $user_id AND pdp.approval IN (4,5) AND pdp.status < 2 GROUP BY pdp.reference ORDER BY pdp.plan_start DESC";
        } else if ($level < 666) {
            $sql = "SELECT
                    pdp.id,
                    CASE
                    WHEN pdp.type = 1 THEN 'Jobs'
                    WHEN pdp.type = 2 THEN 'Luar Kota'
                    END AS type,
                    pdp.reference,
                    pdp.division,
                    pdp.project_code,
                    pdp.project_name,
                    pdp.plan_start,
                    pdp.plan_end,
                    pdp.du_id,
                    pdp.sow,
                    pdp.site_id,
                    pdp.daily_task,
		            pdp.vehicle_no,
                    pdp.approval,
                    pdp.address,
                    pdp.phone_number,
                    pdp.remark_security,
                    pdp.qrcode
                FROM 0_project_daily_plan pdp
                WHERE pdp.emp_id = '$emp_id' AND pdp.approval IN (4,5) AND pdp.status < 2 GROUP BY pdp.reference ORDER BY pdp.plan_start DESC";
        }

        $get_daily_plan = DB::select(DB::raw($sql));

        foreach ($get_daily_plan as $data) {

            $url = URL::to("/storage/qr-code/$data->qrcode.png");

            $tmp = [];
            $tmp['id'] = $data->id;
            $tmp['type'] = $data->type;
            $tmp['reference'] = $data->reference;
            $tmp['division'] = $data->division;
            $tmp['project_code'] = $data->project_code;
            $tmp['project_name'] = $data->project_name;
            $tmp['plan_start'] = $data->plan_start;
            $tmp['plan_end'] = $data->plan_end;
            $tmp['du_id'] = $data->du_id;
            $tmp['sow'] = $data->sow;
            $tmp['site_id'] = $data->site_id;
            $tmp['daily_task'] = $data->daily_task;
            $tmp['address'] = $data->address;
            $tmp['approval_position'] = $data->approval;
            $tmp['qrcode'] = $url;
            $tmp['vehicle_no'] = $data->vehicle_no;


            $sql1 = "SELECT * FROM 0_project_daily_plan
                             WHERE reference = '$data->reference'";

            $get_member = DB::select(DB::raw($sql1));
            foreach ($get_member as $data) {
                $items = [];
                $items['emp_id'] = $data->emp_id;
                $items['emp_name'] = $data->emp_name;
                $items['phone'] = $data->phone_number;
                $items['remark'] = $data->remark;
                $tmp['member'][] = $items;
            }

            $confirmed_by = DB::table('0_pdp_log')->select('users.name')->whereRaw("0_pdp_log.reference = '$data->reference' AND 0_pdp_log.approval = 2")
                ->join('users', 'users.id', '=', '0_pdp_log.person_id')->first();

            $tmp['confirmed_by'] = $confirmed_by->name;

            $doc_no = $tmp['reference'];
            $sql2 = "SELECT pdpl.*, u.name AS name FROM 0_pdp_log pdpl 
                                 LEFT JOIN users u ON (pdpl.person_id = u.id)
                                 WHERE pdpl.reference = '$doc_no' AND pdpl.approval != 2";
            $history = DB::select(DB::raw($sql2));

            foreach ($history as $key) {
                $list = [];
                $list['name'] = $key->name;
                $tmp['approved_by'][] = $list;
            }
            $checked_by = DB::table('0_project_daily_plan')->select('users.name', '0_project_daily_plan.checked_time')->whereRaw("0_project_daily_plan.reference = '$data->reference'")
                ->join('users', 'users.id', '=', '0_project_daily_plan.checked_by')->first();

            if (empty($checked_by)) {
                $checked_name = '';
                $checked_time = '';
            } else if (!empty($checked_by)) {
                $checked_name = $checked_by->name;
                $checked_time = $checked_by->checked_time;
            }
            $tmp['checked_by'] = $checked_name;
            $tmp['checked_time'] = $checked_time;
            $tmp['remark_security'] = $data->remark_security;

            array_push($response, $tmp);
        }


        return $response;
    }

    public static function search($myArr)
    {

        $doc_no = $myArr['doc_no'];
        $user_id = $myArr['user_id'];
        $level =  $myArr['level'];

        $response = [];
        $sql = "SELECT
                    pdp.id,
                    CASE
                    WHEN pdp.type = 1 THEN 'Jobs'
                    WHEN pdp.type = 2 THEN 'Luar Kota'
                    END AS type,
                    pdp.reference,
                    pdp.division,
                    pdp.project_code,
                    pdp.project_name,
                    pdp.plan_start,
                    pdp.plan_end,
                    pdp.du_id,
                    pdp.sow,
                    pdp.site_id,
                    pdp.daily_task,
                    pdp.approval,
                    pdp.address,
		            pdp.vehicle_no,
                    pdp.phone_number,
                    pdp.remark_security,
		            pdp.qrcode
                FROM 0_project_daily_plan pdp
                LEFT JOIN 0_pdp_log pdpl ON (pdpl.reference = pdp.reference)
                LEFT JOIN users u ON (pdpl.person_id = u.id)
                WHERE pdp.reference = '$doc_no' GROUP BY pdp.reference ORDER BY pdp.plan_start DESC";
        $get_daily_plan = DB::select(DB::raw($sql));

        foreach ($get_daily_plan as $data) {
            $url = URL::to("/storage/qr-code/$data->qrcode.png");

            $tmp = [];
            $tmp['id'] = $data->id;
            $tmp['type'] = $data->type;
            $tmp['reference'] = $data->reference;
            $tmp['division'] = $data->division;
            $tmp['project_code'] = $data->project_code;
            $tmp['project_name'] = $data->project_name;
            $tmp['plan_start'] = $data->plan_start;
            $tmp['plan_end'] = $data->plan_end;
            $tmp['du_id'] = $data->du_id;
            $tmp['sow'] = $data->sow;
            $tmp['site_id'] = $data->site_id;
            $tmp['daily_task'] = $data->daily_task;
            $tmp['vehicle_no'] = $data->vehicle_no;
            $tmp['address'] = $data->address;
            $tmp['qrcode'] = $url;

            $sql1 = "SELECT * FROM 0_project_daily_plan
                             WHERE reference = '$data->reference'";

            $get_member = DB::select(DB::raw($sql1));
            foreach ($get_member as $data) {
                $items = [];
                $items['emp_id'] = $data->emp_id;
                $items['emp_name'] = $data->emp_name;
                $items['phone'] = $data->phone_number;
                $items['remark'] = $data->remark;

                $tmp['member'][] = $items;
            }
            $confirmed_by = DB::table('0_pdp_log')->select('users.name')->whereRaw("0_pdp_log.reference = '$data->reference' AND 0_pdp_log.approval = 2")
                ->join('users', 'users.id', '=', '0_pdp_log.person_id')->first();

            $tmp['confirmed_by'] = $confirmed_by->name;

            $sql2 = "SELECT pdpl.*, u.name AS name FROM 0_pdp_log pdpl 
                                 LEFT JOIN users u ON (pdpl.person_id = u.id)
                                 WHERE pdpl.reference = '$doc_no' AND pdpl.approval != 2";
            $history = DB::select(DB::raw($sql2));

            foreach ($history as $key) {
                $list = [];
                $list['name'] = $key->name;
                $tmp['approved_by'][] = $list;
            }

            $checked_by = DB::table('0_project_daily_plan')->select('users.name', '0_project_daily_plan.checked_time')->whereRaw("0_project_daily_plan.reference = '$data->reference'")
                ->join('users', 'users.id', '=', '0_project_daily_plan.checked_by')->first();

            if (empty($checked_by)) {
                $checked_name = '';
                $checked_time = '';
            } else if (!empty($checked_by)) {
                $checked_name = $checked_by->name;
                $checked_time = $checked_by->checked_time;
            }
            $tmp['checked_by'] = $checked_name;
            $tmp['checked_time'] = $checked_time;
            $tmp['remark_security'] = $data->remark_security;


            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function status()
    {
        $response = [];
        $sql = "SELECT
                    pdp.id,
                    CASE
                    WHEN pdp.type = 1 THEN 'Jobs'
                    WHEN pdp.type = 2 THEN 'Luar Kota'
                    END AS type,
                    pdp.reference,
                    pdp.division,
                    pdp.project_code,
                    pdp.project_name,
                    pdp.plan_start,
                    pdp.plan_end,
                    pdp.du_id,
                    pdp.sow,
                    pdp.site_id,
                    pdp.daily_task,
                    CASE
                    WHEN pdp.approval = 1 THEN 'PM'
                    WHEN pdp.approval = 2 THEN 'Employee'
                    WHEN pdp.approval = 3 THEN 'Dept. Head'
                    WHEN pdp.approval = 4 THEN 'Completed'
                    END AS approval,
                    pdp.address,
		            pdp.vehicle_no,
                    pdp.phone_number,
                    pdp.remark_security,
		            pdp.qrcode
                FROM 0_project_daily_plan pdp
                LEFT JOIN 0_pdp_log pdpl ON (pdpl.reference = pdp.reference)
                LEFT JOIN users u ON (pdpl.person_id = u.id)
                WHERE pdp.approval < 4 GROUP BY pdp.reference ORDER BY pdp.plan_start DESC";
        $get_daily_plan = DB::select(DB::raw($sql));

        foreach ($get_daily_plan as $data) {
            $tmp = [];
            $tmp['id'] = $data->id;
            $tmp['type'] = $data->type;
            $tmp['reference'] = $data->reference;
            $tmp['division'] = $data->division;
            $tmp['project_code'] = $data->project_code;
            $tmp['project_name'] = $data->project_name;
            $tmp['plan_start'] = $data->plan_start;
            $tmp['plan_end'] = $data->plan_end;
            $tmp['du_id'] = $data->du_id;
            $tmp['sow'] = $data->sow;
            $tmp['site_id'] = $data->site_id;
            $tmp['daily_task'] = $data->daily_task;
            $tmp['vehicle_no'] = $data->vehicle_no;
            $tmp['address'] = $data->address;

            $tmp['history'] = self::pdp_log($data->reference);
            $tmp['member'] = self::members_log($data->reference);


            array_push($response, $tmp);
        }


        return $response;
    }

    public static function pdp_log($reference)
    {
        $response = [];
        $sql = "SELECT CASE
                WHEN log.approval = 1 THEN 'PM'
                WHEN log.approval = 2 THEN 'Employee'
                WHEN log.approval = 3 THEN 'Dept. Head'
                WHEN log.approval = 4 THEN 'Completed'
                END AS approval,
                log.date,
                u.name as name
                FROM 0_pdp_log log 
                LEFT JOIN users u ON (log.person_id = u.id)
                WHERE log.reference = '$reference'";
        $get_log = DB::select(DB::raw($sql));

        foreach ($get_log as $key) {
            $items = [];
            $items['approval'] = $key->approval;
            $items['name'] = $key->name;
            $items['date'] = date('d-m-Y h:i:s', strtotime($key->date));

            array_push($response, $items);
        }

        return $response;
    }

    public static function members_log($reference)
    {
        $response = [];
        $sql = "SELECT *
                FROM 0_project_daily_plan
                WHERE reference = '$reference'";
        $get_log = DB::select(DB::raw($sql));

        foreach ($get_log as $key) {
            $items = [];
            $items['emp_id'] = $key->emp_id;
            $items['emp_name'] = $key->emp_name;
            $items['phone_number'] = $key->phone_number;
            $items['remark'] = $key->remark;


            array_push($response, $items);
        }

        return $response;
    }

    public static function create_daily_plan($myArr)
    {
        $params = $myArr['params'];
        $creator_name = $myArr['creator_name'];
        $creator_nik = $myArr['creator_nik'];

        $transdate = date('Y-m-d', time());
        $date = date('d', strtotime($transdate));
        $month = date('m', strtotime($transdate));
        $year = date('Y', strtotime($transdate));
        $get_roman = self::numberToRomanRepresentation($month);
        $times = date("His");

        DB::beginTransaction();
        try {
            foreach ($params as $item) {

                $reference = "$date-$times/ATE/" . $item['division'] . "/$get_roman/$year";

                DB::table('0_project_daily_plan')
                    ->insert(array(
                        'type' => $item['type'],
                        'reference' => $reference,
                        'creator_nik' => $creator_nik,
                        'division' => $item['division'],
                        'creator_position' => $item['creator_position'],
                        'emp_name' => $item['emp_name'],
                        'emp_id' => $item['emp_id'],
                        'phone_number' => $item['phone_number'],
                        'emp_position' => $item['emp_position'],
                        'daily_task' => $item['daily_task'],
                        'project_name' => $item['project_name'],
                        'project_code' => $item['project_code'],
                        'plan_start' => $item['plan_start'],
                        'plan_end' => $item['plan_end'],
                        'du_id' => $item['du_id'],
                        'site_id' => $item['site_id'],
                        'sow' => $item['sow'],
                        'address' => $item['address'],
                        'remark' => $item['remark'],
                        'created_at' => Carbon::now(),
                        'created_by' => $creator_name
                    ));

                // Commit Transaction
                DB::commit();

                // Semua proses benar
            }


            return response()->json([
                'success' => true
            ]);
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }

    public static function numberToRomanRepresentation($number)
    {
        $map = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);
        $returnValue = '';
        while ($number > 0) {
            foreach ($map as $roman => $int) {
                if ($number >= $int) {
                    $number -= $int;
                    $returnValue .= $roman;
                    break;
                }
            }
        }
        return $returnValue;
    }
}
