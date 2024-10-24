<?php

namespace App\Api\V1\Controllers;

use JWTAuth;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\DB;
use App\Query\QueryProjectCost;
use App\Modules\PaginationArr;
use App\Http\Controllers\ProjectBudgetController;
use Carbon\Carbon;
use SiteHelper;
use PDF;
use File;
use URL;
use Illuminate\Support\Facades\Input;
use App\Jobs\RABNotification;
use DateInterval;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RABExport;

class ApiTestingController extends Controller
{
    // public $gClient;

    // public function __construct()
    // {
    //     $this->gCdlient = new \Google_Client();

    //     $this->gClient->setApplicationName(env('APP_NAME')); // ADD YOUR AUTH2 APPLICATION NAME (WHEN YOUR GENERATE SECRATE KEY)
    //     $this->gClient->setClientId('912270577830-bhbf4og34c1gamn8liplvet3ej7hv0ek.apps.googleusercontent.com');
    //     $this->gClient->setClientSecret('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');
    //     $this->gClient->setRedirectUri(route('print'));
    //     $this->gClient->setDeveloperKey('AIzaSyCxdr4D-v6z2Mk28SY1kHvn9TNO3_MJlM4');
    //     $this->gClient->setScopes(array(
    //         'https://www.googleapis.com/auth/drive.file',
    //         'https://www.googleapis.com/auth/drive'
    //     ));

    //     $this->gClient->setAccessType("offline");

    //     $this->gClient->setApprovalPrompt("force");
    // }
    public static function countDays($date1, $date2)
    {
        $date1 = strtotime($date1); // or your date as well
        $date2 = strtotime($date2);
        $datediff = $date1 - $date2;
        return floor($datediff / (60 * 60 * 24));
    }


    public static function attendance($id_emp, $date)
    {
        return DB::connection('pgsql')->table('hrd_employee')->where('employee_id', '4830-0026')->gett();
    }
    public function test(Request $request)
    {

        $data = [
            [
                "date" => "April/24",
                "invoice_amount" => 0,
                "total_expenses" => 210250160,
                "acwp" => "3.52",
                "paid_amount" => 0,
                "accumulated" => 0,
                "accumulatedmancost" => 166149400,
                "cost_of_money_month" => 0
            ],
            [
                "date" => "May/24",
                "invoice_amount" => 546480000.017238,
                "total_expenses" => 1863353280.52,
                "acwp" => "38.55",
                "paid_amount" => 0,
                "accumulated" => 166149400,
                "accumulatedmancost" => 1983607507,
                "cost_of_money_month" => 1794413.52
            ]
        ];
        
        $x = 3501425569;
        $budget = 4715096066.91;
        
        $p_value = 5394050977;

        foreach ($data as &$entry) {
            if ($x < $budget) {
                // Calculate management cost percentage using budget
                $management_cost_pct = round($entry["total_expenses"] / $budget, 3);
            } else {
                // Calculate management cost percentage using x
                $management_cost_pct = round($entry["total_expenses"] / $x, 3);
            }
            
            // Add the calculated management cost percentage to the entry
            $entry["management_cost_pct"] = $management_cost_pct;
            
            // Calculate management cost value
            $management_cost_value = $p_value * $management_cost_pct * 0.075;
            
            // Add the calculated management cost value to the entry
            $entry["management_cost_value"] = round($management_cost_value, 2);
        }

        
        return $data;
    }
    public function tadwadawest(Request $request)
    {
        $response = array();
        $sql = "SELECT inv.supp_trans_no, inv.po_detail_item_id, inv.grn_item_id, inv.stock_id, 
                (
                SELECT SUM(pod.quantity_ordered) FROM 0_purch_order_details pod WHERE pod.po_detail_item = inv.po_detail_item_id
                )po_qty,
                SUM(inv.quantity) AS inv_qty,
                (
                SELECT SUM(gi.qty_recd) FROM 0_grn_items gi 
                LEFT OUTER JOIN 0_grn_batch grn ON (gi.grn_batch_id = grn.id)
                WHERE gi.po_detail_item = inv.po_detail_item_id AND gi.qty_recd > 0 AND grn.is_for_invoicing = 0
                )grn_qty
                FROM 0_supp_invoice_items inv
                LEFT OUTER JOIN 0_stock_master sm ON (inv.stock_id = sm.stock_id)
                WHERE sm.category_id = 5 AND inv.supp_trans_no IN(25704,
                25705,
                25705,
                32230) GROUP BY inv.po_detail_item_id";
        $exe = DB::connection('mysql')->select(DB::raw($sql));

        foreach ($exe as $data) {

            if ($data->grn_qty == null) {
                $ref = DB::table('0_refs')->where('type', 20)->where('id', $data->supp_trans_no)->first();
                $pod = DB::table('0_purch_order_details')->where('po_detail_item', $data->po_detail_item_id)->select('order_no')->first();
                $po = DB::table('0_purch_orders')->where('order_no', $pod->order_no)->select('reference', 'order_no')->first();

                $tmp = [];
                $tmp['inv_ref'] = $ref->reference;
                $tmp['inv_no'] = $data->supp_trans_no;
                $tmp['po_ref'] = $po->reference;
                $tmp['po_no'] = $po->order_no;
                $tmp['po_qty'] = $data->po_qty;
                $tmp['inv_qty'] = $data->inv_qty;
                $tmp['grn_qty'] = $data->grn_qty == null ? 0 : $data->grn_qty;

                array_push($response, $tmp);
            }
        }

        return $response;
    }

    public static function update_budget_v_cost()
    {
        set_time_limit(0);
        $response = [];
        $query = "SELECT 
                        p.project_code,
                        p.project_no,
                        p.project_name,
                        mf.rate AS rate
                  FROM budget_expense p
                  LEFT OUTER JOIN 0_projects pj ON (p.project_no = pj.project_no)
                  LEFT OUTER JOIN 0_project_management_fee mf ON (mf.id = pj.management_fee_id)
                  ORDER BY p.id ASC";
        $exe = DB::connection('mysql')->select(DB::raw($query));

        foreach ($exe as $data) {
            $query1 = "SELECT SUM(line.qty_ordered * line.unit_price) AS amount
            FROM 0_sales_orders AS sorder, 0_sales_order_details AS line, 0_groups scategory
            WHERE sorder.order_no = line.order_no
            AND sorder.sales_category_id = scategory.id AND sorder.project_code = '$data->project_code'";
            $exe1 = DB::connection('mysql')->select(DB::raw($query1));
            $total_po = (empty($exe1) || $exe1[0]->amount == null) ? 0 : $exe1[0]->amount;

            $query2 = "SELECT SUM(amount) AS amount FROM 0_project_budget_rab WHERE project_no = $data->project_no";
            $exe2 = DB::connection('mysql')->select(DB::raw($query2));
            $total_rab = (empty($exe2) || $exe2[0]->amount == null) ? 0 : $exe2[0]->amount;

            $query3 = "SELECT SUM(amount) AS amount FROM 0_project_budgets WHERE project_no = $data->project_no";
            $exe3 = DB::connection('mysql')->select(DB::raw($query3));
            $total_budget = (empty($exe3) || $exe3[0]->amount == null) ? 0 : $exe3[0]->amount;

            $query4 = "SELECT
            SUM(pod.quantity_ordered * pod.unit_price * pod.rate)-SUM((pod.unit_price * pod.quantity_ordered * pod.rate) * pod.discount_percent) AS amount
            FROM 0_purch_order_details pod
            WHERE pod.project_no = $data->project_no";
            $exe4 = DB::connection('mysql')->select(DB::raw($query4));
            $exp_po = (empty($exe4) || $exe4[0]->amount == null) ? 0 : $exe4[0]->amount;

            $query5 = "SELECT SUM(cd.amount) AS amount
                        FROM 0_cashadvance c
                        LEFT JOIN 0_cashadvance_details cd ON (c.trans_no = cd.trans_no)
                        WHERE c.ca_type_id IN (1,6,10) AND cd.status_id<2
                        AND cd.project_no = $data->project_no";
            $exe5 = DB::connection('mysql')->select(DB::raw($query5));
            $exp_ca = (empty($exe5) || $exe5[0]->amount == null) ? 0 : $exe5[0]->amount;

            $query6 = "SELECT SUM(cd.amount) AS amount
                        FROM 0_cashadvance c
                        LEFT JOIN 0_cashadvance_details cd ON (c.trans_no = cd.trans_no)
                        WHERE c.ca_type_id IN (4) AND cd.status_id<2
                        AND cd.project_no = $data->project_no";
            $exe6 = DB::connection('mysql')->select(DB::raw($query6));
            $exp_reimburst = (empty($exe6) || $exe6[0]->amount == null) ? 0 : $exe6[0]->amount;

            $query7 = "SELECT 
            CASE WHEN SUM(salary) IS NULL THEN 0 ELSE SUM(salary) END amount
            FROM 0_project_salary_budget
            WHERE project_no=$data->project_no";
            $exe7 = DB::connection('mysql')->select(DB::raw($query7));
            $exp_salary = (empty($exe7) || $exe7[0]->amount == null) ? 0 : $exe7[0]->amount;

            $query8 = "SELECT SUM(gl.amount) as amount
            FROM 0_gl_trans gl
            WHERE gl.project_code ='$data->project_code' AND gl.type=1";
            $exe8 = DB::connection('mysql')->select(DB::raw($query8));
            $exp_bp = (empty($exe8) || $exe8[0]->amount == null) ? 0 : $exe8[0]->amount;

            $query9 = "SELECT
            SUM(
            CASE WHEN i.close_date IS NULL THEN (DATEDIFF(CURDATE(), i.trx_date) * rate) 
            ELSE (DATEDIFF(i.close_date, i.trx_date) * rate) END
            ) AS amount
            FROM 0_am_issues i
            LEFT JOIN 0_am_assets a ON (i.object_id = a.asset_id)
            WHERE a.type_id=1 AND i.project_code = '$data->project_code'";
            $exe9 = DB::connection('mysql')->select(DB::raw($query9));
            $exp_tools = (empty($exe9) || $exe9[0]->amount == null) ? 0 : $exe9[0]->amount;

            $query10 = "SELECT          
                SUM(sod.qty_ordered * sod.unit_price) * '$data->rate' AS amount
            FROM 0_sales_orders so
            INNER JOIN 0_sales_order_details sod ON (so.order_no =sod.order_no)
            WHERE so.project_no=$data->project_no";
            $exe10 = DB::connection('mysql')->select(DB::raw($query10));
            $exp_mgmt_fee = (empty($exe10) || $exe10[0]->amount == null) ? 0 : $exe10[0]->amount;

            $query11 = DB::select(DB::raw(QueryProjectCost::get_project_cost_summary_default($data->project_no)));
            $total_cost = (empty($query11) || $query11[0]->amount == null) ? 0 : $query11[0]->amount;

            $query12 = DB::select(DB::raw(QueryProjectCost::get_project_cost_atk_default($data->project_no)));
            $exp_atk = (empty($query12) || $query12[0]->cost_amount == null) ? 0 : $query12[0]->cost_amount;

            $query13 = DB::select(DB::raw(QueryProjectCost::get_project_cost_rental_vehicle_mobil_default($data->project_no)));
            $exp_rental_mobil = (empty($query13) || $query13[0]->cost_amount == null) ? 0 : $query13[0]->cost_amount;

            $query14 = DB::select(DB::raw(QueryProjectCost::get_project_cost_rental_vehicle_motor_default($data->project_no)));
            $exp_rental_motor = (empty($query14) || $query14[0]->cost_amount == null) ? 0 : $query14[0]->cost_amount;

            $query15 = DB::select(DB::raw(QueryProjectCost::get_project_cost_rental_tools_ict_default($data->project_no)));
            $exp_ict = (empty($query15) || $query15[0]->cost_amount == null) ? 0 : $query15[0]->cost_amount;

            $query16 = DB::select(DB::raw(QueryProjectCost::get_project_cost_customer_deduction_default($data->project_no)));
            $cust_deduction = (empty($query16) || $query16[0]->cost_amount == null) ? 0 : $query16[0]->cost_amount;

            $tmp = [];
            $tmp['project_no'] = $data->project_no;
            $tmp['project_code'] = $data->project_code;
            $tmp['project_name'] = $data->project_name;
            $tmp['total_po'] = round($total_po);
            $tmp['total_rab'] = round($total_rab);
            $tmp['total_budget'] = round($total_budget);
            $tmp['exp_po'] = round($exp_po);
            $tmp['exp_ca'] = round($exp_ca);
            $tmp['exp_reimburst'] = round($exp_reimburst);
            $tmp['exp_salary'] = round($exp_salary);
            $tmp['exp_bp'] = round($exp_bp);
            $tmp['exp_tools'] = round($exp_tools);
            $tmp['management_cost'] = round($exp_mgmt_fee);
            $tmp['total_expense'] = round($total_cost + $exp_salary + $exp_atk + $exp_tools + $exp_rental_mobil + $exp_rental_motor + $exp_ict + $cust_deduction);

            array_push($response, $tmp);
        }
        return $response;
    }
    public function emp_due_date(Request $request)
    {
        $response = [];

        $users_sql =  DB::connection('mysql')->table('0_hrm_employees')->where('name', 'not like', '%Resign%')->get();

        foreach ($users_sql as $data) {
            $tmp = [];

            $users_pgsql = DB::connection('pgsql')->table('hrd_employee')
                ->where('employee_id', $data->emp_id)->first();

            $due_date = (empty($users_pgsql->due_date)) ? '0000-00-00' : $users_pgsql->due_date;

            if ($due_date != $data->due_date) {
                DB::table('0_hrm_employees')->where('emp_id', $data->emp_id)
                    ->update(array(
                        'due_date' => $due_date
                    ));


                $tmp['status'] = "Emp " . $data->emp_id . "_" . $data->name . "Telah diupdate";
            }
            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public function total_po(Request $request)
    {
        $query = "SELECT 
                        p.project_code
                  FROM budget_expense p
                  ORDER BY p.project_no DESC";
        $exe = DB::connection('mysql')->select(DB::raw($query));

        foreach ($exe as $data) {
            $query1 = "SELECT '$data->project_code' AS project_code, SUM(line.qty_ordered * line.unit_price) AS amount
            FROM 0_sales_orders AS sorder, 0_sales_order_details AS line, 0_groups scategory
            WHERE sorder.order_no = line.order_no
            AND sorder.sales_category_id = scategory.id AND sorder.project_code = '$data->project_code'";
            $exe1 = DB::connection('mysql')->select(DB::raw($query1));
            foreach ($exe1 as $data1) {
                DB::connection('mysql')->table('budget_expense')->where('project_code', "$data1->project_code")->update(array(
                    'total_po_customer' => $data1->amount,
                    'updated_at' => Carbon::now()
                ));
            }
        }
        return response()->json([
            'success' => true
        ]);
    }
    public function total_rab(Request $request)
    {
        $query = "SELECT 
                        p.project_code,
                        p.project_no
                  FROM budget_expense p
                  ORDER BY p.project_no DESC";
        $exe = DB::connection('mysql')->select(DB::raw($query));

        foreach ($exe as $data) {
            $query1 = "SELECT SUM(amount) AS amount FROM 0_project_budget_rab WHERE project_no = $data->project_no";
            $exe1 = DB::connection('mysql')->select(DB::raw($query1));
            foreach ($exe1 as $data1) {
                DB::connection('mysql')->table('budget_expense')->where('project_no', "$data->project_no")->update(array(
                    'total_rab' => $data1->amount,
                    'updated_at' => Carbon::now()
                ));
            }
        }
        return response()->json([
            'success' => true
        ]);
    }

    public function total_budget(Request $request)
    {
        $query = "SELECT 
                        p.project_code,
                        p.project_no
                  FROM budget_expense p
                  ORDER BY p.project_no DESC";
        $exe = DB::connection('mysql')->select(DB::raw($query));

        foreach ($exe as $data) {
            $query1 = "SELECT SUM(amount) AS amount FROM 0_project_budgets WHERE project_no = $data->project_no";
            $exe1 = DB::connection('mysql')->select(DB::raw($query1));
            foreach ($exe1 as $data1) {
                DB::connection('mysql')->table('budget_expense')->where('project_no', "$data->project_no")->update(array(
                    'total_budget' => $data1->amount,
                    'updated_at' => Carbon::now()
                ));
            }
        }
        return response()->json([
            'success' => true
        ]);
    }

    public function exp_po(Request $request)
    {
        $query = "SELECT 
                        p.project_code,
                        p.project_no
                  FROM budget_expense p
                  ORDER BY p.project_no DESC";
        $exe = DB::connection('mysql')->select(DB::raw($query));

        foreach ($exe as $data) {
            $query1 = "SELECT
SUM(pod.quantity_ordered * pod.unit_price * pod.rate)-SUM((pod.unit_price * pod.quantity_ordered * pod.rate) * pod.discount_percent) AS amount
FROM 0_purch_order_details pod
WHERE pod.project_no = $data->project_no";
            $exe1 = DB::connection('mysql')->select(DB::raw($query1));
            foreach ($exe1 as $data1) {
                DB::connection('mysql')->table('budget_expense')->where('project_no', "$data->project_no")->update(array(
                    'exp_po' => $data1->amount,
                    'updated_at' => Carbon::now()
                ));
            }
        }
        return response()->json([
            'success' => true
        ]);
    }

    public function exp_ca(Request $request)
    {
        $query = "SELECT 
                        p.project_code,
                        p.project_no
                  FROM budget_expense p
                  ORDER BY p.project_no DESC";
        $exe = DB::connection('mysql')->select(DB::raw($query));

        foreach ($exe as $data) {
            $query1 = "SELECT SUM(cd.amount) AS amount
                        FROM 0_cashadvance c
                        LEFT JOIN 0_cashadvance_details cd ON (c.trans_no = cd.trans_no)
                        WHERE c.ca_type_id IN (1,6,10) AND cd.status_id<2
                        AND cd.project_no = $data->project_no";
            $exe1 = DB::connection('mysql')->select(DB::raw($query1));
            foreach ($exe1 as $data1) {
                DB::connection('mysql')->table('budget_expense')->where('project_no', "$data->project_no")->update(array(
                    'exp_ca' => $data1->amount,
                    'updated_at' => Carbon::now()
                ));
            }
        }
        return response()->json([
            'success' => true
        ]);
    }

    public function exp_reimburst(Request $request)
    {
        $query = "SELECT 
                        p.project_code,
                        p.project_no
                  FROM budget_expense p
                  ORDER BY p.project_no DESC";
        $exe = DB::connection('mysql')->select(DB::raw($query));

        foreach ($exe as $data) {
            $query1 = "SELECT SUM(cd.amount) AS amount
                        FROM 0_cashadvance c
                        LEFT JOIN 0_cashadvance_details cd ON (c.trans_no = cd.trans_no)
                        WHERE c.ca_type_id IN (4) AND cd.status_id<2
                        AND cd.project_no = $data->project_no";
            $exe1 = DB::connection('mysql')->select(DB::raw($query1));
            foreach ($exe1 as $data1) {
                DB::connection('mysql')->table('budget_expense')->where('project_no', "$data->project_no")->update(array(
                    'exp_reimburst' => $data1->amount,
                    'updated_at' => Carbon::now()
                ));
            }
        }
        return response()->json([
            'success' => true
        ]);
    }

    public function exp_salary(Request $request)
    {
        $query = "SELECT 
                        p.project_code,
                        p.project_no
                  FROM budget_expense p
                  ORDER BY p.project_no DESC";
        $exe = DB::connection('mysql')->select(DB::raw($query));

        foreach ($exe as $data) {
            $query1 = "SELECT 
CASE WHEN SUM(salary) IS NULL THEN 0 ELSE SUM(salary) END amount
FROM 0_project_salary_budget
WHERE project_no=$data->project_no";
            $exe1 = DB::connection('mysql')->select(DB::raw($query1));
            foreach ($exe1 as $data1) {
                DB::connection('mysql')->table('budget_expense')->where('project_no', "$data->project_no")->update(array(
                    'exp_salary' => $data1->amount,
                    'updated_at' => Carbon::now()
                ));
            }
        }
        return response()->json([
            'success' => true
        ]);
    }

    public function exp_bp(Request $request)
    {
        $query = "SELECT 
                        p.project_code,
                        p.project_no
                  FROM budget_expense p
                  ORDER BY p.project_no DESC";
        $exe = DB::connection('mysql')->select(DB::raw($query));

        foreach ($exe as $data) {
            $query1 = "SELECT SUM(gl.amount) as amount
FROM 0_gl_trans gl
WHERE gl.project_code ='$data->project_code' AND gl.type=1";
            $exe1 = DB::connection('mysql')->select(DB::raw($query1));
            foreach ($exe1 as $data1) {
                DB::connection('mysql')->table('budget_expense')->where('project_no', "$data->project_no")->update(array(
                    'exp_bp' => $data1->amount,
                    'updated_at' => Carbon::now()
                ));
            }
        }
        return response()->json([
            'success' => true
        ]);
    }

    public function exp_tools(Request $request)
    {
        $query = "SELECT 
                        p.project_code,
                        p.project_no
                  FROM budget_expense p
                  ORDER BY p.project_no DESC";
        $exe = DB::connection('mysql')->select(DB::raw($query));

        foreach ($exe as $data) {
            $query1 = "SELECT
SUM(
CASE WHEN i.close_date IS NULL THEN (DATEDIFF(CURDATE(), i.trx_date) * rate) 
ELSE (DATEDIFF(i.close_date, i.trx_date) * rate) END
) AS amount
FROM 0_am_issues i
LEFT JOIN 0_am_assets a ON (i.object_id = a.asset_id)
WHERE a.type_id=1 AND i.project_code = '$data->project_code'";
            $exe1 = DB::connection('mysql')->select(DB::raw($query1));
            foreach ($exe1 as $data1) {
                DB::connection('mysql')->table('budget_expense')->where('project_no', "$data->project_no")->update(array(
                    'exp_tools' => $data1->amount,
                    'updated_at' => Carbon::now()
                ));
            }
        }
        return response()->json([
            'success' => true
        ]);
    }

    public function mgmt_cost(Request $request)
    {
        $query = "SELECT 
                        p.project_code,
                        p.project_no,
                        mf.rate AS rate
                  FROM budget_expense p
                  LEFT OUTER JOIN 0_projects pj ON (p.project_no = pj.project_no)
                  LEFT OUTER JOIN 0_project_management_fee mf ON (mf.id = pj.management_fee_id)
                  ORDER BY p.project_no DESC";
        $exe = DB::connection('mysql')->select(DB::raw($query));

        foreach ($exe as $data) {
            $query1 = "SELECT          
                SUM(sod.qty_ordered * sod.unit_price) * '$data->rate' AS amount
            FROM 0_sales_orders so
            INNER JOIN 0_sales_order_details sod ON (so.order_no =sod.order_no)
            WHERE so.project_no=$data->project_no";
            $exe1 = DB::connection('mysql')->select(DB::raw($query1));
            foreach ($exe1 as $data1) {
                DB::connection('mysql')->table('budget_expense')->where('project_no', "$data->project_no")->update(array(
                    'exp_management_cost' => $data1->amount,
                    'updated_at' => Carbon::now()
                ));
            }
        }
        return response()->json([
            'success' => true
        ]);
    }


public function upload_ca_deduction(Request $request)
    {

        $query = "SELECT 
                        pg.emp_no,
                        pg.pg_date,
                        pg.pg_amount,
                        pg.reference
                  FROM ca_pot_gj_2408 pg";
        $exe = DB::connection('mysql')->select(DB::raw($query));

        foreach ($exe as $data) {
            $date_ = strtotime('24/08/2024');
            $newDate = date('Y-m-d', $date_);
            DB::connection('pgsql')->table('hrd_loan')->insert(array(
                'created' => $newDate,
                'created_by' => 6320,
                'modified_by' => 6320,
                'id_employee' => $data->emp_no,
                'amount' => $data->pg_amount,
                'type' => 1,
                'periode' => 1,
                'purpose' => "OSKB FA Deduction",
                'note' => "$data->reference",
                'category' => 2,
                'loan_date' => $data->pg_date,
                'payment_from' => date('Y-m-d'),
                'payment_thru' => date('Y-m-d')
            ));
        }
        return response()->json([
            'success' => true
        ]);
    }
}