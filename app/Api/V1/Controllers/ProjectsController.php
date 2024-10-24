<?php

namespace App\Api\V1\Controllers;

use JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Carbon\Carbon;
use Validator, Redirect, Response, File;
use Auth;
use URL;
use QrCode;
use App\Image;
use Storage;
use App\CashAdvance;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\EmployeesController;
use App\Http\Controllers\ProjectController;
use Exception;
use Symfony\Component\HttpKernel\Exception\MaxCAOutstandingException;
use Symfony\Component\HttpKernel\Exception\BudgetAmountException;
use Symfony\Component\HttpKernel\Exception\ValidateDatePDPHttpException;


class ProjectsController extends Controller
{
    //
    use Helpers;

    //==================================================================== Projects CODE =============================================================\\
    public function project_list()
    {
        $response = [];

        $sql = "SELECT project_no,code FROM 0_projects WHERE inactive = 0";
        $projects = DB::select(DB::raw($sql));
        foreach ($projects as $data) {

            $tmp = [];
            $tmp['project_no'] = $data->project_no;
            $tmp['code'] = $data->code;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    //==================================================================== UOM LIST =============================================================\\
    public function uom_list()
    {
        $response = [];

        $sql = "SELECT abbr FROM 0_item_units WHERE inactive = 0";
        $uom = DB::select(DB::raw($sql));
        foreach ($uom as $data) {

            $tmp = [];
            $tmp['uom'] = $data->abbr;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    //==================================================================== Project =============================================================\\
    public function projects()
    {
        $response = [];


        $sql = "SELECT p.*, pt.name as type, dm.name as customer, m.name as project_manager  FROM 0_projects p
                LEFT JOIN 0_project_types pt ON (p.project_type_id = pt.project_type_id)
                LEFT JOIN 0_debtors_master dm ON (p.debtor_no = dm.debtor_no) 
                LEFT JOIN 0_members m ON (p.person_id = m.person_id)";
        $projects = DB::select(DB::raw($sql));
        foreach ($projects as $data) {

            $no = 1;

            $tmp = [];
            $tmp['project_no'] = $data->project_no;
            $tmp['code'] = $data->code;
            $tmp['name'] = $data->name;
            $tmp['customer'] = $data->customer;
            $tmp['type'] = $data->type;
            $tmp['project_manager'] = $data->project_manager;
            $tmp['person_id'] = $data->person_id;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public function projects_search(Request $request)
    {
        $response = [];

        if (!empty($request->project_code)) {
            $project_code = $request->project_code;
        } else {
            $project_code = '';
        }


        $sql = "SELECT p.*, pt.name as type, dm.name as customer, m.name as project_manager  FROM 0_projects p
                LEFT JOIN 0_project_types pt ON (p.project_type_id = pt.project_type_id)
                LEFT JOIN 0_debtors_master dm ON (p.debtor_no = dm.debtor_no) 
                LEFT JOIN 0_members m ON (p.person_id = m.person_id)";

        if ($project_code != '') {
            $sql .= "WHERE p.code LIKE '%$project_code%' OR dm.name LIKE '%$project_code%' OR p.name LIKE '%$project_code%'";
            $sql .= " ORDER BY p.project_no DESC LIMIT 50";
        } else {
            $sql .= " ORDER BY p.project_no DESC LIMIT 24";
        }

        $projects = DB::select(DB::raw($sql));
        foreach ($projects as $data) {

            $no = 1;

            $tmp = [];
            $tmp['project_no'] = $data->project_no;
            $tmp['code'] = $data->code;
            $tmp['name'] = $data->name;
            $tmp['customer'] = $data->customer;
            $tmp['type'] = $data->type;
            $tmp['project_manager'] = $data->project_manager;
            $tmp['person_id'] = $data->person_id;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }


    public function so_search(Request $request)
    {
        $po_number = $request->po_number;

        $sql = "SELECT s.order_no, s.reference AS so_reference, s.customer_ref 
                FROM 0_purch_order_details pod
                LEFT JOIN 0_purch_orders po ON (pod.order_no=po.order_no)
                LEFT OUTER JOIN 0_stock_master sm ON (pod.item_code=sm.stock_id)
                LEFT OUTER JOIN 0_purch_requisition_details prd ON (prd.pr_detail_item = pod.src_id)
                LEFT OUTER JOIN 0_purch_requisitions pr ON (pr.order_no = prd.order_no)
                LEFT OUTER JOIN 0_projects p ON (pod.project_no = p.project_no)
                LEFT OUTER JOIN 0_sales_orders s ON (pr.sales_order_no = s.order_no)    
                LEFT OUTER JOIN 0_project_budgets pb ON (pb.project_budget_id = pod.project_budget_id)
                WHERE po.reference = :po_number GROUP BY s.reference";

        try {
            $data = DB::select(DB::raw($sql), ['po_number' => $po_number]);

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => "Invalid PO number $po_number"
                ], 500);
            }
            
            return response()->json([
                'success' => true,
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while executing the query.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //==================================================================== Purchase Order List =============================================================\\

    public function po_search(Request $request)
    {
        $response = [];
        $response['bank_info'] = [];

        $po_number = $request->po_number;
        
        $additionalInfo = DB::table('0_purch_orders')->where('reference', $po_number)
            ->join('0_suppliers', '0_suppliers.supplier_id', '=', '0_purch_orders.supplier_id')
            ->select('0_suppliers.bank_account', '0_suppliers.bank_account_name', '0_suppliers.bank_account_beneficiary', '0_purch_orders.spk_no', '0_purch_orders.order_no')
            ->get();
      
        if (!$additionalInfo) {
            return response()->json([
                'success' => false,
                'message' => "invalid po nunber $po_number"
            ], 500);
        }

        array_push($response['bank_info'], $additionalInfo);

        $response['spk_no'] = $additionalInfo[0]->spk_no;

        $terminInfo = DB::table('0_purch_order_terms')->where('order_no', $additionalInfo[0]->order_no)
            ->where('used', 0)
            ->select('termin', 'percentage')
            ->orderBy('termin', 'ASC')
            ->limit(1)
            ->get();

        $response['termin'] = $terminInfo;

        $response['item'] = [];

        $sql = "SELECT xxx.* FROM (
                    SELECT pod.*, 
                            (
                                SELECT IF(SUM(sip.quantity) IS NULL,0,SUM(sip.quantity))
                                FROM 0_supp_invoice_items sip
                                LEFT JOIN 0_grn_items grd ON (sip.grn_item_id = grd.id)
                                LEFT JOIN 0_grn_batch gr ON (gr.id = grd.grn_batch_id)
                                WHERE po_detail_item_id=pod.po_detail_item
                                AND gr.is_for_invoicing=1
                            ) qty_direct_invoice,
                            (
                                SELECT SUM(sip.quantity)
                                FROM 0_supp_invoice_items sip
                                LEFT JOIN 0_grn_items grd ON (sip.grn_item_id = grd.id)
                                LEFT JOIN 0_grn_batch gr ON (gr.id = grd.grn_batch_id)
                                WHERE po_detail_item_id=pod.po_detail_item
                                AND gr.is_for_invoicing=0
                            ) qty_gr_invoice,
                            CASE WHEN (sm.category_id=4) THEN prd.uom 
                                WHEN (sm.editable=1 AND sm.category_id!=4) THEN '' 
                            ELSE sm.units END AS units,
                            pr.project_no AS pr_project_no, 
                            p.code AS project_code1,
                            p.division_id,
                            pr.sales_order_no AS pr_sales_order_no, 
                            s.reference AS sales_oder_ref, 
                            pb.budget_name AS nama_budget
                        FROM 0_purch_order_details pod
                        LEFT JOIN 0_purch_orders po ON (pod.order_no=po.order_no)
                        LEFT OUTER JOIN 0_stock_master sm ON (pod.item_code=sm.stock_id)
                        LEFT OUTER JOIN 0_purch_requisition_details prd ON (prd.pr_detail_item = pod.src_id)
                        LEFT OUTER JOIN 0_purch_requisitions pr ON (pr.order_no = prd.order_no)
                        LEFT OUTER JOIN 0_projects p ON (pod.project_no = p.project_no)
                        LEFT OUTER JOIN 0_sales_orders s ON (pr.sales_order_no = s.order_no)	
                        LEFT OUTER JOIN 0_project_budgets pb ON (pb.project_budget_id = pod.project_budget_id)
                        WHERE po.reference=$po_number
                ) xxx WHERE (xxx.quantity_ordered > xxx.qty_direct_invoice)";

        $projects = DB::select(DB::raw($sql));
        foreach ($projects as $data) {

            $tmp = [];
            $tmp['po_detail_item'] = $data->po_detail_item;
            $tmp['order_no'] = $data->sales_order_no;
            $tmp['order_ref'] = $data->sales_oder_ref;
            $tmp['line'] = $data->line;
            $tmp['product_id'] = $data->product_id;
            $tmp['item_code'] = $data->item_code;
            $tmp['description'] = $data->description;
            $tmp['qty_ordered'] = $data->quantity_ordered;
            $tmp['uom'] = $data->uom;
            $tmp['unit_price'] = $data->unit_price;
            $tmp['total_price'] = $data->unit_price * $data->quantity_ordered;
            $tmp['qty_invoice'] = $data->qty_direct_invoice = null ? 0 : $data->qty_direct_invoice;
            $tmp['site_no'] = $data->site_no;
            $tmp['site_id'] = $data->site_id;
            $tmp['site_name'] = $data->site_name;
            $tmp['project_no'] = $data->project_no;
            $tmp['project_code'] = $data->project_code;
            $tmp['division_id'] = $data->division_id;
            $tmp['budget_id'] = $data->project_budget_id;

            array_push($response['item'], $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    //==================================================================== Projects Spk List =============================================================\\


    public function projects_spk(Request $request)
    {
        $response = [];

        if (!empty($request->spk_no)) {
            $spk_no = $request->spk_no;
        } else {
            $spk_no = '';
        }


        $sql = "SELECT t1.spk_no FROM 0_project_spk t1
                INNER JOIN 0_project_spk_details t2 ON ( t1.spk_id = t2.spk_id) ";

        if ($spk_no != '') {
            $sql .= "WHERE t2.paid = 0 AND t1.spk_no LIKE '%$spk_no%'";
            $sql .= " GROUP BY t1.spk_no";
        } else {
            $sql .= " GROUP BY t1.spk_no";
        }

        $projects = DB::select(DB::raw($sql));
        foreach ($projects as $data) {

            $no = 1;

            $tmp = [];
            $tmp['spk_no'] = $data->spk_no;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    //==================================================================== Projects Sites List =============================================================\\
    public function project_sites(Request $request)
    {
        $response = [];

        $site_name = "$request->name";
        $sql = "SELECT ps.site_no, ps.name, ps.site_id FROM 0_project_site ps WHERE ps.name LIKE '%$site_name%' ORDER BY ps.site_no DESC";

        $project_site = DB::select(DB::raw($sql));
        foreach ($project_site as $data) {

            $tmp = [];
            $tmp['site_no'] = $data->site_no;
            $tmp['site_id'] = $data->site_id;
            $tmp['site_name'] = $data->name;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    //==================================================================== Projects Milestone List =============================================================\\
    public function project_milestones()
    {
        $response = [];

        $sql = "SELECT m.milestone_id, m.name FROM 0_milestones m ORDER BY m.milestone_id ASC";

        $project_site = DB::select(DB::raw($sql));
        foreach ($project_site as $data) {

            $tmp = [];
            $tmp['milestone_id'] = $data->milestone_id;
            $tmp['milestone_name'] = $data->name;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    //==================================================================== Projects Budgets =============================================================\\
    /**
     * @OA\Get(
     *     path="/api/projects/budgets/{project_no}",
     *     tags={"project_budgets"},
     *     summary="Returns a Project Budgets API response",
     *     description="A sample project budgets to test out the API",
     *     operationId="budgets",
     *     @OA\Parameter(
     *          name="project_no",
     *          required=false,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Response(
     *         response="default",
     *         description="successful operation"
     *     )
     * )
     */
    public function project_budgets($project_no)
    {
        $response = [];

        $sql = "SELECT pb.project_budget_id,
                        SUBSTR(pb.budget_name, 1, 150) as budget_name,
                        pbt.name AS budget_type_name, 
                        pb.site_id,
                        st.name AS site_name,
                        pb.amount,
                        SUBSTR(pb.description, 1, 140) as description,
                        pb.inactive,
                        u.real_name as creator,
                        pb.budget_type_id,
                        pb.created_date,
                        pb.updated_date
                FROM 0_project_budgets pb
                LEFT JOIN 0_project_cost_type_group pbt ON (pb.budget_type_id = pbt.cost_type_group_id)
                LEFT JOIN 0_project_site st ON (pb.site_id = st.site_no)	
                LEFT JOIN 0_users u ON (pb.created_by = u.id)	
                WHERE pb.project_no = $project_no";

        $project_budgets = DB::select(DB::raw($sql));
        foreach ($project_budgets as $data) {

            $tmp = [];

            $sql_po = "SELECT (if(b.po_amount IS NULL, 0, b.po_amount)) AS balance
			FROM
			(
				SELECT pb.amount AS budget_amount, prj.code AS project_code, pb.budget_name,
				(
					SELECT
						SUM(pod.quantity_ordered * pod.unit_price * pod.rate)
					FROM 0_purch_order_details pod
					INNER JOIN 0_purch_orders po ON (po.order_no = pod.order_no)
					WHERE po.status_id =0 AND pod.project_budget_id = pb.project_budget_id
				) AS po_amount
                FROM 0_project_budgets pb
    			LEFT JOIN 0_projects prj ON (pb.project_no = prj.project_no)
				WHERE pb.project_budget_id= $data->project_budget_id
			) b";

            $po_balance = DB::select(DB::raw($sql_po));
            foreach ($po_balance as $po_data) {
                $tmpl['po_balance'] = $po_data->balance;
            }


            $sql_ca = "SELECT (if(b.ca_amount IS NULL, 0, b.ca_amount)) AS balance
                FROM
                (
                    SELECT pb.amount AS budget_amount, prj.code AS project_code, pb.budget_name,
                    (
                        SELECT SUM(cd.amount)
                        FROM 0_cashadvance c
                        LEFT JOIN 0_cashadvance_details cd ON (c.trans_no = cd.trans_no)
                        WHERE cd.project_budget_id = pb.project_budget_id AND c.ca_type_id IN (1, 4, 6, 10) and cd.status_id<2
                    ) AS ca_amount
                    FROM 0_project_budgets pb
                    LEFT JOIN 0_projects prj ON (pb.project_no = prj.project_no)
                    WHERE pb.project_budget_id= $data->project_budget_id
                ) b";

            $ca_balance = DB::select(DB::raw($sql_ca));
            foreach ($ca_balance as $ca_data) {
                $tmpl['ca_balance'] = $ca_data->balance;
            }


            $sql_gl = "SELECT (if(b.gl_amount IS NULL, 0, b.gl_amount)) AS balance
                FROM
                (
                    SELECT pb.amount AS budget_amount, prj.code AS project_code, pb.budget_name,
                    (
                        SELECT SUM(gl.amount)
                        FROM 0_gl_trans gl
                        WHERE gl.project_budget_id = pb.project_budget_id AND gl.type='1'
                    ) AS gl_amount
                    FROM 0_project_budgets pb
                    LEFT JOIN 0_projects prj ON (pb.project_no = prj.project_no)
                    WHERE pb.project_budget_id= $data->project_budget_id
                ) b";

            $gl_balance = DB::select(DB::raw($sql_gl));
            foreach ($gl_balance as $gl_data) {
                $tmpl['gl_balance'] = $gl_data->balance;
            }

            $pb_id = "$data->project_budget_id";
            $used_amount = $tmpl['po_balance'] + $tmpl['ca_balance'] + $tmpl['gl_balance'];
            $remain_amount = $data->amount - $used_amount;

            $tmp['budget_id'] = $pb_id;
            $tmp['budget_name'] = $data->budget_name;
            $tmp['budget_type'] = $data->budget_type_name;
            $tmp['budget_type_id'] = $data->budget_type_id;
            $tmp['site_no'] = $data->site_id;
            $tmp['site_name'] = $data->site_name;
            $tmp['amount'] = $data->amount;
            $tmp['used_amount'] = $used_amount;
            $tmp['remain_amount'] = $remain_amount;
            $tmp['description'] = $data->description;
            $tmp['inactive'] = $data->inactive;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    //==================================================================== Projects Budget List =============================================================\\
    public function project_budget_list($project_code)
    {
        $response = [];

        $sql = "SELECT pb.* FROM 0_project_budgets pb
                LEFT JOIN 0_projects p ON (pb.project_no = p.project_no)
                WHERE p.inactive = 0 AND p.code ='$project_code'";

        $project_budget_list = DB::select(DB::raw($sql));
        foreach ($project_budget_list as $data) {

            $tmp = [];
            $tmp['budget_id'] = $data->project_budget_id;
            $tmp['budget_name'] = $data->budget_name;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    //==================================================================== Projects Budget Edit =============================================================\\
    public function edit_project_budget(Request $request, $project_budget_id)
    {
        $currentUser = JWTAuth::parseToken()->authenticate();
        $user_id = Auth::guard()->user()->old_id;

        if ($request->used_amount < $request->amount) {
            DB::table('0_project_budgets')->where('project_budget_id', $project_budget_id)
                ->update(array(
                    'budget_name' => $request->budget_name,
                    'site_id' => $request->site,
                    'budget_type_id' => $request->budget_type_id,
                    'amount' => $request->amount,
                    'description' => $request->description,
                    'inactive' => $request->inactive,
                    'updated_date' => Carbon::now(),
                    'updated_by' => $user_id
                ));
        } else if ($request->used_amount > $request->amount) {
            throw new BudgetAmountException();
        }

        return response()->json([
            'success' => true
        ], 200);
    }

    //==================================================================== Projects Budget Edit =============================================================\\
    public function project_cost_type_group_list(Request $request)
    {
        $data = ProjectController::project_cost_type_group_lists($request->project_code);

        return $data;
    }
    //==================================================================== App Version =============================================================\\
    public function checkversion()
    {
        $currentUser = JWTAuth::parseToken()->authenticate();
        $response = [];

        $sql = "SELECT id,app_version FROM app_version
                WHERE inactive = 0
                ORDER BY id DESC
                LIMIT 1";

        $version = DB::select(DB::raw($sql));

        foreach ($version as $data) {
            $ver = str_replace(".", "", $data->app_version);
            $filename = "http://adyawinsa.com/epro-api/adyawinsa-app-v$ver.apk";
            $tmp = [];
            $tmp['app_version'] = $data->app_version;
            $tmp['url'] = $filename;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    //==================================================================== DAILY PLAN Need APPROVAL=============================================================\\

    public function approval_daily_plan()
    {

        $currentUser = JWTAuth::parseToken()->authenticate();
        $emp_id = Auth::guard()->user()->emp_id;
        $level =  Auth::guard()->user()->approval_level;
        $person_id =  Auth::guard()->user()->person_id;
        $user_id =  Auth::guard()->user()->old_id;

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
                                                                                                                WHERE user_id=$user_id
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
                WHERE pdp.approval < 4 GROUP BY pdp.reference ORDER BY pdp.plan_start DESC";
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

    //==================================================================== GET DAILY PLAN (USER)=============================================================\\

    public function get_daily_plan()
    {

        $currentUser = JWTAuth::parseToken()->authenticate();
        $emp_id = Auth::guard()->user()->emp_id;
        $level =  Auth::guard()->user()->approval_level;
        $user_id =  Auth::guard()->user()->id;

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
                WHERE pdp.id != -1 AND pdp.approval IN (4,5) AND pdp.status = 1 GROUP BY pdp.reference ORDER BY pdp.plan_start DESC";
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


        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    //==================================================================== DAILY PLAN GA FOR CARPOOL=============================================================\\

    public function carpool_daily_plan()
    {

        $currentUser = JWTAuth::parseToken()->authenticate();
        $emp_id = Auth::guard()->user()->emp_id;
        $level =  Auth::guard()->user()->approval_level;

        $response = [];
        if ($level >= 555) {
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
                    pdp.site_id,
		            pdp.vehicle_no,
                    pdp.approval,
                    pdp.address,
                    pdp.phone_number,
                    pdp.remark_security,
                    pdp.qrcode
                FROM 0_project_daily_plan pdp
                WHERE pdp.id != -1 AND pdp.approval = 4 AND pdp.status = 1 AND pdp.is_carpool = 0 GROUP BY pdp.reference ORDER BY pdp.plan_start DESC";
        } else {
            $sql = "SELECT * FROM 0_project_daily_plan WHERE id = -1";
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
            $tmp['site_id'] = $data->site_id;
            $tmp['vehicle_no'] = $data->vehicle_no;
            $tmp['address'] = $data->address;
            $tmp['approval_position'] = $data->approval;
            $tmp['qrcode'] = $url;


            $sql1 = "SELECT * FROM 0_project_daily_plan
                            WHERE reference = '$data->reference'";

            $get_member = DB::select(DB::raw($sql1));
            foreach ($get_member as $data) {
                $items = [];
                $items['emp_id'] = $data->emp_id;
                $items['emp_name'] = $data->emp_name;
                $items['phone'] = $data->phone_number;
                $items['task'] = $data->daily_task;
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


        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }
    //==================================================================== SEARCH DAILY PLAN (SECURITY) =============================================================\\

    public function search_daily_plan(Request $request)
    {

        $currentUser = JWTAuth::parseToken()->authenticate();
        $user_id = Auth::guard()->user()->id;
        $level =  Auth::guard()->user()->approval_level;

        $response = [];
        $doc_no = $request->doc_no;
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
    //==================================================================== STATUS DAILY PLAN =============================================================\\

    public function status_daily_plan()
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
            $tmp['approval'] = $data->approval;

            array_push($response, $tmp);
        }


        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }
    //==================================================================== CREATE QR CODE (DOC_NO) DAILY PLAN =============================================================\\
    public function qr_code($id)
    {


        $doc_daily_plan = DB::table('0_project_daily_plan')
            ->where('id', $id)
            ->get();

        foreach ($doc_daily_plan as $data) {

            $code = QrCode::size('255')
                ->format('png')
                ->generate($data->reference);
            $filename = $data->qrcode;
            $output_file = 'storage/qr-code/' . $filename . '.png';
            Storage::disk('public')->put($output_file, $code);
        }


        return $code;
    }

    //==================================================================== UPDATE Project Daily Plan =============================================================\\
    public function update_pdp(Request $request, $id)
    {
        $currentUser = JWTAuth::parseToken()->authenticate();
        $level = Auth::guard()->user()->approval_level;
        $name = Auth::guard()->user()->name;
        $date = Carbon::now();
        $emp_id = Auth::guard()->user()->emp_id;

        $response = [];

        $sql = "SELECT * FROM 0_project_daily_plan WHERE id = $id";
        $updated = DB::select(DB::raw($sql));

        foreach ($updated as $data) {

            $validate_date = date('Y-m-d');
            $task_id = $data->task_id;
            if ($validate_date <= $data->plan_end) {
                switch ($level) {
                    case 0;

                        // $check_outstanding = FinanceController::AllowedCashAdvance($emp_id);
                        // if($check_outstanding == 1){

                        $filename = date('Ymd') . rand(1, 99999);
                        ProjectController::qr_code($data->reference, $filename);
                        $approval = $data->approval + 1;

                        if ($request->personal_vehicle == 1) {
                            $vehicle_no = "$request->personal_vehicle_no''(Kend. Pribadi)";
                        } else if ($request->personal_vehicle < 1) {
                            $vehicle_no = $request->vehicle_no;
                        }
                        DB::table('0_project_daily_plan')->where('reference', $data->reference)
                            ->update(array(
                                'approval' => $approval,
                                'status' => 1,
                                'vehicle_no' => $vehicle_no,
                                'qrcode' => $filename,
                                'last_update' => "($date)-$name Approve; "
                            ));

                        DB::table('0_pdp_log')->insert(array(
                            'reference' => $data->reference,
                            'approval' => $data->approval,
                            'person_id' => Auth::guard()->user()->id
                        ));

                        DB::table('0_project_task')->where('id', $task_id)
                            ->update(array('status' => 1));

                        return response()->json([
                            'success' => true
                        ], 200);

                        // }else if ($check_outstanding < 1){
                        //         throw new MaxCAOutstandingException();
                        // }

                        break;
                    case 1;

                        $div_info = DB::table('0_projects')->where('code', $data->project_code)->first();
                        $fileqrname = date('Ymd') . rand(1, 9999999999);
                        if ($div_info->division_id == 24) {
                            $approval = $data->approval + 2;
                        } else {
                            $approval = $data->approval + 1;
                        }

                        DB::table('0_project_daily_plan')->where('reference', $data->reference)
                            ->update(array(
                                'approval' => $approval,
                                'status' => 1,
                                'qrcode' => $fileqrname,
                                'last_update' => "($date)-$name Approve; "
                            ));

                        DB::table('0_pdp_log')->insert(array(
                            'reference' => $data->reference,
                            'approval' => $data->approval,
                            'person_id' => Auth::guard()->user()->id
                        ));

                        DB::table('0_project_task')->where('id', $task_id)
                            ->update(array('status' => 1));

                        return response()->json([
                            'success' => true
                        ], 200);
                        break;
                    case 2;

                        $approval = $data->approval + 1;
                        DB::table('0_project_daily_plan')->where('reference', $data->reference)
                            ->update(array(
                                'approval' => $approval,
                                'status' => 1,
                                'last_update' => "($date)-$name Approve; "
                            ));

                        DB::table('0_pdp_log')->insert(array(
                            'reference' => $data->reference,
                            'approval' => $data->approval,
                            'person_id' => Auth::guard()->user()->id
                        ));

                        DB::table('0_project_task')->where('id', $task_id)
                            ->update(array('status' => 1));

                        return response()->json([
                            'success' => true
                        ], 200);
                        break;
                    case 3;

                        $approval = $data->approval + 1;
                        DB::table('0_project_daily_plan')->where('reference', $data->reference)
                            ->update(array(
                                'approval' => $approval,
                                'status' => 1,
                                'last_update' => "($date)-$name Approve; "
                            ));

                        DB::table('0_pdp_log')->insert(array(
                            'reference' => $data->reference,
                            'approval' => $data->approval,
                            'person_id' => Auth::guard()->user()->id
                        ));

                        DB::table('0_project_task')->where('id', $task_id)
                            ->update(array('status' => 1));

                        return response()->json([
                            'success' => true
                        ], 200);
                        break;
                    case 999;

                        $approval = $data->approval + 1;
                        DB::table('0_project_daily_plan')->where('reference', $data->reference)
                            ->update(array(
                                'approval' => $approval,
                                'status' => 1,
                                'last_update' => "($date)-$name Approve; "
                            ));

                        DB::table('0_pdp_log')->insert(array(
                            'reference' => $data->reference,
                            'approval' => $data->approval,
                            'person_id' => Auth::guard()->user()->id
                        ));

                        return response()->json([
                            'success' => true
                        ], 200);
                        break;
                }


                if ($level == 1 || $level == 3 || $level == 2 || $level == 999 || $level == 0 || $level == 555) {

                    $fileqrname = date('Ymd') . rand(1, 9999999999);
                    $approval = $tmp['approval'] + 1;
                    DB::table('0_project_daily_plan')->where('reference', $data->reference)
                        ->update(array(
                            'approval' => $approval,
                            'status' => 1,
                            'qrcode' => $fileqrname,
                            'last_update' => "($date)-$name Approve; "
                        ));

                    DB::table('0_pdp_log')->insert(array(
                        'reference' => $data->reference,
                        'approval' => $data->approval,
                        'person_id' => Auth::guard()->user()->id
                    ));

                    $tmp['message'] = "Data Updated";
                }
            } else if ($validate_date > $data->plan_end) {
                throw new ValidateDatePDPHttpException();
            }
        }
    }
    //==================================================================== DISAPPROVE Project Daily Plan =============================================================\\
    public function disapprove_pdp(Request $request, $id)
    {
        $currentUser = JWTAuth::parseToken()->authenticate();
        $level = Auth::guard()->user()->approval_level;
        $name = Auth::guard()->user()->name;
        $date = Carbon::now();

        $response = [];

        $sql = "SELECT * FROM 0_project_daily_plan WHERE id = $id";
        $updated = DB::select(DB::raw($sql));

        foreach ($updated as $data) {
            $tmp = [];
            $tmp['reference'] = $data->reference;
            $tmp['approval'] = $data->approval;

            if ($level == 1 || $level == 3 || $level == 2 || $level == 999 || $level == 0 || $level == 555) {

                DB::table('0_project_daily_plan')->where('reference', $data->reference)
                    ->update(array(
                        'approval' => 5,
                        'remark_disapprove' => $request->remark,
                        'status' => 2,
                        'last_update' => "($date)-$name Disapprove; "
                    ));

                DB::table('0_pdp_log')->insert(array(
                    'reference' => $data->reference,
                    'approval' => $data->approval,
                    'person_id' => Auth::guard()->user()->id
                ));

                $tmp['message'] = "Data Updated";
            } else if ($level != 1 || $level != 3 || $level != 2 || $level != 999 || $level != 0 || $level != 555) {
                $tmp['message'] = "Data Not Updated";
            }

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    //==================================================================== Remark Security Project Daily Plan =============================================================\\
    public function security_remark_pdp(Request $request, $id)
    {
        $currentUser = JWTAuth::parseToken()->authenticate();
        $level = Auth::guard()->user()->approval_level;
        $name = Auth::guard()->user()->name;
        $user_id = Auth::guard()->user()->id;
        $date = Carbon::now();

        $response = [];

        $sql = "SELECT * FROM 0_project_daily_plan WHERE id = $id";
        $updated = DB::select(DB::raw($sql));

        foreach ($updated as $data) {

            DB::table('0_project_daily_plan')->where('reference', $data->reference)
                ->update(array(
                    'remark_security' => $request->remark,
                    'checked_by' => $user_id,
                    'checked_time' => Carbon::now()
                ));
        }

        return response()->json([
            'success' => true
        ], 200);
    }

    public function update_project_sites(Request $request, $site_no)
    {
        $response = [];
        DB::beginTransaction();
        try {
            DB::table('0_project_site')->where('site_no', $site_no)
                ->update(array(
                    'site_id' => $request->site_id,
                    'name' => $request->name,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'is_office' => $request->office
                ));
            // Commit Transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $response
            ], 200);
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }

    public function add_sites(Request $request)
    {
        $response = [];

        DB::beginTransaction();
        try {
            DB::table('0_project_site')->insert(array(
                'site_id' => $request->site_id,
                'name' => $request->site_name,
                'site_code' => $request->site_code,
                'latitude' => $request->lat,
                'longitude' => $request->long,
                'is_office' => $request->office
            ));
            // Commit Transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $response
            ], 200);
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }

    public function upload_new_site(Request $request)
    {
        $file = $request->file('uploaded_file');
        if ($file) {
            $filename = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension(); //Get extension of uploaded file
            $tempPath = $file->getRealPath();
            $fileSize = $file->getSize(); //Get size of uploaded file in bytes
            //Check for file extension and size
            $this->checkUploadedFileProperties($extension, $fileSize);
            //Where uploaded file will be stored on the server 
            $location = 'uploads'; //Created an "uploads" folder for that
            // Upload file
            $file->move($location, $filename);
            // In case the uploaded file path is to be stored in the database 
            $filepath = public_path($location . "/" . $filename);
            // Reading file
            $file = fopen($filepath, "r");
            $importData_arr = array(); // Read through the file and store the contents as an array
            $i = 0;
            //Read the contents of the uploaded file 
            while (($filedata = fgetcsv($file, 1000, ",")) !== FALSE) {
                $num = count($filedata);
                // Skip first row (Remove below comment if you want to skip the first row)
                if ($i == 0) {
                    $i++;
                    continue;
                }
                for ($c = 0; $c < $num; $c++) {
                    $importData_arr[$i][] = $filedata[$c];
                }
                $i++;
            }
            fclose($file); //Close after reading
            $j = 0;
            foreach ($importData_arr as $importData) {
                $j++;
                try {
                    DB::table('0_project_site')->insert(array(
                        'is_office' => $importData[0],
                        'site_id' => $importData[1],
                        'name' => $importData[2],
                        'latitude' => $importData[3],
                        'longitude' => $importData[4]
                    ));
                    DB::commit();
                } catch (\Exception $e) {
                    //throw $th;
                    DB::rollBack();
                }
            }
            return response()->json([
                'message' => "$j records successfully uploaded"
            ]);
        } else {
            //no file was uploaded
            echo "haha";
        }
    }

    public function checkUploadedFileProperties($extension, $fileSize)
    {
        $valid_extension = array("csv", "xlsx"); //Only want csv and excel files
        $maxFileSize = 2097152; // Uploaded file size limit is 2mb
        if (in_array(strtolower($extension), $valid_extension)) {
            if ($fileSize <= $maxFileSize) {
            } else {
                echo '413 error';
            }
        } else {
            echo '415 error';
        }
    }

    public function rab_type_list()
    {
        $response = [];

        $sql = "SELECT * FROM 0_project_type_rab";
        $uom = DB::select(DB::raw($sql));
        foreach ($uom as $data) {

            $tmp = [];
            $tmp['id'] = $data->id;
            $tmp['name'] = $data->items;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }
}
