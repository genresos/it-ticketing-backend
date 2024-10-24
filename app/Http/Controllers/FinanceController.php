<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use JWTAuth;
use Auth;
use Illuminate\Support\Facades\DB;
use Dingo\Api\Routing\Helpers;
use Storage;
use Carbon\Carbon;
use App\Cashadvance;
use App\CashadvanceDetails;
use App\AuditTrail;
use App\GeneralLedger;
use App\Employees;
use App\AssetVehicles;
use App\AssetVehicleDetails;
use Symfony\Component\HttpKernel\Exception\BudgetAmountException;
use Validator,Redirect,Response,File;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EmployeesController;
use App\Http\Controllers\ProjectController;
use App\Exports\CashAdvanceExport;
use Maatwebsite\Excel\Facades\Excel;

class FinanceController extends Controller
{

    public static function get_last_ca_count(){
        $currentUser = JWTAuth::parseToken()->authenticate();
        $user_id = Auth::guard()->user()->old_id;

        $get_user_area = DB::table('0_users')
        ->where('id',$user_id)
        ->first();
        $area_id = $get_user_area->area_id;

        $ca_type_id = 1;
        $year = Carbon::now();
        $year_ = substr($year,2,2);
        $sql = "SELECT
                IF (ISNULL(SUBSTRING((100000 + MAX(SUBSTRING(reference, 4,6)) + 1),2,5)), '00001',
                SUBSTRING((100000 + MAX(SUBSTRING(reference, 4,6)) + 1),2,5)) as last_number
            FROM 0_cashadvance
            WHERE SUBSTRING(reference, 1,1 )=$area_id
            AND SUBSTRING(reference, 2,1 )=$ca_type_id
            AND SUBSTRING(tran_date,3,2)='$year_' AND LENGTH(reference)=9";

        $result = DB::select( DB::raw($sql));

        foreach($result as $data){
            $last_number = $data->last_number;

            return $last_number;
        }



    }

    public static function ca_reference(){
        $currentUser = JWTAuth::parseToken()->authenticate();
        $user_id = Auth::guard()->user()->old_id;

        $area_id = UserController::get_user_area();

        $date = Carbon::now();
        $last_ca_count = self::get_last_ca_count();

        $ca_reference = $area_id.'1'.substr($date,2,2).$last_ca_count;



        return $ca_reference;
    }


    public static function add_cashadvance($doc_no_pdp,
                                           $emp_id,
                                           $project_budget_id,
                                           $project_no,
                                           $petty_cash,
                                           $payment_type,
                                           $vehicle_type_id,
                                           $amount_bbm,
                                           $bbm_doc_no,
                                           $if_tol,
                                           $amount_tol,
                                           $remark_bbm,
                                           $remark_tol,
                                           $tolcard_no,
                                           $vehicle_no){

        //user info
        $level = Auth::guard()->user()->approval_level;
        $person_id = Auth::guard()->user()->person_id;
        $division_id = Auth::guard()->user()->division_id;
        $user_id = Auth::guard()->user()->id;
        $old_id = Auth::guard()->user()->old_id;

        //user info

        $car_no = $vehicle_no;          
        $get_last_id = DB::table('0_cashadvance')
                       ->latest('trans_no')->first();
        $get_fiscal_year = DB::table('0_fiscal_year')
                       ->latest('id')->first();
        $get_last_ref_am_vehicles = DB::table('0_am_vehicles')->latest('reference')->first();
        $get_last_am_order_no = DB::table('0_am_vehicles')->latest('order_no')->first();
        
        $get_vehicle_name = DB::select(DB::raw("SELECT
                                    a.asset_id,a.asset_name, g.name AS group_name, a.asset_model_name,
                                    a.asset_model_number,a.asset_serial_number
                                FROM 0_am_assets a
                                LEFT OUTER JOIN 0_am_groups g ON (a.group_id = g.group_id)
                                WHERE a.asset_name = '$car_no' AND a.inactive = 0 AND a.type_id=2"));

                            foreach ($get_vehicle_name as $item_cars){
                                $vehicle_name = $item_cars->group_name;
                            }

        $order_no_am = $get_last_am_order_no->order_no + 1;
        $ref_am_vehicles = $get_last_ref_am_vehicles->reference + 1;
        $fiscal_year = $get_fiscal_year->id;

            // Variable for insert data
           
            $last_id = $get_last_id->trans_no+1;
            $ca_reference = self::ca_reference();
            $tran_date = date('Y-m-d');
            $ca_type_id = 1;
            $emp_no = UserController::get_emp_no($emp_id);
            $area_id = UserController::get_user_area();
            $approval = 7;
    
    
            if($if_tol == 1){
                $total_amount = $amount_bbm + $amount_tol;
            }else if($if_tol < 1){
                $total_amount = $amount_bbm;
            }
    
            $vehicle_remark_details = "$remark_bbm _ $remark_tol";
            //* VARIABLE FOR GL
            $emp_name = EmployeesController::get_employees($emp_id);
            $memo = "CA $ca_reference _ $emp_id-$emp_name";
            $amount_kredit = 0 - $total_amount;
            $petty_gl = self::get_petty_cash($petty_cash);
            
            // ********************************************** */

            // ***********
            // VALIDATING BUDGET AMOUNT
            
            $amount_budget = ProjectController::project_budgets($project_budget_id);
            $po_amount = ProjectController::get_po_balance_by_project($project_budget_id);
            $ca_amount = ProjectController::get_cashadvance_balance_by_project($project_budget_id);
            $gl_amount = ProjectController::get_gl_balance_by_project($project_budget_id);

            $remain_budget = $amount_budget - ($po_amount+$ca_amount+$gl_amount);

            //********** */

             // GETTING SITE ID FROM PROJECT DAILY PLAN
            
             $pdp = DB::table('0_project_daily_plan')->where('reference',$doc_no_pdp)->first();
             $site_id = DB::table('0_project_site')->whereRaw("site_id LIKE '%$pdp->site_id%' OR name LIKE '%$pdp->site_id%'")->latest('site_no')->first();
             $site_no = $site_id->site_no;
 
             //********** */

            if($remain_budget > $total_amount){

                // Begin Transaction
                DB::beginTransaction();

                        try {
                            //** INSERT INTO CASHADVANCE DETAILS BBM/

                                if($payment_type < 4){

                                    $cashadvance_details_bbm = new CashadvanceDetails;
                                    $cashadvance_details_bbm->trans_no = $last_id;
                                    $cashadvance_details_bbm->project_no = $project_no;
                                    $cashadvance_details_bbm->project_budget_id = $project_budget_id;
                                    $cashadvance_details_bbm->approval = $approval;
                                    $cashadvance_details_bbm->approval_date = $tran_date;
                                    $cashadvance_details_bbm->amount = $amount_bbm;
                                    $cashadvance_details_bbm->act_amount = $amount_bbm;
                                    $cashadvance_details_bbm->approval_amount = $amount_bbm;
                                    $cashadvance_details_bbm->release_amount = $amount_bbm;
                                    $cashadvance_details_bbm->release_date = $tran_date;
                                    $cashadvance_details_bbm->plan_release_date = $tran_date;
                                    $cashadvance_details_bbm->release_cashier = 1;
                                    $cashadvance_details_bbm->remark = $remark_bbm;
                                    $cashadvance_details_bbm->tolcard_no = 0;
                                    $cashadvance_details_bbm->save();

                                    if($if_tol > 0){
                                    //** INSERT INTO CASHADVANCE DETAILS TOL CARD/

                                    $cashadvance_details_tol = new CashadvanceDetails;
                                    $cashadvance_details_tol->trans_no = $last_id;
                                    $cashadvance_details_tol->project_no = $project_no;
                                    $cashadvance_details_tol->project_budget_id = $project_budget_id;
                                    $cashadvance_details_tol->approval = $approval;
                                    $cashadvance_details_tol->approval_date = $tran_date;
                                    $cashadvance_details_tol->amount = $amount_tol;
                                    $cashadvance_details_tol->act_amount = $amount_tol;
                                    $cashadvance_details_tol->approval_amount = $amount_tol;
                                    $cashadvance_details_tol->release_amount = $amount_tol;
                                    $cashadvance_details_tol->release_date = $tran_date;
                                    $cashadvance_details_tol->plan_release_date = $tran_date;
                                    $cashadvance_details_tol->release_cashier = 1;
                                    $cashadvance_details_tol->remark = $remark_tol;
                                    $cashadvance_details_tol->tolcard_no = $tolcard_no;
                                    $cashadvance_details_tol->save();
                                    }

                                    //** INSERT INTO CASHADVANCE/

                                    $cashadvance = new Cashadvance;
                                    $cashadvance->trans_no = $last_id;
                                    $cashadvance->ca_type_id = 1;
                                    $cashadvance->tran_date = $tran_date;
                                    $cashadvance->reference = $ca_reference;
                                    $cashadvance->project_no = $project_no;
                                    $cashadvance->project_budget_id = $project_budget_id;
                                    $cashadvance->bank_account_no = $petty_cash;
                                    $cashadvance->emp_no = $emp_no;
                                    $cashadvance->emp_id = $emp_id;
                                    $cashadvance->payment_type_id = $payment_type;
                                    $cashadvance->area_id = $area_id;
                                    $cashadvance->approval = $approval;
                                    $cashadvance->amount = $total_amount;
                                    $cashadvance->approval_amount = $total_amount;
                                    $cashadvance->release_amount = $total_amount;
                                    $cashadvance->release_date = $tran_date;
                                    $cashadvance->save();

                                    //** INSERT INTO AUDIT TRAIL/

                                    $audit_trail = new AuditTrail;
                                    $audit_trail->trans_no = $last_id;
                                    $audit_trail->type = 2001;
                                    $audit_trail->user = $old_id;
                                    $audit_trail->stamp = Carbon::now();
                                    $audit_trail->fiscal_year = $fiscal_year;
                                    $audit_trail->gl_seq = 0;
                                    $audit_trail->gl_date = $tran_date;
                                    $audit_trail->save();

                                    //** INSERT INTO GL DEBIT/

                                    $gl_debit = new GeneralLedger;
                                    $gl_debit->type = 2001;
                                    $gl_debit->type_no = $last_id;
                                    $gl_debit->tran_date = $tran_date;
                                    $gl_debit->account = 101710;
                                    $gl_debit->memo_ = $memo;
                                    $gl_debit->amount = $total_amount;
                                    $gl_debit->save();


                                    //** INSERT INTO GL KREDIT/

                                    $gl_kredit = new GeneralLedger;
                                    $gl_kredit->type = 2001;
                                    $gl_kredit->type_no = $last_id;
                                    $gl_kredit->tran_date = $tran_date;
                                    $gl_kredit->account = $petty_gl;
                                    $gl_kredit->memo_ = $memo;
                                    $gl_kredit->amount = $amount_kredit;
                                    $gl_kredit->save();


                                    //** INSERT INTO AM VEHICLES/

                                    $am_vehicles = new AssetVehicles;
                                    $am_vehicles->order_no = $order_no_am;
                                    $am_vehicles->type = 7000;
                                    $am_vehicles->reference = $ref_am_vehicles;
                                    $am_vehicles->ord_date = $tran_date;
                                    $am_vehicles->to_date = $tran_date;
                                    $am_vehicles->vehicle_type_id = $vehicle_type_id;
                                    $am_vehicles->emp_no = $emp_no;
                                    $am_vehicles->created_by = $old_id;
                                    $am_vehicles->created_date = $tran_date;
                                    $am_vehicles->bank_account_no = $petty_cash;
                                    $am_vehicles->payment_type_id = $payment_type;
                                    $am_vehicles->cashadvance_ref = $ca_reference;
                                    $am_vehicles->save();

                                    //** INSERT INTO AM VEHICLE DETAILS/

                                    $am_vehicle_details = new AssetVehicleDetails;
                                    $am_vehicle_details->order_no = $order_no_am;
                                    $am_vehicle_details->vehicle_no = $vehicle_no;
                                    $am_vehicle_details->vehicle_name = $vehicle_name;
                                    $am_vehicle_details->project_no = $project_no;
                                    $am_vehicle_details->bbm_doc_no = $bbm_doc_no;
                                    $am_vehicle_details->bbm_amount = $total_amount;
                                    $am_vehicle_details->remark = $vehicle_remark_details;
                                    $am_vehicle_details->site_no = $site_no;
                                    $am_vehicle_details->tolcard_no = $tolcard_no;
                                    $am_vehicle_details->save();

                                    //** UPDATE CA_REFERENCE PDP
                
                                    DB::table('0_project_daily_plan')->where('reference',$doc_no_pdp)
                                        ->update(array('ca_reference' => $ca_reference, 'approval' => 5, 'is_carpool' => 1));

                                    if($if_tol > 0){
                                            //** INSERT INTO TOLCARD MOVES
                    
                                            DB::table('0_am_tolcard_moves')
                                            ->insert(array('tolcard_no' => $tolcard_no,
                                                'v_order_no' => $ca_reference,
                                                'tran_date' => $tran_date,
                                                'amount' => $amount_tol,
                                                'remark' => $remark_tol));      
                                    }
                                
                                }else if($payment_type == 4){
                                    
                                    //** INSERT INTO AM VEHICLES/

                                    $am_vehicles = new AssetVehicles;
                                    $am_vehicles->order_no = $order_no_am;
                                    $am_vehicles->type = 7000;
                                    $am_vehicles->reference = $ref_am_vehicles;
                                    $am_vehicles->ord_date = $tran_date;
                                    $am_vehicles->to_date = $tran_date;
                                    $am_vehicles->vehicle_type_id = $vehicle_type_id;
                                    $am_vehicles->emp_no = $emp_no;
                                    $am_vehicles->created_by = $old_id;
                                    $am_vehicles->created_date = $tran_date;
                                    $am_vehicles->bank_account_no = $petty_cash;
                                    $am_vehicles->payment_type_id = $payment_type;
                                    $am_vehicles->cashadvance_ref = null;
                                    $am_vehicles->save();

                                    //** INSERT INTO AM VEHICLE DETAILS/

                                    $am_vehicle_details = new AssetVehicleDetails;
                                    $am_vehicle_details->order_no = $order_no_am;
                                    $am_vehicle_details->vehicle_no = $vehicle_no;
                                    $am_vehicle_details->vehicle_name = $vehicle_name;
                                    $am_vehicle_details->project_no = $project_no;
                                    $am_vehicle_details->bbm_doc_no = $bbm_doc_no;
                                    $am_vehicle_details->bbm_amount = $total_amount;
                                    $am_vehicle_details->remark = $vehicle_remark_details;
                                    $am_vehicle_details->site_no = $site_no;
                                    $am_vehicle_details->tolcard_no = $tolcard_no;
                                    $am_vehicle_details->save();

                                    //** UPDATE APPROVAL PDP
                
                                    DB::table('0_project_daily_plan')->where('reference',$doc_no_pdp)
                                        ->update(array('approval' => 5, 'is_carpool' => 1));
                                 
                                }

                            //** INSERT AM VEHICLE EMPLOYEES
        
                            DB::table('0_am_vehicle_employees')->insert(array('order_no' => $order_no_am,
                            'emp_no' => $emp_no)); 
                            
                            //** INSERT PDP LOG
        
                            DB::table('0_pdp_log')->insert(array('reference' => $doc_no_pdp,
                                'approval' => 4,
                                'person_id' => Auth::guard()->user()->id)); 

                            // Commit Transaction
                                DB::commit();

                            // Semua proses benar


                            return response()->json([
                                'success' => true
                            ]);
                        } catch (Exception $e) {
                            // Rollback Transaction
                                DB::rollback();

                        }
            }else if($remain_budget < $total_amount){
                throw new BudgetAmountException();
            }
    }
//==================================================================== FUNCTION CA OUTSTANDING=============================================================\\
    /*
 * Check CA Outstanding Karyawan
 */
    public static function AllowedCashAdvance($emp_id)
    {
        //Jumlah maksimal Input

        $sql_input = DB::table('0_sys_prefs')->where('name', 'limit_ca_input')->first();
        $max_ca_emp = $sql_input->value;

        $sql = "SELECT
                    SUM(xx.ca_count) AS ca_count,
                    SUM(xx.settlement_count) AS settlement_count
                FROM
                (
                    SELECT
                        cad.is_project_budget_perijinan,
                        ca.trans_no,
                        ca.reference,
                        CASE WHEN(COUNT(ca.trans_no)>0) THEN '1' ELSE '0' END AS ca_count,
                        (
                            SELECT CASE WHEN COUNT(stl.trans_no)  IS NULL THEN 0 ELSE COUNT(stl.trans_no) END
                            FROM 0_cashadvance_stl stl
                            WHERE stl.ca_trans_no = ca.trans_no
                            AND trans_no IN
                            (
                                SELECT trans_no FROM 0_cashadvance_stl_details
                                WHERE approval > 6
                            )
                            GROUP BY stl.trans_no
                        ) settlement_count
                    FROM 0_cashadvance ca
                    INNER JOIN 0_cashadvance_details cad ON (ca.trans_no = cad.trans_no)
                    LEFT JOIN 0_hrm_employees e ON (e.id = ca.emp_no)
                    WHERE YEAR(ca.tran_date)>2019
                    AND ca.emp_id = '$emp_id'
                    AND ca.ca_type_id <> 4
                    GROUP BY  ca.trans_no
                ) xx WHERE xx.is_project_budget_perijinan = 0";

        $outstanding_ca_emp = DB::select( DB::raw($sql));
        foreach ($outstanding_ca_emp as $data){

            if($max_ca_emp >= 1 )
            {

                if (($data->ca_count - $data->settlement_count) >= $max_ca_emp)
                {
                    $value = 0;
                }
                else if(($data->ca_count - $data->settlement_count) <= $max_ca_emp)
                {
                    $value = 1;
                }
            }
            return $value;
        }
    }

    public static function get_petty_cash($id){
        $sql = DB::table('0_bank_accounts')->where('id',$id)->first();

        return $sql->account_code;
    }

    public static function export_ca()
    {
        $date = date('d-m-Y');
        $filename = "CA_LIST_" . $date;
        Excel::store(new CashAdvanceExport, "$filename.xlsx");
        return response()->json([
            'success' => true
        ]);
    }

    public function download_ca_export()
    {
        $date = date('d-m-Y');
        $filename = "CA_LIST_".$date;
        $path = storage_path() . '/' . 'public' . '/storage/' . "$filename.xlsx";
        return Response::download($path);

    }
}
