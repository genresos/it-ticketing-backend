<?php

namespace App\Api\V1\Controllers;

use JWTAuth;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\DB;
use App\Modules\PaginationArr;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Api\V1\Controllers\ApiProjectOverviewController;

class ApiOsproController extends Controller
{
    public function post_attendance(Request $request)
    {
        $attendance_date = $request->attendance_date;
        $attendance_start = $request->attendance_start;
        $attendance_finish = $request->attendance_finish;
        $emp_id = $request->emp_id;
        $user = DB::table('0_hrm_employees')->where('emp_id', $emp_id)->first();
        if (empty($user->id)) {
            return 'Emp ID tidak ditemukan !';
        }
        $normal_start = '08:00';
        $normal_finish = '17:00';
        $not_late = (abs(strtotime($attendance_start)) <= abs(strtotime($normal_start))) ? 't' : 'f';

        $time_late = abs(strtotime($attendance_start)) - abs(strtotime($normal_start));
        $late_duration = (round($time_late / 60) <= 0) ? 0 : round($time_late / 60);

        $time_duration = abs(strtotime($attendance_finish)) - abs(strtotime($normal_start));
        $total_duration = (round($time_duration / 60) <= 0) ? 0 : round($time_duration / 60);

        DB::beginTransaction();
        try {

            DB::CONNECTION('pgsql')->TABLE('hrd_attendance')
                ->INSERT(array(
                    'id_employee' =>  $user->id,
                    'attendance_date' =>  $attendance_date,
                    'attendance_start' =>  $attendance_start,
                    'attendance_finish' =>  $attendance_finish,
                    'normal_start' =>  $normal_start,
                    'normal_finish' =>  $normal_finish,
                    'not_late' =>  $not_late,
                    'note' =>  'Import From Ospro',
                    'late_duration' =>  $late_duration,
                    'shift_type' =>  1,
                    'total_duration' =>  $total_duration,
                    'is_absence' =>  'f',
                    'created' =>  Carbon::now(),
                    'modified_by' =>  -1,
                    'is_overtime' =>  'f',
                    'auto_overtime' =>  'f',
                    'l5' =>  0,
                    'l1_5' =>  NULL,
                    'l2b' =>  NULL
                ));

            DB::commit();

            return 'success';
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }

    public function project_cost(Request $request)
    {
        $response = [];
        $project_info = DB::table('0_projects')->where('code', $request->project_code)->first();
        $budgetary_cost = DB::table('0_project_budgets')->where('project_no', $project_info->project_no)->sum('amount');
        $project_no = $project_info->project_no;
        $actual = self::actual_cost($project_no);
        $commited = self::commited_cost($project_no);
        $response['budget_amount'] = $budgetary_cost;
        $response['currency'] = '';
        $response['project_code'] = $project_info->code;
        $response['project_name'] = $project_info->name;
        $response['total_commited_cost'] = $commited['total_expense'];
        $response['total_cost'] =  $actual['total_expense'];
        $response['total_invoice_amount'] = $actual['invoice']['cumulative_total'];
        $response['total_invoice_paid_amount'] = $actual['paid']['cumulative_total'];
        $response['total_order'] = floatval($actual['po_received']);


        return $response;
    }

    public static function actual_cost($project_no)
    {
        return ApiProjectOverviewController::actual_project_overview($project_no);
    }

    public static function commited_cost($project_no)
    {
        return ApiProjectOverviewController::commited_project_overview($project_no);
    }
}
