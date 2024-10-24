<?php

namespace App\Api\V1\Controllers;

use Illuminate\Http\Request;
use JWTAuth;
use Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Dingo\Api\Routing\Helpers;
use Carbon\Carbon;
use App\Employees;
use App\Query\QueryEmployees;
use URL;

class ApiProjectDepositController extends Controller
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
    public function project_deposit_need_approval(Request $request)
    {
        $response = [];
        $user_id = $this->user_id;
        $user_level = $this->user_level;
        $person_id = $this->user_person_id;

        $sql = "SELECT	IF(ISNULL(a.gl_seq),0,a.gl_seq) as gl_seq,
			gl.tran_date,			
			'Project Deposit' as x_type,
			gl.type_no,
			refs.reference,
			SUM(IF(gl.amount>0, gl.amount,0)) as amount,
			gl.project_code,
			CONCAT(gl.project_budget_id, '_',pcg.name),
			gl.memo_,
			IF(ISNULL(u.user_id),'',u.user_id) as user_id,
			gl.type
			FROM 0_gl_trans_tmp as gl
			 LEFT JOIN 0_audit_trail as a ON
				(gl.type=a.type AND gl.type_no=a.trans_no)
			 LEFT JOIN 0_comments as com ON
				(gl.type=com.type AND gl.type_no=com.id)
			 LEFT JOIN 0_refs as refs ON
				(gl.type=refs.type AND gl.type_no=refs.id)
			 LEFT JOIN 0_users as u ON
				a.user=u.id
			LEFT JOIN 0_project_budgets pb ON (pb.project_budget_id = gl.project_budget_id)
			LEFT JOIN 0_project_cost_type_group pcg ON (pb.budget_type_id = pcg.cost_type_group_id)
			LEFT JOIN 0_projects prj ON (prj.code = gl.project_code)
			WHERE gl.type = 8 AND gl.amount > 0 AND gl.approval=0";

        // 1,4,3,42,41,6
        if ($user_level == 1) {
            $sql .= " AND gl.x_approval = 1 AND prj.person_id = $person_id";
        } else if ($user_level == 4) {
            $sql .= " AND gl.x_approval = 4 AND prj.division_id IN 
                    (
                        SELECT division_id FROM 0_user_project_control 
                       WHERE user_id=$user_id
                    )";
        } else if ($user_level == 2) {
            $sql .= " AND gl.x_approval = 2 AND prj.division_id IN (SELECT division_id FROM 0_user_dept WHERE user_id=$user_id) OR  gl.x_approval = 1 AND p.person_id = $person_id";
        } else if ($user_level == 3) {
            $sql .= " AND gl.x_approval = 3 AND prj.division_id IN 
                    (
                        SELECT division_id FROM 0_user_divisions
                        WHERE user_id=$user_id
                    ) 
                    OR gl.x_approval = 1 AND prj.person_id = $person_id";
        } else if ($user_level == 42) {
            $sql .= " AND gl.x_approval = 42";
        } else if ($user_level == 41) {
            $sql .= " AND gl.x_approval = 41
                    OR gl.x_approval = 1 AND prj.person_id = $person_id";
        } else if ($user_level == 999) {
            $sql .= " AND gl.x_approval != 6";
        }
        $sql .= " GROUP BY gl.tran_date,  gl.type, gl.type_no";

        $exec = DB::select(DB::raw($sql));
        foreach ($exec as $data) {

            $tmp = [];
            $tmp['type_no'] = $data->type_no;
            $tmp['type_id'] = $data->type;
            $tmp['type_name'] = $data->x_type;
            $tmp['date'] = $data->tran_date;
            $tmp['reference'] = $data->reference;
            $tmp['amount'] = $data->amount;
            $tmp['project_code'] = $data->project_code;
            $tmp['description'] = $data->memo_;
            $tmp['user'] = $data->user_id;

            $tmp['history'] = DB::table('0_project_deposit_approval as pda')
                ->leftJoin('users as u', 'u.id', '=', 'pda.user_id')
                ->where('pda.trans_no', $data->type_no)
                ->select(
                    'pda.id',
                    DB::raw('(CASE WHEN pda.status = 1 THEN "Approved" ELSE "Disapprove" END) AS status'),
                    'u.name as user',
                    'pda.created_at as time'
                )
                ->get();

            array_push($response, $tmp);
        }
        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public function update_project_deposit(Request $request)
    {
        $user_id = $this->user_id;
        $type = 8;
        $type_no = $request->type_no;
        $status = $request->status;
        if ($status == 1) {
            $remark = '-';
        } else {
            $remark = empty($request->remark_disapprove) ? '' : $request->remark_disapprove;
        }
        $data = DB::table('0_gl_trans_tmp')->where('type', $type)->where('type_no', $type_no)->where('amount', '>', 0)->where('approval', 0)
            ->select(
                'x_approval',
                DB::raw("SUM(amount) as amount")
            )
            ->first();
        $routing = DB::table('0_cashadvance_routing_approval')
            ->where('emp_level_id', 0)
            ->where('min_amount', '<=', $data->amount)
            ->where('max_amount', '>=', $data->amount)
            ->get();
        DB::beginTransaction();
        try {
            foreach ($routing as $key) {
                $id_routing = $key->id;
                $sql = DB::table('0_cashadvance_routing_approval')
                    ->where('id', $id_routing)
                    ->first();
                $data_x = explode(',', $sql->next_approval);
                $flipped_array = array_flip($data_x);
                $approval_now = $flipped_array[$data->x_approval];

                /**
                 * NEW ROUTE FOR (TSS WIRELESS & ES) PIC DGM
                 */
                $registered_division = array(2, 24, 21, 17);
                if ($data->x_approval == 4) {
                    if (in_array(24, $registered_division)) {
                        $next = $approval_now + 1;
                    } else {
                        if ($data->amount <= 1000000) {
                            $next = $approval_now + 1;
                        } else if ($data->amount > 1000000) {
                            $next = $approval_now + 2;
                        }
                    }
                } else {
                    $next = $approval_now + 1;
                }

                $next_approval = $data_x[$next];

                DB::table('0_gl_trans_tmp')->where('type', $type)->where('type_no', $type_no)
                    ->update(array(
                        'x_approval' => $next_approval
                    ));
            }
            // if ($status == 1 && $next_approval == 6) {
            //     self::process_insert_to_gl($type, $type_no);
            // }
            DB::table('0_project_deposit_approval')
                ->insert(array(
                    'trans_no' => $type_no,
                    'approval' => $data->x_approval,
                    'status' => $status,
                    'remark_disapprove' => $remark,
                    'user_id' => $user_id,
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
    }


    public function release_project_deposit(Request $request)
    {
        $type_no = $request->type_no;
        $type = 8;

        self::process_insert_to_gl($type, $type_no);
    }

    public static function process_insert_to_gl($type, $type_no)
    {
        $gl_tmp = DB::table('0_gl_trans_tmp')->where('type', $type)->where('type_no', $type_no)->groupBy('counter')->get();
        $bank_trans_tmp = DB::table('0_bank_trans_tmp')->where('type', $type)->where('trans_no', $type_no)->first();
        foreach ($gl_tmp as $gl) {
            //insert into gl_trans
            DB::table('0_gl_trans')
                ->insert(array(
                    'type' => $gl->type,
                    'type_no' => $gl->type_no,
                    'tran_date' => $gl->tran_date,
                    'account' => $gl->account,
                    'memo_' => $gl->memo_,
                    'amount' => $gl->amount,
                    'person_type_id' => $gl->person_type_id,
                    'person_id' => $gl->person_id,
                    'project_code' => $gl->project_code,
                    'project_budget_id' => $gl->project_budget_id,
                    'additional_payment_type' => $gl->additional_payment_type,
                    'vat_no' => $gl->vat_no,
                    'vat_amount' => $gl->vat_amount,
                    'verfied' => 1,
                    'order_no' => $gl->order_no
                ));
        }
        //insert into bank_accounts
        DB::table('0_bank_trans')
            ->insert(array(
                'type' => $bank_trans_tmp->type,
                'trans_no' => $bank_trans_tmp->trans_no,
                'bank_act' => $bank_trans_tmp->bank_act,
                'ref' => $bank_trans_tmp->ref,
                'trans_date' => $bank_trans_tmp->trans_date,
                'amount' => $bank_trans_tmp->amount,
                'person_type_id' => $bank_trans_tmp->person_type_id,
                'person_id' => $bank_trans_tmp->person_id,
                'memo' => $bank_trans_tmp->memo,
                'vat_no' => $bank_trans_tmp->vat_no,
                'vat_date' => $bank_trans_tmp->vat_date,
                'dpp' => $bank_trans_tmp->dpp,
                'ppn' => $bank_trans_tmp->ppn
            ));
        //update approval_status with status in gl_trans_tmp, gl_bank_tmp 
        DB::table('0_gl_trans_tmp')->where('type_no', $type_no)
            ->update(array(
                'approval' => 1
            ));
        DB::table('0_bank_trans_tmp')->where('trans_no', $type_no)
            ->update(array(
                'approval' => 1
            ));


        return response()->json([
            'success' => true
        ]);
    }
}
