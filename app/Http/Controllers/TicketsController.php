<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Validator, Redirect, Response, File;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ICTTicketsExport;
use App\Query\QueryTickets;
use DateTime;

class TicketsController extends Controller
{
    //
    use Helpers;

    public static function tickets(
        $user_id,
        $user_level
    ) {
        $response = [];

        $sql = QueryTickets::tickets(
            $user_id,
            $user_level
        );

        $tickets = DB::select(DB::raw($sql));

        foreach ($tickets as $data) {

            $tmp = [];
            $tmp['id'] = $data->id;
            $tmp['title'] = $data->title;
            $tmp['description'] = $data->description;
            $tmp['category_id'] = $data->category_name;
            $tmp['priority'] = $data->priority_name;
            $tmp['status_id'] = $data->status_name;
            $tmp['asset_name'] = $data->asset_name;
            $tmp['project_no'] = $data->project_no;
            $tmp['project_code'] = $data->project_code;
            $tmp['requestor'] = $data->requestor;
            $tmp['accepted_time'] = $data->accepted_time;
            $tmp['end_time'] = $data->end_time;
            $tmp['created_time'] = $data->created_at;

            $sql_comment = QueryTickets::ticket_info($data->id);
            if (!empty($ticket_comments)) {
                $tmp['ticket_comments'][] = $sql_comment;
            } else if (empty($ticket_comments)) {
                $items = [];

                $items['id'] = 0;
                $items['comment'] = 0;
                $items['creator'] = 0;
                $items['status'] = 0;
                $items['created_time'] = 0;
                $tmp['ticket_comments'][] = $items;
            }
            array_push($response, $tmp);
        }
        return response()->json([
            'success' => true,
            'data' => $response
        ]);
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
        $response = [];

        $sql = QueryTickets::my_tickets(
            $user_id,
            $user_level,
            $user_division,
            $category_id,
            $search,
            $status_id,
            $project_no
        );

        $tickets = DB::select(DB::raw($sql));

        foreach ($tickets as $data) {

            $tmp = [];
            $tmp['id'] = $data->id;
            $tmp['requestor'] = $data->requestor;
            $tmp['title'] = $data->title;
            $tmp['description'] = $data->description;
            $tmp['category_id'] = $data->category_name;
            $tmp['priority'] = $data->priority_name;
            $tmp['status_id'] = $data->status_name;
            $tmp['asset_name'] = $data->asset_name;
            $tmp['project_no'] = $data->project_no;
            $tmp['project_code'] = $data->project_code;
            $tmp['assigned_to'] = $data->assigned_name;
            $tmp['accepted_time'] = $data->accepted_time;
            $tmp['end_time'] = $data->end_time;
            $tmp['created_time'] = $data->created_at;

            $timeStart = new DateTime($data->created_at);
            $timeFinish = new DateTime($data->end_time);
            $diff = $timeFinish->diff($timeStart);
            if ($data->status_id == 7) {
                $tmp['finish_in_hour'] = $diff->format('%h');
            } else {
                $tmp['finish_in_hour'] = null;
            }


            // $sql_comment = QueryTickets::ticket_info($data->id);
            // $tmp['ticket_comments'][] = $sql_comment;

            array_push($response, $tmp);
        }

        return $response;
    }

    public static function detail_ticket(
        $id_ticket
    ) {
        $response = [];

        $sql = QueryTickets::detail_ticket(
            $id_ticket
        );

        $tickets = DB::select(DB::raw($sql));

        foreach ($tickets as $data) {

            $tmp = [];
            $tmp['id'] = $data->id;
            $tmp['requestor'] = $data->requestor;
            $tmp['title'] = $data->title;
            $tmp['description'] = $data->description;
            $tmp['category_id'] = $data->category_name;
            $tmp['priority'] = $data->priority_name;
            $tmp['status_id'] = $data->status_name;
            $tmp['asset_name'] = $data->asset_name;
            $tmp['project_no'] = $data->project_no;
            $tmp['project_code'] = $data->project_code;
            $tmp['assigned_to'] = $data->assigned_name;
            $tmp['accepted_time'] = $data->accepted_time;
            $tmp['end_time'] = $data->end_time;
            $tmp['created_time'] = $data->created_at;

            $timeStart = new DateTime($data->created_at);
            $timeFinish = new DateTime($data->updated_at);
            $diff = $timeFinish->diff($timeStart);
            if ($data->status_id == 7) {
                $tmp['finish_in_hour'] = $diff->format('%h');
            } else {
                $tmp['finish_in_hour'] = null;
            }

            $sql_comment = QueryTickets::ticket_info($data->id);
            $tmp['ticket_comments'] = $sql_comment;

            array_push($response, $tmp);
        }

        return $response;
    }

    public static function create(
        $title,
        $description,
        $priority_id,
        $assigned,
        $asset_name,
        $project_no,
        $user_id
    ) {
        DB::beginTransaction();
        try {
            DB::table('0_ict_tickets')->insert(array(
                'title' => $title,
                'description' => $description,
                'priority_id' => $priority_id,
                'assigned_to' => $assigned,
                'asset_name' => $asset_name,
                'project_no' => $project_no,
                'user_id' => $user_id,
                'created_at' => Carbon::now()
            ));

            DB::commit();

            // Semua proses benar

            return response()->json([
                'success' => true
            ]);
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }

    public static function update(
        $id,
        $status_id,
        $category_id,
        $comment,
        $user_id
    ) {
        DB::beginTransaction();
        try {
            //** UPDATE TICKETS

            if ($status_id == 2) {
                DB::table('0_ict_tickets')->where('id', $id)
                    ->update(array(
                        'status_id' => $status_id,
                        'category_id' => $category_id,
                        'assigned_to' => $user_id,
                        'accepted_time' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ));
            } else if ($status_id == 7) {
                DB::table('0_ict_tickets')->where('id', $id)
                    ->update(array(
                        'status_id' => $status_id,
                        'assigned_to' => $user_id,
                        'end_time' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ));
            } else if ($status_id != 7 || $status_id != 2) {
                DB::table('0_ict_tickets')->where('id', $id)
                    ->update(array(
                        'status_id' => $status_id,
                        'updated_at' => Carbon::now()
                    ));
            }

            DB::table('0_ict_ticket_comments')->insert(array(
                'id_tickets' => $id,
                'status_id' => $status_id,
                'comment' => $comment,
                'user_id' => $user_id
            ));

            DB::commit();

            return response()->json([
                'success' => true
            ]);
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }

    public static function reopen($id, $comment, $user_id)
    {
        DB::beginTransaction();
        try {

            DB::table('0_ict_tickets')->where('id', $id)
                ->update(array('status_id' => 6));

            DB::table('0_ict_ticket_comments')->insert(array(
                'id_tickets' => $id,
                'status_id' => 6,
                'comment' => $comment,
                'user_id' => $user_id
            ));

            DB::commit();
            return response()->json([
                'success' => true
            ]);
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }

    public static function status_list()
    {
        $response = [];

        $sql = QueryTickets::ticket_status_list();

        $status = DB::select(DB::raw($sql));

        foreach ($status as $data) {

            $tmp = [];
            $tmp['id'] = $data->id;
            $tmp['name'] = $data->name;

            array_push($response, $tmp);
        }
        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public static function category_list()
    {
        $response = [];

        $sql = QueryTickets::ticket_category_list();

        $category = DB::select(DB::raw($sql));

        foreach ($category as $data) {

            $tmp = [];
            $tmp['id'] = $data->id;
            $tmp['name'] = $data->name;

            array_push($response, $tmp);
        }
        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public static function priority_list()
    {
        $response = [];

        $sql = QueryTickets::ticket_priority_list();

        $priority = DB::select(DB::raw($sql));

        foreach ($priority as $data) {

            $tmp = [];
            $tmp['id'] = $data->id;
            $tmp['name'] = $data->name;

            array_push($response, $tmp);
        }
        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public static function export($from, $to)
    {
        $filename = "ICT-ISSUE-LIST";
        return Excel::download(new ICTTicketsExport($from, $to), "$filename.xlsx");
    }

    public static function ict_member_list()
    {
        $response = [];

        $sql = QueryTickets::ticket_ict_member_list();

        $members = DB::select(DB::raw($sql));

        foreach ($members as $data) {
            $tmp = [];
            $tmp['id'] = $data->id;
            $tmp['name'] = $data->name;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public static function summary()
    {
        $response = [];

        $time = Carbon::now();

        $all_issue = DB::table('0_ict_tickets')->count();
        $new_issue = DB::table('0_ict_tickets')
            ->where('status_id', '<', 7)
            ->whereYear('created_at', '=', $time->year)
            ->whereMonth('created_at', '=', $time->month)
            ->count();
        $closed_issue = DB::table('0_ict_tickets')->where('status_id', '=', 7)->count();

        $response['all_issue'] = $all_issue;
        $response['new_issue'] = $new_issue;
        $response['closed_issue'] = $closed_issue;

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public static function current_ticket_count()
    {
        $response = [];

        $sql = QueryTickets::current_ticket_count();
        $issue = DB::select(DB::raw($sql));

        foreach ($issue as $data) {
            $date = strftime("%d %b %Y", strtotime($data->created_at));

            $tmp = [];
            $tmp['id'] = $data->id;
            $tmp['requestor'] = $data->requestor;
            $tmp['title'] = $data->title;
            $tmp['description'] = $data->description;
            $tmp['category_id'] = $data->category_name;
            $tmp['priority'] = $data->priority_name;
            $tmp['status_id'] = $data->status_name;
            $tmp['asset_name'] = $data->asset_name;
            $tmp['project_no'] = $data->project_no;
            $tmp['project_code'] = $data->project_code;
            $tmp['assigned_to'] = $data->assigned_name;
            $tmp['accepted_time'] = $data->accepted_time;
            $tmp['end_time'] = $data->end_time;
            $tmp['created_time'] = $data->created_at;

            // $timeStart = new DateTime($data->created_at);
            // $timeFinish = new DateTime($data->end_time);
            // $diff = $timeFinish->diff($timeStart);
            // if ($data->status_id == 7) {
            //     $tmp['finish_in_hour'] = $diff->format('%h');
            // } else {
            //     $tmp['finish_in_hour'] = null;
            // }
            $response['issue'][] = $tmp;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public static function line_chart_year($year)
    {
        $jan   = DB::select(DB::raw(QueryTickets::line_chart_jan($year)));
        $feb   = DB::select(DB::raw(QueryTickets::line_chart_feb($year)));
        $mar   = DB::select(DB::raw(QueryTickets::line_chart_mar($year)));
        $apr   = DB::select(DB::raw(QueryTickets::line_chart_apr($year)));
        $mei   = DB::select(DB::raw(QueryTickets::line_chart_mei($year)));
        $jun   = DB::select(DB::raw(QueryTickets::line_chart_jun($year)));
        $jul   = DB::select(DB::raw(QueryTickets::line_chart_jul($year)));
        $aug   = DB::select(DB::raw(QueryTickets::line_chart_aug($year)));
        $sept  = DB::select(DB::raw(QueryTickets::line_chart_sept($year)));
        $okt   = DB::select(DB::raw(QueryTickets::line_chart_okt($year)));
        $nov   = DB::select(DB::raw(QueryTickets::line_chart_nov($year)));
        $des   = DB::select(DB::raw(QueryTickets::line_chart_des($year)));
        $total = DB::select(DB::raw(QueryTickets::line_chart_total($year)));

        foreach ($jan as $data) {
            $januari = [];
            $januari['total_issue'] = $data->total;
        }
        foreach ($feb as $data) {
            $febuari = [];
            $febuari['total_issue'] = $data->total;
        }
        foreach ($mar as $data) {
            $maret = [];
            $maret['total_issue'] = $data->total;
        }
        foreach ($apr as $data) {
            $april = [];
            $april['total_issue'] = $data->total;
        }
        foreach ($mei as $data) {
            $meii = [];
            $meii['total_issue'] = $data->total;
        }
        foreach ($jun as $data) {
            $juni = [];
            $juni['total_issue'] = $data->total;
        }
        foreach ($jul as $data) {
            $juli = [];
            $juli['total_issue'] = $data->total;
        }
        foreach ($aug as $data) {
            $aug = [];
            $aug['total_issue'] = $data->total;
        }
        foreach ($sept as $data) {
            $september = [];
            $september['total_issue'] = $data->total;
        }
        foreach ($okt as $data) {
            $oktober = [];
            $oktober['total_issue'] = $data->total;
        }
        foreach ($nov as $data) {
            $november = [];
            $november['total_issue'] = $data->total;
        }
        foreach ($des as $data) {
            $desember = [];
            $desember['total_issue'] = $data->total;
        }
        foreach ($total as $data) {
            $total = [];
            $total['issue'] = $data->total;
        }

        $response = [];
        $line_chart = [];
        $line_chart['January'] = $januari;
        $line_chart['February'] = $febuari;
        $line_chart['March'] = $maret;
        $line_chart['April'] = $april;
        $line_chart['May'] = $meii;
        $line_chart['June'] = $juni;
        $line_chart['July'] = $juli;
        $line_chart['August'] = $aug;
        $line_chart['September'] = $september;
        $line_chart['October'] = $oktober;
        $line_chart['November'] = $november;
        $line_chart['December'] = $desember;
        $line_chart['total_issue'] = $total;
        $response['line_chart'][] = $line_chart;

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public static function pie_chart_year($year, $month)
    {
        $time = Carbon::now();
        if (empty($year)) {
            $year = $time->year;
        } else if (!empty($year)) {
            $year = $year;
        }

        if (empty($month)) {
            $month = '1,2,3,4,5,6,7,8,9,10,11,12';
        } else if (!empty($month)) {
            $month = $month;
        }

        $cat1 = DB::select(DB::raw(QueryTickets::pie_chart_1($year, $month)));
        $cat2 = DB::select(DB::raw(QueryTickets::pie_chart_2($year, $month)));
        $cat3 = DB::select(DB::raw(QueryTickets::pie_chart_3($year, $month)));
        $cat4 = DB::select(DB::raw(QueryTickets::pie_chart_4($year, $month)));
        $cat5 = DB::select(DB::raw(QueryTickets::pie_chart_5($year, $month)));
        $cat6 = DB::select(DB::raw(QueryTickets::pie_chart_6($year, $month)));
        $cat7 = DB::select(DB::raw(QueryTickets::pie_chart_7($year, $month)));
        $cat8 = DB::select(DB::raw(QueryTickets::pie_chart_8($year, $month)));
        $cat9 = DB::select(DB::raw(QueryTickets::pie_chart_9($year, $month)));
        $cat_ttl = DB::select(DB::raw(QueryTickets::pie_chart_total($year, $month)));


        foreach ($cat1 as $data) {
            $cat_1 = [];
            $cat_1['total_issue'] = $data->total;
        }
        foreach ($cat2 as $data) {
            $cat_2 = [];
            $cat_2['total_issue'] = $data->total;
        }
        foreach ($cat3 as $data) {
            $cat_3 = [];
            $cat_3['total_issue'] = $data->total;
        }
        foreach ($cat4 as $data) {
            $cat_4 = [];
            $cat_4['total_issue'] = $data->total;
        }
        foreach ($cat5 as $data) {
            $cat_5 = [];
            $cat_5['total_issue'] = $data->total;
        }
        foreach ($cat6 as $data) {
            $cat_6 = [];
            $cat_6['total_issue'] = $data->total;
        }
        foreach ($cat7 as $data) {
            $cat_7 = [];
            $cat_7['total_issue'] = $data->total;
        }
        foreach ($cat8 as $data) {
            $cat_8 = [];
            $cat_8['total_issue'] = $data->total;
        }
        foreach ($cat9 as $data) {
            $cat_9 = [];
            $cat_9['total_issue'] = $data->total;
        }
        foreach ($cat_ttl as $data) {
            $cat_total = [];
            $cat_total['total_issue'] = $data->total;
        }

        $response = [];

        $pie_chart = [];
        $pie_chart['DailyChecking'] = $cat_1;
        $pie_chart['eProject'] = $cat_2;
        $pie_chart['GeneralRequest'] = $cat_3;
        $pie_chart['HardwareInstallation'] = $cat_4;
        $pie_chart['MonthlyPreventiveMaintenance'] = $cat_5;
        $pie_chart['Networking'] = $cat_6;
        $pie_chart['Question'] = $cat_7;
        $pie_chart['SoftwareInstallation'] = $cat_8;
        $pie_chart['Suggestion'] = $cat_9;
        $pie_chart['AllCategory'] = $cat_total;


        $response['pie_chart'][] = $pie_chart;

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public static function close_in_a_day($cat_id)
    {
        $response = [];

        $sql = DB::table('0_ict_tickets')->select(DB::raw("COUNT(id) as count"), DB::raw("GROUP_CONCAT(id SEPARATOR ',') AS id"))->where('status_id', 7)
            ->when($cat_id > 0, function ($query) use ($cat_id) {
                $query->where('category_id', $cat_id);
            })
            ->groupBy(DB::raw("DATE(created_at)"))->orderBy('created_at', 'desc')->get();
        foreach ($sql as $data) {
            $tmp = [];
            $value = DB::table('0_ict_tickets')->select('id', 'category_id', 'created_at', 'updated_at')->whereRaw("id IN ($data->id)")->get();
            $tmp['count'] = $data->count;
            foreach ($value as $key) {
                $tmp_aging = [];
                $tmp_aging['category'] = $key->category_id;
                $start = date_create($key->created_at);
                $end = date_create($key->updated_at);
                $diff = date_diff($start, $end);
                $aging = $diff->format('%a');
                $tmp_aging['diff'] = $aging;




                $tmp['aging'][] = $aging;
            }
            $tmp['in_a_day'] = count(array_keys($tmp['aging'], 0)) + count(array_keys($tmp['aging'], 1));

            array_push($response, $tmp);
        }

        $perday = 0;
        $total = 0;

        $avg = [];

        foreach ($response as $arr) {

            $perday += $arr['in_a_day'];
            $total += $arr['count'];

            array_push($avg, $arr['in_a_day']);
        }

        $average = array_sum($avg) / count($avg);
        return response()->json([
            'success' => true,
            'data' => [
                'average_close_in_a_day' => round($average),
                'total_closed_in_a_day' => $perday
            ]
        ]);
    }

    public static function tickets_table_list($year, $month)
    {

        $response = [];
        $time = Carbon::now();
        if (!empty($year)) {
            $year = $year;
        } else if (empty($year)) {
            $year = $time->year;
        }
        if (!empty($month)) {
            $month = $month;
        } else if (empty($month)) {
            $month = "1,2,3,4,5,6,7,8,9,10,11,12";
        }
        $ict_members = DB::table('users')
            ->select(DB::raw('GROUP_CONCAT(id SEPARATOR ",") AS id_users'))
            ->where('division_id', '=', 11)
            ->first();

        $id_user = explode(',', $ict_members->id_users);
        $index = 0;

        foreach ($id_user as $key => $val) {
            $name_arr = $index;

            $sql = "SELECT COUNT(t.id) AS issue, u.name AS username
                    FROM 0_ict_tickets t
                    LEFT JOIN users u ON (t.assigned_to = u.id)
                    WHERE t.assigned_to = $id_user[$name_arr] AND t.status_id = 7 AND YEAR(t.created_at) = $year AND MONTH(t.created_at) IN ($month)";

            $table = DB::select(DB::raw($sql));

            foreach ($table as $key) {
                $keys = [];
                $keys['user'] = $key->username;
                $keys['total_issue'] = $key->issue;


                $sql1 = "SELECT *
                     FROM 0_ict_ticket_category";

                $table1 = DB::select(DB::raw($sql1));

                foreach ($table1 as $item) {

                    $items = [];
                    $cat_name = str_replace(' ', '', $item->name);

                    $sql2 = "SELECT COUNT(id) as issue
                             FROM 0_ict_tickets WHERE assigned_to = $id_user[$name_arr] AND category_id = $item->id AND status_id = 7 AND YEAR(created_at) = $year AND MONTH(created_at) IN ($month)";

                    $table2 = DB::select(DB::raw($sql2));

                    foreach ($table2 as $data) {
                        $tmp['total'] = $data->issue;
                    }

                    $items[$cat_name] = $tmp['total'];
                    $keys['issue_by_category'][] = $items;
                }

                array_push($response, $keys);
            }

            $index++;
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }
}
