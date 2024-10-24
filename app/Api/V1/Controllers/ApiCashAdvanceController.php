<?php

namespace App\Api\V1\Controllers;

use JWTAuth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\CashAdvanceController;
use Illuminate\Http\Request;
use Auth;
use App\Modules\PaginationArr;
use App\Modules\InputList;


class ApiCashAdvanceController extends Controller
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

    public function ca_approval()
    {
        // $user_session = array($this->old_id, $this->user_level, $this->user_person_id, $this->user_division);
        $myArray = CashAdvanceController::ca_need_approval(
            $this->old_id,
            $this->user_level,
            $this->user_person_id,
            $this->user_division
        );
        return $myArray;
    }

    public function view_ca_revision()
    {
        $myArray = CashAdvanceController::ca_revision_cost_allocation(
            $this->old_id,
            $this->user_level,
            $this->user_person_id,
            $this->user_division
        );
        return $myArray;
    }

    public function history_approval_rev_ca($trans_no)
    {
        $myArray = CashAdvanceController::get_history_approval_rev_ca($trans_no);
        return $myArray;
    }
 
    public function update_approve_rev_ca(Request $request, $id)
    {
        
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['user_id'] = $this->user_id;
        $myArray['user_name'] = $this->user_name;
        //return $request->all();
        
        $myQuery = CashAdvanceController::update_approve_rev_ca($myArray, $id);
        return $myQuery;
    }
    
    public function update_approve_rev_ca_details(Request $request, $id, $id2)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myQuery = CashAdvanceController::update_approve_rev_ca_details($myArray, $id, $id2);
        return $myQuery;
    }

    public function disapprove_rev_ca(Request $request, $id, $detail_id)
    {
        $user_id = $this->user_id;
        DB::beginTransaction();
        try {

            DB::table('0_cashadvance_rev_cost_alloc')->where('rev_id', $id)
            ->update(array('status_id' => 2, 'approval' => 7));

            DB::table('0_cashadvance_rev_cost_alloc_details')->where('id', $detail_id)
            ->update(array('remark_disapprove' => $request->remark,'rejected_by' => $user_id, 'approval' => 7, 'status_id' => 2));

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

    public function update_ca_detail(Request $request, $id, $id2)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myQuery = CashAdvanceController::update_cad($myArray, $id, $id2);
        return $myQuery;
    }

    public function update_ca($id)
    {
        $myArray = [];
        $myArray['user_id'] = $this->user_id;
        $myArray['user_name'] = $this->user_name;

        $myQuery = CashAdvanceController::update_ca($id, $myArray);
        return $myQuery;
    }

    public function ca_approve_all($id)
    {
        $myArray = [];
        $myArray['user_id'] = $this->user_id;
        $myArray['user_name'] = $this->user_name;

        $myQuery = CashAdvanceController::approve_all($id, $myArray);
        return $myQuery;
    }

    public function ca_disapprove_all($id)
    {
        $myArray = [];
        $myArray['user_id'] = $this->user_id;
        $myArray['user_name'] = $this->user_name;

        $myQuery = CashAdvanceController::disapprove_all($id, $myArray);
        return $myQuery;
    }

    public function ca_remark_diss(Request $request, $cad_id)
    {
        $user_id = $this->user_id;
        DB::beginTransaction();
        try {

            DB::table('0_cashadvance_details')->where('cash_advance_detail_id', $cad_id)
                ->update(array('remark_disapprove' => $request->remark, 'rejected_by' => $user_id));

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

    public function ca_remark_diss_all(Request $request, $trans_no)
    {
        $user_id = $this->user_id;

        DB::beginTransaction();
        try {

            DB::table('0_cashadvance_details')->where('trans_no', $trans_no)
                ->update(array('remark_disapprove' => $request->remark, 'rejected_by' => $user_id, 'status_id' => 2));

            DB::table('0_cashadvance')->where('trans_no', $trans_no)
                ->update(array('status_id' => 2));

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

    public function view_ca_list()
    {
        $myArray = CashAdvanceController::ca_list();
        return $myArray;
    }

    public function ca_history($trans_no)
    {
        $myArray = CashAdvanceController::get_ca_history($trans_no);
        return $myArray;
    }

    public function test($project_no)
    {
        $amount_approval = 100200002;
        $level = 2;
        $myArray = CashAdvanceController::test($project_no, $amount_approval, $level);
        return $myArray;
    }
}
