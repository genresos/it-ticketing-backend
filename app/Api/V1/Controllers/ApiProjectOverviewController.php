<?php

namespace App\Api\V1\Controllers;

use JWTAuth;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProjectBudgetController;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\DB;
use App\Modules\PaginationArr;
use App\Query\QueryProjectCostDetails;
use Carbon\Carbon;
use SiteHelper;
use DateInterval;
use Maatwebsite\Excel\Facades\Excel;
use DateTime;

class ApiProjectOverviewController extends Controller
{
    public function __construct()
    {
        $currentUser = JWTAuth::parseToken()->authenticate();

        $this->user_id = Auth::guard()->user()->id;
        $this->old_id = Auth::guard()->user()->old_id;
        $this->user_level = Auth::guard()->user()->approval_level;
        $this->user_division = Auth::guard()->user()->division_id;
        $this->user_person_id = Auth::guard()->user()->person_id;
        $this->user_name = Auth::guard()->user()->name;
    }
    public static function project_info($project_no)
    {
        $budgetary_cost = DB::table('0_project_budgets')->where('project_no', $project_no)->sum('rab_amount');
        $sql_po_received = DB::table('0_sales_orders as so')->leftJoin('0_sales_order_details as sod', 'sod.order_no', '=', 'so.order_no')->where('so.project_no', $project_no)->where('so.customer_ref', 'not like', '%Waiting%')
            ->select(
                DB::raw("SUM(sod.unit_price * sod.qty_ordered) AS po_received")
            )->first();
        $po_received = empty($sql_po_received->po_received) ? 0 : $sql_po_received->po_received;

        $db = DB::table('0_projects as p')
            ->Join('0_members as m', 'm.person_id', '=', 'p.person_id')
            ->leftJoin('0_project_area as pa', 'pa.area_id', '=', 'p.area_id')
            ->leftJoin('0_project_site as s', 's.site_no', '=', 'p.site_id')
            ->leftJoin('0_debtors_master as dm', 'dm.debtor_no', '=', 'p.debtor_no')
            ->leftJoin('0_project_submission_rab AS rab', function ($join) use ($project_no) {
                $join->on('rab.project_no', '=', 'p.project_no');
                $join->on('rab.approval', '=', DB::raw("7"));
                $join->on('rab.status_id', '=', DB::raw("1"));
            })
            ->where('p.project_no', $project_no)
            ->select(
                'm.name AS project_manager',
                'p.name AS project_name',
                'p.code AS project_code',
                DB::raw("IF(pa.name IS NULL, '', pa.name) AS region"),
                'dm.name AS customer',
                DB::raw("CONCAT_WS('_:',dm.debtor_ref,p.name,m.name,s.site_id,p.code) AS account_unique"),
                DB::raw("IFNULL(SUM(rab.project_value), p.project_value) AS project_value"),
                DB::raw("$budgetary_cost AS budgetary_cost"),
                DB::raw("$po_received AS po_received"),
                DB::raw("IFNULL(MAX(rab.management_cost_pct),0) AS management_cost"),
                DB::raw("IFNULL(SUM(rab.project_value),p.project_value) AS work_started")
            )
            ->get();

        return $db;
    }

    public function curva_project_overview(Request $request)
    {
        $response = [];
        $response['bcws'] = [];
        $response['acwp'] = [];
        $response['bcwp'] = [];

        $bcws = self::budget_cost_schedule($request);
        $response['bcws'] = $bcws;

        $acwp = self::actual_project_overview($request->project_no);
        $count = count($acwp['actual_expenses']);
        $accumulation = 0;
        foreach ($acwp['actual_expenses'] as $index => $item) {
            // Skip elemen terakhir karna isi nya interest (beda bentuk array)
            if ($index < $count - 1) {
                $tmp = [];

                $tmp['period'] = $item['date'];
                $tmp['actual_pct'] = floatval($item['acwp']);
                $accumulation += $item["acwp"];
                $tmp["accumulation_pct"] = $accumulation;
                array_push($response['acwp'], $tmp);
            }
        }

        $bcwp = self::budget_cost_performed($request);
        $response['bcwp'] = $bcwp;

        return $response;
    }

    public function index_actual_project_overview(Request $request)
    {
        $project_no = $request->project_no;
        return self::actual_project_overview($project_no);
    }

    public function index_commited_project_overview(Request $request)
    {
        $project_no = $request->project_no;
        return self::commited_project_overview($project_no);
    }

    private function budget_cost_schedule(Request $request)
    {
        $response = [];
        $ArrData = [];

        $rab = DB::table('0_project_submission_rab')->where('project_no', $request->project_no)->where('approval', 7)->select(DB::raw("SUM(total_budget) AS total"))->first();
        $total_rab = $rab->total;

        $sql = "SELECT amount, periode FROM 0_project_submission_cash_flow_out
                WHERE cash_flow_id IN (SELECT id FROM 0_project_submission_cash_flow
                WHERE trans_no IN (SELECT trans_no FROM 0_project_submission_rab
                WHERE project_no = $request->project_no AND approval = 7))";
        $exe = DB::connection('mysql')->select(DB::raw($sql));
        foreach ($exe as $data) {
            $tmp = [];
            $tmp['amount'] = $data->amount;
            $tmp['periode'] = $data->periode;

            array_push($ArrData, $tmp);
        }

        $totalAmountPerPeriod = [];
        foreach ($ArrData as $item) {
            $periode = date('Y-m', strtotime($item['periode'])); // Ambil hanya tahun dan bulan dari periode
            $amount = $item['amount'];
            if (!isset($totalAmountPerPeriod[$periode])) {
                $totalAmountPerPeriod[$periode] = 0;
            }
            $totalAmountPerPeriod[$periode] += $amount;
        }
        ksort($totalAmountPerPeriod);

        $accumulation_pct = 0;
        foreach ($totalAmountPerPeriod as $periode => $totalAmount) {
            $pct = number_format(($totalAmount * 100) / $total_rab, 2);
            $accumulation_pct += $pct;

            $tmp = [];
            $tmp['period'] = date('F/y', strtotime($periode));
            $tmp['actual_pct'] = floatval($pct);
            $tmp['accumulation_pct'] = round($accumulation_pct, 2);
            // $tmp['amount'] = $totalAmount;



            array_push($response, $tmp);
        }
        return $response;
    }

    private function budget_cost_performed(Request $request)
    {
        $response = [];
        $ArrData = [];
        $sql = "SELECT t1.*
                FROM 0_project_progress t1
                WHERE t1.created_at = (
                    SELECT MAX(t2.created_at)
                    FROM 0_project_progress t2
                    WHERE t2.project_no = $request->project_no AND DATE_FORMAT(t2.created_at, '%Y-%m') = DATE_FORMAT(t1.created_at, '%Y-%m')
                )
                ORDER BY t1.created_at DESC";
        $exe = DB::connection('mysql')->select(DB::raw($sql));
        foreach ($exe as $data) {
            $tmp = [];
            $tmp['progress'] = $data->progress;
            $tmp['periode'] = $data->created_at;

            array_push($ArrData, $tmp);
        }

        $totalProgressPerPeriod = [];
        foreach ($ArrData as $item) {
            $periode = date('Y-m', strtotime($item['periode']));
            $progress = $item['progress'];
            if (!isset($totalProgressPerPeriod[$periode])) {
                $totalProgressPerPeriod[$periode] = 0;
            }
            $totalProgressPerPeriod[$periode] += $progress;
        }
        ksort($totalProgressPerPeriod);

        $accumulation_pct = 0;
        foreach ($totalProgressPerPeriod as $periode => $totalProgress) {
            $accumulation_pct += $totalProgress;
            $tmp = [];
            $tmp['period'] = date('F/y', strtotime($periode));
            $tmp['actual_pct'] = floatval($totalProgress);
            $tmp['accumulation_pct'] = $accumulation_pct;

            array_push($response, $tmp);
        }
        return $response;
    }
    public static function actual_project_overview($project_no)
    {

        $response = [];
        // $project_no = $request->project_no;
        if (empty($project_no) || $project_no == 0) {
            return [];
        }
        $project_info = self::project_info($project_no);
        $project_value = ($project_info[0]->project_value == null) ? 0 : $project_info[0]->project_value;
        $budgetary_cost = ($project_info[0]->budgetary_cost == null) ? 0 : round($project_info[0]->budgetary_cost, 2);
        if ($project_value == 0) {
            $budgetary_cost_pct = 0;
        } else {
            $budgetary_cost_pct_calculate = ($budgetary_cost == null) ? 0 :  number_format((($project_value - ($project_value - $budgetary_cost)) / $project_value), 2);
            $budgetary_cost_pct = ($budgetary_cost_pct_calculate > 100) ? 0 : $budgetary_cost_pct_calculate;
        }

        $po_received = ($project_info[0]->po_received == null) ? 0 : $project_info[0]->po_received;
        $management_cost_pct = ($project_info[0]->management_cost == null) ? 0.075 : $project_info[0]->management_cost;
        $work_started = ($project_info[0]->work_started == null) ? 0 : $project_info[0]->work_started;

        $response['project_manager'] = $project_info[0]->project_manager;
        $response['project_name'] = $project_info[0]->project_name;
        $response['project_code'] = $project_info[0]->project_code;
        $response['region'] = $project_info[0]->region;
        $response['customer'] = $project_info[0]->customer;
        $response['account_unique'] = $project_info[0]->account_unique;
        $response['project_value'] = $project_value;
        $response['budgetary_cost'] =
            [
                'cumulative_total' => $budgetary_cost,
                'gap' => ($project_value - $budgetary_cost),
                'percentage' => 100 - ($budgetary_cost_pct * 100) . "%"
            ];
        $response['po_received'] = $po_received;
        $response['work_started'] = $work_started;

        $response['actual_expenses'] = self::actual_expenses($project_no, $budgetary_cost);

        /*key cost of money*/
        $lastKey = key(array_slice($response['actual_expenses'], -1, 1, true));

        /*key for management cost*/
        $dataWithoutLastKey = array_slice($response['actual_expenses'], 0, -1);

        $totalManagementCost = 0;

        $totalManagementCostPct = 0;

        foreach ($dataWithoutLastKey as $keyMcost) {
            $totalManagementCost += $keyMcost['management_cost_value'];
            $totalManagementCostPct += $keyMcost['management_cost_pct'];
        }


        $response['management_cost'] = $totalManagementCost;
        $totalManagementCostPct = empty($totalManagementCost) ? 0 : number_format(($totalManagementCost / $project_value) * 100, 2) . "%";
        $response['management_cost_pct'] = $totalManagementCostPct;


        $response['cost_of_money'] = $response['actual_expenses'][$lastKey]['cost_of_money'];
        $response['cost_of_money_pct'] = $response['actual_expenses'][$lastKey]['cost_of_money_pct'];

        $invoice_total = 0;
        $paid_total = 0;
        $total_expense = 0;
        foreach ($response['actual_expenses'] as $inv) {
            if (isset($inv['invoice_amount'])) {
                $invoice_total += $inv['invoice_amount'];
            }
            if (isset($inv['paid_amount'])) {
                $paid_total += $inv['paid_amount'];
            }
            if (isset($inv['total_expenses'])) {
                $total_expense += $inv['total_expenses'];
            }
        }
        $response['total_expense'] = $total_expense;
        $margin = $po_received - $total_expense;

        $response['margin'] =
            [
                'cumulative_total' => $po_received - $total_expense,
                'gap' => $po_received - $margin,
                'percentage' => ($po_received == 0) ? 0 : number_format(($margin / $po_received) * 100, 2) . "%"
            ];

        $invoice = $po_received - $invoice_total;
        $response['invoice'] =
            [
                'cumulative_total' => $invoice_total,
                'gap' => ($po_received - $invoice_total),
                'percentage' => ($po_received == 0) ? 0 : number_format((($po_received - $invoice) / $po_received * 100), 2) . "%"
            ];
        $response['paid'] =
            [
                'cumulative_total' => $paid_total,
                'gap' => abs($invoice_total - $paid_total),
                'percentage' => ''
            ];


        return $response;
    }

    public function details_commited_project_overview(Request $request)
    {
        set_time_limit(0);
        $response = null;

        $project_no = $request->project_no;
        if (empty($project_no) || $project_no == 0) {
            return [];
        }

        $sql = QueryProjectCostDetails::sql_Cost_Details($project_no, null, null, 0);

        $response = DB::select(DB::raw($sql));

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public function details_actual_project_overview(Request $request)
    {
        set_time_limit(0);
        $response = null;

        $project_no = $request->project_no;
        if (empty($project_no) || $project_no == 0) {
            return [];
        }

        $sql = QueryProjectCostDetails::sql_Cost_Details($project_no, null, null, 1);

        $response = DB::select(DB::raw($sql));

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public static function actual_expenses($project_no, $budgetary_cost = 0)
    {
        $project_info = self::project_info($project_no);
        $project_value = ($project_info[0]->project_value == null) ? 0 : $project_info[0]->project_value;
        $project_code = $project_info[0]->project_code;
        $budgetary_cost = $project_info[0]->budgetary_cost;
        $po_received = ($project_info[0]->po_received == null) ? 0 : $project_info[0]->po_received;
        $management_cost_pct = ($project_info[0]->management_cost == null) ? 0.075 : $project_info[0]->management_cost;

        $range_project = DB::connection('mysql')->table('0_projects')->where('project_no', $project_no)->first();
        $arr_custom = array();
        $a = Carbon::parse($range_project->start_date);
        $b = Carbon::parse($range_project->end_date);

        $interestPct = ProjectBudgetController::get_interest_rab_val($range_project->start_date);

        $dates = [];
        while ($a->lessThanOrEqualTo($b)) {
            $dates[] = $a->format('Y-m');
            $a->addMonth();
        }

        $totalCostforManagementcost = 0;

        foreach ($dates as $data) {
            $payment = self::customer_payment_value($project_code, $data);
            $cost = (self::calculated_cost_expense($project_no, $data, 1));
            $inv = (self::invoice_value($project_code, $data));
            $tmp = array();
            $tmp['date'] = date('F/y', strtotime($data));
            $tmp['invoice_amount'] = $inv;
            $tmp['total_expenses'] = $cost;
            $tmp['acwp'] =  ($budgetary_cost == 0) ? 0 : number_format(($cost  /  $budgetary_cost) * 100, 2);
            $tmp['paid_amount'] = $payment;

            $totalCostforManagementcost += $cost;

            array_push($arr_custom, $tmp);
        }

        $accumulatedCostTotal = null; // Nilai akumulasi awal
        $accumulatedCostForMencos = null; // Nilai akumulasi awal

        $accumulatedPaymentTotal = 0;

        $final_data = [];

        foreach ($arr_custom as &$item) {
            if ($accumulatedCostTotal !== null) {
                $item["accumulated"] = $accumulatedCostTotal;
            } else {
                $item["accumulated"] = 0;
            }

            if ($accumulatedCostForMencos !== null) {
                $item["accumulatedmancost"] = $accumulatedCostForMencos;
            } else {
                $item["accumulatedmancost"] = 0;
            }

            $accumulatedCostTotal += $item["total_expenses"];
            $accumulatedCostForMencos += $item["total_expenses"];

            $tmp = array();
            $tmp['date'] = $item["date"];
            $tmp['invoice_amount'] = $item["invoice_amount"];
            $tmp['total_expenses'] = $item["total_expenses"];
            $tmp['acwp'] =  $item["acwp"];
            $tmp['paid_amount'] = $item["paid_amount"];
            $tmp['accumulated'] = $item["accumulated"];
            $tmp['accumulatedmancost'] = $accumulatedCostForMencos;

            if ($totalCostforManagementcost < $budgetary_cost) {
                // Calculate management cost percentage using budget
                $management_cost_pct = ($item["total_expenses"] / $budgetary_cost);
                $management_cost_value = $project_value * $management_cost_pct * 0.075;
            } else {
                // Calculate management cost percentage using 
                $management_cost_pct = ($item["total_expenses"] / $totalCostforManagementcost);
                $management_cost_value = $project_value * $management_cost_pct * 0.075;
            }

            // $management_cost_pct = round($item["total_expenses"] / $totalCostforManagementcost, 3);


            // Add the calculated management cost percentage to the item
            $tmp["management_cost_pct"] = $management_cost_pct;

            // Add the calculated management cost value to the item
            $tmp["management_cost_value"] = $management_cost_value;

            array_push($final_data, $tmp);
        }

        // Iterasi melalui setiap item dalam data
        foreach ($final_data as $key => $item) {
            $accumulatedPaymentTotal += $item['paid_amount'];

            $interest_formula = ($item['accumulated'] - $accumulatedPaymentTotal) * $interestPct;

            if ($key == 0) {
                $interest = 0;
            } else {
                $interest = ($interest_formula < 0) ? 0 : $interest_formula;
            }

            $final_data[$key]['cost_of_money_month'] = $interest;
            $final_data[$key]['total_expenses'] += $final_data[$key]['management_cost_value'];
            $final_data[$key]['total_expenses'] += $interest;
        }

        // Inisialisasi variabel untuk jumlah
        $total_cost_of_money = 0;

        // Menghitung jumlah "cost_of_money_month"
        foreach ($final_data as $item_interest) {
            $total_cost_of_money += $item_interest['cost_of_money_month'];
        }

        $cost_of_money = [
            "cost_of_money" => ceil($total_cost_of_money),
            "cost_of_money_pct" => number_format($interestPct * 100, 2) . "%"
        ];

        array_push($final_data, $cost_of_money);

        return $final_data;
    }

    public static function commited_project_overview($project_no)
    {

        $response = [];
        // $project_no = $request->project_no;
        if (empty($project_no) || $project_no == 0) {
            return [];
        }
        $project_info = self::project_info($project_no);
        $project_code = $project_info[0]->project_code;
        $project_value = ($project_info[0]->project_value == null) ? 0 : $project_info[0]->project_value;
        $budgetary_cost = ($project_info[0]->budgetary_cost == null) ? 0 : round($project_info[0]->budgetary_cost, 2);
        if ($project_value == 0) {
            $budgetary_cost_pct = 0;
        } else {
            $budgetary_cost_pct = ($budgetary_cost == null) ? 0 :  number_format((($project_value - ($project_value - $budgetary_cost)) / $project_value), 2);
        }
        $po_received = ($project_info[0]->po_received == null) ? 0 : $project_info[0]->po_received;
        $management_cost_pct = ($project_info[0]->management_cost == null) ? 0.075 : $project_info[0]->management_cost;
        $work_started = ($project_info[0]->work_started == null) ? 0 : $project_info[0]->work_started;

        $response['project_manager'] = $project_info[0]->project_manager;
        $response['project_name'] = $project_info[0]->project_name;
        $response['project_code'] = $project_info[0]->project_code;
        $response['region'] = $project_info[0]->region;
        $response['customer'] = $project_info[0]->customer;
        $response['account_unique'] = $project_info[0]->account_unique;
        $response['project_value'] = $project_value;
        $response['budgetary_cost'] =
            [
                'cumulative_total' => $budgetary_cost,
                'gap' => ($project_value - $budgetary_cost),
                'percentage' => 100 - ($budgetary_cost_pct * 100) . "%"
            ];
        $response['po_received'] = $po_received;
        $response['work_started'] = $work_started;

        $response['commited_cost'] = self::commited_cost($project_no);

        /*key cost of money*/
        $lastKey = key(array_slice($response['commited_cost'], -1, 1, true));

        $total_expense_after_interest = (empty($response['commited_cost']) ? 0 : $response['commited_cost'][$lastKey]['cost_of_money']);

        /*key for management cost*/
        $dataWithoutLastKey = array_slice($response['commited_cost'], 0, -1);

        $totalManagementCost = 0;

        $totalManagementCostPct = 0;

        foreach ($dataWithoutLastKey as $keyMcost) {
            $totalManagementCost += $keyMcost['management_cost_value'];
            $totalManagementCostPct += $keyMcost['management_cost_pct'];
        }


        $response['management_cost'] = $totalManagementCost;
        $totalManagementCostPct = empty($totalManagementCost) ? 0 : number_format(($totalManagementCost / $project_value) * 100, 2) . "%";
        $response['management_cost_pct'] = $totalManagementCostPct;

        $response['cost_of_money'] = $response['commited_cost'][$lastKey]['cost_of_money'];
        $response['cost_of_money_pct'] = $response['commited_cost'][$lastKey]['cost_of_money_pct'];

        $invoice_total = 0;
        $paid_total = 0;
        $total_expense = 0;
        foreach ($response['commited_cost'] as $inv) {
            if (isset($inv['invoice_amount'])) {
                $invoice_total += $inv['invoice_amount'];
            }
            if (isset($inv['paid_amount'])) {
                $paid_total += $inv['paid_amount'];
            }
            if (isset($inv['total_expenses'])) {
                $total_expense += $inv['total_expenses'];
            }
        }

        $response['total_expense'] = $total_expense;
        $margin = $po_received - $total_expense;

        $response['margin'] =
            [
                'cumulative_total' => $po_received - $total_expense,
                'gap' => $po_received - $margin,
                'percentage' => ($po_received == 0) ? 0 : number_format(($margin / $po_received) * 100, 2) . "%"
            ];

        $invoice = $po_received - $invoice_total;
        $response['invoice'] =
            [
                'cumulative_total' => $invoice_total,
                'gap' => ($po_received - $invoice_total),
                'percentage' => ($po_received == 0) ? 0 : number_format((($po_received - $invoice) / $po_received * 100), 2) . "%"
            ];
        $response['paid'] =
            [
                'cumulative_total' => $paid_total,
                'gap' => abs($invoice_total - $paid_total),
                'percentage' => ''
            ];

        return $response;
    }

    public static function commited_cost($project_no)
    {
        $project_info = self::project_info($project_no);
        $project_value = ($project_info[0]->project_value == null) ? 0 : $project_info[0]->project_value;
        $project_code = $project_info[0]->project_code;
        $budgetary_cost = $project_info[0]->budgetary_cost;
        $po_received = ($project_info[0]->po_received == null) ? 0 : $project_info[0]->po_received;
        $management_cost_pct = ($project_info[0]->management_cost == null) ? 0.075 : $project_info[0]->management_cost;
        $range_project = DB::connection('mysql')->table('0_projects')->where('project_no', $project_no)->first();
        $arr_custom = array();
        $a = Carbon::parse($range_project->start_date);
        $b = Carbon::parse($range_project->end_date);

        $interestPct = ProjectBudgetController::get_interest_rab_val($range_project->start_date);

        $dates = [];
        while ($a->lessThanOrEqualTo($b)) {
            $dates[] = $a->format('Y-m');
            $a->addMonth();
        }

        $totalCostforManagementcost = 0;

        foreach ($dates as $data) {
            $payment = self::customer_payment_value($project_code, $data);
            $cost = (self::calculated_cost_expense($project_no, $data, 0));
            $inv = (self::invoice_value($project_code, $data));
            $tmp = array();
            $tmp['date'] = date('F/y', strtotime($data));
            $tmp['invoice_amount'] = $inv;
            $tmp['total_expenses'] = $cost;
            $tmp['acwp'] =  ($budgetary_cost == 0) ? 0 : number_format(($cost  /  $budgetary_cost) * 100, 2);
            $tmp['paid_amount'] = $payment;

            $totalCostforManagementcost += $cost;


            array_push($arr_custom, $tmp);
        }

        $accumulatedCostTotal = null; // Nilai akumulasi awal
        $accumulatedCostForMencos = null; // Nilai akumulasi awal

        $accumulatedPaymentTotal = 0;

        $final_data = [];

        foreach ($arr_custom as &$item) {
            if ($accumulatedCostTotal !== null) {
                $item["accumulated"] = $accumulatedCostTotal;
            } else {
                $item["accumulated"] = 0;
            }
            if ($accumulatedCostForMencos !== null) {
                $item["accumulatedmancost"] = $accumulatedCostForMencos;
            } else {
                $item["accumulatedmancost"] = 0;
            }

            $accumulatedCostTotal += $item["total_expenses"];
            $accumulatedCostForMencos += $item["total_expenses"];

            $tmp = array();
            $tmp['date'] = $item["date"];
            $tmp['invoice_amount'] = $item["invoice_amount"];
            $tmp['total_expenses'] = $item["total_expenses"];
            $tmp['acwp'] =  $item["acwp"];
            $tmp['paid_amount'] = $item["paid_amount"];
            $tmp['accumulated'] = $item["accumulated"];
            $tmp['accumulatedmancost'] = $accumulatedCostForMencos;

            if ($totalCostforManagementcost < $budgetary_cost) {
                // Calculate management cost percentage using budget
                $management_cost_pct = ($item["total_expenses"] / $budgetary_cost);
            } else {
                // Calculate management cost percentage using 
                $management_cost_pct = ($item["total_expenses"] / $totalCostforManagementcost);
            }

            // $management_cost_pct = round($item["total_expenses"] / $totalCostforManagementcost, 3);


            // Add the calculated management cost percentage to the item
            $tmp["management_cost_pct"] = $management_cost_pct;

            // Calculate management cost value
            $management_cost_value = $project_value * $management_cost_pct * 0.075;

            // Add the calculated management cost value to the item
            $tmp["management_cost_value"] = round($management_cost_value, 2);


            array_push($final_data, $tmp);
        }

        // // // Iterasi melalui setiap item dalam data
        foreach ($final_data as $key => $item) {
            $accumulatedPaymentTotal += $item['paid_amount'];

            $interest_formula = ($item['accumulated'] - $accumulatedPaymentTotal) * $interestPct;

            if ($key == 0) {
                $interest = 0;
            } else {
                $interest = ($interest_formula < 0) ? 0 : $interest_formula;
            }

            $final_data[$key]['cost_of_money_month'] = $interest;
            $final_data[$key]['total_expenses'] += $final_data[$key]['management_cost_value'];
            $final_data[$key]['total_expenses'] += $interest;
        }

        // Inisialisasi variabel untuk jumlah
        $total_cost_of_money = 0;

        // Menghitung jumlah "cost_of_money_month"
        foreach ($final_data as $item_interest) {
            $total_cost_of_money += $item_interest['cost_of_money_month'];
        }

        $cost_of_money = [
            "cost_of_money" => ceil($total_cost_of_money),
            "cost_of_money_pct" => number_format($interestPct * 100, 2) . "%"
        ];

        array_push($final_data, $cost_of_money);

        return $final_data;
    }

    function PaymentgroupByPaidDate($data)
    {
        $result = [];

        foreach ($data as $item) {
            if ($item['paid_date'] === null) {
                continue;
            }
            if (!isset($result[$item['paid_date']])) {
                $result[$item['paid_date']] = 0;
            }
            $result[$item['paid_date']] += $item['invoice_amount'];
        }

        $grouped_data = [];

        foreach ($result as $key => $value) {
            $month_key = substr($key, 0, 7);

            if (!isset($grouped_data[$month_key])) {
                $grouped_data[$month_key] = 0;
            }
            $grouped_data[$month_key] += $value;
        }

        return $grouped_data;
    }

    public static function invoice_value($project_code, $date)
    {
        $sql = DB::connection('mysql')->select(DB::raw("SELECT 
            'CUSTOMER INVOICES' AS doc_source, 
              dt.trans_no, 
              dt.reference, 
              dtd.stock_id,
              dtd.description,
              dtd.quantity,
              dtd.unit_price,
              site.site_id,
              dtd.site_name,
              dtd.unit_price,
              dt.type, 
              dt.ov_discount, 
              dt.ov_line_amount AS dpp, 
              dt.ov_amount AS line_amount_after_disc, 
              dt.rate, 
              dt.curr_code AS currency_code,
              dt.prepaid_tax AS paidtax,
              dt.ov_discount AS inv_deduction, 
              dt.prepaid_tax_amount AS paidtax_amount,  
              dt.ppn_excluded, 
              dm.name AS customer_name, 
              dt.order_reference,
              dt.tran_date,
              dt.date_received,
	            CASE WHEN SUM(dtd.quantity * dtd.unit_price * dt.rate) IS NULL THEN 0 ELSE SUM(dtd.quantity * dtd.unit_price * dt.rate) END 
	            AS invoice_amount,
              (
                  SELECT MAX(date_alloc) 
                  FROM 0_cust_allocations_2020 alloc
                  WHERE alloc.trans_no_to = dt.trans_no AND trans_type_from=12   
              ) AS paid_date
          FROM 0_debtor_trans_details_2020 dtd
          INNER JOIN 0_debtor_trans_2020 dt ON (dt.trans_no = dtd.debtor_trans_no AND dt.type=dtd.debtor_trans_type)
          LEFT OUTER JOIN 0_debtors_master dm ON (dt.debtor_no = dm.debtor_no)  
          LEFT JOIN 0_project_site site ON (dtd.site_no = site.site_no)      
          WHERE dtd.debtor_trans_type=10  AND dt.is_proforma=0 AND DATE_FORMAT(dt.tran_date, '%Y-%m') = '$date' AND  dtd.sales_order_detail_id IN
          (
            SELECT sod.id
            FROM 0_sales_order_details sod
            INNER JOIN 0_sales_orders so ON (so.order_no = sod.order_no)
            LEFT JOIN 0_projects p ON (p.project_no = so.project_no)
            WHERE p.code= '$project_code'
          )
          GROUP BY dt.type, dt.trans_no
          UNION
          SELECT 
            'CUSTOMER INVOICES' AS doc_source, 
              dt.trans_no, 
              dt.reference, 
              dtd.stock_id,
              dtd.description,
              dtd.quantity,
              dtd.unit_price,
              site.site_id,
              dtd.site_name, 
              dtd.unit_price,
              dt.type, 
              dt.ov_discount, 
              dt.ov_line_amount AS dpp, 
              dt.ov_amount AS line_amount_after_disc, 
              dt.rate, 
              dt.curr_code AS currency_code, 
              dt.prepaid_tax AS paidtax,
              dt.ov_discount AS inv_deduction, 
              dt.prepaid_tax_amount AS paidtax_amount,  
              dt.ppn_excluded, 
              dm.name AS customer_name, 
              dt.order_reference,
              dt.tran_date,
              dt.date_received,
	            CASE WHEN SUM(dtd.quantity * dtd.unit_price * dt.rate) 
              IS NULL THEN 0 ELSE SUM(dtd.quantity * dtd.unit_price * dt.rate) END 
	            AS invoice_amount,
              (
                  SELECT MAX(date_alloc) 
                  FROM 0_cust_allocations alloc
                  WHERE alloc.trans_no_to = dt.trans_no AND trans_type_from=12   
              ) AS paid_date
          FROM 0_debtor_trans_details dtd
          INNER JOIN 0_debtor_trans dt ON (dt.trans_no = dtd.debtor_trans_no AND dt.type=dtd.debtor_trans_type)
          LEFT OUTER JOIN 0_debtors_master dm ON (dt.debtor_no = dm.debtor_no)       
          LEFT JOIN 0_project_site site ON (dtd.site_no = site.site_no)  
          WHERE dtd.debtor_trans_type=10  AND dt.is_proforma=0 AND DATE_FORMAT(dt.tran_date, '%Y-%m') = '$date' AND dtd.sales_order_detail_id IN
          (
            SELECT sod.id
            FROM 0_sales_order_details sod
            INNER JOIN 0_sales_orders so ON (so.order_no = sod.order_no)
            LEFT JOIN 0_projects p ON (p.project_no = so.project_no)
            WHERE p.code= '$project_code'
          )
          GROUP BY dt.type, dt.trans_no"));
        $total = 0;
        foreach ($sql as $data) {
            $total += ($data->invoice_amount);
        }

        return $total;
    }

    public static function customer_payment_value($project_code, $date = '')
    {
        $sql = DB::connection('mysql')->select(DB::raw("SELECT 
	      CASE WHEN SUM(dtd.quantity * dtd.unit_price * dt.rate) 
              IS NULL THEN 0 ELSE SUM(dtd.quantity * dtd.unit_price * dt.rate) END 
	            AS invoice_amount,
              (
                  SELECT MAX(date_alloc) 
                  FROM 0_cust_allocations alloc
                  WHERE alloc.trans_no_to = dt.trans_no AND trans_type_from=12 AND DATE_FORMAT(date_alloc, '%Y-%m') = '$date'
              ) AS paid_date
          FROM 0_debtor_trans_details dtd
          INNER JOIN 0_debtor_trans dt ON (dt.trans_no = dtd.debtor_trans_no AND dt.type=dtd.debtor_trans_type)       
          WHERE dtd.debtor_trans_type=10  AND dt.is_proforma=0 AND  dtd.sales_order_detail_id IN
          (
            SELECT sod.id
            FROM 0_sales_order_details sod
            INNER JOIN 0_sales_orders so ON (so.order_no = sod.order_no)
            LEFT JOIN 0_projects p ON (p.project_no = so.project_no)
            WHERE p.code= '$project_code'
          )
          GROUP BY dt.type, dt.trans_no"));
        $totalPaidAmount = 0;
        foreach ($sql as $item) {
            // Periksa apakah "paid_date" tidak null
            if (!is_null($item->paid_date)) {
                $totalPaidAmount += $item->invoice_amount;
            }
        }
        return $totalPaidAmount;
    }

    public static function calculated_cost_expense($project_no, $date, $type)
    {
        /* type 0 = commited cost , type 1 = actual cost */

        if ($type == 0) {
            $extend_sql = "(
					SELECT 
						CASE WHEN SUM(pod.quantity_ordered * pod.unit_price * pod.rate) IS NULL THEN 0 ELSE 
						SUM(pod.quantity_ordered * pod.unit_price * pod.rate) END 
					FROM 0_purch_orders po 
					INNER JOIN 0_purch_order_details pod ON (po.order_no = pod.order_no)
                    LEFT JOIN 0_project_budgets pb ON (pod.project_budget_id = pb.project_budget_id)
					WHERE po.doc_type_id NOT IN (4008,4009) AND po.status_id=0 AND pod.quantity_ordered > 0
					AND pb.budget_type_id NOT IN (72,704,701,703,706,63,55,33,69,52,714) AND pod.project_code=p.code AND DATE_FORMAT(po.ord_date, '%Y-%m') = '$date'
				) po";
        } else if ($type == 1) {
            $extend_sql = "(
                            SELECT COALESCE(SUM(invd.qty_invd * pod.unit_price * pod.rate), 0)
                            FROM 0_purch_orders po 
                            INNER JOIN 0_purch_order_details pod ON po.order_no = pod.order_no
                            LEFT JOIN 0_project_budgets pb ON pod.project_budget_id = pb.project_budget_id
                            LEFT JOIN (
                                SELECT 
                                    invd.po_detail_item_id, 
                                    SUM(quantity) AS qty_invd, 
                                    GROUP_CONCAT(inv.trans_no) AS group_of_trans_no
                                FROM 0_supp_invoice_items invd
                                INNER JOIN 0_supp_trans inv ON inv.trans_no = invd.supp_trans_no
                                AND inv.type = invd.supp_trans_type             
                                WHERE inv.type = 20
                                GROUP BY invd.po_detail_item_id, inv.trans_no 
                            ) invd ON invd.po_detail_item_id = pod.po_detail_item   
                            WHERE po.doc_type_id NOT IN (4008, 4009) 
                            AND po.status_id = 0 
                            AND pod.quantity_ordered > 0 
                            AND pb.budget_type_id NOT IN (72, 704, 701, 703, 706, 63, 55, 33, 69,52,714)
                            AND pod.project_code = p.code 
                            AND DATE_FORMAT(po.ord_date, '%Y-%m') = '$date'
                        ) AS po";
        }
        $sql = DB::connection('mysql')->select(DB::raw("SELECT 
                (
                    SELECT COALESCE(SUM(cad.act_amount), 0)
                    FROM 0_cashadvance ca 
                    INNER JOIN 0_cashadvance_details cad ON ca.trans_no = cad.trans_no
                    LEFT JOIN 0_project_budgets pb ON cad.project_budget_id = pb.project_budget_id
                    WHERE ca.ca_type_id IN (
                        SELECT ca_type_id FROM 0_cashadvance_types
                        WHERE type_group_id IN (1)
                    )			
                    AND cad.status_id < 2 
                    AND cad.project_no = p.project_no 
                    AND pb.budget_type_id NOT IN (72, 704, 701, 703, 706, 63, 55, 33, 69,52,714)
                    AND cad.approval >= 7 
                    AND DATE_FORMAT(ca.tran_date, '%Y-%m') = '$date'
                ) AS ca_amount,
                (
                    SELECT COALESCE(SUM(cad.act_amount), 0)
                    FROM 0_cashadvance ca 
                    INNER JOIN 0_cashadvance_details cad ON ca.trans_no = cad.trans_no
                    LEFT JOIN 0_project_budgets pb ON cad.project_budget_id = pb.project_budget_id
                    WHERE ca.ca_type_id = 4			
                    AND cad.status_id < 2 
                    AND cad.project_no = p.project_no 
                    AND pb.budget_type_id NOT IN (72, 704, 701, 703, 706, 63, 55, 33, 69,52,714)
                    AND cad.approval >= 7 
                    AND DATE_FORMAT(ca.tran_date, '%Y-%m') = '$date'
                ) AS rmb_amount,
                (
                    SELECT COALESCE(SUM(gl.amount), 0)
                    FROM 0_gl_trans gl		
                    LEFT JOIN 0_project_budgets pb ON gl.project_budget_id = pb.project_budget_id
                    WHERE gl.project_code = p.code 
                    AND pb.budget_type_id NOT IN (72, 704, 701, 703, 706, 63, 55, 33, 69,52,714)
                    AND gl.amount > 0
                    AND gl.type = 1 
                    AND DATE_FORMAT(gl.tran_date, '%Y-%m') = '$date'   	 		
                ) AS bp_2021,
                $extend_sql,
                (
                    SELECT COALESCE(SUM(ps.salary), 0)
                    FROM 0_project_salary_budget ps
                    LEFT JOIN 0_project_budgets pb ON ps.budget_id = pb.project_budget_id
                    WHERE ps.project_no = p.project_no 
                    AND pb.budget_type_id NOT IN (72, 704, 701, 703, 706, 63, 55, 33, 69,52,714) 
                    AND DATE_FORMAT(ps.date, '%Y-%m') = '$date'
                ) AS salary,
                (
                    SELECT COALESCE(SUM(prv.amount), 0)
                    FROM 0_project_rent_vehicle prv
                    LEFT JOIN 0_project_budgets pb ON prv.budget_id = pb.project_budget_id
                    INNER JOIN 0_projects pj on (pj.code = prv.project_code)
                    WHERE pj.project_no = p.project_no
                    AND pb.budget_type_id NOT IN (72, 704, 701, 703, 706, 63, 55, 33, 69,52,714) 
                    AND DATE_FORMAT(prv.periode, '%Y-%m') = '$date'
                ) AS rent_vehicle,
                (
                    SELECT COALESCE(SUM(prt.total), 0)
                    FROM 0_project_rent_tools prt
                    LEFT JOIN 0_project_budgets pb ON prt.budget_id = pb.project_budget_id
                    INNER JOIN 0_projects pj on (pj.project_no = pb.project_no)
                    WHERE pj.project_no = p.project_no
                    AND pb.budget_type_id NOT IN (72, 704, 701, 703, 706, 63, 55, 33, 69,52,714) 
                    AND DATE_FORMAT(prt.tran_date, '%Y-%m') = '$date'
                ) AS rent_tools
            FROM 0_projects p
            WHERE p.project_no = $project_no"));


        $total = 0;
        foreach ($sql as $data) {
            $total += ($data->ca_amount + $data->rmb_amount + $data->bp_2021 + $data->po + $data->salary + $data->rent_vehicle + $data->rent_tools);
        }

        return $total;
    }

    public function update_bcwp_project(Request $request)
    {
        $user_id = $this->user_id;
        $time = (empty($request->date)) ? Carbon::now() : Carbon::parse($request->date);
        // $time = Carbon::parse('2024-04-01');

        // Menetapkan jam, menit, dan detik secara acak
        $time->hour(mt_rand(0, 23)); // Jam antara 0 dan 23
        $time->minute(mt_rand(0, 59)); // Menit antara 0 dan 59
        $time->second(mt_rand(0, 59)); // Detik antara 0 dan 59

        $bcwp = DB::table('0_project_progress')->where('project_no', $request->project_no)->sum('progress');
        if (($bcwp + $request->progress) > 100) {
            return response()->json(['error' => [
                'message' => 'Progress tidak boleh melebihi 100%',
                'status_code' => 403,
            ]], 403);
        }
        DB::beginTransaction();
        try {

            DB::table('0_project_progress')
                ->insert(array(
                    'project_no' => $request->project_no,
                    'progress' => $request->progress,
                    'remark' => $request->remark,
                    'created_by' => $user_id,
                    'created_at' => $time->toDateTimeString()
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

    public function get_transaction_date($project_no)
    {
        $sql = "SELECT ca.tran_date AS tran_date
            FROM 0_cashadvance_details cad
            INNER JOIN 0_cashadvance ca ON (cad.trans_no = ca.trans_no)
            WHERE cad.project_no = $project_no
            UNION
            SELECT po.ord_date AS tran_date FROM 0_purch_order_details pod
            INNER JOIN 0_purch_orders po ON (pod.order_no = po.order_no)
            WHERE pod.project_no = $project_no
            UNION
            SELECT gl.tran_date AS tran_date
            FROM 0_gl_trans gl	
            INNER JOIN 0_projects p ON (p.code = gl.project_code)	
            WHERE p.project_no=$project_no
            AND gl.amount > 0
            AND gl.type=1 AND gl.account NOT IN ('501012','501006', '601033')";
        $exec = DB::connection('mysql')->select(DB::raw($sql));

        return $exec;
    }

    public function overview_budget_v_cost(Request $request)
    {
        $response = [];
        $project_info = self::project_info($request->project_no);
        $response['project_info'] = [
            'project_manager' => $project_info[0]->project_manager,
            'project_name' => $project_info[0]->project_name,
            'project_code' => $project_info[0]->project_code,
            'region' => $project_info[0]->region,
            'customer' => $project_info[0]->customer,
            'account_unique' => $project_info[0]->account_unique
        ];
        $response['header'] = [];

        $actual = self::actual_project_overview($request->project_no);
        $committed = self::commited_project_overview($request->project_no);

        $tmp = [];
        $tmp['project_value'] = [
            'rab_plan' => $committed['project_value'],
            'commited' => $committed['project_value'],
            'actual' => $actual['project_value']
        ];
        $tmp['budgetary_cost'] = [
            'rab_plan' => $committed['budgetary_cost']['cumulative_total'],
            'commited' => $committed['budgetary_cost']['cumulative_total'],
            'actual' => $actual['budgetary_cost']['cumulative_total']
        ];

        $margin_value = ($committed['project_value'] - $committed['budgetary_cost']['cumulative_total']);

        $tmp['margin_plan_value'] = [
            'rab_plan' => $margin_value,
            'commited' => 0,
            'actual' => 0
        ];

        $tmp['margin_plan_pct'] = [
            'rab_plan' => ($committed['po_received'] == 0) ? 0 : number_format(($margin_value / $committed['po_received']) * 100, 2) . "%",
            'commited' => 0,
            'actual' => 0
        ];

        $tmp['po_received'] = [
            'rab_plan' => 0,
            'commited' => $committed['po_received'],
            'actual' => $actual['po_received']
        ];
        $tmp['work_started'] = [
            'rab_plan' => 0,
            'commited' => 0,
            'actual' => 0
        ];
        $tmp['invoice'] = [
            'rab_plan' => 0,
            'commited' => $committed['invoice']['cumulative_total'],
            'actual' => $actual['invoice']['cumulative_total']
        ];

        $tmp['paid'] = [
            'rab_plan' => 0,
            'commited' => $committed['paid']['cumulative_total'],
            'actual' => $actual['paid']['cumulative_total']
        ];

        $tmp['total_expense'] = [
            'rab_plan' => 0,
            'commited' => $committed['total_expense'],
            'actual' => $actual['total_expense'],
            'gap' => ($committed['budgetary_cost']['cumulative_total'] - $actual['total_expense']),
            'percentage' => ($committed['budgetary_cost']['cumulative_total'] == 0) ? 0 : number_format(($actual['total_expense'] / $committed['budgetary_cost']['cumulative_total']) * 100, 2) . "%"

        ];

        $margin = ($committed['total_expense'] - $committed['invoice']['cumulative_total']);
        $tmp['margin'] = [
            'rab_plan' => 0,
            'commited' => 0,
            'actual' => $margin,
            'gap' => ($committed['invoice']['cumulative_total'] == 0) ? 0 : number_format(($margin / $committed['invoice']['cumulative_total']) * 100, 2) . "%",
            'percentage' => 0

        ];

        array_push($response['header'], $tmp);

        $commitedDetails = QueryProjectCostDetails::sql_Cost_Details($request->project_no, null, null, 0);
        $actualDetails = QueryProjectCostDetails::sql_Cost_Details($request->project_no, null, null, 1);

        $execcommitedDetails = DB::select(DB::raw($commitedDetails));
        $execactualDetails = DB::select(DB::raw($actualDetails));

        foreach ($execcommitedDetails as &$itemCommitted) {
            $itemCommitted->type = 0;
        }
        unset($itemCommitted);

        foreach ($execactualDetails as &$itemActual) {
            $itemActual->type = 0;
        }
        unset($itemActual);

        // Gabungin dlu data berdasarkan budget_type_id, _year, dan _month
        $mergedData = [];

        foreach ($execcommitedDetails as $item) {
            $key = $item->budget_type_id . '-' . $item->_year . '-' . $item->_month;
            if (!isset($mergedData[$key])) {
                $mergedData[$key] = [
                    'budget_type_id' => $item->budget_type_id,
                    'cost_name' => $item->cost_name,
                    'project_no' => $item->project_no,
                    'project_budget_id' => $item->project_budget_id,
                    '_year' => $item->_year,
                    '_month' => $item->_month,
                    'commited_cost' => $item->amount,
                    'actual_cost' => null
                ];
            } else {
                $mergedData[$key]['commited_cost'] += $item->amount;
            }
        }
        foreach ($execactualDetails as $item) {
            $key = $item->budget_type_id . '-' . $item->_year . '-' . $item->_month;
            if (isset($mergedData[$key])) {
                $mergedData[$key]['actual_cost'] = $item->amount;
            }
        }

        // Mengelompokkan dan menghitung total commited_cost dan actual_cost
        $groupedData = collect(array_values($mergedData))->groupBy('budget_type_id')->map(function ($items) {
            $totalCommitedCost = $items->sum('commited_cost');
            $totalActualCost = $items->sum('actual_cost');

            // Mengambil nilai cost_name dari item pertama karena semuanya sama dalam grup
            $firstItem = $items->first();
            $rab_amount = DB::table('0_project_budgets')->where('project_no', $firstItem['project_no'])->where('budget_type_id', $firstItem['budget_type_id'])->sum('rab_amount');
            $status = ($rab_amount >= $totalCommitedCost) ? 'Under Cost' : 'Over Cost';

            return [
                "budget_type_id" => $firstItem['budget_type_id'],
                "cost_name" => $firstItem['cost_name'],
                "rab_plan" => $rab_amount,
                "commited_cost" => $totalCommitedCost,
                "actual_cost" => $totalActualCost,
                "gap" => $rab_amount - $totalCommitedCost,
                "absorption budget" => empty($rab_amount) ? 0 : number_format(($totalCommitedCost / $rab_amount) * 100, 2) . "%",
                "status" => $status
            ];
        })->values()->toArray();

        $response['body'] = $groupedData;
        $response['footer'] = [];

        $tmp2 = [];
        $tmp2['management_cost'] = [
            'rab_plan' => $committed['management_cost'],
            'commited' => $committed['management_cost'],
            'actual' => $actual['management_cost']
        ];

        $tmp2['interest'] = [
            'rab_plan' => 0, //perlu tanya bpc dari mana
            'commited' => $committed['cost_of_money'],
            'actual' => $actual['cost_of_money']
        ];

        array_push($response['footer'], $tmp2);


        return $response;
    }

    public static function calculate_management_cost() {}
}
