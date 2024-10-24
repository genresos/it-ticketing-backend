<?php

namespace App\Api\V1\Controllers;
use JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Carbon\Carbon;
use Auth;

class AssetsapprovalController extends Controller
{
    //
    use Helpers;
//==================================================================== ASSETS ISSUE NEED APPROVAL =============================================================\\
    public function needApproval() {

        $currentUser = JWTAuth::parseToken()->authenticate();
        $session=Auth::guard()->user()->person_id;
        $date = Carbon::now();
	$level_id = Auth::guard()->user()->approval_level;
        $response = [];
        if ($level_id < 999) {
            $sql = "SELECT
                                d.name AS division_name,
                                e.emp_id,
                                i.trx_date,
                                i.doc_no AS doc_no,
                                e.id,
                                i.approval_status,
                                -- CASE WHEN i.approval_status = 0 THEN 'Open' END AS approval_status,
                                e.name AS assignee,
                                CASE WHEN (i.project_code='') THEN so.project_code ELSE i.project_code END AS project_code,
                                COUNT(i.issue_id) as count_issue
                            FROM 0_am_issues i
                            LEFT OUTER JOIN 0_am_assets a ON (a.asset_id = i.object_id)
                            LEFT OUTER JOIN 0_hrm_employees e ON (i.issue_assignee = e.emp_id)
                            LEFT OUTER JOIN 0_users u ON (i.creator = u.id)
                            LEFT OUTER JOIN 0_sales_orders so ON (i.order_ref = so.order_no)
                            LEFT OUTER JOIN 0_projects pj ON (i.project_code = pj.code)
                            LEFT OUTER JOIN 0_hrm_divisions d ON (pj.division_id = d.division_id)
                            WHERE i.issue_approval=1 AND i.issue_status=7 AND a.type_id IN (1,3) AND i.inactive=0  AND YEAR(i.trx_date) > 2020 AND pj.person_id= $session
                            GROUP BY i.doc_no";
        }else if ($level_id == 999){
            $sql = "SELECT
                                d.name AS division_name,
                                e.emp_id,
                                i.trx_date,
                                i.doc_no AS doc_no,
                                e.id,
				i.approval_status,
				-- CASE WHEN i.approval_status = 0 THEN 'Open' END AS approval_status,
                                e.name AS assignee,
                                CASE WHEN (i.project_code='') THEN so.project_code ELSE i.project_code END AS project_code,
                                COUNT(i.issue_id) as count_issue
                            FROM 0_am_issues i
                            LEFT OUTER JOIN 0_am_assets a ON (a.asset_id = i.object_id)
                            LEFT OUTER JOIN 0_hrm_employees e ON (i.issue_assignee = e.emp_id)
                            LEFT OUTER JOIN 0_users u ON (i.creator = u.id)
                            LEFT OUTER JOIN 0_sales_orders so ON (i.order_ref = so.order_no)
                            LEFT OUTER JOIN 0_projects pj ON (i.project_code = pj.code)
                            LEFT OUTER JOIN 0_hrm_divisions d ON (pj.division_id = d.division_id)
                            WHERE i.issue_approval=1 AND i.issue_status=7 AND a.type_id IN (1,3) AND i.inactive=0  AND YEAR(i.trx_date) > 2020
                            GROUP BY i.doc_no ORDER BY i.issue_id DESC";
        }
        $issues = DB::select( DB::raw($sql));

        foreach ($issues as $issue) {
           
            $tmp = [];
            $tmp['doc_no'] = $issue->doc_no;
            $tmp['trx_date'] = $issue->trx_date;
            $tmp['assignee_id'] = $issue->emp_id; 
            $tmp['assignee_to'] = $issue->assignee;  
            $tmp['division'] = $issue->division_name; 
            $tmp['project_code'] = $issue->project_code;
            $tmp['total_issue'] = $issue->count_issue;
            $tmp['status'] = $issue->approval_status;
            
            $doc_no = $tmp['doc_no'];
            $sql1 = "SELECT i.issue_id, i.doc_no, i.trx_date, a.asset_name,  
                            g.name as group_name, a.asset_model_number, e.emp_id, e.name AS assignee, d.name AS division_name, 
                            i.project_code AS project_code, i.approval_status,
                            u.real_name, i.creation_date, i.object_id, a.asset_name
                            FROM 0_am_issues i
                            LEFT OUTER JOIN 0_am_issue_type it ON (i.issue_type = it.issue_type_id)
                            LEFT OUTER JOIN 0_am_status s ON (s.asset_status_id = i.issue_status)
                            LEFT OUTER JOIN 0_am_assets a ON (a.asset_id = i.object_id)
                            LEFT OUTER JOIN 0_am_groups g ON (a.group_id = g.group_id)
                            LEFT OUTER JOIN 0_hrm_employees e ON (i.issue_assignee = e.emp_id)
                            LEFT OUTER JOIN 0_users u ON (i.creator = u.id)
                            LEFT OUTER JOIN 0_projects pj ON (i.project_code = pj.code)
                            LEFT OUTER JOIN 0_hrm_divisions d ON (pj.division_id = d.division_id)		
                            WHERE i.doc_no = '$doc_no' AND i.issue_approval=1 AND i.issue_status=7 AND a.type_id IN (1,3) AND i.inactive=0  AND YEAR(i.trx_date) > 2020 GROUP BY i.issue_id ORDER BY i.issue_id DESC";
            $issue_list = DB::select( DB::raw($sql1));

            foreach ($issue_list as $key){
                $items = [];
                $items['status'] = $key->approval_status;
                $items['issue_id'] = $key->issue_id;
                $items['asset_id'] = $key->object_id;
                $items['assets_name'] = $key->asset_name." ".$key->group_name." ". $key->asset_model_number;
                $items['project_code'] = $key->project_code;

                $tmp['asset_list'][] = $items;
            }

            array_push($response, $tmp);
            
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ]);

    }


//==================================================================== ASSETS ISSUE UPDATE DETAIL =============================================================\\
    public function update_detail(Request $request, $doc_no, $issue_id){

        $currentUser = JWTAuth::parseToken()->authenticate();
        $session=Auth::guard()->user()->id;
        $approved_by=Auth::guard()->user()->person_id;
        $date = Carbon::now();

        $response = [];

        $sql = "SELECT i.issue_id, i.doc_no, i.trx_date, a.asset_name,  
                        g.name as group_name, a.asset_model_number, e.emp_id, e.name AS assignee, d.name AS division_name, 
                        i.project_code AS project_code, i.approval_status as approval, i.issue_approval as issue_approval,
                        u.real_name, i.creation_date, i.object_id, a.asset_name
                        FROM 0_am_issues i
                        LEFT OUTER JOIN 0_am_issue_type it ON (i.issue_type = it.issue_type_id)
                        LEFT OUTER JOIN 0_am_status s ON (s.asset_status_id = i.issue_status)
                        LEFT OUTER JOIN 0_am_assets a ON (a.asset_id = i.object_id)
                        LEFT OUTER JOIN 0_am_groups g ON (a.group_id = g.group_id)
                        LEFT OUTER JOIN 0_hrm_employees e ON (i.issue_assignee = e.emp_id)
                        LEFT OUTER JOIN 0_users u ON (i.creator = u.id)
                        LEFT OUTER JOIN 0_projects pj ON (i.project_code = pj.code)
                        LEFT OUTER JOIN 0_hrm_divisions d ON (pj.division_id = d.division_id)		
                        WHERE i.doc_no = '$doc_no' AND i.issue_id = $issue_id ORDER BY i.issue_id DESC";
        
        $issues = DB::select( DB::raw($sql));

        foreach ($issues as $issue) {
           
            $tmp = [];
            $tmp['issue_id'] = $issue->issue_id;
            $tmp['asset_id'] = $issue->object_id;
            $tmp['assets_name'] = $issue->asset_name." ".$issue->group_name." ". $issue->asset_model_number;
            $tmp['project_code'] = $issue->project_code;
           

            $issue_id = $tmp['issue_id'];
            $approval = $issue->issue_approval;
            $issue_approval = $approval + 1;

            DB::table('0_am_issues')->where('issue_id',$issue_id)
            ->update(array('approval_status' => $request->approval_status,
                           'approval_date' => $date,
                           'approval_by' => $approved_by,
                           'issue_approval' => $issue_approval)); 

            $tmp['approval_status'] = $request->approval_status;

            array_push($response, $tmp);
            
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

//==================================================================== ASSETS ISSUE UPDATE =============================================================\\
    public function update_issue(Request $request, $doc_no){

        $currentUser = JWTAuth::parseToken()->authenticate();
        $session=Auth::guard()->user()->id;
        $approved_by=Auth::guard()->user()->person_id;

        $response = [];

        $sql = "SELECT i.issue_id, i.doc_no, i.trx_date, a.asset_name,  
                        g.name as group_name, a.asset_model_number, e.emp_id, e.name AS assignee, d.name AS division_name, 
                        i.project_code AS project_code, i.approval_status as approval,
                        u.real_name, i.creation_date, i.object_id, a.asset_name
                        FROM 0_am_issues i
                        LEFT OUTER JOIN 0_am_issue_type it ON (i.issue_type = it.issue_type_id)
                        LEFT OUTER JOIN 0_am_status s ON (s.asset_status_id = i.issue_status)
                        LEFT OUTER JOIN 0_am_assets a ON (a.asset_id = i.object_id)
                        LEFT OUTER JOIN 0_am_groups g ON (a.group_id = g.group_id)
                        LEFT OUTER JOIN 0_hrm_employees e ON (i.issue_assignee = e.emp_id)
                        LEFT OUTER JOIN 0_users u ON (i.creator = u.id)
                        LEFT OUTER JOIN 0_projects pj ON (i.project_code = pj.code)
                        LEFT OUTER JOIN 0_hrm_divisions d ON (pj.division_id = d.division_id)		
                        WHERE i.doc_no = '$doc_no' AND i.approval_status = 2 ORDER BY i.issue_id DESC";
        
        $issues = DB::select( DB::raw($sql));

        foreach ($issues as $issue) {
           
            $tmp = [];
            $tmp['issue_id'] = $issue->issue_id;
            $tmp['asset_id'] = $issue->object_id;
            $tmp['assets_name'] = $issue->asset_name." ".$issue->group_name." ". $issue->asset_model_number;
            $tmp['project_code'] = $issue->project_code;

            $issue_id = $tmp['issue_id'];

            DB::table('0_am_issues')->where('issue_id',$issue_id)
            ->update(array('issue_status' => 13)); 

            array_push($response, $tmp);
            
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }


//==================================================================== DISAPPROVE ALL =============================================================\\
    public function disapprove_all(Request $request, $doc_no){

        $currentUser = JWTAuth::parseToken()->authenticate();
        $session=Auth::guard()->user()->id;
        $approved_by=Auth::guard()->user()->person_id;

        $response = [];

        $sql = "SELECT i.issue_id, i.doc_no, i.trx_date, a.asset_name,  
                        g.name as group_name, a.asset_model_number, e.emp_id, e.name AS assignee, d.name AS division_name, 
                        i.project_code AS project_code, i.approval_status as approval, i.approval_status as approval_now,
                        u.real_name, i.creation_date, i.object_id, a.asset_name
                        FROM 0_am_issues i
                        LEFT OUTER JOIN 0_am_issue_type it ON (i.issue_type = it.issue_type_id)
                        LEFT OUTER JOIN 0_am_status s ON (s.asset_status_id = i.issue_status)
                        LEFT OUTER JOIN 0_am_assets a ON (a.asset_id = i.object_id)
                        LEFT OUTER JOIN 0_am_groups g ON (a.group_id = g.group_id)
                        LEFT OUTER JOIN 0_hrm_employees e ON (i.issue_assignee = e.emp_id)
                        LEFT OUTER JOIN 0_users u ON (i.creator = u.id)
                        LEFT OUTER JOIN 0_projects pj ON (i.project_code = pj.code)
                        LEFT OUTER JOIN 0_hrm_divisions d ON (pj.division_id = d.division_id)		
                        WHERE i.doc_no = '$doc_no' ORDER BY i.issue_id DESC";
        
        $issues = DB::select( DB::raw($sql));

        foreach ($issues as $issue) {
        
            $tmp = [];
            $tmp['issue_id'] = $issue->issue_id;
            $tmp['asset_id'] = $issue->object_id;
            $tmp['assets_name'] = $issue->asset_name." ".$issue->group_name." ". $issue->asset_model_number;
            $tmp['project_code'] = $issue->project_code;

            $issue_id = $tmp['issue_id'];
            $approval = $issue->approval_now;
            $issue_approval = $approval + 1;

            DB::table('0_am_issues')->where('issue_id',$issue_id)
            ->update(array('approval_status' => 2,
                           'approval_date' => Carbon::now(),
                           'approval_by' => $approved_by,
                           'issue_approval' => $issue_approval,
                           'issue_status' => 13)); 

            array_push($response, $tmp);
            
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ]);

    }

//==================================================================== APPROVE ALL =============================================================\\
    public function approve_all(Request $request, $doc_no){

        $currentUser = JWTAuth::parseToken()->authenticate();
        $session=Auth::guard()->user()->id;
        $approved_by=Auth::guard()->user()->person_id;

        $response = [];

        $sql = "SELECT i.issue_id, i.doc_no, i.trx_date, a.asset_name,  
                        g.name as group_name, a.asset_model_number, e.emp_id, e.name AS assignee, d.name AS division_name, 
                        i.project_code AS project_code, i.approval_status as approval_now,
                        u.real_name, i.creation_date, i.object_id, a.asset_name
                        FROM 0_am_issues i
                        LEFT OUTER JOIN 0_am_issue_type it ON (i.issue_type = it.issue_type_id)
                        LEFT OUTER JOIN 0_am_status s ON (s.asset_status_id = i.issue_status)
                        LEFT OUTER JOIN 0_am_assets a ON (a.asset_id = i.object_id)
                        LEFT OUTER JOIN 0_am_groups g ON (a.group_id = g.group_id)
                        LEFT OUTER JOIN 0_hrm_employees e ON (i.issue_assignee = e.emp_id)
                        LEFT OUTER JOIN 0_users u ON (i.creator = u.id)
                        LEFT OUTER JOIN 0_projects pj ON (i.project_code = pj.code)
                        LEFT OUTER JOIN 0_hrm_divisions d ON (pj.division_id = d.division_id)		
                        WHERE i.doc_no = '$doc_no' ORDER BY i.issue_id DESC";
        
        $issues = DB::select( DB::raw($sql));

        foreach ($issues as $issue) {
        
            $tmp = [];
            $tmp['issue_id'] = $issue->issue_id;
            $tmp['asset_id'] = $issue->object_id;
            $tmp['assets_name'] = $issue->asset_name." ".$issue->group_name." ". $issue->asset_model_number;
            $tmp['project_code'] = $issue->project_code;

            $issue_id = $tmp['issue_id'];
            $approval = $issue->approval_now;
            $issue_approval = $approval + 1;

           $data =  DB::table('0_am_issues')->where('issue_id',$issue_id)
            ->update(array('approval_status' => 1,
                        'approval_date' => Carbon::now(),
                        'approval_by' => $approved_by,
                        'issue_approval' => $issue_approval)); 

            array_push($response, $tmp);
            
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ]);

    }
}
