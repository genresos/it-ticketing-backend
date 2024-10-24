<?php

namespace App\Api\V1\Controllers;
use JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Carbon\Carbon;
use Auth;

class CashadvanceSettlementController extends Controller
{
    //
    use Helpers;

//==================================================================== FUNCTION NEED APPROVAL =============================================================\\
    public function needApproval() {

        $currentUser = JWTAuth::parseToken()->authenticate();
        
        $response = [];

        $level = Auth::guard()->user()->approval_level;
        $person_id = Auth::guard()->user()->person_id;
        $division_id = Auth::guard()->user()->division_id;
        $user_id = Auth::guard()->user()->old_id;
        if ($level == 1) {  // ===================== Display For PM

            $sql = "SELECT stl.trans_no, stl.reference, ca.reference AS ca_ref,  stl.tran_date,
                        ca.emp_id, em.name AS employee_name, d.name AS division_name,
                        stl.amount, stl.approval_amount, stl.ca_amount,
                        cat.name AS ca_type_name,
                        stl.approval AS approval, stl.ca_trans_no,
                        COUNT(stld.ca_stl_id) as count_stld,
                        m.person_id
                        FROM 0_cashadvance_stl stl
                        LEFT OUTER JOIN 0_cashadvance_stl_details stld ON (stl.trans_no = stld.trans_no)
                        LEFT OUTER JOIN 0_cashadvance ca ON (ca.trans_no = stl.ca_trans_no)
                        LEFT OUTER JOIN 0_cashadvance_types cat ON (ca.ca_type_id = cat.ca_type_id)
                        LEFT OUTER JOIN 0_projects prj ON (stld.project_no = prj.project_no)
                        LEFT OUTER JOIN 0_members m ON (prj.person_id = m.person_id)
                        LEFT OUTER JOIN 0_hrm_employees em ON (ca.emp_no = em.id)
                        LEFT OUTER JOIN 0_hrm_divisions d ON (prj.division_id = d.division_id)
                        WHERE stl.approval=1 AND stld.status_id < 2 AND ca.ca_type_id NOT IN (2,9,6) AND stl.active=1 AND prj.person_id=$person_id AND YEAR(stl.tran_date) >= 2021
                        GROUP BY stl.trans_no, stl.reference, ca.reference, 
                        stl.tran_date,ca.emp_id, em.name, d.name, stl.amount, stl.approval_amount, stl.ca_amount, cat.name,stl.approval,stl.ca_trans_no, m.person_id
                        ORDER BY stl.tran_date DESC";
                        
        }else if ($level == 1 && $user_id == 1117)    // ===================== Display For PM (Maintance Kendaraan)
        {

            $sql = "SELECT stl.trans_no, stl.reference, ca.reference AS ca_ref,  stl.tran_date,
                        ca.emp_id, em.name AS employee_name, d.name AS division_name,
                        stl.amount, stl.approval_amount, stl.ca_amount,
                        cat.name AS ca_type_name,
                        stl.approval AS approval, stl.ca_trans_no,
                        COUNT(stld.ca_stl_id) as count_stld,
                        m.person_id
                        FROM 0_cashadvance_stl stl
                        LEFT OUTER JOIN 0_cashadvance_stl_details stld ON (stl.trans_no = stld.trans_no)
                        LEFT OUTER JOIN 0_cashadvance ca ON (ca.trans_no = stl.ca_trans_no)
                        LEFT OUTER JOIN 0_cashadvance_types cat ON (ca.ca_type_id = cat.ca_type_id)
                        LEFT OUTER JOIN 0_projects prj ON (stld.project_no = prj.project_no)
                        LEFT OUTER JOIN 0_members m ON (prj.person_id = m.person_id)
                        LEFT OUTER JOIN 0_hrm_employees em ON (ca.emp_no = em.id)
                        LEFT OUTER JOIN 0_hrm_divisions d ON (prj.division_id = d.division_id)
                        WHERE stl.approval=1 AND stld.status_id < 2 AND ca.ca_type_id NOT IN (2,9) AND stl.active=1 AND prj.person_id=$person_id AND YEAR(stl.tran_date) >= 2019
                        OR stl.approval=1 AND stld.status_id < 2 AND ca.ca_type_id = 6 AND stl.active=1 AND YEAR(stl.tran_date) >= 2019
                        GROUP BY stl.trans_no, stl.reference, ca.reference, 
                        stl.tran_date,ca.emp_id, em.name, d.name, stl.amount, stl.approval_amount, stl.ca_amount, cat.name,stl.approval,stl.ca_trans_no, m.person_id
                        ORDER BY stl.tran_date DESC";

        }else if ($level == 4)    // ===================== Display For PC
        {

            $sql = "SELECT stl.trans_no, stl.reference, ca.reference AS ca_ref,  stl.tran_date,
                        ca.emp_id, em.name AS employee_name, d.name AS division_name,
                        stl.amount, stl.approval_amount, stl.ca_amount,
                        cat.name AS ca_type_name,
                        stl.approval AS approval, stl.ca_trans_no,
                        COUNT(stld.ca_stl_id) as count_stld,
                        m.person_id
                        FROM 0_cashadvance_stl stl
                        LEFT OUTER JOIN 0_cashadvance_stl_details stld ON (stl.trans_no = stld.trans_no)
                        LEFT OUTER JOIN 0_cashadvance ca ON (ca.trans_no = stl.ca_trans_no)
                        LEFT OUTER JOIN 0_cashadvance_types cat ON (ca.ca_type_id = cat.ca_type_id)
                        LEFT OUTER JOIN 0_projects prj ON (stld.project_no = prj.project_no)
                        LEFT OUTER JOIN 0_members m ON (prj.person_id = m.person_id)
                        LEFT OUTER JOIN 0_hrm_employees em ON (ca.emp_no = em.id)
                        LEFT OUTER JOIN 0_hrm_divisions d ON (prj.division_id = d.division_id)
                        WHERE stl.approval=4 AND stld.status_id < 2 AND ca.ca_type_id NOT IN (2,9) AND stl.active=1  AND YEAR(stl.tran_date) >= 2019
                        AND prj.division_id IN 
                                                                        (
                                                                            SELECT division_id FROM 0_user_project_control 
                                                                            WHERE user_id=$user_id
                                                                        )
                        GROUP BY stl.trans_no, stl.reference, ca.reference,
                        stl.tran_date,ca.emp_id, em.name, d.name, stl.amount, stl.approval_amount, stl.ca_amount, cat.name,stl.approval,stl.ca_trans_no, m.person_id
                        ORDER BY stl.tran_date DESC";

        }else if ($level == 51)    // ===================== Display For Dept Head HR/GA/AM/ICT
        {
                    $sql = "SELECT stl.trans_no, stl.reference, ca.reference AS ca_ref,  stl.tran_date,
                            ca.emp_id, em.name AS employee_name, d.name AS division_name,
                            stl.amount, stl.approval_amount, stl.ca_amount,
                            cat.name AS ca_type_name,
                            stl.approval AS approval, stl.ca_trans_no,
                            SUM(stld.approval_amount) as detail_amount,
                            COUNT(stld.ca_stl_id) as count_stld,
                            m.person_id
                            FROM 0_cashadvance_stl stl
                            LEFT OUTER JOIN 0_cashadvance_stl_details stld ON (stl.trans_no = stld.trans_no)
                            LEFT OUTER JOIN 0_cashadvance ca ON (ca.trans_no = stl.ca_trans_no)
                            LEFT OUTER JOIN 0_cashadvance_types cat ON (ca.ca_type_id = cat.ca_type_id)
                            LEFT OUTER JOIN 0_projects prj ON (stld.project_no = prj.project_no)
                            LEFT OUTER JOIN 0_members m ON (prj.person_id = m.person_id)
                            LEFT OUTER JOIN 0_hrm_employees em ON (ca.emp_no = em.id)
                            LEFT OUTER JOIN 0_hrm_divisions d ON (prj.division_id = d.division_id)
                            WHERE stl.approval = 51 AND stld.status_id < 2 AND ca.ca_type_id NOT IN (2,9) AND stl.active=1  AND YEAR(stl.tran_date) >= 2019
                            GROUP BY stl.trans_no, stl.reference, ca.reference,
                            stl.tran_date,ca.emp_id, em.name, d.name, stl.amount, stl.approval_amount, stl.ca_amount, cat.name,stl.approval,stl.ca_trans_no, m.person_id
                            ORDER BY stl.tran_date DESC";

        }else if ($level == 52)    // ===================== Display For Dept Head FA/BPC/PROC
        {
                    $sql = "SELECT stl.trans_no, stl.reference, ca.reference AS ca_ref,  stl.tran_date,
                            ca.emp_id, em.name AS employee_name, d.name AS division_name,
                            stl.amount, stl.approval_amount, stl.ca_amount,
                            cat.name AS ca_type_name,
                            stl.approval AS approval, stl.ca_trans_no,
                            SUM(stld.approval_amount) as detail_amount,
                            COUNT(stld.ca_stl_id) as count_stld,
                            m.person_id
                            FROM 0_cashadvance_stl stl
                            LEFT OUTER JOIN 0_cashadvance_stl_details stld ON (stl.trans_no = stld.trans_no)
                            LEFT OUTER JOIN 0_cashadvance ca ON (ca.trans_no = stl.ca_trans_no)
                            LEFT OUTER JOIN 0_cashadvance_types cat ON (ca.ca_type_id = cat.ca_type_id)
                            LEFT OUTER JOIN 0_projects prj ON (stld.project_no = prj.project_no)
                            LEFT OUTER JOIN 0_members m ON (prj.person_id = m.person_id)
                            LEFT OUTER JOIN 0_hrm_employees em ON (ca.emp_no = em.id)
                            LEFT OUTER JOIN 0_hrm_divisions d ON (prj.division_id = d.division_id)
                            WHERE stl.approval = 52 AND stld.status_id < 2 AND ca.ca_type_id NOT IN (2,9) AND stl.active=1  AND YEAR(stl.tran_date) >= 2019
                            GROUP BY stl.trans_no, stl.reference, ca.reference,
                            stl.tran_date,ca.emp_id, em.name, d.name, stl.amount, stl.approval_amount, stl.ca_amount, cat.name,stl.approval,stl.ca_trans_no, m.person_id
                            ORDER BY stl.tran_date DESC";

        }else if ($level == 3) {   // ===================== Display For GM
                $sql = "SELECT stl.trans_no, stl.reference, ca.reference AS ca_ref,  stl.tran_date,
                        ca.emp_id, em.name AS employee_name, d.name AS division_name,
                        stl.amount, stl.approval_amount, stl.ca_amount,
                        cat.name AS ca_type_name,
                        stl.approval AS approval, stl.ca_trans_no,
                        COUNT(stld.ca_stl_id) as count_stld,
                        m.person_id
                        FROM 0_cashadvance_stl stl
                        LEFT OUTER JOIN 0_cashadvance_stl_details stld ON (stl.trans_no = stld.trans_no)
                        LEFT OUTER JOIN 0_cashadvance ca ON (ca.trans_no = stl.ca_trans_no)
                        LEFT OUTER JOIN 0_cashadvance_types cat ON (ca.ca_type_id = cat.ca_type_id)
                        LEFT OUTER JOIN 0_projects prj ON (stld.project_no = prj.project_no)
                        LEFT OUTER JOIN 0_members m ON (prj.person_id = m.person_id)
                        LEFT OUTER JOIN 0_hrm_employees em ON (ca.emp_no = em.id)
                        LEFT OUTER JOIN 0_hrm_divisions d ON (prj.division_id = d.division_id)
                        WHERE stld.status_id < 2 AND ca.ca_type_id NOT IN (2,9) AND stl.active=1 AND YEAR(stl.tran_date) >= 2019 AND stl.approval = 3 
                        AND prj.division_id IN 
                                                                                                        (
                                                                                                            SELECT division_id FROM 0_user_divisions
                                                                                                            WHERE user_id=$user_id
                                                                                                        )
			OR stld.status_id < 2 AND YEAR(stl.tran_date) >= 2019 AND stl.approval = 1 AND prj.person_id = $person_id
                        GROUP BY stl.trans_no, stl.reference, ca.reference,
                        stl.tran_date,ca.emp_id, em.name, d.name, stl.amount, stl.approval_amount, stl.ca_amount, cat.name,stl.approval,stl.ca_trans_no, m.person_id
                        ORDER BY stl.tran_date DESC";
            
        }else if ($level == 5)    // ===================== Display For FA
        {   
            
            
            $sql = "SELECT stl.trans_no, stl.reference, ca.reference AS ca_ref,  stl.tran_date,
                    ca.emp_id, em.name AS employee_name, d.name AS division_name,
                    stl.amount, stl.approval_amount, stl.ca_amount,
                    cat.name AS ca_type_name,
                    stl.approval AS approval, stl.ca_trans_no,
                    SUM(stld.approval_amount) as detail_amount,
                    COUNT(stld.ca_stl_id) as count_stld,
                    m.person_id
                    FROM 0_cashadvance_stl stl
                    LEFT OUTER JOIN 0_cashadvance_stl_details stld ON (stl.trans_no = stld.trans_no)
                    LEFT OUTER JOIN 0_cashadvance ca ON (ca.trans_no = stl.ca_trans_no)
                    LEFT OUTER JOIN 0_cashadvance_types cat ON (ca.ca_type_id = cat.ca_type_id)
                    LEFT OUTER JOIN 0_projects prj ON (stld.project_no = prj.project_no)
                    LEFT OUTER JOIN 0_members m ON (prj.person_id = m.person_id)
                    LEFT OUTER JOIN 0_hrm_employees em ON (ca.emp_no = em.id)
                    LEFT OUTER JOIN 0_hrm_divisions d ON (prj.division_id = d.division_id)
                    WHERE stl.approval=5 AND stld.status_id < 2 AND ca.ca_type_id NOT IN (2,9) AND stl.active=1  AND YEAR(stl.tran_date) >= 2019
                    GROUP BY stl.trans_no, stl.reference, ca.reference,
                    stl.tran_date,ca.emp_id, em.name, d.name, stl.amount, stl.approval_amount, stl.ca_amount, cat.name,stl.approval,stl.ca_trans_no, m.person_id
                    ORDER BY stl.tran_date DESC";

        }else if ($level == 42)    // ===================== Display For Dir. Ops
        {  
            $sql = "SELECT stl.trans_no, stl.reference, ca.reference AS ca_ref,  stl.tran_date,
                    ca.emp_id, em.name AS employee_name, d.name AS division_name,
                    stl.amount, stl.approval_amount, stl.ca_amount,
                    cat.name AS ca_type_name,
                    stl.approval AS approval, stl.ca_trans_no,
                    SUM(stld.approval_amount) as detail_amount,
                    COUNT(stld.ca_stl_id) as count_stld,
                    m.person_id
                    FROM 0_cashadvance_stl stl
                    LEFT OUTER JOIN 0_cashadvance_stl_details stld ON (stl.trans_no = stld.trans_no)
                    LEFT OUTER JOIN 0_cashadvance ca ON (ca.trans_no = stl.ca_trans_no)
                    LEFT OUTER JOIN 0_cashadvance_types cat ON (ca.ca_type_id = cat.ca_type_id)
                    LEFT OUTER JOIN 0_projects prj ON (stld.project_no = prj.project_no)
                    LEFT OUTER JOIN 0_members m ON (prj.person_id = m.person_id)
                    LEFT OUTER JOIN 0_hrm_employees em ON (ca.emp_no = em.id)
                    LEFT OUTER JOIN 0_hrm_divisions d ON (prj.division_id = d.division_id)
                    WHERE stl.approval=42 AND stld.status_id < 2 AND ca.ca_type_id NOT IN (2,9) AND stl.active=1  AND YEAR(stl.tran_date) >= 2019 
                    GROUP BY stl.trans_no, stl.reference, ca.reference,
                    stl.tran_date,ca.emp_id, em.name, d.name, stl.amount, stl.approval_amount, stl.ca_amount, cat.name,stl.approval,stl.ca_trans_no, m.person_id
                    ORDER BY stl.tran_date DESC";

        }else if ($level == 43)    // ===================== Display For Dir. FA
        {
            $sql = "SELECT stl.trans_no, stl.reference, ca.reference AS ca_ref,  stl.tran_date,
                    ca.emp_id, em.name AS employee_name, d.name AS division_name,
                    stl.amount, stl.approval_amount, stl.ca_amount,
                    cat.name AS ca_type_name,
                    stl.approval AS approval, stl.ca_trans_no,
                    SUM(stld.approval_amount) as detail_amount,
                    COUNT(stld.ca_stl_id) as count_stld,
                    m.person_id
                    FROM 0_cashadvance_stl stl
                    LEFT OUTER JOIN 0_cashadvance_stl_details stld ON (stl.trans_no = stld.trans_no)
                    LEFT OUTER JOIN 0_cashadvance ca ON (ca.trans_no = stl.ca_trans_no)
                    LEFT OUTER JOIN 0_cashadvance_types cat ON (ca.ca_type_id = cat.ca_type_id)
                    LEFT OUTER JOIN 0_projects prj ON (stld.project_no = prj.project_no)
                    LEFT OUTER JOIN 0_members m ON (prj.person_id = m.person_id)
                    LEFT OUTER JOIN 0_hrm_employees em ON (ca.emp_no = em.id)
                    LEFT OUTER JOIN 0_hrm_divisions d ON (prj.division_id = d.division_id)
                    WHERE stl.approval=43 AND stld.status_id < 2 AND ca.ca_type_id NOT IN (2,9) AND stl.active=1  AND YEAR(stl.tran_date) >= 2019
                    GROUP BY stl.trans_no, stl.reference, ca.reference,
                    stl.tran_date,ca.emp_id, em.name, d.name, stl.amount, stl.approval_amount, stl.ca_amount, cat.name,stl.approval,stl.ca_trans_no, m.person_id
                    ORDER BY stl.tran_date DESC";

        }else if ($level == 41)    // ===================== Display For Dirut
        {
            $sql = "SELECT stl.trans_no, stl.reference, ca.reference AS ca_ref,  stl.tran_date,
                    ca.emp_id, em.name AS employee_name, d.name AS division_name,
                    stl.amount, stl.approval_amount, stl.ca_amount,
                    cat.name AS ca_type_name,
                    stl.approval AS approval, stl.ca_trans_no,
                    COUNT(stld.ca_stl_id) as count_stld,
                    prj.person_id
                    FROM 0_cashadvance_stl stl
                    LEFT OUTER JOIN 0_cashadvance_stl_details stld ON (stl.trans_no = stld.trans_no)
                    LEFT OUTER JOIN 0_cashadvance ca ON (ca.trans_no = stl.ca_trans_no)
                    LEFT OUTER JOIN 0_cashadvance_types cat ON (ca.ca_type_id = cat.ca_type_id)
                    LEFT OUTER JOIN 0_projects prj ON (stld.project_no = prj.project_no)
                    LEFT OUTER JOIN 0_members m ON (prj.person_id = m.person_id)
                    LEFT OUTER JOIN 0_hrm_employees em ON (ca.emp_no = em.id)
                    LEFT OUTER JOIN 0_hrm_divisions d ON (prj.division_id = d.division_id)
                    WHERE stl.approval = 41 AND stld.status_id < 2 AND ca.ca_type_id NOT IN (2,9) AND stl.active=1  AND YEAR(stl.tran_date) >= 2019 
                    GROUP BY stl.trans_no, stl.reference, ca.reference,
                    stl.tran_date,ca.emp_id, em.name, d.name, stl.amount, stl.approval_amount, stl.ca_amount, cat.name,stl.approval,stl.ca_trans_no
                    ORDER BY stl.tran_date DESC";

        }else if ($level == 999){ // ===================== Display For Admin

            $sql = "SELECT stl.trans_no, stl.reference, ca.reference AS ca_ref,  stl.tran_date,
                ca.emp_id, em.name AS employee_name, d.name AS division_name,
                stl.amount, stl.approval_amount, stl.ca_amount,
                cat.name AS ca_type_name,
                stl.approval AS approval, stl.ca_trans_no,
                SUM(stld.approval_amount) as detail_amount,
                COUNT(stld.ca_stl_id) as count_stld,
                m.person_id
                FROM 0_cashadvance_stl stl
                LEFT OUTER JOIN 0_cashadvance_stl_details stld ON (stl.trans_no = stld.trans_no)
                LEFT OUTER JOIN 0_cashadvance ca ON (ca.trans_no = stl.ca_trans_no)
                LEFT OUTER JOIN 0_cashadvance_types cat ON (ca.ca_type_id = cat.ca_type_id)
                LEFT OUTER JOIN 0_projects prj ON (stld.project_no = prj.project_no)
                LEFT OUTER JOIN 0_members m ON (prj.person_id = m.person_id)
                LEFT OUTER JOIN 0_hrm_employees em ON (ca.emp_no = em.id)
                LEFT OUTER JOIN 0_hrm_divisions d ON (prj.division_id = d.division_id)
                WHERE stl.approval < 6 AND stld.status_id < 2 AND ca.ca_type_id NOT IN (2,9) AND stl.active=1  AND YEAR(stl.tran_date) >= 2019
                GROUP BY stl.trans_no, stl.reference, ca.reference,
                stl.tran_date,ca.emp_id, em.name, d.name, stl.amount, stl.approval_amount, stl.ca_amount, cat.name,stl.approval,stl.ca_trans_no, m.person_id
                ORDER BY stl.tran_date DESC";
        }else if ($level == 0){
            $sql = "SELECT * FROM 0_cashadvance_stl WHERE trans_no = 999999999";
        }
        else if ($level == 555){
            $sql = "SELECT * FROM 0_cashadvance WHERE trans_no = 999999999";
        }
        else if ($level == 111){
            $sql = "SELECT * FROM 0_cashadvance WHERE trans_no = 999999999";
        }


        $cashadvance_stl_details_stl = DB::select( DB::raw($sql));

        
        foreach ($cashadvance_stl_details_stl as $cashadvance_stl) {
           
            $tmp = [];
            $tmp['transaction_id'] = $cashadvance_stl->trans_no;
            $tmp['document_no'] = $cashadvance_stl->reference;
            $tmp['ca_refrence'] = $cashadvance_stl->ca_ref;
            $tmp['tran_date'] = $cashadvance_stl->tran_date;
            $tmp['emp_id'] = $cashadvance_stl->emp_id;
            $tmp['employee'] = $cashadvance_stl->employee_name;
            $tmp['division'] = $cashadvance_stl->division_name;             
            $tmp['amount'] = $cashadvance_stl->amount;
            $tmp['approval_amount'] = $cashadvance_stl->approval_amount;
            $tmp['detail_count'] = $cashadvance_stl->count_stld;


            $sql = "SELECT 
                        stld.*,
                        CASE WHEN stld.approval = 0 THEN 'Open'
                        WHEN stld.approval = 1 THEN 'Approve'
                        WHEN stld.approval = 2 THEN 'Disapprove' ELSE stld.approval END AS approval_status,
                        p.project_no,
                        p.code as project_code,
                        m.name as project_manager,
                        pct.name as cost_type_name,
                        s.name as site_name,
                        stl.tran_date as stl_date,
                        s.site_no as site_id,
                        s.name as site_name
                FROM 0_cashadvance_stl_details stld  
                LEFT OUTER JOIN 0_cashadvance_stl stl ON (stl.trans_no = stld.trans_no)
                JOIN 0_cashadvance c ON (stl.ca_trans_no =  c.trans_no)
                JOIN 0_cashadvance_details cad ON (c.trans_no = cad.trans_no)
                LEFT JOIN 0_projects p ON (p.project_no = stld.project_no)
                LEFT JOIN 0_project_site s ON (s.site_no = cad.site_id)
                LEFT JOIN 0_members m On (m.person_id = p.person_id)
                LEFT JOIN 0_project_cost_type pct ON (pct.cost_type_id = stld.cost_code)
                WHERE stld.trans_no=$cashadvance_stl->trans_no AND stld.status_id < 2
                GROUP BY ca_stl_id";

            $cashadvance_stl_details = DB::select( DB::raw($sql));

        
            foreach ($cashadvance_stl_details as $cashadvance_stl_detail) {
                    $items = [];
                    $items['list_id'] = $cashadvance_stl_detail->ca_stl_id;
                    $items['stl_no'] = $cashadvance_stl_detail->trans_no;
                    $items['stl_date'] = $cashadvance_stl_detail->stl_date;
                    $items['project_id'] = $cashadvance_stl_detail->project_code;
                    $items['site_id'] = $cashadvance_stl_detail->site_id;                
                    $items['site_name'] = $cashadvance_stl_detail->site_name;
                    $items['project_manager'] = $cashadvance_stl_detail->project_manager;
                    $items['cost_type_name'] = $cashadvance_stl_detail->cost_type_name;
                    $items['remark'] = $cashadvance_stl_detail->remark;
                    $items['amount'] = $cashadvance_stl_detail->amount;
                    $items['approval_amount'] = $cashadvance_stl_detail->approval_amount;
                    $items['status'] = $cashadvance_stl_detail->status_id;
                    $tmp['stld_list'][] = $items;
                
            }

            array_push($response,$tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
        

    }

    /*
     * Get Service Report Today
     */
//==================================================================== FUNCTION DETAIL =============================================================\\
    public function detail(Request $request, $id) {
        $currentUser = JWTAuth::parseToken()->authenticate();

        $sql = "SELECT 
                        stld.*,
                        CASE WHEN stld.approval = 0 THEN 'Open'
                        WHEN stld.approval = 1 THEN 'Approve'
                        WHEN stld.approval = 2 THEN 'Disapprove' ELSE stld.approval END AS approval_status,
                        p.project_no,
                        p.code as project_code,
                        m.name as project_manager,
                        pct.name as cost_type_name,
                        s.name as site_name,
                        stl.tran_date as stl_date,
                        s.site_no as site_id,
                        s.name as site_name
                FROM 0_cashadvance_stl_details stld  
                LEFT OUTER JOIN 0_cashadvance_stl stl ON (stl.trans_no = stld.trans_no)
                JOIN 0_cashadvance c ON (stl.ca_trans_no =  c.trans_no)
                JOIN 0_cashadvance_details cad ON (c.trans_no = cad.trans_no)
                LEFT JOIN 0_projects p ON (p.project_no = stld.project_no)
                LEFT JOIN 0_project_site s ON (s.site_no = cad.site_id)
                LEFT JOIN 0_members m On (m.person_id = p.person_id)
                LEFT JOIN 0_project_cost_type pct ON (pct.cost_type_id = stld.cost_code)
                WHERE stld.ca_stl_id = $id";

        $cashadvance_stl_details = DB::select( DB::raw($sql));

        
        foreach ($cashadvance_stl_details as $cashadvance_stl_detail) {
           
                $items = [];
                $items['list_id'] = $cashadvance_stl_detail->ca_stl_id;
                $items['stl_no'] = $cashadvance_stl_detail->trans_no;
                $items['stl_date'] = $cashadvance_stl_detail->stl_date;
                $items['project_id'] = $cashadvance_stl_detail->project_code;
                $items['site_id'] = $cashadvance_stl_detail->site_id;                
                $items['site_name'] = $cashadvance_stl_detail->site_name;
                $items['project_manager'] = $cashadvance_stl_detail->project_manager;
                $items['cost_type_name'] = $cashadvance_stl_detail->cost_type_name;
                $items['remark'] = $cashadvance_stl_detail->remark;
                $items['amount'] = $cashadvance_stl_detail->amount;
                $items['approval_amount'] = $cashadvance_stl_detail->approval_amount;
                $items['status'] = $cashadvance_stl_detail->status_id;
            
           
                
                $tmp['stld_list'][] = $items;
            
            array_push($tmp);
            
        }

        return response()->json([
            'success' => true,
            'data' => $tmp
        ]);

    }
//==================================================================== FUNCTION UPDATE DETAIL =============================================================\\
    public function update_detail(Request $request, $id, $id2) {
        $currentUser = JWTAuth::parseToken()->authenticate();
        $response=[];

        $sql = "SELECT 
                        stld.*,
                        CASE WHEN stld.approval = 0 THEN 'Open'
                        WHEN stld.approval = 1 THEN 'Approve'
                        WHEN stld.approval = 2 THEN 'Disapprove' ELSE stld.approval END AS approval_status,
                        p.project_no,
                        p.code as project_code,
                        m.name as project_manager,
                        pct.name as cost_type_name,
                        s.name as site_name,
                        stl.tran_date as stl_date,
                        s.site_no as site_id,
                        s.name as site_name
                FROM 0_cashadvance_stl_details stld  
                LEFT OUTER JOIN 0_cashadvance_stl stl ON (stl.trans_no = stld.trans_no)
                JOIN 0_cashadvance c ON (stl.ca_trans_no =  c.trans_no)
                JOIN 0_cashadvance_details cad ON (c.trans_no = cad.trans_no)
                LEFT JOIN 0_projects p ON (p.project_no = stld.project_no)
                LEFT JOIN 0_project_site s ON (s.site_no = cad.site_id)
                LEFT JOIN 0_members m On (m.person_id = p.person_id)
                LEFT JOIN 0_project_cost_type pct ON (pct.cost_type_id = stld.cost_code)
                WHERE stl.trans_no=$id AND stld.ca_stl_id = $id2";

        $cashadvance_stl_details = DB::select( DB::raw($sql));

        
        foreach ($cashadvance_stl_details as $cashadvance_stl_detail) {
           
                $items = [];
                $items['list_id'] = $cashadvance_stl_detail->ca_stl_id;
                $items['stl_no'] = $cashadvance_stl_detail->trans_no;
                $items['stl_date'] = $cashadvance_stl_detail->stl_date;
                $items['project_id'] = $cashadvance_stl_detail->project_code;
                $items['site_id'] = $cashadvance_stl_detail->site_id;                
                $items['site_name'] = $cashadvance_stl_detail->site_name;
                $items['project_manager'] = $cashadvance_stl_detail->project_manager;
                $items['cost_type_name'] = $cashadvance_stl_detail->cost_type_name; 
                $items['remark'] = $cashadvance_stl_detail->remark;
                $items['amount'] = $cashadvance_stl_detail->amount;
                $items['approval_amount'] = $request->approval_amount;
                $items['status'] = $request->status_id;

                DB::table('0_cashadvance_stl_details')->where('ca_stl_id',$id2)
                ->update(array('status_id' => $request->status_id,                //========================================//
                               'approval_amount' => $request->approval_amount,    //     This Request needed for update     //
                               'approval_date' => Carbon::now(),
                               'act_amount' => $request->approval_amount,         //                                        //
                               'remark' => $request->remark));                    //========================================//
            
            array_push($response,$items);
            
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ]);

    }

//==================================================================== FUNCTION UPDATE CA STL =============================================================\\
    public function update_ca_stl($id){

        $currentUser = JWTAuth::parseToken()->authenticate();
        $session=Auth::guard()->user()->name;
        $date=Carbon::now();
        $approval_desc= "$date - $session";
        $response = [];

        $sql = "SELECT 
                    stl.trans_no,
                    stl.reference,
                    stl.ca_type_id as ca_type_id,
                    stl.tran_date,
                    ct.name as ca_type_name,
                    e.name as employee_name,
                    e.emp_id as emp_id,
                    e.level_id as emp_level,
                    d.name as division_name,
                    stl.amount,
                    stl.approval as approval_position,
                    MIN(stld.status_id) as status_id,
                    SUM(stld.approval_amount) as approval_amount,
                    COUNT(stld.ca_stl_id) as count_cad,
                    e.level_id as level_id,
                    prj.division_id as division,
                    ca.project_no as project_no,
                    d.division_group_id as division_group
                FROM 0_cashadvance_stl stl
                JOIN 0_cashadvance ca ON (stl.ca_trans_no = ca.trans_no)
                INNER JOIN 0_cashadvance_stl_details stld ON (stl.trans_no = stld.trans_no)
                LEFT JOIN 0_projects prj ON (ca.project_no = prj.project_no)
                LEFT JOIN 0_hrm_employees e ON (e.id = ca.emp_no)
                LEFT JOIN 0_hrm_divisions d ON (d.division_id = prj.division_id)
                LEFT JOIN 0_cashadvance_types ct ON (ct.ca_type_id = stl.ca_type_id)
                WHERE stl.trans_no=$id
                GROUP BY stl.trans_no, stl.reference, stl.tran_date, ct.name, e.name, e.emp_id, d.name, stl.amount, stl.approval_amount";

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
            $tmp['amount'] = $cashadvance->amount;    
            $tmp['approval_amount'] = $cashadvance->approval_amount;
            $tmp['approval_position'] = $cashadvance->approval_position;
            $tmp['detail_count'] = $cashadvance->count_cad;  

                $ca_type = $cashadvance->ca_type_id;
                $level = $tmp['emp_level'];
                $amount_approval = $tmp['approval_amount'];
                $approval_position = $tmp['approval_position'];
                if($ca_type == 6){
                    $routing = DB::table('0_ca_route_vehicle')
                    ->where('min_amount','<=',$amount_approval)
                    ->where('max_amount','>=',$amount_approval)
                    ->get();
                }else if ($ca_type != 6){
                    $routing = DB::table('0_cashadvance_routing_approval')
                    ->where('emp_level_id',$level)
                    ->where('min_amount','<=',$amount_approval)
                    ->where('max_amount','>=',$amount_approval)
                    ->get();
                }
            
                foreach ($routing as $key) {
                    $id_routing = $key->id;
                    
                    if($ca_type != 6){
                        $sql = DB::table('0_cashadvance_routing_approval')
                        ->where('id',$id_routing)
                        ->first();
                    }else if($ca_type == 6){
                        $sql = DB::table('0_cashadvance_routing_approval')
                        ->where('id',$id_routing)
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
                        DB::table('0_cashadvance_stl_details')->where('trans_no',$id)
                        ->update(array('approval' => $next_approval,
                        'approval_date' => $date));

                        DB::table('0_cashadvance_stl')->where('trans_no',$id)
                            ->update(array('approval' => 6,
                                        'approval_amount' => $amount_approval,
                                        'approval_description' => $approval_desc));

                    }else if($amount_approval == 0)
                    {
                        DB::table('0_cashadvance_stl_details')->where('trans_no',$id)
                        ->update(array('approval' => 7,
                        'approval_date' => $date));

                        DB::table('0_cashadvance_stl')->where('trans_no',$id)
                            ->update(array('approval' => 7,
                                        'approval_amount' => $amount_approval,
                                        'approval_description' => $approval_desc));
                    }


                    DB::table('0_cashadvance_log1')->insert(array('trans_no' => $tmp['transaction_id'],
                        'type' => 4,
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

        $sql = "SELECT * FROM 0_cashadvance_stl WHERE trans_no=$id";

        $cashadvances_stl = DB::select( DB::raw($sql));

        
        foreach ($cashadvances_stl as $cashadvance) {
           
            $tmp['type'] = $cashadvance ->ca_type_id;

            DB::table('0_cashadvance_stl_details')->where('trans_no',$id)
            ->update(array('approval' => 7,
                           'approval_amount' => 0,
                           'act_amount' => 0,
                           'status_id' => 2));
    
            DB::table('0_cashadvance_stl')->where('trans_no',$id)
            ->update(array('approval' => 7,
                           'approval_amount' => 0,
                           'approval_description' => $approval_desc));

            DB::table('0_cashadvance_log1')->insert(array('trans_no' => $id,
                        'type' => 4,
                        'approval_id' => $cashadvance->approval,
                        'approved_amount' => 0,
                        'updated' => $date,
                        'person_id' => Auth::guard()->user()->id));

        }

        return response()->json([
            'success' => true
        ]);
    
    }

//==================================================================== FUNCTION APPROVE ALL =============================================================\\
    public function approve_all($id){

        $currentUser = JWTAuth::parseToken()->authenticate();
        $session=Auth::guard()->user()->name;
        $date=Carbon::now();
        $approval_desc= "$date - $session";
        $response = [];

        $sql = "SELECT 
                    stl.trans_no,
                    stl.reference,
                    stl.ca_type_id as ca_type_id,
                    stl.tran_date,
                    ct.name as ca_type_name,
                    e.name as employee_name,
                    e.emp_id as emp_id,
                    e.level_id as emp_level,
                    d.name as division_name,
                    stl.amount as ca_amount,
                    stld.amount as stld_amount,
                    stl.approval as approval_position,
                    MIN(stld.status_id) as status_id,
                    SUM(stld.approval_amount) as approval_amount,
                    COUNT(stld.ca_stl_id) as count_cad,
                    e.level_id as level_id,
                    prj.division_id as division,
                    ca.project_no as project_no,
                    d.division_group_id as division_group
                FROM 0_cashadvance_stl stl
                JOIN 0_cashadvance ca ON (stl.ca_trans_no = ca.trans_no)
                JOIN 0_projects prj ON (ca.project_no = prj.project_no)
                INNER JOIN 0_cashadvance_stl_details stld ON (stl.trans_no = stld.trans_no)
                LEFT JOIN 0_hrm_employees e ON (e.id = ca.emp_no)
                LEFT JOIN 0_hrm_divisions d ON (d.division_id = prj.division_id)
                LEFT JOIN 0_cashadvance_types ct ON (ct.ca_type_id = stl.ca_type_id)
                WHERE stl.trans_no=$id AND stld.status_id < 2
                GROUP BY stl.trans_no, stl.reference, stl.tran_date, ct.name, e.name, e.emp_id, d.name, stl.amount, stl.approval_amount";

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
            $tmp['approval_position'] = $cashadvance->approval_position;
            $tmp['detail_count'] = $cashadvance->count_cad;  

            $sql1 = "SELECT ca_stl_id, trans_no, amount FROM 0_cashadvance_stl_details WHERE trans_no = $cashadvance->trans_no AND status_id < 2";
            $stl_detail = DB::select( DB::raw($sql1));
            $ca_type = $cashadvance->ca_type_id;
            foreach ($stl_detail as $item){

                $tmp['stl_id'] = $item->ca_stl_id;
                $tmp['trans_no'] = $item->trans_no;
                $tmp['cad_amount'] = $item->amount;
            

                $stl_amount = $tmp['amount'];
                $level = $tmp['emp_level'];
                $amount_approval = $tmp['approval_amount'];
                $approval_position = $tmp['approval_position'];
               
                if($ca_type == 6){
                    $routing = DB::table('0_ca_route_vehicle')
                    ->where('min_amount','<=',$amount_approval)
                    ->where('max_amount','>=',$amount_approval)
                    ->get();
                }else if ($ca_type != 6){
                    $routing = DB::table('0_cashadvance_routing_approval')
                    ->where('emp_level_id',$level)
                    ->where('min_amount','<=',$amount_approval)
                    ->where('max_amount','>=',$amount_approval)
                    ->get();
                }
                
                foreach ($routing as $key) {
                    $id_routing = $key->id;

                    $amount_approve_all = DB::table('0_cashadvance_stl_details')
                                    ->where('trans_no',$cashadvance->trans_no)
                                    ->where('status_id','<',2)
                                    ->sum('amount');

                                    if($ca_type != 6){
                                        $sql = DB::table('0_cashadvance_routing_approval')
                                        ->where('id',$id_routing)
                                        ->first();
                                    }else if($ca_type == 6){
                                        $sql = DB::table('0_ca_route_vehicle')
                                        ->where('id',$id_routing)
                                        ->first();
                                    }
                    
                    $data = explode(',',$sql->next_approval);
                    $flipped_array = array_flip($data);
                    $approval_now = $flipped_array[$approval_position];
                    $next = $approval_now + 1;
                    $next_approval = $data[$next];

                    $tmp['next_approval'] = $next_approval;

                    $approval_update = $tmp['next_approval'];
                    
                    DB::table('0_cashadvance_stl_details')
                            ->where('ca_stl_id',$tmp['stl_id'])
                            ->update(array('approval' => $approval_update, 'approval_amount' => $tmp['cad_amount'], 'act_amount' => $tmp['cad_amount'], 'status_id' => 1, 'approval_date' => $date));
                            
                    DB::table('0_cashadvance_stl')->where('trans_no',$tmp['transaction_id'])
                            ->update(array('approval' => $approval_update,
                                            'approval_amount' => $amount_approve_all,
                                            'approval_description' => $approval_desc));
                }
            }
            
            DB::table('0_cashadvance_log1')->insert(array('trans_no' => $tmp['transaction_id'],
            'type' => 4,
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

//==================================================================== CA STL approval history =============================================================\\

    public function get_stl_history(Request $request, $trans_no){
        $currentUser = JWTAuth::parseToken()->authenticate();
            $response =[];
            $sql ="SELECT stl_log.trans_no, stl.reference, stl_log.approval_id,
                    CASE
                        WHEN (stl_log.approval_id=0) THEN 'ADMIN'
                        WHEN (stl_log.approval_id=1) THEN 'PM'
                        WHEN (stl_log.approval_id=2) THEN 'DGM'
                        WHEN (stl_log.approval_id=3) THEN 'GM'
                        WHEN (stl_log.approval_id=31) THEN 'GM(TI/MS)'
                        WHEN (stl_log.approval_id=41) THEN 'DIRECTOR'
                        WHEN (stl_log.approval_id) = 32 THEN 'Dir.Ops'
                        WHEN (stl_log.approval_id) = 42 THEN 'Dir.Ops'
                        WHEN (stl_log.approval_id) = 43 THEN 'Dir.FA'
                        WHEN (stl_log.approval_id=4) THEN 'PC'
                        WHEN (stl_log.approval_id=5) THEN 'FA'
                        WHEN (stl_log.approval_id=6) THEN 'CASHIER'
                        WHEN (stl_log.approval_id=7) THEN 'CLOSE'
                    END AS routing_approval, 
                    CASE
                        WHEN (stl_log.type=2) THEN u.real_name
                        WHEN (stl_log.type=4) THEN us.name
                    END AS person_name, stl_log.updated, c.memo_
                    FROM 0_cashadvance_log1 stl_log
                    INNER JOIN 0_cashadvance_stl stl ON (stl_log.trans_no = stl.trans_no)
                    LEFT JOIN 0_cashadvance_comments c ON (stl_log.trans_no = c.trans_no AND stl_log.approval_id = c.approval_id AND c.type=1)
                    LEFT JOIN 0_users u ON (stl_log.person_id = u.id)
                    LEFT JOIN users us ON (stl_log.person_id = us.id)
                    WHERE stl_log.type IN (2,4) AND stl_log.trans_no=$trans_no GROUP BY stl_log.approval_id";

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

    public function remark_diss(Request $request,$castl_id){
        $currentUser = JWTAuth::parseToken()->authenticate();

        DB::table('0_cashadvance_stl_details')->where('ca_stl_id',$castl_id)
                            ->update(array('remark_disapprove' => $request->remark));


        return response()->json([
            'success' => true
        ]);
    }

//==================================================================== FUNCTION Remark Disapprove All =============================================================\\

    public function remark_diss_all(Request $request, $trans_no){
        $currentUser = JWTAuth::parseToken()->authenticate();

        DB::table('0_cashadvance_stl_details')->where('trans_no',$trans_no)
                                ->update(array('remark_disapprove' => $request->remark));

            return response()->json([
                'success' => true
            ]);
    }

}
