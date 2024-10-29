<?php

namespace App\Query;

use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

class QueryTickets
{
    public static function tickets(
        $user_id,
        $user_level
    ) {
        $sql = "SELECT t.*, u.name AS requestor, tc.name AS category_name, tp.name AS priority_name, ts.name AS status_name, '' AS project_code
                FROM 0_ict_tickets t 
                LEFT JOIN users u ON (t.user_id = u.id)
                LEFT JOIN 0_ict_ticket_category tc ON (tc.id = t.category_id)
                LEFT JOIN 0_ict_ticket_priority tp ON (tp.id = t.priority_id)
                LEFT JOIN 0_ict_ticket_status ts ON (ts.id = t.status_id)
                WHERE t.id != 0 ";

        if ($user_level == 999) {
            $sql .= "AND t.status_id != 7 ";
        } else if ($user_level < 999) {
            $sql .= "AND t.assigned_to IN ($user_id) AND t.status_id != 7 ";
        }

        $sql .= "ORDER by t.created_at DESC";

        return $sql;
    }

    public static function ticket_info($id)
    {

        $sql =
            DB::table('0_ict_ticket_comments AS c')
            ->leftJoin('users AS u', 'u.id', '=', 'c.user_id')
            ->leftJoin('0_ict_ticket_status AS ts', 'ts.id', '=', 'c.status_id')
            ->select(
                'c.id_tickets AS id',
                'c.comment',
                'u.name AS creator',
                'ts.name AS status',
                'c.created_at AS created_time'
            )
            ->where('c.id_tickets', $id)
            ->get();

        return $sql;
    }

    public static function my_tickets(
        $user_id,
        $user_level,
        $user_division,
        $category_id,
        $search,
        $status_id,
        $project_no
    ) {
        $sql = "SELECT t.*,
                    (SELECT cn.name FROM users cn WHERE cn.id = t.user_id) requestor,
                    u.name AS assigned_name, tc.name AS category_name, tp.name AS priority_name, ts.name AS status_name, '' AS project_code 
                    FROM 0_ict_tickets t 
                    LEFT JOIN users u ON (t.assigned_to = u.id)
                    LEFT JOIN 0_ict_ticket_category tc ON (tc.id = t.category_id)
                    LEFT JOIN 0_ict_ticket_priority tp ON (tp.id = t.priority_id)
                    LEFT JOIN 0_ict_ticket_status ts ON (ts.id = t.status_id)
                    WHERE t.id != -1";

        if ($user_level != 999) {
            if ($user_division == 11) {
                $sql .= " AND (t.assigned_to = $user_id OR t.user_id = $user_id)";
            } else if ($user_id == 50) {
                $sql .= " AND u.division_id = $user_division";
            } else {
                $sql .= " AND t.user_id = $user_id";
            }
        }

        if ($search != '') {
            $sql .= " AND (t.title LIKE '%$search%' OR t.asset_name LIKE '$search%')";
        }

        if ($status_id > 0) {
            $sql .= " AND t.status_id = $status_id";
        }

        if ($category_id > 0) {
            $sql .= " AND t.category_id = $category_id";
        }

        if ($project_no > 0) {
            $sql .= " AND t.project_no = $project_no";
        }

        $sql .= " ORDER by t.id DESC";


        return $sql;
    }

    public static function detail_ticket($id_ticket)
    {
        $sql = "SELECT t.*, 
                    (SELECT cn.name FROM users cn WHERE cn.id = t.user_id) requestor,
                    u.name AS assigned_name, tc.name AS category_name, tp.name AS priority_name, ts.name AS status_name, '' AS project_code 
                FROM 0_ict_tickets t 
                LEFT JOIN users u ON (t.assigned_to = u.id)
                LEFT JOIN 0_ict_ticket_category tc ON (tc.id = t.category_id)
                LEFT JOIN 0_ict_ticket_priority tp ON (tp.id = t.priority_id)
                LEFT JOIN 0_ict_ticket_status ts ON (ts.id = t.status_id)
                WHERE t.id = $id_ticket";

        return $sql;
    }

    public static function ticket_status_list()
    {

        $sql = "SELECT * FROM 0_ict_ticket_status ORDER by id ASC";

        return $sql;
    }

    public static function ticket_category_list()
    {

        $sql = "SELECT * FROM 0_ict_ticket_category ORDER by id ASC";

        return $sql;
    }

    public static function ticket_priority_list()
    {

        $sql = "SELECT * FROM 0_ict_ticket_priority ORDER by id ASC";

        return $sql;
    }

    public static function ticket_ict_member_list()
    {

        $sql = "SELECT * FROM users WHERE division_id = 11 ORDER by id ASC";

        return $sql;
    }

    public static function current_ticket_count()
    {
        $time = Carbon::now();

        $sql = "SELECT t.*, u.name AS requestor, us.name AS assigned_name, tc.name AS category_name, tp.name AS priority_name, ts.name AS status_name, '' AS project_code
                FROM 0_ict_tickets t
                LEFT JOIN users u ON (t.user_id = u.id)
                LEFT JOIN users us ON (t.assigned_to = us.id)
                LEFT JOIN 0_ict_ticket_category tc ON (tc.id = t.category_id)
                LEFT JOIN 0_ict_ticket_priority tp ON (tp.id = t.priority_id)
                LEFT JOIN 0_ict_ticket_status ts ON (ts.id = t.status_id)
                WHERE YEAR(t.created_at) = $time->year AND MONTH(t.created_at) = $time->month GROUP BY t.id ORDER BY t.id DESC";

        return $sql;
    }

    public static function line_chart_jan($year)
    {
        $sql = "SELECT COUNT(id) AS total FROM 0_ict_tickets WHERE YEAR(created_at) = $year AND status_id = 7 AND MONTH(created_at) = 1";

        return $sql;
    }

    public static function line_chart_feb($year)
    {
        $sql = "SELECT COUNT(id) AS total FROM 0_ict_tickets WHERE YEAR(created_at) = $year AND status_id = 7 AND MONTH(created_at) = 2";

        return $sql;
    }

    public static function line_chart_mar($year)
    {
        $sql = "SELECT COUNT(id) AS total FROM 0_ict_tickets WHERE YEAR(created_at) = $year AND status_id = 7 AND MONTH(created_at) = 3";

        return $sql;
    }

    public static function line_chart_apr($year)
    {
        $sql = "SELECT COUNT(id) AS total FROM 0_ict_tickets WHERE YEAR(created_at) = $year AND status_id = 7 AND MONTH(created_at) = 4";

        return $sql;
    }

    public static function line_chart_mei($year)
    {
        $sql = "SELECT COUNT(id) AS total FROM 0_ict_tickets WHERE YEAR(created_at) = $year AND status_id = 7 AND MONTH(created_at) = 5";

        return $sql;
    }

    public static function line_chart_jun($year)
    {
        $sql = "SELECT COUNT(id) AS total FROM 0_ict_tickets WHERE YEAR(created_at) = $year AND status_id = 7 AND MONTH(created_at) = 6";

        return $sql;
    }

    public static function line_chart_jul($year)
    {
        $sql = "SELECT COUNT(id) AS total FROM 0_ict_tickets WHERE YEAR(created_at) = $year AND status_id = 7 AND MONTH(created_at) = 7";

        return $sql;
    }

    public static function line_chart_aug($year)
    {
        $sql = "SELECT COUNT(id) AS total FROM 0_ict_tickets WHERE YEAR(created_at) = $year AND status_id = 7 AND MONTH(created_at) = 8";

        return $sql;
    }

    public static function line_chart_sept($year)
    {
        $sql = "SELECT COUNT(id) AS total FROM 0_ict_tickets WHERE YEAR(created_at) = $year AND status_id = 7 AND MONTH(created_at) = 9";

        return $sql;
    }

    public static function line_chart_okt($year)
    {
        $sql = "SELECT COUNT(id) AS total FROM 0_ict_tickets WHERE YEAR(created_at) = $year AND status_id = 7 AND MONTH(created_at) = 10";

        return $sql;
    }

    public static function line_chart_nov($year)
    {
        $sql = "SELECT COUNT(id) AS total FROM 0_ict_tickets WHERE YEAR(created_at) = $year AND status_id = 7 AND MONTH(created_at) = 11";

        return $sql;
    }

    public static function line_chart_des($year)
    {
        $sql = "SELECT COUNT(id) AS total FROM 0_ict_tickets WHERE YEAR(created_at) = $year AND status_id = 7 AND MONTH(created_at) = 12";

        return $sql;
    }

    public static function line_chart_total($year)
    {
        $sql = "SELECT COUNT(id) AS total FROM 0_ict_tickets WHERE YEAR(created_at) = $year AND status_id = 7 AND MONTH(created_at) IN (1,2,3,4,5,6,7,8,9,10,11,12)";

        return $sql;
    }

    public static function pie_chart_1($year, $month)
    {

        $sql = "SELECT COUNT(id) AS total FROM 0_ict_tickets WHERE category_id = 1 AND YEAR(created_at) = $year AND status_id = 7 AND MONTH(created_at) IN ($month)";

        return $sql;
    }

    public static function pie_chart_2($year, $month)
    {

        $sql = "SELECT COUNT(id) AS total FROM 0_ict_tickets WHERE category_id = 2 AND YEAR(created_at) = $year AND status_id = 7 AND MONTH(created_at) IN ($month)";

        return $sql;
    }

    public static function pie_chart_3($year, $month)
    {

        $sql = "SELECT COUNT(id) AS total FROM 0_ict_tickets WHERE category_id = 3 AND YEAR(created_at) = $year AND status_id = 7 AND MONTH(created_at) IN ($month)";

        return $sql;
    }

    public static function pie_chart_4($year, $month)
    {

        $sql = "SELECT COUNT(id) AS total FROM 0_ict_tickets WHERE category_id = 4 AND YEAR(created_at) = $year AND status_id = 7 AND MONTH(created_at) IN ($month)";

        return $sql;
    }

    public static function pie_chart_5($year, $month)
    {

        $sql = "SELECT COUNT(id) AS total FROM 0_ict_tickets WHERE category_id = 5 AND YEAR(created_at) = $year AND status_id = 7 AND MONTH(created_at) IN ($month)";

        return $sql;
    }

    public static function pie_chart_6($year, $month)
    {

        $sql = "SELECT COUNT(id) AS total FROM 0_ict_tickets WHERE category_id = 6 AND YEAR(created_at) = $year AND status_id = 7 AND MONTH(created_at) IN ($month)";

        return $sql;
    }

    public static function pie_chart_7($year, $month)
    {

        $sql = "SELECT COUNT(id) AS total FROM 0_ict_tickets WHERE category_id = 7 AND YEAR(created_at) = $year AND status_id = 7 AND MONTH(created_at) IN ($month)";

        return $sql;
    }

    public static function pie_chart_8($year, $month)
    {

        $sql = "SELECT COUNT(id) AS total FROM 0_ict_tickets WHERE category_id = 8 AND YEAR(created_at) = $year AND status_id = 7 AND MONTH(created_at) IN ($month)";

        return $sql;
    }

    public static function pie_chart_9($year, $month)
    {

        $sql = "SELECT COUNT(id) AS total FROM 0_ict_tickets WHERE category_id = 9 AND YEAR(created_at) = $year AND status_id = 7 AND MONTH(created_at) IN ($month)";

        return $sql;
    }

    public static function pie_chart_total($year, $month)
    {

        $sql = "SELECT COUNT(id) AS total FROM 0_ict_tickets WHERE YEAR(created_at) = $year AND status_id = 7 AND MONTH(created_at) IN ($month)";

        return $sql;
    }
}
