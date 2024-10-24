<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\TestingExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Query\QueryProjectList;
use App\Query\QueryProjectCost;

use Illuminate\Support\Facades\DB;

class TestingController extends Controller
{
    // public static function index()
    // {
    //     Excel::create('New file', function ($excel) {

    //         $excel->sheet('First sheet', function ($sheet) {
    //             $sheet->loadView('excel.exp');
    //         });
    //     })->export('xls');;
    // }

    public static function export()
    {
        $project_no = 27617;
        // return Excel::download(new TestingExport, 'PROJECT PERFORMANCE SUMMARY.xlsx');
        $response = [];
        $project_info = DB::select(DB::raw(QueryProjectList::get_project($project_no)));
        $order_info = DB::select(DB::raw(QueryProjectCost::get_project_order_info($project_no)));
        $invoice_info = DB::select(DB::raw(QueryProjectCost::get_total_invoice($project_no)));
        $invoice_less_2021 = DB::select(DB::raw(QueryProjectCost::get_project_invoice_less_2021($project_no)));
        $expenses_total = DB::select(DB::raw(QueryProjectCost::get_project_cost_summary_default($project_no)));
        $salary_data = DB::select(DB::raw(QueryProjectCost::get_project_cost_salary_default($project_no)));
        $atk_data = DB::select(DB::raw(QueryProjectCost::get_project_cost_atk_default($project_no)));
        $result = DB::select(DB::raw(QueryProjectCost::get_project_cost_default($project_no)));
        $rental_mobil_data = DB::select(DB::raw(QueryProjectCost::get_project_cost_rental_vehicle_mobil_default($project_no)));
        $rental_motor_data = DB::select(DB::raw(QueryProjectCost::get_project_cost_rental_vehicle_motor_default($project_no)));
        $tools_data = DB::select(DB::raw(QueryProjectCost::get_project_cost_rental_tools_default($project_no)));
        $tools_ict_data = DB::select(DB::raw(QueryProjectCost::get_project_cost_rental_tools_ict_default($project_no)));
        $customer_deduction_data = DB::select(DB::raw(QueryProjectCost::get_project_cost_customer_deduction_default($project_no)));

        $tmp1 = [];
        $tmp1['project_manager'] = $project_info[0]->person_name;
        $tmp1['project_name'] = $project_info[0]->name;
        $tmp1['project_code'] = $project_info[0]->code;
        $tmp1['customer'] = $project_info[0]->debtor_name;
        $response['project_info'] = $tmp1;

        $tmp2 = [];
        $tmp2['project_value'] = 0;
        $tmp2['po_received'] = $order_info[0]->order_amount;
        $tmp2['total_budget_amount'] = $order_info[0]->budget_amount;
        $tmp2['work_started'] = 0;
        $tmp2['work_started_po'] = 0;
        $tmp2['total_invoice_amount'] = $invoice_info[0]->amount + $invoice_less_2021[0]->invoice_amount;
        $tmp2['total_invoice_paid_amount'] = $order_info[0]->paid_amount + $invoice_less_2021[0]->paid_amount;
        $response['project_delivey'] = $tmp2;

        $tmp3 = [];
        $tmp3['budget_total'] = $expenses_total[0]->budget_amount;
        $tmp3['cost_total'] = $expenses_total[0]->amount;
        $response['total_expenses'] = $tmp3;


        foreach ($result as $key) {
            $tmp4 = [];
            if ($key->cost_amount == null) {
                $cost_amount = 0;
            } else {
                $cost_amount = $key->cost_amount;
            }
            $tmp4['name'] = $key->name;
            $tmp4['budget_amount'] = $key->budget_amount;
            $tmp4['cost_amount'] = $cost_amount;
            $response['project_expenses'][] = $tmp4;
        }

        $tmp5 = [];
        /* salary */
        $item1['name'] = $salary_data[0]->name;
        $item1['budget_amount'] = $salary_data[0]->budget_amount;
        if ($salary_data[0]->cost_amount == null) {
            $cost_amount_item1 = 0;
        } else {
            $cost_amount_item1 = $salary_data[0]->cost_amount;
        }
        $item1['cost_amount'] = $cost_amount_item1;
        $tmp5['salary'] = $item1;

        /* Atk */
        $item2['name'] = $atk_data[0]->name;
        $item2['budget_amount'] = $atk_data[0]->budget_amount;
        if ($atk_data[0]->cost_amount == null) {
            $cost_amount_item2 = 0;
        } else {
            $cost_amount_item2 = $atk_data[0]->cost_amount;
        }
        $item2['cost_amount'] = $cost_amount_item2;
        $tmp5['atk'] = $item2;

        /* Rental Mobil */
        $item3['name'] = $rental_mobil_data[0]->name;
        $item3['budget_amount'] = $rental_mobil_data[0]->budget_amount;
        if ($rental_mobil_data[0]->cost_amount == null) {
            $cost_amount_item3 = 0;
        } else {
            $cost_amount_item3 = $rental_mobil_data[0]->cost_amount;
        }
        $item3['cost_amount'] = $cost_amount_item3;
        $tmp5['car_rental'] = $item3;

        /* Rental Motor */
        $item4['name'] = $rental_motor_data[0]->name;
        $item4['budget_amount'] = $rental_motor_data[0]->budget_amount;
        if ($rental_motor_data[0]->cost_amount == null) {
            $cost_amount_item4 = 0;
        } else {
            $cost_amount_item4 = $rental_motor_data[0]->cost_amount;
        }
        $item4['cost_amount'] = $cost_amount_item4;
        $tmp5['motor_rental'] = $item4;

        /* Rental Tools */
        $item5['name'] = $tools_data[0]->name;
        $item5['budget_amount'] = $tools_data[0]->budget_amount;
        if ($tools_data[0]->cost_amount == null) {
            $cost_amount_item5 = 0;
        } else {
            $cost_amount_item5 = $tools_data[0]->cost_amount;
        }
        $item5['cost_amount'] = $cost_amount_item5;
        $tmp5['tools_rental'] = $item5;

        /* Rental Tools ICT */
        $item6['name'] = $tools_ict_data[0]->name;
        $item6['budget_amount'] = $tools_ict_data[0]->budget_amount;
        if ($tools_ict_data[0]->cost_amount == null) {
            $cost_amount_item6 = 0;
        } else {
            $cost_amount_item6 = $tools_ict_data[0]->cost_amount;
        }
        $item6['cost_amount'] = $cost_amount_item6;
        $tmp5['tools_ict_rental'] = $item6;

        /* Customer Deduction */
        $item7['name'] = $customer_deduction_data[0]->name;
        $item7['budget_amount'] = $customer_deduction_data[0]->budget_amount;
        if ($customer_deduction_data[0]->cost_amount == null) {
            $cost_amount_item7 = 0;
        } else {
            $cost_amount_item7 = $customer_deduction_data[0]->cost_amount;
        }
        $item7['cost_amount'] = $cost_amount_item7;
        $tmp5['customer_deduction'] = $item7;

        $response['other_expenses'] = $tmp5;

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }
}
