<?php

namespace App\Api\V1\Controllers;

use JWTAuth;
use App\Http\Controllers\Controller;
use App\Http\Controllers\TicketsController;
use Illuminate\Http\Request;
use Auth;
use App\Modules\PaginationArr;

class ICTTicketController extends Controller
{

    public function __construct()
    {
        $currentUser = JWTAuth::parseToken()->authenticate();

        $this->user_id = Auth::guard()->user()->id;
        $this->user_level = Auth::guard()->user()->approval_level;
        $this->user_division = Auth::guard()->user()->division_id;
    }
    public function get_tickets(Request $request)
    {
        $myArray = TicketsController::tickets(
            $this->user_id,
            $this->user_level
        );
        return $myArray;
    }

    public function get_my_tickets(Request $request)
    {

        $myPage = $request->page;
        $myUrl = $request->url();
        $query = $request->query();

        if (empty($request->perpage)) {
            $perPage = 10;
        } else {
            $perPage = $request->perpage;
        }
        if (empty($request->category_id)) {
            $category_id = 0;
        } else {
            $category_id = $request->category_id;
        }

        if (empty($request->search)) {
            $search = '';
        } else {
            $search = $request->search;
        }

        if (empty($request->status_id)) {
            $status_id = 0;
        } else {
            $status_id = $request->status_id;
        }

        if (empty($request->project_no)) {
            $project_no = 0;
        } else {
            $project_no = $request->project_no;
        }

        $myArray = TicketsController::my_tickets(
            $this->user_id,
            $this->user_level,
            $this->user_division,
            $category_id,
            $search,
            $status_id,
            $project_no
        );

        return PaginationArr::arr_pagination(
            $myArray,
            $myPage,
            $perPage,
            $myUrl,
            $query
        );
    }

    public function detail_tickets(Request $request)
    {
        $myArray = TicketsController::detail_ticket(
            $request->id_ticket
        );

        return $myArray;
    }

    public function create_tickets(Request $request)
    {
        $assigned = "$request->assigned_1,$request->assigned_2,$request->assigned_3";
        $title = $request->title;
        $description = $request->description;
        $priority_id = $request->priority_id;
        $asset_name = $request->asset_name;
        $project_no = $request->project_no;

        $myQuery = TicketsController::create(
            $title,
            $description,
            $priority_id,
            $assigned,
            $asset_name,
            $project_no,
            $this->user_id
        );
        return $myQuery;
    }

    public function update_tickets(Request $request, $id)
    {
        $status_id = $request->status_id;
        $category_id = $request->category_id;
        $priority_id = $request->priority_id;
        $comment = $request->comment;

        $myQuery = TicketsController::update(
            $id,
            $status_id,
            $category_id,
            $comment,
            $this->user_id
        );
        return $myQuery;
    }

    public function reopen_tickets(Request $request, $id)
    {
        $comment = $request->comment;
        $myQuery = TicketsController::reopen(
            $id,
            $comment,
            $this->user_id
        );
        return $myQuery;
    }

    public function status_ticket()
    {
        $myArray = TicketsController::status_list();
        return $myArray;
    }

    public function category_ticket()
    {
        $myArray = TicketsController::category_list();
        return $myArray;
    }

    public function priority_ticket()
    {
        $myArray = TicketsController::priority_list();
        return $myArray;
    }

    public function export_tickets(Request $request)
    {
        $myExport = TicketsController::export($request->from, $request->to);
        return $myExport;
    }

    public function ict_members()
    {
        $myArray = TicketsController::ict_member_list();
        return $myArray;
    }

    public function issue_summary()
    {
        $myArray = TicketsController::summary();
        return $myArray;
    }

    public function current_issue()
    {
        $myArray = TicketsController::current_ticket_count();
        return $myArray;
    }

    public function issue_line_chart_year($year)
    {
        $myArray = TicketsController::line_chart_year($year);
        return $myArray;
    }

    public function issue_pie_chart_year(Request $request)
    {
        $year = $request->year;
        $month = $request->month;

        $myArray = TicketsController::pie_chart_year($year, $month);
        return $myArray;
    }

    public function issue_close_in_a_day(Request $request)
    {

        $cat_id = empty($request->category_id) ? 0 : $request->category_id;

        return TicketsController::close_in_a_day($cat_id);
    }

    public function table_list(Request $request)
    {
        $year = $request->year;
        $month = $request->month;

        $myArray = TicketsController::tickets_table_list($year, $month);
        return $myArray;
    }
}
