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

class ApiBankPaymentController extends Controller
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
    public function bank_payment_need_approval(Request $request)
    {
        $validate_if_project_control = DB::table('0_user_project_control')->where('user_id', $this->old_id)->first();

        if (!empty($request->from_date)) {
            $from_date = $request->from_date;
        } else {
            $from_date =
                date("Y-m-d", strtotime(date(
                    "Y-m-d",
                    strtotime(date("Y-m-d"))
                ) . "-1 month"));
        }

        if (!empty($request->to_date)) {
            $to_date = $request->to_date;
        } else {
            $to_date =
                date("Y-m-d");
        }

        if (!empty($request->doc_no)) {
            $doc_no = $request->doc_no;
        } else {
            $doc_no = '';
        }
        $sql = "SELECT	IF(ISNULL(a.gl_seq),0,a.gl_seq) AS gl_seq,
			gl.tran_date,			
			'Bank Payment' AS TYPE,
			gl.type_no,
			refs.reference,
			SUM(IF(gl.amount>0, gl.amount,0)) AS amount,
			gl.project_code,
			CONCAT(gl.project_budget_id, '_',pcg.name) AS budget_type,
			gl.memo_,
			IF(ISNULL(u.user_id),'',u.user_id) AS user_id,
			gl.type
			FROM 0_gl_trans_tmp AS gl
			 LEFT JOIN 0_audit_trail AS a ON
				(gl.type=a.type AND gl.type_no=a.trans_no)
			 LEFT JOIN 0_comments AS com ON
				(gl.type=com.type AND gl.type_no=com.id)
			 LEFT JOIN 0_refs AS refs ON
				(gl.type=refs.type AND gl.type_no=refs.id)
			 LEFT JOIN 0_users AS u ON
				a.user=u.id
			LEFT JOIN 0_project_budgets pb ON (pb.project_budget_id = gl.project_budget_id)
			LEFT JOIN 0_project_cost_type_group pcg ON (pb.budget_type_id = pcg.cost_type_group_id)
			LEFT JOIN 0_projects prj ON (prj.code = gl.project_code)";

        $sql .= " WHERE gl.amount > 0 AND gl.approval=0";

        if (empty($validate_if_project_control)) {
            $sql .= " AND refs.reference LIKE '%9999999999%'";
        }

        if ($doc_no != '') {
            $sql .= " AND refs.reference LIKE '%$doc_no%'";
        }

        $sql .= " GROUP BY gl.tran_date,  gl.type, gl.type_no";

        return response()->json([
            'success' => true,
            'data' => DB::connection('mysql')->select(DB::raw($sql))
        ]);
    }

    public function update_bank_payment(Request $request)
    {
        $myArray = [];
        $myArray['params'] = $request->data;
        $myArray['user_id'] = $this->old_id;

        return self::process_udpate_bp($myArray);
    }

    public static function process_udpate_bp($myArray)
    {
        $user_id = $myArray['user_id'];
        foreach ($myArray['params'] as $data) {

            if ($data['status'] == 1) {
                $gl_tmp = DB::table('0_gl_trans_tmp')->where('type_no', $data['type_no'])->groupBy('counter')->get();
                $bank_trans_tmp = DB::table('0_bank_trans_tmp')->where('trans_no', $data['type_no'])->first();
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
            }
            //update approval_status with status in gl_trans_tmp, gl_bank_tmp 
            DB::table('0_gl_trans_tmp')->where('type_no', $data['type_no'])
                ->update(array(
                    'approval' => $data['status']
                ));
            DB::table('0_bank_trans_tmp')->where('trans_no', $data['type_no'])
                ->update(array(
                    'approval' => $data['status']
                ));
        }

        return response()->json([
            'success' => true
        ]);
    }
}
