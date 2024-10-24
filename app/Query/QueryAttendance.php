<?php

namespace App\Query;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class QueryAttendance
{
    public static function index(
        $emp_no,
        $type,
        $from_date,
        $to_date,
        $division
    ) {


        $user_info = DB::table('users')
            ->where('emp_no', $emp_no)
            ->first();

        $user_id = (!empty($user_info->id)) ? $user_info->id : 0;

        $from =  date('Y-m-d', strtotime($from_date));
        $to =  date('Y-m-d', strtotime($to_date));

        $sql = "SELECT ptc.id,
                       u.emp_id,
                       u.emp_no,
                       u.name AS emp_name,
                       u.division_name,
                       aty.code,
                       aty.name AS attendance_type,
                       ptc.date,
                       ptc.start_time,
                       ps.site_id,
                       ps.latitude AS lat_site,
                       ps.longitude AS long_site,
                       ptc.lat_in,
                       ptc.long_in,
                       ptc.image_in,
                       ptc.end_time,
                       ptc.lat_out,
                       ptc.long_out,
                       ptc.image_out,
                       ptc.attachment,
                       CASE
                       WHEN ptc.status = 0 THEN 'Need Approval'
                       WHEN ptc.status = 1 THEN 'Approved'
                       WHEN ptc.status = 2 THEN 'Disapproved'
                       END AS status,
                       ptc.remark
                FROM 0_project_task_cico ptc
                LEFT OUTER JOIN 0_attendance_type aty ON (ptc.attendance_id = aty.id)
                JOIN 0_project_site ps ON (ptc.site_no = ps.site_no)
                LEFT JOIN users u ON (ptc.user_id = u.id)";

        if ($type > 0) {
            $sql .= " AND ptc.attendance_id = $type";
        }

        if ($emp_no > 0) {
            $sql .= " WHERE aty.id != -1 AND ptc.date BETWEEN '$from' AND '$to' AND u.id = $user_id AND ptc.status < 3";
        } else {
            $sql .= " WHERE ptc.check_out = 1 AND ptc.date BETWEEN '$from' AND '$to' AND ptc.status = 1 AND ptc.already_sync = 0";
            if ($division != '') {
                $sql .= " AND u.division_name = '$division'";
            }
        }
        $sql .= " ORDER BY ptc.id DESC";

        return $sql;
    }

    public static function need_approval($user_id, $level, $person_id, $page, $perPage)
    {
        $offset = ($page * $perPage) - $perPage ;

        $sql = " FROM 0_project_task_cico ptc
                LEFT JOIN 0_attendance_type aty ON (ptc.attendance_id = aty.id)
                LEFT OUTER JOIN 0_projects p ON (ptc.person_id = p.person_id)
                LEFT OUTER JOIN users u ON (ptc.user_id = u.id)
                WHERE ptc.status = 0 AND ptc.date > DATE_ADD(NOW(), INTERVAL -1 MONTH)";

        // if ($level == 1) {
        //     $sql .= " AND p.person_id = $person_id OR ptc.person_id = $person_id AND ptc.attendance_id NOT IN (1,23)";
        // } else if ($level == 3) {
        //     $sql .= " AND p.division_id IN 
        //             (
        //                 SELECT division_id FROM 0_user_divisions
        //                 WHERE user_id=$user_id
        //             ) OR ptc.person_id = $person_id AND ptc.attendance_id IN (12,23,14)";
        // } else if ($level == 999) {
        //     $sql .= " AND p.person_id != -1";
        // } else if ($level != 1 || $level != 3 || $level != 999) {
        //     $sql .= " AND p.person_id = 10000";
        // }

        if ($level == 1) {
            $sql .= " AND p.person_id = $person_id AND ptc.attendance_id NOT IN (1,12)";
        } else if ($level == 2 || $level == 3) {
            $sql .= " AND ptc.person_id = $person_id OR p.division_id IN 
                    (
                        SELECT division_id FROM 0_user_divisions
                        WHERE user_id=$user_id
                    )AND ptc.status = 0 AND ptc.attendance_id NOT IN (1,12) AND ptc.date > DATE_ADD(NOW(), INTERVAL -1 MONTH)";
        } else if ($level == 999) {
            $sql .= " AND p.person_id != -1";
        } else if ($level != 1 || $level != 2 || $level != 3 || $level != 999) {
            $sql .= " AND p.person_id = 10000";
        }

        $sql .= " GROUP BY ptc.id";
        $sql .= " ORDER BY ptc.id DESC";

        $sqlList = "SELECT ptc.*, aty.name AS attendance_type , u.name, u.emp_id" . $sql . " LIMIT $perPage OFFSET $offset";

        $sqlCount = "SELECT ptc.id" . $sql . " LIMIT 180";
        $sqlCountFinal = "SELECT COUNT(main.id) AS grand_total FROM (" . $sqlCount . ") AS main";

        return [
            'list' => $sqlList,
            'count' => $sqlCountFinal
        ];
    }
}
