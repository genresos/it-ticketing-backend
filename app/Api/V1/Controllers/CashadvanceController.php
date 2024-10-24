<?php

namespace App\Api\V1\Controllers;
use JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Carbon\Carbon;
use Auth;
use App\CashAdvance;


class CashadvanceController extends Controller
{
    //
    use Helpers;

//==================================================================== FUNCTION NEED APPROVAL =============================================================\\  
    /**
     * @OA\Get(
     *     path="/api/ca",
     *     tags={"ca"},
     * 
     *     summary="Returns a Sample API response",
     *     description="A sample login to test out the API",
     *     operationId="ca",
     * 
     *     @OA\Response(response=200, description="Return a list of resources"),
     *     security={{ "apiAuth": {} }}
     * )
     */

    public function needApproval()
    {
          /*
     * For Displaying CA that requires Approval by PM 
     * 
     */

        $currentUser = JWTAuth::parseToken()->authenticate();
        
        $response = [];
        $level = Auth::guard()->user()->approval_level;
        $person_id = Auth::guard()->user()->person_id;
        $division_id = Auth::guard()->user()->division_id;
        $user_id = Auth::guard()->user()->old_id;
        if ($level == 1 && $user_id != 1117) {  // ===================== Display For PM
            $sql = "SELECT 
                    ca.trans_no,
                    ca.reference,
                    ca.tran_date,
                    ct.name as ca_type_name,
                    e.name as employee_name,
                    e.emp_id as emp_id,
                    d.name as division_name,
                    ca.amount,                                                             
                    SUM(cad.approval_amount) as approval_amount,
                    COUNT(cad.cash_advance_detail_id) as count_cad,
                    m.person_id
                FROM 0_cashadvance ca 
                INNER JOIN 0_cashadvance_details cad ON (ca.trans_no = cad.trans_no)
                LEFT JOIN 0_hrm_employees e ON (e.id = ca.emp_no)
                LEFT JOIN 0_projects p ON (ca.project_no = p.project_no)
                LEFT OUTER JOIN 0_members m ON (p.person_id = m.person_id)
                LEFT JOIN 0_hrm_divisions d ON (d.division_id = p.division_id)
                LEFT JOIN 0_cashadvance_types ct ON (ct.ca_type_id = ca.ca_type_id)
                WHERE ca.approval=1 AND ca.ca_type_id NOT IN (2,9,6)
                AND YEAR(ca.tran_date) >2017 AND p.person_id = $person_id
                GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id"; // OKKKKK

            }else if ($level == 1 && $user_id == 1117) {  // ===================== Display For PM (Maintance Kendaraan)
                $sql = "SELECT 
                        ca.trans_no,
                        ca.reference,
                        ca.tran_date,
                        ct.name as ca_type_name,
                        e.name as employee_name,
                        e.emp_id as emp_id,
                        d.name as division_name,
                        ca.amount,                                                             
                        SUM(cad.approval_amount) as approval_amount,
                        COUNT(cad.cash_advance_detail_id) as count_cad,
                        m.person_id
                    FROM 0_cashadvance ca 
                    INNER JOIN 0_cashadvance_details cad ON (ca.trans_no = cad.trans_no)
                    LEFT JOIN 0_hrm_employees e ON (e.id = ca.emp_no)
                    LEFT JOIN 0_projects p ON (ca.project_no = p.project_no)
                    LEFT OUTER JOIN 0_members m ON (p.person_id = m.person_id)
                    LEFT JOIN 0_hrm_divisions d ON (d.division_id = p.division_id)
                    LEFT JOIN 0_cashadvance_types ct ON (ct.ca_type_id = ca.ca_type_id)
                    WHERE ca.approval=1 AND ca.ca_type_id NOT IN (2,9)
                    AND YEAR(ca.tran_date) >2017 AND p.person_id = $person_id
                    OR ca.approval=1 AND ca.ca_type_id = 6
                    AND YEAR(ca.tran_date) >2017
                    GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id"; // OKKKKK

        } else if ($level == 2) {  // ===================== Display For DGM
            $sql = "SELECT 
                    ca.trans_no,
                    ca.reference,
                    ca.tran_date,
                    ct.name as ca_type_name,
                    e.name as employee_name,
                    e.emp_id as emp_id,
                    d.name as division_name,
                    ca.amount,                                                             
                    SUM(cad.approval_amount) as approval_amount,
                    COUNT(cad.cash_advance_detail_id) as count_cad,
                    m.person_id
                FROM 0_cashadvance ca 
                INNER JOIN 0_cashadvance_details cad ON (ca.trans_no = cad.trans_no)
                LEFT JOIN 0_hrm_employees e ON (e.id = ca.emp_no)
                LEFT JOIN 0_projects p ON (ca.project_no = p.project_no)
                LEFT OUTER JOIN 0_members m ON (p.person_id = m.person_id)
                LEFT JOIN 0_hrm_divisions d ON (d.division_id = p.division_id)
                LEFT JOIN 0_cashadvance_types ct ON (ct.ca_type_id = ca.ca_type_id)
                WHERE ca.approval=2 AND ca.ca_type_id NOT IN (2,9,6)
                AND YEAR(ca.tran_date) >2017 AND  p.division_id IN (SELECT division_id FROM 0_user_divisions WHERE user_id=$user_id) 
                GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id"; // OKKKKK

            }else if ($level == 4)    // ===================== Display For PC
            {
                    $sql = "SELECT 
                        ca.trans_no,
                        ca.reference,
                        ca.tran_date,
                        ct.name as ca_type_name,
                        e.name as employee_name,
                        e.emp_id as emp_id,
                        d.name as division_name,
                        ca.amount,
                        SUM(cad.approval_amount) as approval_amount,
                        COUNT(cad.cash_advance_detail_id) as count_cad,
                        m.person_id
                    FROM 0_cashadvance ca 
                    INNER JOIN 0_cashadvance_details cad ON (ca.trans_no = cad.trans_no)
                    LEFT JOIN 0_hrm_employees e ON (e.id = ca.emp_no)
                    LEFT JOIN 0_projects p ON (ca.project_no = p.project_no)
                    LEFT OUTER JOIN 0_members m ON (p.person_id = m.person_id)
                    LEFT JOIN 0_hrm_divisions d ON (d.division_id = p.division_id)
                    LEFT JOIN 0_cashadvance_types ct ON (ct.ca_type_id = ca.ca_type_id)
                    WHERE ca.approval=1 AND cad.status_id < 2 AND ca.ca_type_id NOT IN (2,9)
                    AND YEAR(ca.tran_date) >2017 AND p.person_id = $person_id
            OR ca.approval=4 AND cad.status_id < 2 AND ca.ca_type_id NOT IN (2,9)
                    AND YEAR(ca.tran_date) >2017 AND p.division_id IN 
                                                                            (
                                                                                SELECT division_id FROM 0_user_project_control 
                                                                                WHERE user_id=$user_id
                                                                            )
                    GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id"; //OKKK

            }else if ($level == 51)    // ===================== Display For Dept Head HR/GA/AM/ICT
            {
                                        $sql = "SELECT 
                                            ca.trans_no,
                                            ca.reference,
                                            ca.tran_date,
                                            ct.name as ca_type_name,
                                            e.name as employee_name,
                                            e.emp_id as emp_id,
                                            d.name as division_name,
                                            ca.amount,
                                            SUM(cad.approval_amount) as approval_amount,
                                            COUNT(cad.cash_advance_detail_id) as count_cad,
                                            m.person_id
                                        FROM 0_cashadvance ca 
                                        INNER JOIN 0_cashadvance_details cad ON (ca.trans_no = cad.trans_no)
                                        LEFT JOIN 0_hrm_employees e ON (e.id = ca.emp_no)
                                        LEFT JOIN 0_projects p ON (ca.project_no = p.project_no)
                                        LEFT OUTER JOIN 0_members m ON (p.person_id = m.person_id)
                                        LEFT JOIN 0_hrm_divisions d ON (d.division_id = p.division_id)
                                        LEFT JOIN 0_cashadvance_types ct ON (ct.ca_type_id = ca.ca_type_id)
                                        WHERE ca.approval=51 AND cad.status_id < 2 AND YEAR(ca.tran_date) >2017 AND p.division_id = $division_id
                                        GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id";

            }else if ($level == 52)    // ===================== Display For Dept Head FA/BPC/PROC
            {
                                        $sql = "SELECT 
                                            ca.trans_no,
                                            ca.reference,
                                            ca.tran_date,
                                            ct.name as ca_type_name,
                                            e.name as employee_name,
                                            e.emp_id as emp_id,
                                            d.name as division_name,
                                            ca.amount,
                                            SUM(cad.approval_amount) as approval_amount,
                                            COUNT(cad.cash_advance_detail_id) as count_cad,
                                            m.person_id
                                        FROM 0_cashadvance ca 
                                        INNER JOIN 0_cashadvance_details cad ON (ca.trans_no = cad.trans_no)
                                        LEFT JOIN 0_hrm_employees e ON (e.id = ca.emp_no)
                                        LEFT JOIN 0_projects p ON (ca.project_no = p.project_no)
                                        LEFT OUTER JOIN 0_members m ON (p.person_id = m.person_id)
                                        LEFT JOIN 0_hrm_divisions d ON (d.division_id = p.division_id)
                                        LEFT JOIN 0_cashadvance_types ct ON (ct.ca_type_id = ca.ca_type_id)
                                        WHERE ca.approval=52 AND cad.status_id < 2 AND YEAR(ca.tran_date) >2017 AND p.division_id = $division_id
                                        GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id";

            }else if ($level == 3){  // ===================== Display For GM
                                            $sql = "SELECT 
                                                    ca.trans_no,
                                                    ca.reference,
                                                    ca.tran_date,
                                                    ct.name as ca_type_name,
                                                    e.name as employee_name,
                                                    e.emp_id as emp_id,
                                                    d.name as division_name,
                                                    ca.amount,
                                                    SUM(cad.approval_amount) as approval_amount,
                                                    COUNT(cad.cash_advance_detail_id) as count_cad,
                                                    m.person_id
                                                FROM 0_cashadvance ca 
                                                INNER JOIN 0_cashadvance_details cad ON (ca.trans_no = cad.trans_no)
                                                LEFT JOIN 0_hrm_employees e ON (e.id = ca.emp_no)
                                                LEFT JOIN 0_projects p ON (ca.project_no = p.project_no)
                                                LEFT OUTER JOIN 0_members m ON (p.person_id = m.person_id)
                                                LEFT JOIN 0_hrm_divisions d ON (d.division_id = p.division_id)
                                                LEFT JOIN 0_cashadvance_types ct ON (ct.ca_type_id = ca.ca_type_id)
                                                WHERE cad.status_id < 2 AND YEAR(ca.tran_date) >2017 AND ca.approval = 3 AND p.division_id IN 
                                                                                                        (
                                                                                                            SELECT division_id FROM 0_user_divisions
                                                                                                            WHERE user_id=$user_id
                                                                                                        ) 
                                                OR cad.status_id < 2 AND YEAR(ca.tran_date) >2017 AND ca.approval = 1 AND p.person_id = $person_id
                                                GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id";
                                        

            }else if ($level == 5)    // ===================== Display For FA
            {
                $sql = "SELECT 
                    ca.trans_no,
                    ca.reference,
                    ca.tran_date,
                    ct.name as ca_type_name,
                    e.name as employee_name,
                    e.emp_id as emp_id,
                    d.name as division_name,
                    ca.amount,
                    SUM(cad.approval_amount) as approval_amount,
                    COUNT(cad.cash_advance_detail_id) as count_cad,
                    m.person_id
                FROM 0_cashadvance ca 
                INNER JOIN 0_cashadvance_details cad ON (ca.trans_no = cad.trans_no)
                LEFT JOIN 0_hrm_employees e ON (e.id = ca.emp_no)
                LEFT JOIN 0_projects p ON (ca.project_no = p.project_no)
                LEFT OUTER JOIN 0_members m ON (p.person_id = m.person_id)
                LEFT JOIN 0_hrm_divisions d ON (d.division_id = p.division_id)
                LEFT JOIN 0_cashadvance_types ct ON (ct.ca_type_id = ca.ca_type_id)
                WHERE ca.approval=5 AND cad.status_id < 2 AND ca.ca_type_id IN (2,9) AND YEAR(ca.tran_date) >2017
                GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id";

            }else if ($level == 42)    // ===================== Display For Dir. Ops
            {
                $sql = "SELECT 
                    ca.trans_no,
                    ca.reference,
                    ca.tran_date,
                    ct.name as ca_type_name,
                    e.name as employee_name,
                    e.emp_id as emp_id,
                    d.name as division_name,
                    ca.amount,
                    SUM(cad.approval_amount) as approval_amount,
                    COUNT(cad.cash_advance_detail_id) as count_cad,
                    m.person_id
                FROM 0_cashadvance ca 
                INNER JOIN 0_cashadvance_details cad ON (ca.trans_no = cad.trans_no)
                LEFT JOIN 0_hrm_employees e ON (e.id = ca.emp_no)
                LEFT JOIN 0_projects p ON (ca.project_no = p.project_no)
                LEFT OUTER JOIN 0_members m ON (p.person_id = m.person_id)
                LEFT JOIN 0_hrm_divisions d ON (d.division_id = p.division_id)
                LEFT JOIN 0_cashadvance_types ct ON (ct.ca_type_id = ca.ca_type_id)
                WHERE ca.approval=42 AND cad.status_id < 2 AND ca.ca_type_id NOT IN (2,9) AND YEAR(ca.tran_date) >2017
		OR ca.approval = 3 AND cad.status_id < 2 AND ca.ca_type_id NOT IN (2,9) AND YEAR(ca.tran_date) >2017 
		AND p.division_id IN (5,6,7,8,10,11,25)
                GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id";
            }else if ($level == 43)    // ===================== Display For Dir. FA
            {
                $sql = "SELECT 
                    ca.trans_no,
                    ca.reference,
                    ca.tran_date,
                    ct.name as ca_type_name,
                    e.name as employee_name,
                    e.emp_id as emp_id,
                    d.name as division_name,
                    ca.amount,
                    SUM(cad.approval_amount) as approval_amount,
                    COUNT(cad.cash_advance_detail_id) as count_cad,
                    m.person_id
                FROM 0_cashadvance ca 
                INNER JOIN 0_cashadvance_details cad ON (ca.trans_no = cad.trans_no)
                LEFT JOIN 0_hrm_employees e ON (e.id = ca.emp_no)
                LEFT JOIN 0_projects p ON (ca.project_no = p.project_no)
                LEFT OUTER JOIN 0_members m ON (p.person_id = m.person_id)
                LEFT JOIN 0_hrm_divisions d ON (d.division_id = p.division_id)
                LEFT JOIN 0_cashadvance_types ct ON (ct.ca_type_id = ca.ca_type_id)
                WHERE ca.approval=43 AND cad.status_id < 2 AND ca.ca_type_id NOT IN (2,9) AND YEAR(ca.tran_date) >2017
		OR ca.approval=1 AND p.division_id = 3 AND cad.status_id < 2 AND ca.ca_type_id NOT IN (2,9) AND YEAR(ca.tran_date) >2017
                OR cad.status_id < 2 AND YEAR(ca.tran_date) >2017 AND ca.approval = 3 AND p.division_id IN 
                                                                                                        (
                                                                                                            SELECT division_id FROM 0_user_divisions
                                                                                                            WHERE user_id=$user_id
                                                                                                        ) 
                GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id";

            }else if ($level == 41)    // ===================== Display For Direktur Utama
            {
                $sql = "SELECT 
                        ca.trans_no,
                        ca.reference,
                        ca.tran_date,
                        ct.name AS ca_type_name,
                        e.name AS employee_name,
                        e.emp_id AS emp_id,
                        d.name AS division_name,
                        ca.amount,
                        SUM(cad.approval_amount) AS approval_amount,
                        COUNT(cad.cash_advance_detail_id) AS count_cad,
                        m.person_id
                    FROM 0_cashadvance ca 
                    INNER JOIN 0_cashadvance_details cad ON (ca.trans_no = cad.trans_no)
                    LEFT JOIN 0_hrm_employees e ON (e.id = ca.emp_no)
                    LEFT JOIN 0_projects p ON (ca.project_no = p.project_no)
                    LEFT OUTER JOIN 0_members m ON (p.person_id = m.person_id)
                    LEFT JOIN 0_hrm_divisions d ON (d.division_id = p.division_id)
                    LEFT JOIN 0_cashadvance_types ct ON (ct.ca_type_id = ca.ca_type_id)
                    WHERE ca.approval = 41 AND cad.status_id < 2 AND ca.ca_type_id NOT IN (2,9) AND YEAR(ca.tran_date) >2017 
                    OR ca.approval = 1 AND cad.status_id < 2 AND ca.ca_type_id NOT IN (2,9) AND YEAR(ca.tran_date) >2017 AND p.person_id = $person_id
                    OR ca.approval = 3 AND cad.status_id < 2 AND ca.ca_type_id NOT IN (2,9) AND YEAR(ca.tran_date) >2017 AND p.division_id IN 
                                                                                                        (
                                                                                                            SELECT division_id FROM 0_user_divisions
                                                                                                            WHERE user_id=$user_id
                                                                                                        ) 
		    GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id
		    ORDER BY ca.tran_date DESC";

            }else if ($level == 999){

             // ===================== Display For Admin

            $sql = "SELECT 
                    ca.trans_no,
                    ca.reference,
                    ca.tran_date,
                    ct.name as ca_type_name,
                    e.name as employee_name,
                    e.emp_id as emp_id,
                    d.name as division_name,
                    ca.amount,
                    SUM(cad.approval_amount) as approval_amount,
                    COUNT(cad.cash_advance_detail_id) as count_cad,
                    m.person_id
                FROM 0_cashadvance ca 
                INNER JOIN 0_cashadvance_details cad ON (ca.trans_no = cad.trans_no)
                LEFT JOIN 0_hrm_employees e ON (e.id = ca.emp_no)
                LEFT JOIN 0_projects p ON (ca.project_no = p.project_no)
                LEFT OUTER JOIN 0_members m ON (p.person_id = m.person_id)
                LEFT JOIN 0_hrm_divisions d ON (d.division_id = p.division_id)
                LEFT JOIN 0_cashadvance_types ct ON (ct.ca_type_id = ca.ca_type_id)
                WHERE ca.approval < 6 AND cad.status_id < 2 AND ca.ca_type_id NOT IN (2,9) 
                AND YEAR(ca.tran_date) >2017
                GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id
		ORDER BY ca.tran_date DESC";
        } else {
            $sql = "SELECT * FROM 0_cashadvance WHERE trans_no = 999999999";
        }

        $cashadvances = DB::select( DB::raw($sql));

        
        foreach ($cashadvances as $cashadvance) {
           
            $tmp = [];
            $tmp['transaction_id'] = $cashadvance ->trans_no;
            $tmp['document_no'] = $cashadvance->reference;
            $tmp['ca_type'] = $cashadvance->ca_type_name;
            $tmp['tran_date'] = $cashadvance->tran_date;
            $tmp['requestor'] = $cashadvance->employee_name;
            $tmp['employe_id'] = $cashadvance->emp_id;
            $tmp['division'] = $cashadvance->division_name;  
            $tmp['amount'] = $cashadvance->amount;    
            $tmp['approval_amount'] = $cashadvance->approval_amount;
            $tmp['detail_count'] = $cashadvance->count_cad;   
          
            $sql = "SELECT 
                        cad.*,
                        CASE WHEN cad.approval = 0 THEN 'Open'
                        WHEN cad.approval = 1 THEN 'Approve'
                        WHEN cad.approval = 2 THEN 'Disapprove' ELSE cad.approval END AS approval_status,
                        p.project_no,
                        p.code as project_code,
                        m.name as project_manager,
                        pct.name as cost_type_name,
                        s.name as site_name,
			pcg.name as budget_type_name,
                        pb.budget_name as budget_name,
                        ca.tran_date as ca_date
                FROM 0_cashadvance_details cad  
                LEFT OUTER JOIN 0_cashadvance ca ON (ca.trans_no = cad.trans_no)
                LEFT JOIN 0_projects p ON (p.project_no = cad.project_no)
                LEFT JOIN 0_project_site s ON (s.site_no = cad.site_id)
                LEFT JOIN 0_members m On (m.person_id = p.person_id)
                LEFT JOIN 0_project_cost_type pct ON (pct.cost_type_id = cad.cost_code)
		LEFT JOIN 0_project_budgets pb ON (cad.project_budget_id = pb.project_budget_id)
                LEFT JOIN 0_project_cost_type_group pcg ON (pb.budget_type_id = pcg.cost_type_group_id)
                WHERE cad.trans_no = $cashadvance->trans_no AND cad.status_id < 2";
           
            $cashadvances_details = DB::select( DB::raw($sql));

        
            foreach ($cashadvances_details as $cashadvance_detail) {
                    $items = [];
                    $items['list_id'] = $cashadvance_detail->cash_advance_detail_id;
                    $items['ca_date'] = $cashadvance_detail->ca_date;
                    $items['project_id'] = $cashadvance_detail->project_code;
                    $items['budget_id'] = $cashadvance_detail->project_budget_id;
		    $items['budget_type_name'] = $cashadvance_detail->budget_type_name;
                    $items['budget_name'] = $cashadvance_detail->budget_name;
                    $items['site_id'] = $cashadvance_detail->site_id;                
                    $items['site_name'] = $cashadvance_detail->site_name;
                    $items['project_manager'] = $cashadvance_detail->project_manager;
                    $items['cost_type_name'] = $cashadvance_detail->cost_type_name;
                    $items['remark'] = $cashadvance_detail->remark;
                    $items['amount'] = $cashadvance_detail->amount;
                    $items['approval_amount'] = $cashadvance_detail->approval_amount;
                    $items['status'] = $cashadvance_detail->status_id;
                
            
                    
                    $tmp['cad_list'][] = $items;
                
            }
            array_push($response, $tmp);
        }
        return response()->json([
            'success' => true,
            'data' => $response
        ]);

    }


    //==================================================================== FUNCTION DETAIL =============================================================\\  
    /*
     * For Get Detail CA ITEMS by cash_advance_detail_id
     * 
     */

    public function detail(Request $request, $id)
    {
        $currentUser = JWTAuth::parseToken()->authenticate();
        $response = [];
        $sql = "SELECT 
                        cad.*,
                        CASE WHEN cad.approval = 0 THEN 'Open'
                        WHEN cad.approval = 1 THEN 'Approve'
                        WHEN cad.approval = 2 THEN 'Disapprove' ELSE cad.approval END AS approval_status,
                        p.project_no,
                        p.code as project_code,
                        m.name as project_manager,
                        pct.name as cost_type_name,
                        s.name as site_name,
                        ca.tran_date as ca_date
                FROM 0_cashadvance_details cad  
                LEFT OUTER JOIN 0_cashadvance ca ON (ca.trans_no = cad.trans_no)
                LEFT JOIN 0_projects p ON (p.project_no = cad.project_no)
                LEFT JOIN 0_project_site s ON (s.site_no = cad.site_id)
                LEFT JOIN 0_members m On (m.person_id = p.person_id)
                LEFT JOIN 0_project_cost_type pct ON (pct.cost_type_id = cad.cost_code)
                WHERE cad.cash_advance_detail_id = $id";

        $cashadvances = DB::select( DB::raw($sql));

        
        foreach ($cashadvances as $cashadvance_detail) {
           
            $tmp = [];
                $items['list_id'] = $cashadvance_detail->cash_advance_detail_id;
                $items['ca_trans_no'] = $cashadvance_detail->trans_no;
                $items['ca_date'] = $cashadvance_detail->ca_date;
                $items['project_id'] = $cashadvance_detail->project_code;
                $items['budget_id'] = $cashadvance_detail->project_budget_id;
                $items['site_id'] = $cashadvance_detail->site_id;                
                $items['site_name'] = $cashadvance_detail->site_name;
                $items['project_manager'] = $cashadvance_detail->project_manager;
                $items['cost_type_name'] = $cashadvance_detail->cost_type_name;
                $items['remark'] = $cashadvance_detail->remark;
                $items['amount'] = $cashadvance_detail->amount;
                $items['approval_amount'] = $cashadvance_detail->approval_amount;
                $items['status'] = $cashadvance_detail->status_id;
            
           
                
                $tmp['cad_list'][] = $items;
            
            array_push($response, $tmp);
            
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ]);

    }


    /*
     * For Update CA Approval Detail by cash_advance_detail_id
     * 
     */


    //==================================================================== FUNCTION UPDATE DETAIL =============================================================\\  
    public function update_detail(Request $request, $id, $id2)
    {
        $currentUser = JWTAuth::parseToken()->authenticate();

        $date=Carbon::now();
        $response=[];
        $sql = "SELECT 
                        cad.*,
                        ca.tran_date as ca_date,
                        p.project_no,
                        p.code as project_code,
                        m.name as project_manager,
                        pct.name as cost_type_name,
                        s.name as site_name
                FROM 0_cashadvance_details cad  
                LEFT JOIN 0_cashadvance ca ON (cad.trans_no=ca.trans_no)
                LEFT JOIN 0_projects p ON (p.project_no = cad.project_no)
                LEFT JOIN 0_project_site s ON (s.site_no = cad.site_id)
                LEFT JOIN 0_members m On (m.person_id = p.person_id)
                LEFT JOIN 0_project_cost_type pct ON (pct.cost_type_id = cad.cost_code)
                WHERE cad.trans_no = $id AND cad.cash_advance_detail_id=$id2";

        $cashadvances = DB::select( DB::raw($sql));

        
        foreach ($cashadvances as $cashadvance_detail) {
           
                $items = [];
                $items['list_id'] = $cashadvance_detail->cash_advance_detail_id;
                $items['ca_trans_no'] = $cashadvance_detail->trans_no;
                $items['ca_date'] = $cashadvance_detail->ca_date;
                $items['project_id'] = $cashadvance_detail->project_code;
                $items['budget_id'] = $cashadvance_detail->project_budget_id;
                $items['site_id'] = $cashadvance_detail->site_id;                
                $items['site_name'] = $cashadvance_detail->site_name;
                $items['project_manager'] = $cashadvance_detail->project_manager;
                $items['cost_type_name'] = $cashadvance_detail->cost_type_name;
                $items['remark'] = $cashadvance_detail->remark;
                $items['amount'] = $cashadvance_detail->amount;
                $items['approval_amount'] = $request->approval_amount; 
                $items['status'] = $request->status_id;

 
                DB::table('0_cashadvance_details')->where('cash_advance_detail_id',$id2)
                                              ->update(array('status_id' => $request->status_id,                //========================================//
                                                             'approval_amount' => $request->approval_amount,    //     This Request needed for update     //
                                                             'approval_date' => $date,                          //                                        //
                                                             'act_amount' => $request->approval_amount,         //                                        //
                                                             'remark' => $request->remark));                    //========================================//
            

                array_push($response,$items);
            
        }
     

        return response()->json([
            'success' => true,
            'data' => $response
        ]);

    }

    /*
     * For Update CA if all details are inputed done.
     * 
     * 
     */

//==================================================================== FUNCTION UPDATE CA =============================================================\\  
    public function update_ca($id) {

        $currentUser = JWTAuth::parseToken()->authenticate();
        $session=Auth::guard()->user()->name;
        $date=Carbon::now();
        $approval_desc= "$date - $session";
        $response = [];

        $sql = "SELECT 
                    ca.trans_no,
                    ca.reference,
                    ca.tran_date,
                    ct.name as ca_type_name,
                    e.name as employee_name,
                    e.emp_id as emp_id,
                    e.level_id as emp_level,
                    d.name as division_name,
                    ca.amount as ca_amount,
                    ca.approval as approval,
                    MIN(cad.status_id) as status_id,
                    SUM(cad.approval_amount) as approval_amount,
                    COUNT(cad.cash_advance_detail_id) as count_cad,
                    ca.approval as approval_position,
                    ca.ca_type_id,
                    e.level_id as level_id,
                    p.division_id as division,
                    ca.project_no,
                    d.division_group_id as division_group
                FROM 0_cashadvance ca 
                INNER JOIN 0_cashadvance_details cad ON (ca.trans_no = cad.trans_no)
                LEFT JOIN 0_projects p ON (ca.project_no = p.project_no)
                LEFT JOIN 0_hrm_employees e ON (e.id = ca.emp_no)
                LEFT JOIN 0_hrm_divisions d ON (p.division_id = d.division_id)
                LEFT JOIN 0_cashadvance_types ct ON (ct.ca_type_id = ca.ca_type_id)
                WHERE ca.trans_no=$id
                GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, ca.ca_type_id";

        $cashadvances = DB::select( DB::raw($sql));

        
        foreach ($cashadvances as $cashadvance) {
           
            $tmp = [];
            $tmp['transaction_id'] = $cashadvance ->trans_no;
            $tmp['document_no'] = $cashadvance->reference;
            $tmp['ca_type'] = $cashadvance->ca_type_name;
            $tmp['tran_date'] = $cashadvance->tran_date;
            $tmp['requestor'] = $cashadvance->employee_name;
            $tmp['employe_id'] = $cashadvance->emp_id;
            $tmp['emp_level'] = $cashadvance->emp_level;
            $tmp['division'] = $cashadvance->division_name;  
            $tmp['amount'] = $cashadvance->ca_amount;    
            $tmp['approval_amount'] = $cashadvance->approval_amount;
            $tmp['approval_position'] = $cashadvance->approval_position;
            $tmp['detail_count'] = $cashadvance->count_cad;
            $tmp['project_no'] = $cashadvance->project_no;

            $ca_project_no = $tmp['project_no'];
            $ca_type = $cashadvance->ca_type_id;
            $level = $tmp['emp_level'];
            $amount_approval = $tmp['approval_amount'];
            $approval_position = $tmp['approval_position'];

              // FIND IF REQUESTOR AS MARKETING

              $marketing = DB::table('0_member_marketing')->where('emp_id', $cashadvance->emp_id)->first();
              if($ca_type == 6 && empty($marketing)){
                $routing = DB::table('0_ca_route_vehicle')
                ->where('min_amount','<=',$amount_approval)
                ->where('max_amount','>=',$amount_approval)
                ->get();
                }else if ($ca_type != 6 && empty($marketing)){
                $sql_if_office = "SELECT IF(CODE LIKE '%OFC%', 0, 1) AS value, division_id FROM 0_projects
                            WHERE project_no = $ca_project_no";

                $is_project_query = DB::select(DB::raw($sql_if_office));
                $exe_is_project_query = json_decode(json_encode($is_project_query), true);
                $is_project = $exe_is_project_query[0]['value'];
                $division_id = $exe_is_project_query[0]['division_id'];
                switch ($is_project) {
                    case 1:
                        $routing = DB::table('0_cashadvance_routing_approval')
                            ->where('emp_level_id', $level)
                            ->where('min_amount', '<=', $amount_approval)
                            ->where('max_amount', '>=', $amount_approval)
                            ->get();
                        break;
                    case 0:
                        if ($division_id == 8 || $division_id == 11) {
                            $routing = DB::table('0_cashadvance_routing_approval')
                                ->where(function ($query) use ($is_project, $amount_approval, $level) {
                                    $query->where('emp_level_id', $level)
                                        ->where('is_project', $is_project)
                                        ->where('group_id', 2)
                                        ->where('min_amount', '<=', $amount_approval)
                                        ->where('max_amount', '>=', $amount_approval);
                                })
                                ->get();
                        } else if ($division_id == 7 || $division_id ==  25 || $division_id == 10) {
                            $routing = DB::table('0_cashadvance_routing_approval')
                                ->where(function ($query) use ($is_project, $amount_approval, $level) {
                                    $query->where('emp_level_id', $level)
                                        ->where('is_project', $is_project)
                                        ->where('group_id', 1)
                                        ->where('min_amount', '<=', $amount_approval)
                                        ->where('max_amount', '>=', $amount_approval);
                                })
                                ->get();
                            break;
                        }
                }
                    
                    // $routing = DB::table('0_cashadvance_routing_approval')
                    // ->where('emp_level_id',$level)
                    // ->where('min_amount','<=',$amount_approval)
                    // ->where('max_amount','>=',$amount_approval)
                    // ->get();
                }else if ($ca_type != 6 && !empty($marketing)){
                    $routing = DB::table('0_member_marketing')->where('id', $marketing->id)
                    ->get();
                }
              
            
              foreach ($routing as $key) {
                $id_routing = $key->id;

                $marketing_emp = DB::table('0_member_marketing')->where('id', $key->id)->first();
                if($ca_type != 6 && empty($marketing)){
                    $sql = DB::table('0_cashadvance_routing_approval')
                        ->where('id',$id_routing)
                        ->first();
                }else if($ca_type == 6 && empty($marketing))    {
                            $sql = DB::table('0_ca_route_vehicle')
                            ->where('id',$id_routing)
                            ->first();
                }else if($ca_type != 6 && !empty($marketing)){
                    $sql = DB::table('0_member_marketing')
                    ->where('id',$marketing_emp->id)
                    ->first();
                }
                
                  
                  $data = explode(',',$sql->next_approval);
                  $flipped_array = array_flip($data);
                  $approval_now = $flipped_array[$approval_position];
                  $next = $approval_now + 1;
                  $next_approval = $data[$next];

                  $tmp['next_approval'] = $next_approval;

                  $approval_update = $tmp['next_approval'];
                  
                  if($amount_approval != 0)
                  {
                        DB::table('0_cashadvance_details')->where('trans_no',$tmp['transaction_id'])
                        ->update(array('approval' => $approval_update,
                        'approval_date' => $approval_desc));

                        DB::table('0_cashadvance')->where('trans_no',$tmp['transaction_id'])
                        ->update(array('approval' => $approval_update,
                        'approval_amount' => $amount_approval,
                        'approval_description' => $approval_desc));

                  }else if ($amount_approval == 0)
                  {
                        DB::table('0_cashadvance_details')->where('trans_no',$tmp['transaction_id'])
                        ->update(array('approval' => 7,
                        'approval_date' => $approval_desc));
    
                        DB::table('0_cashadvance')->where('trans_no',$tmp['transaction_id'])
                        ->update(array('approval' => 7,
                        'approval_amount' => $amount_approval,
                        'approval_description' => $approval_desc));
                  }

                
                  DB::table('0_cashadvance_log1')->insert(array('trans_no' => $tmp['transaction_id'],
                        'type' => 3,
                        'approval_id' => $tmp['approval_position'],
                        'approved_amount' => $amount_approval,
                        'updated' => $date,
                        'person_id' => Auth::guard()->user()->id));

              }
        
            array_push($response, $tmp);
            
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ]);

    }

//==================================================================== FUNCTION DISAPPROVE ALL =============================================================\\  
    public function disapprove_all($id){
        $currentUser = JWTAuth::parseToken()->authenticate();
        $session=Auth::guard()->user()->id;
        $name=Auth::guard()->user()->name;
        $date=Carbon::now();
        $approval_desc= "$date - $name";

        $sql = "SELECT * FROM 0_cashadvance WHERE trans_no=$id";

        $cashadvances = DB::select( DB::raw($sql));

        
        foreach ($cashadvances as $cashadvance) {
           
            $tmp['type'] = $cashadvance ->ca_type_id;

            DB::table('0_cashadvance_details')->where('trans_no',$id)
            ->update(array('approval' => 7,
                           'approval_amount' => 0,
                           'act_amount' => 0,
                           'status_id' => 2));
    
            DB::table('0_cashadvance')->where('trans_no',$id)
            ->update(array('approval' => 7,
                           'approval_amount' => 0,
                           'approval_description' => $approval_desc));

            DB::table('0_cashadvance_log1')->insert(array('trans_no' => $id,
                        'type' => 3,
                        'approval_id' => $cashadvance->approval,
                        'approved_amount' => 0,
                        'updated' => $date,
                        'person_id' => $session)); 
        }

        return response()->json([
            'success' => true
        ]);
    
    }
    //==================================================================== FUNCTION APPROVE ALL =============================================================\\  
    public function approve_all($id)
    {
        $currentUser = JWTAuth::parseToken()->authenticate();
        $session=Auth::guard()->user()->name;
        $date=Carbon::now();
        $approval_desc= "$date - $session";
        $response = [];

        $sql = "SELECT 
                    ca.trans_no,
                    ca.reference,
                    ca.tran_date,
                    ct.name as ca_type_name,
                    e.name as employee_name,
                    e.emp_id as emp_id,
                    e.level_id as emp_level,
                    d.name as division_name,
                    ca.amount as ca_amount,
                    cad.amount as cad_amount,
                    ca.approval as approval,
                    MIN(cad.status_id) as status_id,
                    SUM(cad.approval_amount) as approval_amount,
                    COUNT(cad.cash_advance_detail_id) as count_cad,
                    ca.ca_type_id,
                    e.level_id as level_id,
                    p.division_id as division,
                    ca.project_no,
                    d.division_group_id as division_group
                FROM 0_cashadvance ca 
                INNER JOIN 0_cashadvance_details cad ON (ca.trans_no = cad.trans_no)
                LEFT JOIN 0_projects p ON (ca.project_no = p.project_no)
                LEFT JOIN 0_hrm_employees e ON (e.id = ca.emp_no)
                LEFT JOIN 0_hrm_divisions d ON (p.division_id = d.division_id)
                LEFT JOIN 0_cashadvance_types ct ON (ct.ca_type_id = ca.ca_type_id)
                WHERE ca.trans_no=$id AND ca.status_id < 2
                GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, ca.ca_type_id";

        $cashadvances = DB::select( DB::raw($sql));

        
        foreach ($cashadvances as $cashadvance) {
           
            $tmp = [];
            $tmp['transaction_id'] = $cashadvance->trans_no;
            $tmp['document_no'] = $cashadvance->reference;
            $tmp['ca_type'] = $cashadvance->ca_type_name;
            $tmp['tran_date'] = $cashadvance->tran_date;
            $tmp['requestor'] = $cashadvance->employee_name;
            $tmp['employe_id'] = $cashadvance->emp_id;
            $tmp['emp_level'] = $cashadvance->emp_level;
            $tmp['division'] = $cashadvance->division_name;  
            $tmp['amount'] = $cashadvance->ca_amount;    
            $tmp['approval_amount'] = $cashadvance->approval_amount;
            $tmp['approval_position'] = $cashadvance->approval;
            $tmp['detail_count'] = $cashadvance->count_cad;
            $tmp['project_no'] = $cashadvance->project_no;
        

            $sql1 = "SELECT cash_advance_detail_id, trans_no, amount FROM 0_cashadvance_details WHERE trans_no = $cashadvance->trans_no AND status_id < 2 GROUP BY cash_advance_detail_id";
            $ca_detail = DB::select( DB::raw($sql1));
            $ca_type = $cashadvance->ca_type_id;
            $ca_project_no = $tmp['project_no'];
                    foreach ($ca_detail as $item) {

                            $tmp['cad_id'] = $item->cash_advance_detail_id;
                            $tmp['trans_no'] = $item->trans_no;
                            $tmp['cad_amount'] = $item->amount;

                        $ca_amount = $tmp['amount'];
                        $level = $tmp['emp_level'];
                        $amount_approval = $tmp['amount'];
                        $approval_position = $tmp['approval_position'];

                        $marketing = DB::table('0_member_marketing')->where('emp_id', $cashadvance->emp_id)->first();
                            if($ca_type == 6 && empty($marketing)){
                                $routing = DB::table('0_ca_route_vehicle')
                                ->where('min_amount','<=',$amount_approval)
                                ->where('max_amount','>=',$amount_approval)
                                ->get();
                            }else if ($ca_type != 6 && empty($marketing)){

                    $sql_if_office = "SELECT IF(CODE LIKE '%OFC%', 0, 1) AS value, division_id FROM 0_projects
                                                WHERE project_no = $ca_project_no";

                    $is_project_query = DB::select(DB::raw($sql_if_office));

                    foreach ($is_project_query as $key) {
                        $is_project = $key->value;
                        $division_id = $key->division_id;
                    }

                    if ($division_id == 8 || $division_id == 11) {
                        $routing = DB::table('0_cashadvance_routing_approval')
                            ->where(function ($query) use ($is_project, $amount_approval, $level) {
                                $query->where('emp_level_id', $level)
                                    ->where('is_project', $is_project)
                                    ->where('group_id', 2)
                                    ->where('min_amount', '<=', $amount_approval)
                                    ->where('max_amount', '>=', $amount_approval);
                            })
                            ->get();
                    } else if ($division_id == 7 || $division_id ==  25 || $division_id == 10) {
                        $routing = DB::table('0_cashadvance_routing_approval')
                            ->where(function ($query) use ($is_project, $amount_approval, $level) {
                                $query->where('emp_level_id', $level)
                                    ->where('is_project', $is_project)
                                    ->where('group_id', 1)
                                    ->where('min_amount', '<=', $amount_approval)
                                    ->where('max_amount', '>=', $amount_approval);
                            })
                            ->get();
                    } else {
                                                $routing = DB::table('0_cashadvance_routing_approval')
                            ->where('emp_level_id', $level)
                            ->where('min_amount', '<=', $amount_approval)
                            ->where('max_amount', '>=', $amount_approval)
                                                    ->get();
                    }
                                // $routing = DB::table('0_cashadvance_routing_approval')
                                // ->where('emp_level_id',$level)
                                // ->where('min_amount','<=',$amount_approval)
                                // ->where('max_amount','>=',$amount_approval)
                                // ->get();
                            }else if ($ca_type != 6 && !empty($marketing)){
                                $routing = DB::table('0_member_marketing')->where('id', $marketing->id)
                                ->get();
                            }
                        
                        foreach ($routing as $key) {
                            $id_routing = $key->id;

                            $amount_approve_all = DB::table('0_cashadvance_details')
                                    ->where('trans_no',$cashadvance->trans_no)
                                    ->where('status_id','<',2)
                                    ->sum('amount');

                            $marketing_emp = DB::table('0_member_marketing')->where('id', $key->id)->first();
                            if($ca_type != 6 && empty($marketing)){
                                $sql = DB::table('0_cashadvance_routing_approval')
                                    ->where('id',$id_routing)
                                    ->first();
                            }else if($ca_type == 6 && empty($marketing))    {
                                        $sql = DB::table('0_ca_route_vehicle')
                                        ->where('id',$id_routing)
                                        ->first();
                            }else if($ca_type != 6 && !empty($marketing)){
                                $sql = DB::table('0_member_marketing')
                                ->where('id',$marketing_emp->id)
                                ->first();
                            }
                            $data = explode(',',$sql->next_approval);
                            $flipped_array = array_flip($data);
                            $approval_now = $flipped_array[$approval_position];
                            $next = $approval_now + 1;
                            $next_approval = $data[$next];
                            
                            $tmp['next_approval'] = $next_approval;
                            
                            $approval_update = $tmp['next_approval'];
          
                            DB::table('0_cashadvance_details')
                            ->where('cash_advance_detail_id',$tmp['cad_id'])
                            ->update(array('approval' => $approval_update, 'approval_amount' => $tmp['cad_amount'], 'act_amount' => $tmp['cad_amount'], 'status_id' => 1, 'approval_date' => $date));
                            
                            DB::table('0_cashadvance')->where('trans_no',$tmp['transaction_id'])
                            ->update(array('approval' => $approval_update,
                                            'approval_amount' => $amount_approve_all,
                                            'approval_description' => $approval_desc));
                         
                        }
                    }
                    
                    DB::table('0_cashadvance_log1')->insert(array('trans_no' => $tmp['transaction_id'],
                        'type' => 3,
                        'approval_id' => $tmp['approval_position'],
                        'approved_amount' => $amount_approve_all,
                        'updated' => $date,
                        'person_id' => Auth::guard()->user()->id)); 

            array_push($response, $tmp); 
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    
    }

//==================================================================== FUNCTION TESTING =============================================================\\  


    public function test_approval(){
       
        // $routing = "SELECT * FROM 0_cashadvance_routing_approval GROUP BY id";
        // $approval = DB::select( DB::raw($routing));
 
        $list = [];
 
        $amount =1000000000;
        $level = 5;
       
        $routing = DB::table('0_cashadvance_routing_approval')
        ->where('emp_level_id',$level)
        ->where('min_amount','<=',$amount)
        ->where('max_amount','>=',$amount)
        ->get();
 
        foreach ($routing as $key) {
            $id_routing = $key->id;
 
            $approval_position = 43;
 
            $sql = DB::table('0_cashadvance_routing_approval')
            ->where('id',$id_routing)
            ->first();
            
            $data = explode(',',$sql->next_approval);
            $flipped_array = array_flip($data);
            $approval_now = $flipped_array[$approval_position];
            $next = $approval_now + 1;
            $next_approval = $data[$next];

            if(empty($next_approval)){
                return response()->json([
                    'failure' => false
                ],500);
            }else{
                $list['next_approval'][] = $next_approval;
            }
 
            array_push($list); 
        }
 
        return response()->json([
            'success' => true,
            'data' => $list
        ],200);
    
 
    }

    //==================================================================== CA approval history =============================================================\\

    public function get_ca_history(Request $request, $trans_no)
    {
    // $currentUser = JWTAuth::parseToken()->authenticate();
        $response =[];
        $sql ="SELECT ca_log.trans_no, ca.reference, ca_log.approval_id,
                CASE
                    WHEN (ca_log.approval_id=0) THEN 'ADMIN'
                    WHEN (ca_log.approval_id=1) THEN 'PM'
                    WHEN (ca_log.approval_id=2) THEN 'DGM'
                    WHEN (ca_log.approval_id=3) THEN 'GM'
                    WHEN (ca_log.approval_id=31) THEN 'GM(TI/MS)'
                    WHEN (ca_log.approval_id=41) THEN 'DIRECTOR'
                    WHEN (ca_log.approval_id) = 32 THEN 'Dir.Ops'
                    WHEN (ca_log.approval_id) = 42 THEN 'Dir.Ops'
                    WHEN (ca_log.approval_id) = 43 THEN 'Dir.FA'
                    WHEN (ca_log.approval_id=4) THEN 'PC'
                    WHEN (ca_log.approval_id=5) THEN 'FA'
                    WHEN (ca_log.approval_id=6) THEN 'CASHIER'
                    WHEN (ca_log.approval_id=7) THEN 'CLOSE'
                END AS routing_approval, 
                CASE
                    WHEN (ca_log.type=1) THEN u.real_name
                    WHEN (ca_log.type=3) THEN us.name
                END AS person_name, ca_log.updated, c.memo_
                FROM 0_cashadvance_log1 ca_log
                INNER JOIN 0_cashadvance ca ON (ca_log.trans_no = ca.trans_no)
                LEFT JOIN 0_cashadvance_comments c ON (ca_log.trans_no = c.trans_no AND ca_log.approval_id = c.approval_id AND c.type=1)
                LEFT JOIN 0_users u ON (ca_log.person_id = u.id)
                LEFT JOIN users us ON (ca_log.person_id = us.id)
                WHERE ca_log.type IN (1,3) AND ca_log.trans_no=$trans_no GROUP BY ca_log.approval_id ORDER BY ca_log.updated ASC";

        $ca_history = DB::select( DB::raw($sql));

        foreach ($ca_history as $data) {
            
            $tmp = [];
            $tmp['approver_level'] = $data->routing_approval;
            $tmp['approver_name'] = $data->person_name;
            $tmp['last_update'] = $data->updated;

            array_push($response,$tmp);
          
        }

        return response()->json([
            'success' => true,
            'data' => $response 
        ],200);
    }


    //==================================================================== FUNCTION Remark Disapprove =============================================================\\

    public function remark_diss(Request $request, $cad_id)
    {
        $currentUser = JWTAuth::parseToken()->authenticate();

        DB::table('0_cashadvance_details')->where('cash_advance_detail_id',$cad_id)
                            ->update(array('remark_disapprove' => $request->remark));


        return response()->json([
            'success' => true
        ]);
    }

    //==================================================================== FUNCTION Remark Disapprove All =============================================================\\

    public function remark_diss_all(Request $request, $trans_no)
    {
        $currentUser = JWTAuth::parseToken()->authenticate();

        DB::table('0_cashadvance_details')->where('trans_no',$trans_no)
                                ->update(array('remark_disapprove' => $request->remark));

            return response()->json([
                'success' => true
            ]);
    }

    //==================================================================== FUNCTION CA LIST User =============================================================\\  
    public function ca_list_users()
    {

        $currentUser = JWTAuth::parseToken()->authenticate();
        
        $response = [];

            $sql = "SELECT 
                    ca.trans_no,
                    ca.reference,
                    ca.tran_date,
                    ct.name as ca_type_name,
                    e.name as employee_name,
                    e.emp_id as emp_id,
                    d.name as division_name,
                    ca.amount,                                                             
                    SUM(cad.approval_amount) as approval_amount,
                    COUNT(cad.cash_advance_detail_id) as count_cad,
		    CASE
                        WHEN (ca.approval=0) THEN 'ADMIN'
                        WHEN (ca.approval=1) THEN 'PM'
                        WHEN (ca.approval=2) THEN 'DGM'
                        WHEN (ca.approval=3) THEN 'GM'
                        WHEN (ca.approval=31) THEN 'GM(TI/MS)'
                        WHEN (ca.approval=41) THEN 'DIRECTOR'
                        WHEN (ca.approval) = 32 THEN 'Dir.Ops'
                        WHEN (ca.approval) = 42 THEN 'Dir.Ops'
                        WHEN (ca.approval) = 43 THEN 'Dir.FA'
                        WHEN (ca.approval=4) THEN 'PC'
                        WHEN (ca.approval=5) THEN 'FA'
                        WHEN (ca.approval=6) THEN 'CASHIER'
                        WHEN (ca.approval=7) THEN 'CLOSE'
                    END AS pic, 
                    m.person_id
                FROM 0_cashadvance ca 
                INNER JOIN 0_cashadvance_details cad ON (ca.trans_no = cad.trans_no)
                LEFT JOIN 0_hrm_employees e ON (e.id = ca.emp_no)
                LEFT JOIN 0_projects p ON (ca.project_no = p.project_no)
                LEFT OUTER JOIN 0_members m ON (p.person_id = m.person_id)
                LEFT JOIN 0_hrm_divisions d ON (d.division_id = p.division_id)
                LEFT JOIN 0_cashadvance_types ct ON (ct.ca_type_id = ca.ca_type_id)
                WHERE ca.approval != 7 AND ca.ca_type_id NOT IN (2,9)
                AND YEAR(ca.tran_date) >2017
                GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id
                ORDER BY ca.tran_date DESC"; // OKKKKK


        $cashadvances = DB::select( DB::raw($sql));

        
        foreach ($cashadvances as $cashadvance) {
        
            $tmp = [];
            $tmp['transaction_id'] = $cashadvance ->trans_no;
            $tmp['document_no'] = $cashadvance->reference;
            $tmp['ca_type'] = $cashadvance->ca_type_name;
            $tmp['tran_date'] = $cashadvance->tran_date;
            $tmp['requestor'] = $cashadvance->employee_name;
            $tmp['employe_id'] = $cashadvance->emp_id;
            $tmp['division'] = $cashadvance->division_name;  
            $tmp['amount'] = $cashadvance->amount;    
            $tmp['approval_amount'] = $cashadvance->approval_amount;
            $tmp['pic'] = $cashadvance->pic;
	    $tmp['detail_count'] = $cashadvance->count_cad;   
                
            array_push($response, $tmp);
        }
        return response()->json([
            'success' => true,
            'data' => $response
        ]);

    }
}