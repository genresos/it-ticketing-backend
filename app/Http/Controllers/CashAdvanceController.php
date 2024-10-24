<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Carbon\Carbon;
use Auth;
use App\CashAdvance;
use App\Query\QueryCashAdvance;
use App\Query\QueryProjectBudget;
use App\Jobs\CashAdvanceNotification;

class CashAdvanceController extends Controller
{
    //
    use Helpers;

    public static function ca_need_approval($user_id, $level, $person_id, $division_id)
    {
        $response = [];

        $sql = QueryCashAdvance::ca_need_approval($user_id, $level, $person_id, $division_id);
        $cashadvances = DB::select(DB::raw($sql));

        foreach ($cashadvances as $cashadvance) {

            $tmp = [];
            $tmp['transaction_id'] = $cashadvance->trans_no;
            $tmp['document_no'] = $cashadvance->reference;
            $tmp['ca_type'] = $cashadvance->ca_type_name;
            $tmp['tran_date'] = $cashadvance->tran_date;
            $tmp['requestor'] = $cashadvance->employee_name;
            $tmp['employe_id'] = $cashadvance->emp_id;
            $tmp['division'] = $cashadvance->division_name;
            $tmp['amount'] = $cashadvance->amount;
            $tmp['approval_amount'] = $cashadvance->approval_amount;
            $tmp['detail_count'] = $cashadvance->count_cad;
            $tmp['cashadvance_ref'] = $cashadvance->cashadvance_ref;

            $sql = QueryCashAdvance::ca_need_approval_detail($cashadvance->trans_no);
            $cashadvances_details = DB::select(DB::raw($sql));

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
                $items['spk_no'] = $cashadvance_detail->spk_no;

                $tmp['cad_list'][] = $items;
            }
            array_push($response, $tmp);
        }
        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public static function ca_revision_cost_allocation($user_id, $level, $person_id, $division_id)
    {
        $response = [];

        $sql = QueryCashAdvance::ca_revision_cost_allocation($user_id, $level, $person_id, $division_id);
        $ca_rev_allocation = DB::select(DB::raw($sql));

        foreach ($ca_rev_allocation as $ca_revisi_allocation) {

            $tmp = [];
            $tmp['id'] = $ca_revisi_allocation->rev_id;
            $tmp['ca_ref'] = $ca_revisi_allocation->ca_ref;
            $tmp['ca_tran_date'] = $ca_revisi_allocation->ca_tran_date;
            $tmp['emp_id'] = $ca_revisi_allocation->emp_id;
            $tmp['emp_name'] = $ca_revisi_allocation->emp_name;
            $tmp['ca_amount'] = $ca_revisi_allocation->ca_amount;


            $sql = QueryCashAdvance::ca_revision_cost_allocation_details($ca_revisi_allocation->rev_id);
            $ca_rev_allocation_details = DB::select(DB::raw($sql));

            foreach ($ca_rev_allocation_details as $ca_rev_allocation_detail) {

                $items = [];
                $items['list_id'] = $ca_rev_allocation_detail->id;
                $items['project_name'] = $ca_rev_allocation_detail->project_name;
                $items['project_budget_id'] = $ca_rev_allocation_detail->project_budget_id;
                $items['new_project_budget_id'] = $ca_rev_allocation_detail->new_project_budget_id;
                $items['ca_detail_id'] = $ca_rev_allocation_detail->ca_detail_id;
                $items['site_id'] = $ca_rev_allocation_detail->site_id;
                $items['site_name'] = $ca_rev_allocation_detail->site_name;
                $items['cost_allocation_code'] = $ca_rev_allocation_detail->cost_allocation_code;
                $items['cost_allocation_name'] = $ca_rev_allocation_detail->cost_allocation_name;
                $items['new_cost_allocation_code'] = $ca_rev_allocation_detail->new_cost_allocation_code;
                $items['new_cost_allocation_name'] = $ca_rev_allocation_detail->new_cost_allocation_name;
                $items['remark'] = $ca_rev_allocation_detail->remark;
                $items['amount'] = $ca_rev_allocation_detail->amount;

                $tmp['cad_list'][] = $items;
                //print_r($ca_rev_allocation_detail);
            }
            array_push($response, $tmp);
        }
        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public static function get_history_approval_rev_ca($trans_no)
    {
        $response = [];
        $sql = QueryCashAdvance::get_history_approval_rev_ca($trans_no);

        $ca_history = DB::select(DB::raw($sql));

        foreach ($ca_history as $data) {

            $tmp = [];
            $tmp['approver_level'] = $data->routing_approval;
            $tmp['approver_name'] = $data->person_name;
            $tmp['last_update'] = $data->updated;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }


    public static function update_approve_rev_ca($myArr, $rev_id)
    {
        $date = Carbon::now();

        $session = $myArr['user_name'];
        $user_id = $myArr['user_id'];
        $params = $myArr['params'];

        $status_id = $params['status_id'];
        $approval_desc = "$date - $session";

        $response = [];
        $params_notification = [];
        $budget_for_check = array();

        $sql = " SELECT rev.rev_id,
                        rev.ca_type_id,
                        rev.ca_ref,
                        rev.ca_tran_date,
                        rev.emp_no,
                        rev.emp_id,
                        rev.emp_name,
                        emp.level_id,
                        revd.amount AS ca_amount,
                        rev.ca_amount AS total_amount,
                        rev.status_id,
                        revd.id,
                        revd.ca_trans_detail_id,
                        revd.project_code,
                        revd.project_name,
                        revd.project_budget_id,
                        revd.new_project_budget_id,
                        revd.ca_detail_id,
                        revd.site_id,
                        revd.site_name,
                        revd.cost_allocation_code,
                        revd.cost_allocation_name,
                        revd.new_cost_allocation_code,
                        revd.new_cost_allocation_name,
                        rev.approval,
                        ct.name AS ca_type_name,
                        COUNT(revd.ca_trans_detail_id) AS count_ctrans_id,
                        d.division_id
                    
        FROM 0_cashadvance_rev_cost_alloc rev
        INNER JOIN 0_cashadvance_rev_cost_alloc_details revd ON (rev.rev_id = revd.rev_id)		
        LEFT JOIN 0_projects p ON (revd.project_no = p.project_no)
        LEFT OUTER JOIN 0_members m ON (p.person_id = m.person_id)
        LEFT JOIN 0_hrm_divisions d ON (d.division_id = p.division_id)
        LEFT JOIN 0_hrm_employees emp ON (emp.id = rev.emp_no)
        LEFT JOIN 0_cashadvance_types ct ON (ct.ca_type_id = rev.ca_type_id)
        WHERE rev.rev_id=$rev_id

        GROUP BY rev.rev_id, 
                    rev.ca_type_id,         
                    rev.ca_ref, 
                    rev.ca_tran_date, 
                    rev.emp_id, 
                    rev.emp_name,
                    rev.emp_name,
                    emp.level_id, 
                    rev.ca_amount, 
                    rev.status_id, 
                    revd.id, 
                    revd.ca_trans_detail_id, 
                    revd.project_code, 
                    revd.project_name, 
                    revd.project_budget_id, 
                    revd.new_project_budget_id,
                    revd.ca_detail_id,
                    revd.site_id,
                    revd.site_name,
                    revd.cost_allocation_code,
                    revd.cost_allocation_name,
                    revd.new_cost_allocation_code,
                    revd.new_cost_allocation_name,
                    rev.approval,
                    ct.name,
                    d.division_id";

        $ca_revision = DB::select(DB::raw($sql));

        foreach ($ca_revision as $ca_rev) {
            $tmp = [];
            $tmp['rev_id']              = $ca_rev->rev_id;
            $tmp['ca_trans_detail_id']  = $ca_rev->ca_trans_detail_id;
            $tmp['document_no']       = $ca_rev->ca_ref;
            $tmp['ca_type_name']      = $ca_rev->ca_type_name;
            $tmp['tran_date']         = $ca_rev->ca_tran_date;
            $tmp['employe_id']        = $ca_rev->emp_id;
            $tmp['emp_name']          = $ca_rev->emp_name;
            $tmp['emp_level']         = $ca_rev->level_id;
            $tmp['division_id']       = $ca_rev->division_id;
            $tmp['ca_amount']         = $ca_rev->ca_amount;
            $tmp['total_amount']         = $ca_rev->total_amount;
            $tmp['approval_position'] = $ca_rev->approval;
            $tmp['detail_count']      = $ca_rev->count_ctrans_id;
            $tmp['rev_detail_id']     = $ca_rev->id;

            $ca_type = $ca_rev->ca_type_id;
            $level = $tmp['emp_level'];
            $amount_approval = $tmp['total_amount'];
            $approval_position = $tmp['approval_position'];

            // FIND IF REQUESTOR AS MARKETING
            if ($approval_position != 7) {

                $marketing = DB::table('0_member_marketing')->where('emp_id', $ca_rev->emp_id)->first();
                if ($ca_type == 6 && empty($marketing)) {
                    $routing = DB::table('0_ca_route_vehicle')
                        ->where('min_amount', '<=', $amount_approval)
                        ->where('max_amount', '>=', $amount_approval)
                        ->get();
                } else if ($ca_type != 6 && empty($marketing)) {
                    $routing = DB::table('0_cashadvance_routing_approval')
                        ->where('emp_level_id', $level)
                        ->where('min_amount', '<=', $amount_approval)
                        ->where('max_amount', '>=', $amount_approval)
                        ->get();
                } else if ($ca_type != 6 && !empty($marketing)) {
                    $routing = DB::table('0_member_marketing')->where('id', $marketing->id)
                        ->get();
                }

                foreach ($routing as $key) {
                    $id_routing = $key->id;

                    $marketing_emp = DB::table('0_member_marketing')->where('id', $key->id)->first();
                    if ($ca_type != 6 && empty($marketing)) {
                        $sql = DB::table('0_cashadvance_routing_approval')
                            ->where('id', $id_routing)
                            ->first();
                    } else if ($ca_type == 6 && empty($marketing)) {
                        $sql = DB::table('0_ca_route_vehicle')
                            ->where('id', $id_routing)
                            ->first();
                    } else if ($ca_type != 6 && !empty($marketing)) {
                        $sql = DB::table('0_member_marketing')
                            ->where('id', $marketing_emp->id)
                            ->first();
                    }

                    $data = explode(',', $sql->next_approval);
                    $flipped_array = array_flip($data);
                    $approval_now = $flipped_array[$approval_position];
                    //return $approval_now;


                    /**
                     * NEW ROUTE FOR (TSS WIRELESS) PIC DGM
                     */


                    $registered_division = array(2);

                    if ($approval_position != 6) {

                        if ($approval_position == 4) {

                            if (in_array($tmp['division_id'], $registered_division)) {
                                $next = $approval_now + 1;
                            } else {
                                if ($tmp['emp_level'] == 0) {
                                    if ($amount_approval <= 1000000) {
                                        $next = $approval_now + 1;
                                    } else if ($amount_approval > 1000000) {
                                        $next = $approval_now + 2;
                                    }
                                } else if ($tmp['emp_level'] != 0) {
                                    $next = $approval_now + 2;
                                }
                            }
                        } else {
                            $next = $approval_now + 1;
                        }

                        $next_approval = $data[$next];
                    } else {
                        $next_approval = 7;
                    }

                    $tmp['next_approval'] = $next_approval;
                    $approval_update = $tmp['next_approval'];
                }

                /**
                 * PARAMS UPDATE NOTIFICATION
                 */
                $params_notification['rev_id'] = $ca_rev->rev_id;

                if ($approval_position != 7) {
                    $params_notification['approval'] = $approval_update;
                }

                if ($ca_rev->approval == 1) {
                }
                $ca_rev->approval;

                if ($tmp['approval_position'] == 1) {
                    $budget_check = [];
                    $budget_check['rev_id'] = $rev_id;
                    $budget_check['budget_id'] = $ca_rev->new_project_budget_id;
                    $budget_check['amount'] = $ca_rev->ca_amount;
                    $budget_check['current_approval'] = $tmp['approval_position'];
                    $budget_check['approval_update'] = $approval_update;
                    $budget_check['approval_desc'] = $approval_desc;
                    array_push($budget_for_check, $budget_check);
                } else {
                    DB::table('0_cashadvance_rev_cost_alloc')->where('rev_id', $rev_id)
                        ->update(array(
                            'approval_description' => $approval_desc,
                            'approval' => $approval_update
                        ));

                    DB::table('0_cashadvance_rev_cost_alloc_details')->where('rev_id', $rev_id)
                        ->update(array(
                            'approval' => $approval_update
                        ));

                    DB::table('0_cashadvance_rev_cost_alloc_log')->insert(array(
                        'trans_rev_no' => $rev_id,
                        'approval_id' => $tmp['approval_position'],
                        'updated' => $date,
                        'person_id' => Auth::guard()->user()->id
                    ));

                    DB::table('0_cashadvance_rev_cost_alloc')->where('rev_id', $rev_id)
                        ->update(array(
                            'approval_description' => $approval_desc
                        ));
                }
            }

            array_push($response, $tmp);
        }
        if ($tmp['approval_position'] == 1) {
            foreach ($budget_for_check as $item) {
                $budget_id = $item['budget_id'];
                $amount = $item['amount'];

                if (!isset($totals[$budget_id])) {
                    $totals[$budget_id] = 0;
                }

                $totals[$budget_id] += $amount;
            }
            foreach ($totals as $budget_id => $total_amount) {
                $remain = QueryProjectBudget::check_budget_tmp($budget_id);

                if (($remain - $total_amount) < 0) {
                    return response()->json(
                        [
                            'error' => array(
                                'message' => 'Budget ID : ' . $budget_id . " ini tidak mencukupi, sisa budget = $remain",
                                'status_code' => 403
                            )
                        ],
                        403
                    );
                    break;
                } else {

                    DB::table('0_cashadvance_rev_cost_alloc')->where('rev_id', $rev_id)
                        ->update(array(
                            'approval_description' => $approval_desc,
                            'approval' => $approval_update
                        ));

                    DB::table('0_cashadvance_rev_cost_alloc_details')->where('rev_id', $rev_id)
                        ->update(array(
                            'approval' => $approval_update
                        ));

                    DB::table('0_cashadvance_rev_cost_alloc_log')->insert(array(
                        'trans_rev_no' => $rev_id,
                        'approval_id' => $tmp['approval_position'],
                        'updated' => $date,
                        'person_id' => Auth::guard()->user()->id
                    ));

                    DB::table('0_cashadvance_rev_cost_alloc')->where('rev_id', $rev_id)
                        ->update(array(
                            'approval_description' => $approval_desc
                        ));
                }
            }
        }

        /**
         * SEND NOTIFICATION
         */
        // dispatch(new CashAdvanceNotification($params_notification['rev_id'], $params_notification['approval']));
        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public static function update_approve_rev_ca_details($myArr, $rev_id, $rev_detail_id)
    {
        $date   = Carbon::now();
        $params = $myArr['params'];
        $status_id = $params['status_id'];

        $response = [];
        $sql = "SELECT  
                rev.rev_id,
                rev.ca_ref,
                ct.name AS ca_type_name,
                rev.ca_tran_date,
                rev.emp_name,
                revd.id AS rev_detail_id,
                revd.project_code,
                revd.site_id,
                revd.site_name,
                revd.project_budget_id,
                revd.new_project_budget_id,
                revd.cost_allocation_code,
                revd.new_cost_allocation_code,
                revd.amount,
	            rev.approval,
                revd.status_id,
                rev.ca_trans_no,
                revd.ca_trans_detail_id

            FROM 0_cashadvance_rev_cost_alloc rev
            INNER JOIN 0_cashadvance_rev_cost_alloc_details revd ON (rev.rev_id = revd.rev_id)
            LEFT JOIN 0_cashadvance_types ct ON (ct.ca_type_id = rev.ca_type_id)

            WHERE revd.id=$rev_detail_id
            GROUP BY rev.rev_id,
                rev.ca_ref,
                ct.name,
                rev.ca_tran_date,
                rev.emp_name,
                revd.id,
                revd.project_code,
                revd.site_id,
                revd.site_name,
                revd.project_budget_id,
                revd.new_project_budget_id,
                revd.cost_allocation_code,
                revd.new_cost_allocation_code,
                revd.amount,
	            rev.approval,
                revd.status_id,
                rev.ca_trans_no,
                revd.ca_trans_detail_id";

        $cashadvances = DB::select(DB::raw($sql));

        foreach ($cashadvances as $cashadvance_detail) {

            $items = [];
            $items['trans_id'] = $cashadvance_detail->rev_id;
            $items['ca_ref'] = $cashadvance_detail->ca_ref;
            $items['ca_type_name'] = $cashadvance_detail->ca_type_name;
            $items['ca_tran_date'] = $cashadvance_detail->ca_tran_date;
            $items['emp_name'] = $cashadvance_detail->emp_name;

            $items['trans_detail_id'] = $cashadvance_detail->rev_detail_id;
            $items['project_code'] = $cashadvance_detail->project_code;
            $items['site_id'] = $cashadvance_detail->site_id;
            $items['site_name'] = $cashadvance_detail->site_name;
            $items['budget_id'] = $cashadvance_detail->project_budget_id;
            $items['new_project_budget_id'] = $cashadvance_detail->new_project_budget_id;
            $items['cost_allocation_code'] = $cashadvance_detail->cost_allocation_code;
            $items['new_cost_allocation_code'] = $cashadvance_detail->new_cost_allocation_code;
            $items['ca_amount'] = $cashadvance_detail->amount;
            $items['status'] = $status_id;

            $next_approval = $cashadvance_detail->approval;
            $new_project_budget_id = $cashadvance_detail->new_project_budget_id;
            $new_cost_allocation_code = $cashadvance_detail->new_cost_allocation_code;
            DB::beginTransaction();
            try {

                if ($next_approval == 7 && $cashadvance_detail->status_id == 0) {
                    if ($new_project_budget_id != '' && $new_cost_allocation_code != 0 || $new_cost_allocation_code != '') {
                        DB::table('0_cashadvance_rev_cost_alloc_details')->where('id', $rev_detail_id)
                            ->update(array('status_id' => $status_id, 'approval_date' => $date));

                        DB::table('0_cashadvance_rev_cost_alloc')->where('rev_id', $rev_id)
                            ->update(array('status_id' => $status_id));

                        // DB::table('0_cashadvance')->where('trans_no', $cashadvance_detail->ca_trans_no)
                        //     ->update(array('approval' => $approval_update,'approval_date' => $approval_desc));

                        DB::table('0_cashadvance_details')->where('cash_advance_detail_id',  $cashadvance_detail->ca_trans_detail_id)
                            ->update(array('project_budget_id' => $new_project_budget_id, 'cost_code' => $new_cost_allocation_code));

                        DB::commit();
                    } else {
                        DB::table('0_cashadvance_rev_cost_alloc_details')->where('id', $rev_detail_id)
                            ->update(array('status_id' => $status_id, 'approval_date' => $date));

                        DB::commit();
                    }
                } else {
                    DB::table('0_cashadvance_rev_cost_alloc_details')->where('id', $rev_detail_id)
                        ->update(array(
                            'approval_date' => $date
                        ));

                    DB::commit();
                }
            } catch (Exception $e) {
                // Rollback Transaction
                DB::rollback();
            }

            array_push($response, $items);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public static function update_cad($myArr, $trans_no, $cad_id)
    {
        $date = Carbon::now();

        $params = $myArr['params'];

        $status_id = $params['status_id'];
        $approval_amount = $params['approval_amount'];
        $remark = $params['remark'];

        $response = [];
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
                WHERE cad.trans_no = $trans_no AND cad.cash_advance_detail_id=$cad_id";

        $cashadvances = DB::select(DB::raw($sql));


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
            $items['approval_amount'] = $approval_amount;
            $items['status'] = $status_id;

            DB::beginTransaction();
            try {
                DB::table('0_cashadvance_details')->where('cash_advance_detail_id', $cad_id)
                    ->update(array(
                        'status_id' => $status_id,
                        'approval_amount' => $approval_amount,
                        'approval_date' => $date,
                        'act_amount' => $approval_amount,
                        'remark' => $remark
                    ));

                DB::commit();
            } catch (Exception $e) {
                // Rollback Transaction
                DB::rollback();
            }



            array_push($response, $items);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public static function update_ca($id, $myArr)
    {
        $date = Carbon::now();

        $session = $myArr['user_name'];
        $user_id = $myArr['user_id'];

        $approval_desc = "$date - $session";
        $response = [];
        $params_notification = [];

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

        $cashadvances = DB::select(DB::raw($sql));


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
            $tmp['division_id'] = $cashadvance->division;
            $tmp['amount'] = $cashadvance->ca_amount;
            $tmp['approval_amount'] = $cashadvance->approval_amount;
            $tmp['approval_position'] = $cashadvance->approval_position;
            $tmp['detail_count'] = $cashadvance->count_cad;
            $tmp['project_no'] = $cashadvance->project_no;

            // $ca_project_no = $tmp['project_no'];
            $ca_type = $cashadvance->ca_type_id;
            $level = $tmp['emp_level'];
            $amount_approval = $tmp['approval_amount'];
            $approval_position = $tmp['approval_position'];

            // FIND IF REQUESTOR AS MARKETING

            $marketing = DB::table('0_member_marketing')->where('emp_id', $cashadvance->emp_id)->first();
            if ($ca_type == 6 && empty($marketing)) {
                $routing = DB::table('0_ca_route_vehicle')
                    ->where('min_amount', '<=', $amount_approval)
                    ->where('max_amount', '>=', $amount_approval)
                    ->get();
            } else if ($ca_type != 6 && empty($marketing)) {
                $routing = DB::table('0_cashadvance_routing_approval')
                    ->where('emp_level_id', $level)
                    ->where('min_amount', '<=', $amount_approval)
                    ->where('max_amount', '>=', $amount_approval)
                    ->get();
            } else if ($ca_type != 6 && !empty($marketing)) {
                $routing = DB::table('0_member_marketing')->where('id', $marketing->id)
                    ->get();
            }


            foreach ($routing as $key) {
                $id_routing = $key->id;

                $marketing_emp = DB::table('0_member_marketing')->where('id', $key->id)->first();
                if ($ca_type != 6 && empty($marketing)) {
                    $sql = DB::table('0_cashadvance_routing_approval')
                        ->where('id', $id_routing)
                        ->first();
                } else if ($ca_type == 6 && empty($marketing)) {
                    $sql = DB::table('0_ca_route_vehicle')
                        ->where('id', $id_routing)
                        ->first();
                } else if ($ca_type != 6 && !empty($marketing)) {
                    $sql = DB::table('0_member_marketing')
                        ->where('id', $marketing_emp->id)
                        ->first();
                }


                $data = explode(',', $sql->next_approval);
                $flipped_array = array_flip($data);
                $approval_now = $flipped_array[$approval_position];

                /**
                 * NEW ROUTE FOR (TSS WIRELESS) PIC DGM
                 */
                $registered_division = array(2);
                if ($approval_position == 4) {
                    if (in_array($tmp['division_id'], $registered_division)) {
                        if ($amount_approval <= 1000000) {
                            $next = $approval_now + 1;
                        } else if ($amount_approval > 1000000) {
                            $next = $approval_now + 1;
                        }
                    } else {
                        if (!empty($marketing)) {
                            $next = $approval_now + 1;
                        } else {
                            if ($level == 0) {
                                if ($amount_approval <= 1000000) {
                                    $next = $approval_now + 1;
                                } else if ($amount_approval > 1000000) {
                                    $next = $approval_now + 2;
                                }
                            } else if ($level == 3) {
                                $next = $approval_now + 1;
                            } else if ($level != 0) {
                                $next = $approval_now + 2;
                            }
                        }
                    }
                } else {
                    $next = $approval_now + 1;
                }

                $next_approval = $data[$next];

                $tmp['next_approval'] = $next_approval;

                $approval_update = $tmp['next_approval'];


                /**
                 * PARAMS NOTIFICATION
                 */
                $params_notification['trans_no'] = $cashadvance->trans_no;
                $params_notification['approval'] = $approval_update;

                if ($amount_approval != 0) {
                    DB::table('0_cashadvance_details')->where('trans_no', $tmp['transaction_id'])
                        ->update(array(
                            'approval' => $approval_update,
                            'approval_date' => $approval_desc
                        ));

                    DB::table('0_cashadvance')->where('trans_no', $tmp['transaction_id'])
                        ->update(array(
                            'approval' => $approval_update,
                            'approval_amount' => $amount_approval,
                            'approval_description' => $approval_desc
                        ));
                } else if ($amount_approval == 0) {
                    DB::table('0_cashadvance_details')->where('trans_no', $tmp['transaction_id'])
                        ->update(array(
                            'approval' => 9,
                            'approval_date' => $approval_desc
                        ));

                    DB::table('0_cashadvance')->where('trans_no', $tmp['transaction_id'])
                        ->update(array(
                            'approval' => 9,
                            'approval_amount' => $amount_approval,
                            'approval_description' => $approval_desc
                        ));
                }


                DB::table('0_cashadvance_log1')->insert(array(
                    'trans_no' => $tmp['transaction_id'],
                    'type' => 3,
                    'approval_id' => $tmp['approval_position'],
                    'approved_amount' => $amount_approval,
                    'updated' => $date,
                    'person_id' => Auth::guard()->user()->id
                ));
            }

            array_push($response, $tmp);
        }
        /**
         * SEND NOTIFICATION
         */
        dispatch(new CashAdvanceNotification($params_notification['trans_no'], $params_notification['approval']));
        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public static function approve_all($id, $myArr)
    {
        $date = Carbon::now();

        $session = $myArr['user_name'];
        $user_id = $myArr['user_id'];

        $approval_desc = "$date - $session";
        $response = [];

        $params_notification = [];

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

        $cashadvances = DB::select(DB::raw($sql));


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
            $tmp['division_id'] = $cashadvance->division;
            $tmp['amount'] = $cashadvance->ca_amount;
            $tmp['approval_amount'] = $cashadvance->approval_amount;
            $tmp['approval_position'] = $cashadvance->approval;
            $tmp['detail_count'] = $cashadvance->count_cad;
            $tmp['project_no'] = $cashadvance->project_no;

            $sql1 = "SELECT cash_advance_detail_id, trans_no, amount FROM 0_cashadvance_details WHERE trans_no = $cashadvance->trans_no AND status_id < 2 GROUP BY cash_advance_detail_id";
            $ca_detail = DB::select(DB::raw($sql1));
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
                if ($ca_type == 6 && empty($marketing)) {
                    $routing = DB::table('0_ca_route_vehicle')
                        ->where('min_amount', '<=', $amount_approval)
                        ->where('max_amount', '>=', $amount_approval)
                        ->get();
                } else if ($ca_type != 6 && empty($marketing)) {
                    $routing = DB::table('0_cashadvance_routing_approval')
                        ->where('emp_level_id', $level)
                        ->where('min_amount', '<=', $amount_approval)
                        ->where('max_amount', '>=', $amount_approval)
                        ->get();
                } else if ($ca_type != 6 && !empty($marketing)) {
                    $routing = DB::table('0_member_marketing')->where('id', $marketing->id)
                        ->get();
                }

                foreach ($routing as $key) {
                    $id_routing = $key->id;

                    $amount_approve_all = DB::table('0_cashadvance_details')
                        ->where('trans_no', $cashadvance->trans_no)
                        ->where('status_id', '<', 2)
                        ->sum('amount');

                    $marketing_emp = DB::table('0_member_marketing')->where('id', $key->id)->first();
                    if ($ca_type != 6 && empty($marketing)) {
                        $sql = DB::table('0_cashadvance_routing_approval')
                            ->where('id', $id_routing)
                            ->first();
                    } else if ($ca_type == 6 && empty($marketing)) {
                        $sql = DB::table('0_ca_route_vehicle')
                            ->where('id', $id_routing)
                            ->first();
                    } else if ($ca_type != 6 && !empty($marketing)) {
                        $sql = DB::table('0_member_marketing')
                            ->where('id', $marketing_emp->id)
                            ->first();
                    }
                    $data = explode(',', $sql->next_approval);
                    $flipped_array = array_flip($data);
                    $approval_now = $flipped_array[$approval_position];

                    /**
                     * NEW ROUTE FOR (TSS WIRELESS) PIC DGM
                     */
                    $registered_division = array(2);
                    if ($approval_position == 4) {
                        if (in_array($tmp['division_id'], $registered_division)) {
                            if ($amount_approval <= 1000000) {
                                $next = $approval_now + 1;
                            } else if ($amount_approval > 1000000) {
                                $next = $approval_now + 1;
                            }
                        } else {
                            if ($tmp['emp_level'] == 0) {
                                if ($amount_approval <= 1000000) {
                                    $next = $approval_now + 1;
                                } else if ($amount_approval > 1000000) {
                                    $next = $approval_now + 2;
                                }
                            } else if ($tmp['emp_level'] == 3) {
                                $next = $approval_now + 1;
                            } else if ($tmp['emp_level'] != 0) {
                                $next = $approval_now + 2;
                            }
                        }
                    } else {
                        $next = $approval_now + 1;
                    }
                    $next_approval = $data[$next];

                    $tmp['next_approval'] = $next_approval;

                    $approval_update = $tmp['next_approval'];


                    /**
                     * PARAMS NOTIFICATION
                     */
                    $params_notification['trans_no'] = $cashadvance->trans_no;
                    $params_notification['approval'] = $approval_update;

                    DB::beginTransaction();
                    try {

                        DB::table('0_cashadvance_details')
                            ->where('cash_advance_detail_id', $tmp['cad_id'])
                            ->update(array(
                                'approval' => $approval_update,
                                'approval_amount' => $tmp['cad_amount'],
                                'act_amount' => $tmp['cad_amount'], 'status_id' => 1,
                                'approval_date' => $date
                            ));

                        DB::table('0_cashadvance')->where('trans_no', $tmp['transaction_id'])
                            ->update(array(
                                'approval' => $approval_update,
                                'approval_amount' => $amount_approve_all,
                                'approval_description' => $approval_desc
                            ));;

                        DB::table('0_cashadvance_log1')->insert(array(
                            'trans_no' => $tmp['transaction_id'],
                            'type' => 3,
                            'approval_id' => $tmp['approval_position'],
                            'approved_amount' => $amount_approve_all,
                            'updated' => $date,
                            'person_id' => $user_id
                        ));

                        // Commit Transaction
                        DB::commit();
                    } catch (Exception $e) {
                        // Rollback Transaction
                        DB::rollback();
                    }
                }
            }


            array_push($response, $tmp);
        }
        /**
         * SEND NOTIFICATION
         */
        dispatch(new CashAdvanceNotification($params_notification['trans_no'], $params_notification['approval']));
        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public static function disapprove_all($id, $myArr)
    {
        $date = Carbon::now();

        $session = $myArr['user_name'];
        $user_id = $myArr['user_id'];

        $approval_desc = "$date - $session";

        $sql = "SELECT * FROM 0_cashadvance WHERE trans_no=$id";

        $cashadvances = DB::select(DB::raw($sql));


        foreach ($cashadvances as $cashadvance) {

            DB::beginTransaction();
            try {
                DB::table('0_cashadvance_details')->where('trans_no', $id)
                    ->update(array(
                        'approval' => 9,
                        'approval_amount' => 0,
                        'act_amount' => 0,
                        'status_id' => 2
                    ));

                DB::table('0_cashadvance')->where('trans_no', $id)
                    ->update(array(
                        'approval' => 9,
                        'approval_amount' => 0,
                        'approval_description' => $approval_desc
                    ));

                DB::table('0_cashadvance_log1')->insert(array(
                    'trans_no' => $id,
                    'type' => 3,
                    'approval_id' => $cashadvance->approval,
                    'approved_amount' => 0,
                    'updated' => $date,
                    'person_id' => $user_id
                ));

                return response()->json([
                    'success' => true
                ]);
            } catch (Exception $e) {
                // Rollback Transaction
                DB::rollback();
            }
        }
    }

    public static function ca_list()
    {
        $response = [];

        $sql = QueryCashAdvance::ca_list();


        $cashadvances = DB::select(DB::raw($sql));


        foreach ($cashadvances as $cashadvance) {

            $tmp = [];
            $tmp['transaction_id'] = $cashadvance->trans_no;
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

    public static function get_ca_history($trans_no)
    {
        $response = [];
        $sql = QueryCashAdvance::get_ca_history($trans_no);

        $ca_history = DB::select(DB::raw($sql));

        foreach ($ca_history as $data) {

            $tmp = [];
            $tmp['approver_level'] = $data->routing_approval;
            $tmp['approver_name'] = $data->person_name;
            $tmp['last_update'] = $data->updated;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    // public static function test($project_no, $amount_approval, $level)
    // {
    //     $sql_if_office = "SELECT IF(CODE LIKE '%OFC%', 0, 1) AS value, division_id FROM 0_projects
    //                     WHERE project_no = $project_no";

    //     $is_project_query = DB::select(DB::raw($sql_if_office));

    //     foreach ($is_project_query as $key) {
    //         $is_project = $key->value;
    //         $division_id = $key->division_id;
    //     }

    //     if ($division_id == 8 || $division_id == 11) {
    //         $routing = DB::table('0_cashadvance_routing_approval')
    //             ->where(function ($query) use ($is_project, $amount_approval, $level) {
    //                 $query->where('emp_level_id', $level)
    //                     ->where('is_project', $is_project)
    //                     ->where('group_id', 2)
    //                     ->where('min_amount', '<=', $amount_approval)
    //                     ->where('max_amount', '>=', $amount_approval);
    //             })
    //             ->get();
    //     } else if ($division_id == 7 || $division_id ==  25 || $division_id == 10) {
    //         $routing = DB::table('0_cashadvance_routing_approval')
    //             ->where(function ($query) use ($is_project, $amount_approval, $level) {
    //                 $query->where('emp_level_id', $level)
    //                     ->where('is_project', $is_project)
    //                     ->where('group_id', 1)
    //                     ->where('min_amount', '<=', $amount_approval)
    //                     ->where('max_amount', '>=', $amount_approval);
    //             })
    //             ->get();
    //     } else {
    //         $routing = DB::table('0_cashadvance_routing_approval')
    //             ->where('emp_level_id', $level)
    //             ->where('min_amount', '<=', $amount_approval)
    //             ->where('max_amount', '>=', $amount_approval)
    //             ->get();
    //     }

    //     return $routing;

    // }



}
