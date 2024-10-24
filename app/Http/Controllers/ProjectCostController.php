<?php

namespace App\Http\Controllers;

use JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use URL;
use App\Query\QueryProjectList;
use App\Query\QueryProjectCost;
use App\Exports\ProjectPerformanceSummaryExport;


class ProjectCostController extends Controller
{
    // public static function project_performance_summary($project_no)
    // {
    //     $filename = "PROJECT PERFORMANCE SUMMARY";

    //     return Excel::download(new ProjectPerformanceSummaryExport($project_no), "$filename.xlsx");
    // }

    public static function project_performance_summary($project_no)
    {
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

    public static function project_performance_summary_monthly($project_no)
    {
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

            // $result_invoice = DB::select(DB::raw(QueryProjectCost::get_project_cost_default($project_no)));

            // foreach ($result_invoice as $data) {
            $p_expense = [];
            $p_expense['budget_type_id'] = $key->name;

            $result_cost = DB::select(DB::raw(QueryProjectCost::get_cost_monthly($key->budget_type_id, $project_no)));

            foreach ($result_cost as $item) {
                $items = [];
                $items['year'] = $item->_year;
                $items['month'] = $item->_month;
                $items['amount'] = $item->amount;
                $p_expense['monthly'][] = $items;
            }
            // array_push($response, $p_expense);
            // }
            $tmp4['breakdown_cost'] = $p_expense;
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

    public static function curdate_project_transaction()
    {
        DB::table('0_project_log')->delete();
        $get_trx = DB::select(DB::raw(QueryProjectCost::current_date_project_transaction()));

        foreach ($get_trx as $data) {
            self::check_compare_cost_to_order_over($data->project_no);
        }
    }

    public static function check_compare_cost_to_order_over($project_no)
    {
        $check = DB::select(DB::raw(QueryProjectCost::check_compare_cost_to_order_over($project_no)));

        foreach ($check as $data) {

            $order_amount = $data->po_amount;

            $cost_amount = ($data->ca_amount +
                $data->rmb_amount +
                $data->bp_2020 +
                $data->bp_2021 +
                $data->po +
                $data->stk_atk +
                $data->salary +
                $data->bpd_2020 +
                $data->bpd_2021 +
                $data->vehicle_rental +
                $data->tool_laptop +
                $data->deduction_2020 +
                $data->deduction_2021 + ($order_amount * ($data->rate / 100)));


            if ((($cost_amount / $order_amount) * 100) > 70) {
                self::active_inactive_project_code($project_no, $cost_amount, $order_amount, 1, 3);
                return true;
            } else {
                self::active_inactive_project_code($project_no, $cost_amount, $order_amount, 0, 1);
                return false;
            }
        }
    }
    public static function active_inactive_project_code($project_no, $cost_amount = 0, $order_amount = 0, $inactive = 0, $status)
    {

        DB::beginTransaction();
        try {

            $auto = ($inactive == 0) ? 1 : 0;

            // inactive=0, mengaktifkan kembali, 
            // jika proses inactive karena proses 70%, bukan inactive manual dilakukan PC
            // maka inactive bisa dilakukan
            DB::table('0_projects')->where('project_no', $project_no)->where('auto_inactive', $auto)
                ->update(array(
                    'inactive' => $inactive,
                    'project_status_id' => $status,
                    'auto_inactive' => $inactive
                ));


            // add log
            if ($status == 3) {
                $remark = ($inactive == 0) ? 'Activate Project Code' : 'The cost is more than 70% of the order amount';
                //$remark = $inactive; //($inactive ==0)? 'Activate Project Code':'The cost is more than 70% of the order amount';
                DB::table('0_project_log')
                    ->insert(array(
                        'project_no' => $project_no,
                        'tran_date' => date('Y-m-d'),
                        'remark' => $remark,
                        'cost_amount' => $cost_amount,
                        'order_amount' => $order_amount
                    ));
            }
            // Commit Transaction
            DB::commit();

            return response()->json([
                'success' => true
            ], 200);
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }
    //===========================================================================================================//

    public static function check_compare_rab_v_cost()
    {
        $get_trx = DB::select(DB::raw(QueryProjectCost::current_date_project_transaction()));

        foreach ($get_trx as $data) {
            self::rab_v_cost($data->project_no);
        }
    }

    public static function rab_v_cost($project_no)
    {
        $check = DB::select(DB::raw(QueryProjectCost::check_compare_cost_to_order_over($project_no)));
        foreach ($check as $data) {
            $rab = $data->rab_amount;
            $order_amount = $data->po_amount;
            $cost = ($data->ca_amount +
                $data->rmb_amount +
                $data->bp_2020 +
                $data->bp_2021 +
                $data->po +
                $data->stk_atk +
                $data->salary +
                $data->bpd_2020 +
                $data->bpd_2021 +
                $data->vehicle_rental +
                $data->tool_laptop +
                $data->deduction_2020 +
                $data->deduction_2021 + ($order_amount * ($data->rate / 100)));

            $percentage = ($cost * 100) / $rab;
            if (
                $percentage >= 30 && $percentage <= 49.999
            ) {
                $reason = '30%';
                if (empty($already_lock_by_percentage)) {
                    return self::auto_inactive_project_by_rab($project_no, $reason, $rab, $cost);
                } else {
                    return null;
                }
            } else if ($percentage >= 50 && $percentage <= 69.999) {
                $reason = "50%";
                if (empty($already_lock_by_percentage)) {
                    return self::auto_inactive_project_by_rab(
                        $project_no,
                        $reason,
                        $rab,
                        $cost
                    );
                } else {
                    return null;
                }
            } else if ($percentage >= 70 && $percentage <= 89.999) {
                $reason = "70%";
                if (empty($already_lock_by_percentage)) {
                    return self::auto_inactive_project_by_rab(
                        $project_no,
                        $reason,
                        $rab,
                        $cost
                    );
                } else {
                    return null;
                }
            } else if ($percentage >= 90) {
                $reason = "90%";
                if (empty($already_lock_by_percentage)) {
                    return self::auto_inactive_project_by_rab($project_no, $reason, $rab, $cost);
                } else {
                    return null;
                }
            } else {
                return null;
            }
        }
    }

    public static function auto_inactive_project_by_rab($project_no, $reason, $rab, $cost)
    {
        $already_lock_by_percentage = DB::table('history_project_inactive')->where('project_no', $project_no)->where('reason', $reason)->first();
        if (empty($already_lock_by_percentage)) {
            DB::beginTransaction();
            try {

                DB::table('0_projects')->where('project_no', $project_no)
                    ->update(array('inactive' => 1));

                DB::table('history_project_inactive')
                    ->insert(array(
                        'project_no' => $project_no,
                        'rab' => $rab,
                        'cost' => $cost,
                        'reason' => $reason,
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
            return null;
        }
    }
}
