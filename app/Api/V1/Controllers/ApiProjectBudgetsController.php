<?php

namespace App\Api\V1\Controllers;

use JWTAuth;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ProjectListController;
use App\Http\Controllers\ProjectBudgetController;
use Illuminate\Http\Request;
use Auth;
use App\Modules\PaginationArr;
use App\Modules\InputList;
use App\Query\QueryProjectBudget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use DateTime;
use Exception;
use PhpParser\Node\Stmt\Else_;
use PhpParser\Node\Stmt\TryCatch;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RABExport;
use URL;

class ApiProjectBudgetsController extends Controller
{

    public function __construct()
    {
        $currentUser = JWTAuth::parseToken()->authenticate();

        $this->user_id = Auth::guard()->user()->id;
        $this->user_name = Auth::guard()->user()->name;
        $this->user_old_id = Auth::guard()->user()->old_id;
        $this->user_level = Auth::guard()->user()->approval_level;
        $this->user_division = Auth::guard()->user()->division_id;
        $this->user_person_id = Auth::guard()->user()->person_id;
        $this->user_emp_id = Auth::guard()->user()->emp_id;
    }

    public function project_budgets(Request $request, $project_no)
    {
        if (!empty($request->search)) {
            $search = $request->search;
        } else {
            $search = '';
        }
        if (!empty($request->isacc)) {
            $isacc = $request->isacc;
        } else {
            $isacc = 0;
        }
        $budget_id = 0;

        if ($isacc == 1) {
            $myArray = ProjectBudgetController::show_budgets_acc(
                $project_no,
                $budget_id,
                $search
            );
        } else {
            $myArray = ProjectBudgetController::show_budgets(
                $project_no,
                $budget_id,
                $search
            );
        }

        $myPage = $request->page;
        $myUrl = $request->url();
        $query = $request->query();

        if (empty($request->perpage)) {
            $perPage = 10;
        } else {
            $perPage = $request->perpage;
        }

        return PaginationArr::arr_pagination(
            $myArray,
            $myPage,
            $perPage,
            $myUrl,
            $query
        );
    }

    public function edit_project_budget(Request $request, $project_budget_id)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['user_id'] = $this->user_old_id;
        $myQuery = ProjectBudgetController::edit_budget($myArray, $project_budget_id);
        return $myQuery;
    }

    public function add_new_budget(Request $request)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['user_id'] = $this->user_old_id;
        $myQuery = ProjectBudgetController::add_budget($myArray);
        return $myQuery;
    }

    public function add_req_budget(Request $request, $project_budget_id)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['user_id'] = $this->user_old_id;
        $myQuery = ProjectBudgetController::req_add_budget($myArray, $project_budget_id);
        return $myQuery;
    }

    public function budget_approve(Request $request, $budget_detail_id)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['user_id'] = $this->user_old_id;
        $myQuery = ProjectBudgetController::detail_budget_approve($myArray, $budget_detail_id);
        return $myQuery;
    }

    public function budget_disapprove(Request $request, $budget_detail_id)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['user_id'] = $this->user_old_id;
        $myQuery = ProjectBudgetController::detail_budget_disapprove($myArray, $budget_detail_id);
        return $myQuery;
    }

    public function budget_detail($budget_id)
    {
        $myArray = ProjectBudgetController::budget_detail($budget_id);
        return $myArray;
    }

    public function budget_approve_list(Request $request)
    {
        if (empty($request->budget_id)) {
            $budget_id = 0;
        } else {
            $budget_id = $request->budget_id;
        }

        if (empty($request->project_code)) {
            $project_code = '';
        } else {
            $project_code = $request->project_code;
        }

        $myArray = ProjectBudgetController::budget_need_approve(
            $this->user_level,
            $this->user_old_id,
            $budget_id,
            $project_code
        );
        $myPage = $request->page;
        $myUrl = $request->url();
        $query = $request->query();

        if (empty($request->perpage)) {
            $perPage = 10;
        } else {
            $perPage = $request->perpage;
        }

        return PaginationArr::arr_pagination(
            $myArray,
            $myPage,
            $perPage,
            $myUrl,
            $query
        );
    }

    public function show_used_budget($project_budget_id)
    {
        $myArray = ProjectBudgetController::show_used_budget_amount($project_budget_id);
        return $myArray;
    }
    public function test(Request $request)
    {
        $myArray = ProjectBudgetController::test(
            $this->user_id
        );
        $myPage = $request->page;
        $myUrl = $request->url();
        $query = $request->query();

        if (empty($request->perpage)) {
            $perPage = 10;
        } else {
            $perPage = $request->perpage;
        }

        return PaginationArr::arr_pagination(
            $myArray,
            $myPage,
            $perPage,
            $myUrl,
            $query
        );
    }


    public function budget_by_project_no(Request $request)
    {
        return InputList::list_budget_by_project_no($request->project_no);
    }

    public function get_export_budget_use(Request $request)
    {
        $project_budget_id = $request->project_budget_id;

        $myData = ProjectBudgetController::export_used_budget($project_budget_id);
        return $myData;
    }

    public function get_curve_graph_total_budget(Request $request)
    {
        $year = $request->year;
        $project_no = $request->project_no;
        $project_code = ProjectListController::get_project_code($project_no);
        $get_info_project = ProjectListController::project($project_no);
        $encode_data = json_decode(json_encode($get_info_project), true);
        $get_data = collect($encode_data['original']['data'])
            ->all();

        $inactive_status =  $get_data[0]['inactive'];

        $myData = ProjectBudgetController::curve_graph_total_budget($year, $project_no);


        $myPrevData = ProjectBudgetController::total_prev_all_cost($inactive_status, $project_code, $year);

        $start_budget_create = QueryProjectBudget::get_first_budget_created_date($project_no);
        $start_from = date('Y', strtotime($start_budget_create));
        $prev_data = [];
        $prev_data['start_from'] = ($start_from < 2018) ? 0 : $start_from;
        $prev_data['cost'] =  $myPrevData;
        $prev_data['budget'] = ProjectBudgetController::sum_total_prev_budget($year, $project_no);


        return response()->json([
            'success' => true,
            'prev_data' => $prev_data,
            'current_data' => $myData

        ], 200);
    }


    public function rab_budget_adjustment(Request $request, $project_budget_id)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['user_id'] = $this->user_old_id;
        $myQuery = ProjectBudgetController::rab_adjustment($myArray, $project_budget_id);
        return $myQuery;
    }

    public function project_rab_history(Request $request)
    {
        if ($request->rab_id > 0) {
            $rab_id = $request->rab_id;
        } else {
            $rab_id = 0;
        }
        $budget_id = 0;
        $myArray = ProjectBudgetController::rab_history(
            $rab_id
        );
        $myPage = $request->page;
        $myUrl = $request->url();
        $query = $request->query();

        if (empty($request->perpage)) {
            $perPage = 10;
        } else {
            $perPage = $request->perpage;
        }

        return PaginationArr::arr_pagination(
            $myArray,
            $myPage,
            $perPage,
            $myUrl,
            $query
        );
    }

    public function upload_rab(Request $request)
    {
        $user_id = $this->user_id;
        $file = $request->file('file');
        if ($file) {
            $filename = $file->getClientOriginalName();
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
                $budget_id = $importData[2];
                $rab_amount = $importData[3];
                $budget_info = DB::table('0_project_budgets')->where('project_budget_id', $budget_id)->first();
                $check_rab = DB::table('0_project_budget_rab')->where('budget_id', $budget_id)->first();

                try {

                    DB::table('0_project_budgets')->where('project_budget_id', $budget_id)
                        ->update(array('rab_amount' => $rab_amount));

                    if (empty($check_rab)) {
                        DB::table('0_project_budget_rab')
                            ->insert(array(
                                'budget_id' => $budget_id,
                                'budget_type_id' => $budget_info->budget_type_id,
                                'project_no' => $budget_info->project_no,
                                'amount' => $rab_amount,
                                'user_id' => $user_id
                            ));
                    }
                    $last_rab_id = DB::table('0_project_budget_rab')->where('budget_id', $budget_id)->first();

                    DB::table('0_project_budget_rab_details')
                        ->insert(array(
                            'rab_id' => $last_rab_id->id,
                            'from_rab' => 0,
                            'amount' => $rab_amount,
                            'remark' => "New RAB FROM UPLOAD",
                            'description' => '',
                            'type' => 1,
                            'user_id' => $user_id,
                            'created_at' => Carbon::now()
                        ));

                    DB::commit();
                } catch (\Exception $e) {
                    //throw $th;
                    DB::rollBack();
                }
            }

            return response()->json([
                'status' => true,
                'message' => "$j records successfully uploaded"
            ]);
        } else {
            //no file was uploaded
            echo "Error";
        }
    }

    // public function budget_rab(Request $request)
    // {

    //     $project_no = (empty($request->project_no)) ? 0 : $request->project_no;
    //     if (empty($request->type) || $request->type == null) {
    //         $type = '';
    //     } else {
    //         $type = $request->type;
    //     }

    //     $myArray = ProjectBudgetController::rab_index(
    //         $project_no,
    //         $type
    //     );

    //     $myPage = $request->page;
    //     $myUrl = $request->url();
    //     $query = $request->query();

    //     if (empty($request->perpage)) {
    //         $perPage = 10;
    //     } else {
    //         $perPage = $request->perpage;
    //     }

    //     return PaginationArr::arr_pagination(
    //         $myArray,
    //         $myPage,
    //         $perPage,
    //         $myUrl,
    //         $query
    //     );
    // }

    // public function add_rab_budget(Request $request, $project_no)
    // {
    //     $myArray = [];
    //     $myArray['params'] = $request->all();
    //     $myArray['user_id'] = $this->user_id;
    //     $myQuery = ProjectBudgetController::add_rab($myArray, $project_no);
    //     return $myQuery;
    // }


    // public function rab_need_approve(Request $request)
    // {
    //     $myArray = ProjectBudgetController::rab_approve($this->user_id);
    //     $myPage = $request->page;
    //     $myUrl = $request->url();
    //     $query = $request->query();

    //     if (empty($request->perpage)) {
    //         $perPage = 10;
    //     } else {
    //         $perPage = $request->perpage;
    //     }

    //     return PaginationArr::arr_pagination(
    //         $myArray,
    //         $myPage,
    //         $perPage,
    //         $myUrl,
    //         $query
    //     );
    // }

    // public function approval_rab(Request $request)
    // {
    //     $myArray = [];
    //     $myArray['params'] = $request->all();
    //     $myArray['user_id'] = $this->user_id;
    //     $myQuery = ProjectBudgetController::approve_rab($myArray);
    //     return $myQuery;
    // }


    // public function edit_rab(Request $request)
    // {
    //     $myArray = [];
    //     $myArray['params'] = $request->all();
    //     $myArray['user_id'] = $this->user_id;
    //     $myQuery = ProjectBudgetController::edit_rab($myArray);
    //     return $myQuery;
    // }

    // public function req_add_rab(Request $request)
    // {
    //     $myArray = [];
    //     $myArray['params'] = $request->all();
    //     $myArray['user_id'] = $this->user_id;
    //     $myQuery = ProjectBudgetController::add_req_rab($myArray);
    //     return $myQuery;
    // }

    public function create_project_budgetary(Request $request)
    {
        $all_request = $request->data;

        DB::beginTransaction();
        try {
            foreach ($all_request as $data) {
                $projectinfo = DB::table('0_projects')->where('project_no', $data['project_no'])->first();

                if ($data['remark'] == null) {
                    $remark =  $projectinfo->name;
                    $ref = 'RAB-' . $data['project_code'] . '-' . $remark;
                    $site_no = 0;
                } else {
                    if (strtotime($data['remark']) !== false) {
                        $remark = $data['remark'];
                        $ref = 'RAB-' . $data['project_code'] . '-' . $remark;
                        $site_no = 0;
                    } else {
                        $project_site = DB::table('0_project_site')->where('site_no', $data['remark'])->first();
                        $ref = 'RAB-' . $data['project_code'] . '-' . $project_site->site_id . '-' . $project_site->name;
                        $site_no = $data['remark'];
                    }
                }

                // $ref = 'RAB-' . $data['project_code'] . '-' . $project_site->site_id . '-' . $project_site->name;
                $risk_management = $data['project_value'] * $data['risk_management_pct'];

                DB::table('0_project_submission_rab')
                    ->insert(array(
                        'reference' => $ref,
                        'project_no' => $data['project_no'],
                        'project_code' => $data['project_code'],
                        'sales_person' => $data['sales_person'],
                        'project_value' => $data['project_value'],
                        'total_budget' => $data['total_budget'],
                        'cost_of_money_permonth' => $data['cost_of_money_permonth'],
                        'cost_of_money_pct' => $data['cost_of_money_pct'],
                        'management_cost' => $data['management_cost'],
                        'management_cost_pct' => $data['management_cost_pct'],
                        'risk_management_pct' => $data['risk_management_pct'],
                        'risk_management' => $risk_management,
                        'debtor_no' => $projectinfo->debtor_no,
                        'area_id' => $projectinfo->area_id,
                        'project_head_id' => $data['project_head_id'],
                        'pc_user_id' => $data['pc_user_id'],
                        'work_start' => $data['work_start'],
                        'work_end' => $data['work_end'],
                        'remark' => $data['remark'],
                        'site_no' => $site_no,
                        'created_at' => Carbon::now(),
                        'created_by' => $this->user_id
                    ));
            }

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

    public function edit_project_budgetary(Request $request, $trans_no)
    {
        // $data = DB::table('0_project_submission_rab')->where('trans_no', $trans_no)->first();
        $all_request = $request->data;
        $trans_no = $request->trans_no;

        DB::beginTransaction();
        try {
            foreach ($all_request as $data) {
                DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                    ->update(array(
                        'management_cost_pct' => $data['management_cost_pct'],
                        'risk_management_pct' => $data['risk_management_pct'],
                        'work_start' => $data['work_start'],
                        'work_end' => $data['work_end'],
                        'updated_at' => Carbon::now(),
                        'updated_by' => $this->user_id
                    ));

                // Commit Transaction
                DB::commit();

                return response()->json([
                    'success' => true
                ]);
            }
        } catch (Exception $e) {
            echo 'Terjadi kesalahan: ' . $e->getMessage();
            // Rollback Transaction
            DB::rollback();
        }
    }

    public function duplicated_project_budgetary(Request $request, $trans_no)
    {
        $all_request = $request->data;

        DB::beginTransaction();
        try {

            foreach ($all_request as $data) {
                $projectinfo = DB::table('0_projects')->where('project_no', $data['project_no'])->first();

                if ($data['remark'] == null) {
                    $remark =  $projectinfo->name;
                    $ref = 'RAB-' . $data['project_code'] . '-' . $remark;
                    $site_no = 0;
                } else {
                    if (strtotime($data['remark']) !== false) {
                        $remark = $data['remark'];
                        $ref = 'RAB-' . $data['project_code'] . '-' . $remark;
                        $site_no = 0;
                    } else {
                        $project_site = DB::table('0_project_site')->where('site_no', $data['remark'])->first();
                        $ref = 'RAB-' . $data['project_code'] . '-' . $project_site->site_id . '-' . $project_site->name;
                        $site_no = $data['remark'];
                    }
                }

                // $ref = 'RAB-' . $data['project_code'] . '-' . $project_site->site_id . '-' . $project_site->name;
                $risk_management = $data['project_value'] * $data['risk_management_pct'];

                $header = DB::table('0_project_submission_rab')
                    ->insertGetId(array(
                        'reference' => $ref,
                        'project_no' => $data['project_no'],
                        'project_code' => $data['project_code'],
                        'sales_person' => $data['sales_person'],
                        'project_value' => $data['project_value'],
                        'total_budget' => $data['total_budget'],
                        'cost_of_money_permonth' => $data['cost_of_money_permonth'],
                        'cost_of_money_pct' => $data['cost_of_money_pct'],
                        'management_cost' => $data['management_cost'],
                        'management_cost_pct' => $data['management_cost_pct'],
                        'risk_management_pct' => $data['risk_management_pct'],
                        'risk_management' => $risk_management,
                        'debtor_no' => $projectinfo->debtor_no,
                        'area_id' => $projectinfo->area_id,
                        'project_head_id' => $data['project_head_id'],
                        'pc_user_id' => $data['pc_user_id'],
                        'work_start' => $data['work_start'],
                        'work_end' => $data['work_end'],
                        'remark' => $data['remark'],
                        'site_no' => $site_no,
                        'created_at' => Carbon::now(),
                        'created_by' => $this->user_id
                    ));
            }

            $cashflow = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->first();
            // clone data parent
            $header_cashflow = DB::table('0_project_submission_cash_flow')
                ->insertGetId(array(
                    'trans_no' => $header,
                    'total_cash_in' => $cashflow->total_cash_in,
                    'total_cash_out' => $cashflow->total_cash_out,
                    'cost_of_money' => $cashflow->cost_of_money,
                    'created_at' => Carbon::now(),
                    'created_by' => $this->user_id
                ));

            // $cashflow_in = DB::table('0_project_submission_cash_flow_in')->where('cash_flow_id', $cashflow->id)->get();
            // foreach ($cashflow_in as $data) {
            //     DB::table('0_project_submission_cash_flow_in')
            //         ->insert(array(
            //             'cash_flow_id' => $header_cashflow,
            //             'item' => $data->item,
            //             'amount' => $data->amount,
            //             'periode' => $data->periode,
            //             'remark' => $data->remark,
            //             'expense_type' => $data->expense_type,
            //         ));
            // }

            // $data_pv = DB::table('0_project_submission_rab_project_value')->where('trans_no', $trans_no)->get();
            // foreach ($data_pv as $data) {
            //     DB::table('0_project_submission_rab_project_value')
            //         ->insert(array(
            //             'trans_no' => $header,
            //             'site_no' => $data->site_no,
            //             'du_id' => $data->du_id,
            //             'sow' => $data->sow,
            //             'qty' => $data->qty,
            //             'price' => $data->price,
            //             'total_amount' =>  $data->total_amount,
            //             'remark' => $data->remark,
            //             'revenue_type' => $data->revenue_type
            //         ));
            // }

            // $sum_project_value = DB::table('0_project_submission_rab_project_value')->where('trans_no', $header)->sum('total_amount');
            // DB::table('0_project_submission_rab')->where('trans_no', $header)
            //     ->update(array(
            //         'project_value' => $sum_project_value
            //     ));

            // clone data child mp
            $data_mp = DB::table('0_project_submission_rab_man_power')->where('trans_no', $trans_no)->get();
            foreach ($data_mp as $data) {

                $id_mp = DB::table('0_project_submission_rab_man_power')
                    ->insertGetId(array(
                        'trans_no' => $header,
                        'type_category' => $data->type_category,
                        'position_id' => $data->position_id,
                        'emp_id' => $data->emp_id,
                        'qty' => $data->qty,
                        'amount' => $data->amount,
                        'percentage' => $data->percentage,
                        'duration' => $data->duration,
                        'duration_type' => $data->duration_type,
                        'total_amount' => $data->total_amount,
                        'remark' => $data->remark
                    ));

                $cashflow_out = DB::table('0_project_submission_cash_flow_out')->where('rab_category',  2)->where('detail_id',  $data->id)->get();
                foreach ($cashflow_out as $data) {
                    DB::table('0_project_submission_cash_flow_out')
                        ->insert(array(
                            'cash_flow_id' => $header_cashflow,
                            'cost_type_group_id' => $data->cost_type_group_id,
                            'rab_category' => $data->rab_category,
                            'detail_id' => $id_mp,
                            'amount' => $data->amount,
                            'periode' => $data->periode
                        ));
                }
            }

            $data_vhc = DB::table('0_project_submission_rab_vehicle_ops')->where('trans_no', $trans_no)->get();
            foreach ($data_vhc as $data) {
                $id_vhc = DB::table('0_project_submission_rab_vehicle_ops')
                    ->insertGetId(array(
                        'trans_no' => $header,
                        'type_category' => $data->type_category,
                        'qty' => $data->qty,
                        'amount' => $data->amount,
                        'duration' => $data->duration,
                        'duration_type' => $data->duration_type,
                        'total_amount' => $data->total_amount,
                        'remark' => $data->remark
                    ));

                $cashflow_out = DB::table('0_project_submission_cash_flow_out')->where('rab_category',  3)->where('detail_id',  $data->id)->get();
                foreach ($cashflow_out as $data) {
                    DB::table('0_project_submission_cash_flow_out')
                        ->insert(array(
                            'cash_flow_id' => $header_cashflow,
                            'cost_type_group_id' => $data->cost_type_group_id,
                            'rab_category' => $data->rab_category,
                            'detail_id' => $id_vhc,
                            'amount' => $data->amount,
                            'periode' => $data->periode
                        ));
                }
            }


            $data_pr = DB::table('0_project_submission_rab_procurement')->where('trans_no', $trans_no)->get();
            foreach ($data_pr as $data) {
                $id_pr = DB::table('0_project_submission_rab_procurement')
                    ->insertGetId(array(
                        'trans_no' => $header,
                        'type_category' => $data->type_category,
                        'description' => $data->description,
                        'qty' => $data->qty,
                        'uom' => $data->uom,
                        'price' => $data->price,
                        'total_amount' => $data->total_amount,
                        'remark' => $data->remark
                    ));

                $cashflow_out = DB::table('0_project_submission_cash_flow_out')->where('rab_category',  4)->where('detail_id',  $data->id)->get();
                foreach ($cashflow_out as $data) {
                    DB::table('0_project_submission_cash_flow_out')
                        ->insert(array(
                            'cash_flow_id' => $header_cashflow,
                            'cost_type_group_id' => $data->cost_type_group_id,
                            'rab_category' => $data->rab_category,
                            'detail_id' => $id_pr,
                            'amount' => $data->amount,
                            'periode' => $data->periode
                        ));
                }
            }

            $data_tl = DB::table('0_project_submission_rab_tools')->where('trans_no', $trans_no)->get();
            foreach ($data_tl as $data) {
                $id_tl = DB::table('0_project_submission_rab_tools')
                    ->insertGetId(array(
                        'trans_no' => $header,
                        'type_category' => $data->type_category,
                        'description' => $data->description,
                        'qty' => $data->qty,
                        'uom' => $data->uom,
                        'amount' => $data->amount,
                        'duration' => $data->duration,
                        'duration_type' => $data->duration_type,
                        'total_amount' => $data->total_amount,
                        'remark' => $data->remark
                    ));

                $cashflow_out = DB::table('0_project_submission_cash_flow_out')->where('rab_category',  5)->where('detail_id',  $data->id)->get();
                foreach ($cashflow_out as $data) {
                    DB::table('0_project_submission_cash_flow_out')
                        ->insert(array(
                            'cash_flow_id' => $header_cashflow,
                            'cost_type_group_id' => $data->cost_type_group_id,
                            'rab_category' => $data->rab_category,
                            'detail_id' => $id_tl,
                            'amount' => $data->amount,
                            'periode' => $data->periode
                        ));
                }
            }

            $data_tr = DB::table('0_project_submission_rab_training')->where('trans_no', $trans_no)->get();
            foreach ($data_tr as $data) {
                $id_tr = DB::table('0_project_submission_rab_training')
                    ->insertGetId(array(
                        'trans_no' => $header,
                        'type_category' => $data->type_category,
                        'position' => $data->position,
                        'qty' => $data->qty,
                        'price' => $data->price,
                        'total_amount' => $data->total_amount,
                        'remark' => $data->remark
                    ));

                $cashflow_out = DB::table('0_project_submission_cash_flow_out')->where('rab_category',  6)->where('detail_id',  $data->id)->get();
                foreach ($cashflow_out as $data) {
                    DB::table('0_project_submission_cash_flow_out')
                        ->insert(array(
                            'cash_flow_id' => $header_cashflow,
                            'cost_type_group_id' => $data->cost_type_group_id,
                            'rab_category' => $data->rab_category,
                            'detail_id' => $id_tr,
                            'amount' => $data->amount,
                            'periode' => $data->periode
                        ));
                }
            }

            $data_exps = DB::table('0_project_submission_rab_other_expenses')->where('trans_no', $trans_no)->get();
            foreach ($data_exps as $data) {
                $id_exps = DB::table('0_project_submission_rab_other_expenses')
                    ->insertGetId(array(
                        'trans_no' => $header,
                        'type_category' => $data->type_category,
                        'qty' => $data->qty,
                        'amount' => $data->amount,
                        'total_amount' => $data->total_amount,
                        'remark' => $data->remark
                    ));

                $cashflow_out = DB::table('0_project_submission_cash_flow_out')->where('rab_category',  7)->where('detail_id',  $data->id)->get();
                foreach ($cashflow_out as $data) {
                    DB::table('0_project_submission_cash_flow_out')
                        ->insert(array(
                            'cash_flow_id' => $header_cashflow,
                            'cost_type_group_id' => $data->cost_type_group_id,
                            'rab_category' => $data->rab_category,
                            'detail_id' => $id_exps,
                            'amount' => $data->amount,
                            'periode' => $data->periode
                        ));
                }
            }

            $data_inf = DB::table('0_project_submission_rab_other_information')->where('trans_no', $trans_no)->get();
            foreach ($data_inf as $data) {
                $id_inf = DB::table('0_project_submission_rab_other_information')
                    ->insertGetId(array(
                        'trans_no' => $header,
                        'type_category' => $data->type_category,
                        'qty' => $data->qty,
                        'amount' => $data->amount,
                        'total_amount' => $data->total_amount,
                        'remark' => $data->remark
                    ));

                $cashflow_out = DB::table('0_project_submission_cash_flow_out')->where('rab_category',  8)->where('detail_id',  $data->id)->get();
                foreach ($cashflow_out as $data) {
                    DB::table('0_project_submission_cash_flow_out')
                        ->insert(array(
                            'cash_flow_id' => $header_cashflow,
                            'cost_type_group_id' => $data->cost_type_group_id,
                            'rab_category' => $data->rab_category,
                            'detail_id' => $id_inf,
                            'amount' => $data->amount,
                            'periode' => $data->periode
                        ));
                }
            }

            $sum_cost = DB::table('0_project_submission_cash_flow_out')->where('cash_flow_id', $header_cashflow)->sum('amount');
            DB::table('0_project_submission_rab')->where('trans_no', $header)
                ->update(array(
                    'total_budget' => $sum_cost
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

    public function get_rab_bytrans_no(Request $request)
    {

        $response = null;
        $project_no = $request->project_no;
        $reference = $request->reference;
        $trans_no = $request->trans_no;

        try {

            $data =  DB::table('0_project_submission_rab as rab')
                ->leftJoin('0_project_submission_cash_flow as cashflow', 'cashflow.trans_no', '=', 'rab.trans_no')
                ->leftJoin('0_projects as p', 'p.project_no', '=', 'rab.project_no')
                ->leftJoin('0_hrm_divisions as d', 'd.division_id', '=', 'p.division_id')
                ->leftJoin('0_project_area as pa', 'pa.area_id', '=', 'rab.area_id')
                ->leftJoin('0_members as m', 'm.person_id', '=', 'p.person_id')
                ->leftJoin('0_members as head', 'head.person_id', '=', 'rab.project_head_id')
                ->leftJoin('0_debtors_master as dt', 'dt.debtor_no', '=', 'rab.debtor_no')
                ->leftJoin('0_hrm_employees as e', 'e.id', '=', 'rab.pc_user_id')
                ->leftJoin('users as u', 'u.id', '=', 'rab.created_by')
                ->select(
                    'rab.trans_no',
                    'rab.parent',
                    'rab.revision',
                    'rab.reference',
                    'rab.project_no',
                    'rab.project_code',
                    'p.name as project_name',
                    'm.name as project_manager',
                    'd.division_id',
                    'd.name as division_name',
                    'rab.sales_person',
                    'rab.project_value',
                    'rab.total_budget',
                    'rab.management_cost_pct',
                    'rab.cost_of_money_pct',
                    'rab.risk_management_pct',
                    'rab.risk_management',
                    'rab.debtor_no',
                    'dt.name as customer_name',
                    'rab.area_id',
                    'pa.name as area_name',
                    'head.name as project_head_name',
                    'e.name as pc_user_name',
                    'rab.work_start',
                    'rab.work_end',
                    'rab.remark',
                    'rab.approval',
                    DB::raw("CASE
                    WHEN rab.approval = 0 THEN 'New'
                    WHEN rab.approval = 1 THEN 'DGM'
                    WHEN rab.approval = 3 THEN 'GM'
                    WHEN rab.approval = 4 THEN 'BPC & PMO'
                    WHEN rab.approval = 42 THEN 'Director Ops.'
                    WHEN rab.approval = 41 THEN 'Director'
                    WHEN rab.approval = 7 THEN 'Approved' END AS approval_name"),
                    DB::raw("CASE
                    WHEN rab.status_id = 0 THEN 'Open'
                    WHEN rab.status_id = 1 THEN 'Approve'
                    WHEN rab.status_id = 2 THEN 'Pending'
                    WHEN rab.status_id = 3 THEN 'Disapprove' END AS status_name"),
                    'rab.status_id',
                    'rab.created_at',
                    'cashflow.id as cashflow_id',
                    'u.name as created_by',
                    'rab.created_by as creator_id'
                )
                ->when($project_no != '', function ($query) use ($project_no) {
                    $query->where('rab.project_no', $project_no);
                })
                ->when($reference != '', function ($query) use ($reference) {
                    $query->where('rab.reference', $reference);
                })
                ->where('rab.trans_no', $trans_no)
                ->get();

            $pm = DB::table('0_project_submission_rab_logs AS aprv')
                ->leftJoin('users AS u', 'u.id', '=', 'aprv.created_by')
                ->where('aprv.trans_no', $trans_no)->where('aprv.approval', 0)
                ->select(
                    'aprv.created_at AS date',
                    'u.name',
                    'aprv.remark',
                    'u.signature AS signature_exist',
                    DB::raw("CONCAT('http://192.168.0.5/storage/profiles/signature/',u.id,'.png') AS signature")
                )
                ->orderBy('aprv.id', 'desc')
                ->limit(1)
                ->get();

            foreach ($pm as $val => $key) {
                if ($key->signature_exist == 0) {
                    return response()->json([
                        'error' => array(
                            'message' => $key->name . ' ' . "hasn't added a signature!",
                            'status_code' => 403
                        )
                    ], 403);
                }
            }

            $dgm = DB::table('0_project_submission_rab_logs AS aprv')
                ->leftJoin('users AS u', 'u.id', '=', 'aprv.created_by')
                ->where('aprv.trans_no', $trans_no)->where('aprv.approval', 1)
                ->select(
                    'aprv.created_at AS date',
                    'u.name',
                    'aprv.remark',
                    'u.signature AS signature_exist',
                    DB::raw("CONCAT('http://192.168.0.5/storage/profiles/signature/',u.id,'.png') AS signature")
                )
                ->orderBy('aprv.id', 'desc')
                ->limit(1)
                ->get();

            foreach ($dgm as $val => $key) {
                if ($key->signature_exist == 0) {
                    return response()->json([
                        'error' => array(
                            'message' => $key->name . ' ' . "hasn't added a signature!",
                            'status_code' => 403
                        )
                    ], 403);
                }
            }

            $gm = DB::table('0_project_submission_rab_logs AS aprv')
                ->leftJoin('users AS u', 'u.id', '=', 'aprv.created_by')
                ->where('aprv.trans_no', $trans_no)->where('aprv.approval', 3)
                ->select(
                    'aprv.created_at AS date',
                    'u.name',
                    'aprv.remark',
                    'u.signature AS signature_exist',
                    DB::raw("CONCAT('http://192.168.0.5/storage/profiles/signature/',u.id,'.png') AS signature")
                )
                ->orderBy('aprv.id', 'desc')
                ->limit(1)
                ->get();

            foreach ($gm as $val => $key) {
                if ($key->signature_exist == 0) {
                    return response()->json([
                        'error' => array(
                            'message' => $key->name . ' ' . "hasn't added a signature!",
                            'status_code' => 403
                        )
                    ], 403);
                }
            }

            $bpc = DB::table('0_project_submission_rab_logs AS aprv')
                ->leftJoin('users AS u', 'u.id', '=', 'aprv.created_by')
                ->where('aprv.trans_no', $trans_no)->where('aprv.approval', 4)
                ->select(
                    'aprv.created_at AS date',
                    'u.name',
                    'aprv.remark',
                    'u.signature AS signature_exist',
                    DB::raw("CONCAT('http://192.168.0.5/storage/profiles/signature/',u.id,'.png') AS signature")
                )
                ->orderBy('aprv.id', 'desc')
                ->limit(1)
                ->get();

            foreach ($bpc as $val => $key) {
                if ($key->signature_exist == 0) {
                    return response()->json([
                        'error' => array(
                            'message' => $key->name . ' ' . "hasn't added a signature!",
                            'status_code' => 403
                        )
                    ], 403);
                }
            }

            // $bpc_head = DB::table('0_project_submission_rab_logs AS aprv')
            //     ->leftJoin('users AS u', 'u.id', '=', 'aprv.created_by')
            //     ->where('aprv.trans_no', $trans_no)->where('aprv.approval', 5)
            //     ->select(
            //         'aprv.created_at AS date',
            //         'u.name',
            //         'aprv.remark',
            //         'u.signature AS signature_exist',
            //         DB::raw("CONCAT('http://192.168.0.5/storage/profiles/signature/',u.id,'.png') AS signature")
            //     )
            //     ->orderBy('aprv.id', 'desc')
            //     ->limit(1)
            //     ->get();

            // foreach ($bpc_head as $val => $key) {
            //     if ($key->signature_exist == 0) {
            //         return response()->json([
            //             'error' => array(
            //                 'message' => $key->name . ' ' . "hasn't added a signature!",
            //                 'status_code' => 403
            //             )
            //         ], 403);
            //     }
            // }

            $dirops = DB::table('0_project_submission_rab_logs AS aprv')
                ->leftJoin('users AS u', 'u.id', '=', 'aprv.created_by')
                ->where('aprv.trans_no', $trans_no)->where('aprv.approval', 42)
                ->select(
                    'aprv.created_at AS date',
                    'u.name',
                    'aprv.remark',
                    'u.signature AS signature_exist',
                    DB::raw("CONCAT('http://192.168.0.5/storage/profiles/signature/',u.id,'.png') AS signature")
                )
                ->orderBy('aprv.id', 'desc')
                ->limit(1)
                ->get();

            foreach ($dirops as $val => $key) {
                if ($key->signature_exist == 0) {
                    return response()->json([
                        'error' => array(
                            'message' => $key->name . ' ' . "hasn't added a signature!",
                            'status_code' => 403
                        )
                    ], 403);
                }
            }

            $dirut = DB::table('0_project_submission_rab_logs AS aprv')
                ->leftJoin('users AS u', 'u.id', '=', 'aprv.created_by')
                ->where('aprv.trans_no', $trans_no)->where('aprv.approval', 41)
                ->select(
                    'aprv.created_at AS date',
                    'u.name',
                    'aprv.remark',
                    'u.signature AS signature_exist',
                    DB::raw("CONCAT('http://192.168.0.5/storage/profiles/signature/',u.id,'.png') AS signature")
                )
                ->orderBy('aprv.id', 'desc')
                ->limit(1)
                ->get();

            foreach ($dirut as $val => $key) {
                if ($key->signature_exist == 0) {
                    return response()->json([
                        'error' => array(
                            'message' => $key->name . ' ' . "hasn't added a signature!",
                            'status_code' => 403
                        )
                    ], 403);
                }
            }

            foreach ($data as $val) {
                $risk_management = $val->project_value * $val->risk_management_pct;
                $management_cost = ($val->management_cost_pct * $val->project_value); /* project value * management cost*/
                $cost_of_money = self::calculated_interest($val->cashflow_id, $val->trans_no, $val->work_start);
                $total_expense =  $cost_of_money + $val->total_budget + $management_cost + $risk_management;  /* (total budget + management cost + cost of money)*/
                $margin_value = $val->project_value - ($val->total_budget + $management_cost + $risk_management + $cost_of_money); /* project value - (total budget + management cost + cost of money)*/
                // $margin_value = $val->project_value - $total_expense; /* (project value - total expense) ferry by excel*/
                $margin_pct = empty($margin_value) || $margin_value <= 0  ? 0 : ($margin_value / $val->project_value) * 100;

                $total_sales_internal = DB::table('0_project_submission_rab_project_value')->where('trans_no', $val->trans_no)->where('revenue_type', 1)->sum('total_amount');
                $total_sales_external = DB::table('0_project_submission_rab_project_value')->where('trans_no', $val->trans_no)->where('revenue_type', 2)->sum('total_amount');
                /* project value * management cost*/
                $management_cost_internal = $val->management_cost_pct * $total_sales_internal;
                $management_cost_external = $val->management_cost_pct * $total_sales_external;

                // /* (total budget + management cost + cost of money)*/ total_expenses_internal 
                $cost_of_money_internal = self::calculated_interest_internal($val->cashflow_id, $val->work_start);
                $cost_internal = DB::table('0_project_submission_cash_flow as cf')
                    ->leftJoin('0_project_submission_cash_flow_out AS cfout', 'cf.id', '=', 'cfout.cash_flow_id')
                    ->where('cf.trans_no', $val->trans_no)
                    ->where('cfout.expense_type', 1)
                    ->sum('cfout.amount');
                $total_expenses_internal = $cost_of_money_internal + $cost_internal + $management_cost_internal + 0;

                // /* (total budget + management cost + cost of money)*/ total_expenses_external
                $cost_of_money_external = self::calculated_interest_external($val->cashflow_id, $val->work_start);
                $cost_external = DB::table('0_project_submission_cash_flow as cf')
                    ->leftJoin('0_project_submission_cash_flow_out AS cfout', 'cf.id', '=', 'cfout.cash_flow_id')
                    ->where('cf.trans_no', $val->trans_no)
                    ->where('cfout.expense_type', 2)
                    ->sum('cfout.amount');
                $total_expenses_external = $cost_of_money_external + $cost_external + $management_cost_external + 0;

                /* project value - total expenses*/
                $margin_value_internal = $total_sales_internal - ($cost_internal + $management_cost_internal + 0);
                $margin_value_external = $total_sales_external - ($cost_external + $management_cost_external + 0);

                $margin_pct_internal = empty($margin_value_internal) || $margin_value_internal <= 0 ? 0 : ($margin_value_internal / $total_sales_internal) * 100;
                $margin_pct_external = empty($margin_value_external) || $margin_value_external <= 0 ? 0 : ($margin_value_external / $total_sales_external) * 100;

                $interestPct = ProjectBudgetController::get_interest_rab_val($val->work_start);

                $header = [];
                $header['trans_no'] = $val->trans_no;
                $header['parent'] = $val->parent;
                $header['revision'] = $val->revision;
                $header['reference'] = $val->reference;
                $header['project_no'] = $val->project_no;
                $header['project_code'] = $val->project_code;
                $header['project_name'] = $val->project_name;
                $header['project_manager'] = $val->project_manager;
                $header['division_id'] = $val->division_id;
                $header['division_name'] = $val->division_name;
                $header['sales_person'] = $val->sales_person;
                $header['project_value'] = $val->project_value;
                $header['total_budget'] = $val->total_budget;
                $header['management_cost_pct'] = $val->management_cost_pct;
                $header['management_cost'] = $management_cost;
                $header['management_cost_internal'] = $management_cost_internal;
                $header['management_cost_external'] = $management_cost_external;
                $header['cost_of_money_permonth'] = $cost_of_money;
                $header['cost_of_money_internal'] = $cost_of_money_internal;
                $header['cost_of_money_external'] = $cost_of_money_external;
                $header['cost_of_money_pct'] = $interestPct;
                $header['risk_management_pct'] = $val->risk_management_pct;
                $header['risk_management'] = $risk_management;
                $header['total_sales'] = $val->project_value;
                $header['total_sales_internal'] = $total_sales_internal;
                $header['total_sales_external'] = $total_sales_external;
                $header['total_expenses'] = $total_expense;
                $header['total_expenses_internal'] = $total_expenses_internal;
                $header['total_expenses_external'] = $total_expenses_external;
                $header['margin_pct'] = $margin_pct;
                $header['margin_pct_internal'] = $margin_pct_internal;
                $header['margin_pct_external'] = $margin_pct_external;
                $header['margin_value'] = $margin_value;
                $header['margin_value_internal'] = $margin_value_internal;
                $header['margin_value_external'] = $margin_value_external;
                $header['debtor_no'] = $val->debtor_no;
                $header['customer'] = $val->customer_name;
                $header['area_name'] = $val->area_name;
                $header['project_head_name'] = $val->project_head_name;
                $header['pc_user_name'] = $val->pc_user_name;
                $header['work_start'] = $val->work_start;
                $header['work_end'] = $val->work_end;
                $header['remark'] = $val->remark;
                $header['approval'] = $val->approval;
                $header['approval_name'] = $val->approval_name;
                $header['status_id'] = $val->status_id;
                $header['status_name'] = $val->status_name;
                $header['created_at'] = $val->created_at;
                $header['created_by'] = $val->created_by;

                $header['man_power'] = DB::table('0_project_submission_rab_man_power as rab')
                    ->leftJoin('0_project_position_rab as pct', 'pct.id', '=', 'rab.position_id')
                    ->leftJoin('0_hrm_employees as emp', 'emp.emp_id', '=', 'rab.emp_id')
                    ->where('trans_no', $val->trans_no)
                    ->select('rab.*', 'pct.position', 'emp.name')->get();
                $header['vehicle'] = DB::table('0_project_submission_rab_vehicle_ops as rab')
                    ->leftJoin('0_project_cost_type_group as pct', 'pct.cost_type_group_id', '=', 'rab.type_category')
                    ->where('rab.trans_no', $val->trans_no)
                    ->select('rab.*', 'pct.name as type_name')->get();
                $header['procurement'] = DB::table('0_project_submission_rab_procurement as rab')
                    ->leftJoin('0_project_cost_type_group as pct', 'pct.cost_type_group_id', '=', 'rab.type_category')
                    ->where('rab.trans_no', $val->trans_no)
                    ->select('rab.*', 'pct.name as type_name')->get();
                $header['tools'] = DB::table('0_project_submission_rab_tools as rab')
                    ->leftJoin('0_project_cost_type_group as pct', 'pct.cost_type_group_id', '=', 'rab.type_category')
                    ->where('rab.trans_no', $val->trans_no)
                    ->select('rab.*', 'pct.name as type_name')->get();
                $header['training'] = DB::table('0_project_submission_rab_training as rab')
                    ->leftJoin('0_project_cost_type_group as pct', 'pct.cost_type_group_id', '=', 'rab.type_category')
                    ->where('rab.trans_no', $val->trans_no)
                    ->select('rab.*', 'pct.name as type_name')->get();

                $header['other_expenses'] = DB::table('0_project_submission_rab_other_expenses as rab')
                    ->leftJoin('0_project_cost_type_group as pct', 'pct.cost_type_group_id', '=', 'rab.type_category')
                    ->where('trans_no', $val->trans_no)
                    ->select('rab.*', 'pct.name as type_name')->get();

                $header['other_info'] = DB::table('0_project_submission_rab_other_information as rab')
                    ->leftJoin('0_project_cost_type_group as pct', 'pct.cost_type_group_id', '=', 'rab.type_category')
                    ->where('trans_no', $val->trans_no)
                    ->select('rab.*', 'pct.name as type_name')->get();

                $header['project_value_list'] = DB::table('0_project_submission_rab_project_value as rab')
                    ->leftJoin('0_project_site as pct', 'pct.site_no', '=', 'rab.site_no')
                    ->where('trans_no', $val->trans_no)
                    ->select('rab.*', 'pct.name as site_name')->get();

                $header['sign_pm'] = $pm;
                $header['sign_dgm'] = $dgm;
                $header['sign_gm'] = $gm;
                $header['sign_bpc'] = $bpc;
                // $header['sign_bpc_head'] = $bpc_head;
                $header['sign_dirops'] = $dirops;
                $header['sign_dirut'] = $dirut;

                //array_push($response, $header); 
                $response = $header;
            }

            return response()->json([
                'success' => true,
                'data' => $response
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => array(
                    'message' => $e,
                    'status_code' => 500
                )
            ], 500);
        }
    }
    public function submit_approval_rab(Request $request, $trans_no)
    {
        $remark = $request->remark;

        DB::beginTransaction();
        try {
            $rab_info = DB::table('0_project_submission_rab')->where('trans_no', $trans_no)->first();

            $cashinxworkstart = DB::table('0_project_submission_cash_flow as cf')
                ->leftJoin('0_project_submission_cash_flow_in as cfin', 'cf.id', '=', 'cfin.cash_flow_id')
                ->where('trans_no', $trans_no)
                ->select('cfin.periode as periode_cash_in')
                ->orderBy('cfin.periode', 'asc')
                ->first();

            if ($rab_info->work_start > $cashinxworkstart->periode_cash_in) {
                return response()->json([
                    'error' => array(
                        'message' => "tanggal work start tidak boleh lebih besar dari tanggal cash in",
                        'status_code' => 500
                    )
                ], 500);
            }

            $cashinxworkend = DB::table('0_project_submission_cash_flow as cf')
                ->leftJoin('0_project_submission_cash_flow_in as cfin', 'cf.id', '=', 'cfin.cash_flow_id')
                ->where('trans_no', $trans_no)
                ->select('cfin.periode as periode_cash_in')
                ->orderBy('cfin.periode', 'desc')
                ->first();

            if ($cashinxworkend->periode_cash_in > $rab_info->work_end) {
                return response()->json([
                    'error' => array(
                        'message' => "tanggal cash in tidak boleh lebih besar dari tanggal work end",
                        'status_code' => 500
                    )
                ], 500);
            }

            $cashoutxworkend = DB::table('0_project_submission_cash_flow as cf')
                ->leftJoin('0_project_submission_cash_flow_out as cfout', 'cf.id', '=', 'cfout.cash_flow_id')
                ->where('trans_no', $trans_no)
                ->select('cfout.periode as periode_cash_out')
                ->orderBy('cfout.periode', 'desc')
                ->first();

            if ($rab_info->work_end < $cashoutxworkend->periode_cash_out) {
                return response()->json([
                    'error' => array(
                        'message' => "tanggal work end tidak boleh lebih kecil dari tanggal cash out",
                        'status_code' => 500
                    )
                ], 500);
            }

            $cashoutxworkstart = DB::table('0_project_submission_cash_flow as cf')
                ->leftJoin('0_project_submission_cash_flow_out as cfout', 'cf.id', '=', 'cfout.cash_flow_id')
                ->where('trans_no', $trans_no)
                ->select('cfout.periode as periode_cash_out')
                ->orderBy('cfout.periode', 'asc')
                ->first();

            if ($cashoutxworkstart->periode_cash_out < $rab_info->work_start) {
                return response()->json([
                    'error' => array(
                        'message' => "tanggal cash out tidak boleh lebih kecil dari tanggal work start",
                        'status_code' => 500
                    )
                ], 500);
            }

            /**
             * NEW ROUTE FOR (TSS WIRELESS & TSS NON WL) PIC DGM
             */


            $registered_division = array(2); // now only TSS Wireless, before array(2, 24)

            $rabinfo = DB::table('0_project_submission_rab')->where('trans_no', $trans_no)->first();
            $project_info = DB::table('0_projects')->where('project_no', $rabinfo->project_no)->first();
            if (in_array($project_info->division_id, $registered_division)) {
                $next = 1;
            } else {
                $next = 3;
            }
            DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                ->update(array(
                    'approval' => $next,
                    'status_id' => 1
                ));

            DB::table('0_project_submission_rab_logs')
                ->insert(array(
                    'trans_no' => $trans_no,
                    'approval' => 0,
                    'status_id' => 1,
                    'remark' => $remark,
                    'created_at' => Carbon::now(),
                    'created_by' =>  $this->user_id
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
    public function get_rab_list(Request $request)
    {

        $response = [];
        $project_no = $request->project_no;
        $search = $request->search;
        $user_id = $this->user_id;
        $approval_user = $this->user_level;
        try {

            $data =  DB::table('0_project_submission_rab as rab')
                ->leftJoin('0_project_submission_cash_flow as cashflow', 'cashflow.trans_no', '=', 'rab.trans_no')
                ->leftJoin('0_projects as p', 'p.project_no', '=', 'rab.project_no')
                ->leftJoin('0_hrm_divisions as d', 'd.division_id', '=', 'p.division_id')
                ->leftJoin('0_project_area as pa', 'pa.area_id', '=', 'rab.area_id')
                ->leftJoin('0_members as m', 'm.person_id', '=', 'p.person_id')
                ->leftJoin('0_members as head', 'head.person_id', '=', 'rab.project_head_id')
                ->leftJoin('0_debtors_master as dt', 'dt.debtor_no', '=', 'rab.debtor_no')
                ->leftJoin('0_hrm_employees as e', 'e.id', '=', 'rab.pc_user_id')
                ->leftJoin('users as u', 'u.id', '=', 'rab.created_by')
                ->select(
                    'rab.trans_no',
                    'rab.reference',
                    'rab.project_no',
                    'rab.project_code',
                    'p.name as project_name',
                    'm.name as project_manager',
                    'd.name as division_name',
                    'rab.sales_person',
                    'rab.project_value',
                    'rab.total_budget',
                    'rab.management_cost_pct',
                    'rab.cost_of_money_pct',
                    'rab.risk_management_pct',
                    'rab.risk_management',
                    'rab.debtor_no',
                    'dt.name as customer_name',
                    'rab.area_id',
                    'pa.name as area_name',
                    'rab.project_head_id',
                    'head.name as project_head_name',
                    'rab.pc_user_id',
                    'e.name as pc_user_name',
                    'rab.work_start',
                    'rab.work_end',
                    'rab.remark',
                    'rab.approval',
                    DB::raw("CASE
                    WHEN rab.approval = 0 THEN 'New'
                    WHEN rab.approval = 1 THEN 'DGM'
                    WHEN rab.approval = 3 THEN 'GM'
                    WHEN rab.approval = 4 THEN 'BPC & PMO'
                    WHEN rab.approval = 42 THEN 'Director Ops.'
                    WHEN rab.approval = 41 THEN 'Director'
                    WHEN rab.approval = 7 THEN 'Approved' END AS approval_name"),
                    DB::raw("CASE
                    WHEN rab.status_id = 0 THEN 'Open'
                    WHEN rab.status_id = 1 THEN 'Approve'
                    WHEN rab.status_id = 2 THEN 'Pending'
                    WHEN rab.status_id = 3 THEN 'Disapprove' END AS status_name"),
                    'rab.status_id',
                    'rab.created_at',
                    'rab.parent',
                    'rab.revision',
                    'rab.reject_history',
                    'cashflow.id as cashflow_id',
                    'u.name as created_by'
                )
                ->when($search != '', function ($query) use ($search) {
                    $query->where('rab.project_code', 'LIKE', '%' . $search . '%');
                    $query->orWhere('rab.reference', 'LIKE', '%' . $search . '%');
                    $query->orWhere('rab.remark', 'LIKE', '%' . $search . '%');
                })
                ->when($approval_user == 1, function ($query) use ($user_id) {
                    $query->where('rab.created_by', $user_id);
                })
                ->orderBy('revision', 'desc')
                ->get();

            foreach ($data as $val) {

                $other_cost = DB::table('0_project_budgets')->where('project_no', $val->project_no)->where('rab_no', $val->trans_no)->whereIn('budget_type_id', [708, 709, 710])->sum('rab_amount');
                $cost = empty($other_cost) ? 0 : $other_cost;

                $header = [];
                $header['trans_no'] = $val->trans_no;
                $header['reference'] = $val->reference;
                $header['project_no'] = $val->project_no;
                $header['project_code'] = $val->project_code;
                $header['project_name'] = $val->project_name;
                $header['project_manager'] = $val->project_manager;
                $header['division_name'] = $val->division_name;
                $header['sales_person'] = $val->sales_person;
                $header['project_value'] = $val->project_value;
                $header['total_budget'] = $val->total_budget + $cost;
                $header['customer'] = $val->customer_name;
                $header['area_name'] = $val->area_name;
                $header['project_head_id'] = $val->project_head_id;
                $header['project_head_name'] = $val->project_head_name;
                $header['pc_user_id'] = $val->pc_user_id;
                $header['pc_user_name'] = $val->pc_user_name;
                $header['risk_management_pct'] = $val->risk_management_pct;
                $header['management_cost_pct'] = $val->management_cost_pct;
                $header['work_start'] = $val->work_start;
                $header['work_end'] = $val->work_end;
                $header['remark'] = $val->remark;
                $header['approval'] = $val->approval;
                $header['approval_name'] = $val->approval_name;
                $header['status_id'] = $val->status_id;
                $header['status_name'] = $val->status_name;
                $header['created_at'] = $val->created_at;
                $header['created_by'] = $val->created_by;
                $header['parent'] = $val->parent;
                $header['revision'] = $val->revision;
                $header['reject_history'] = $val->reject_history;
                array_push($response, $header);
            }

            return response()->json([
                'success' => true,
                'data' => $response
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }
    }

    public function need_approval_project_budgetary(Request $request)
    {

        $response = [];
        $project_no = $request->project_no;
        $reference = $request->reference;
        $approval_user = $this->user_level;
        try {

            $data =  DB::table('0_project_submission_rab as rab')
                ->leftJoin('0_project_submission_cash_flow as cashflow', 'cashflow.trans_no', '=', 'rab.trans_no')
                ->leftJoin('0_projects as p', 'p.project_no', '=', 'rab.project_no')
                ->leftJoin('0_hrm_divisions as d', 'd.division_id', '=', 'p.division_id')
                ->leftJoin('0_project_area as pa', 'pa.area_id', '=', 'rab.area_id')
                ->leftJoin('0_members as m', 'm.person_id', '=', 'p.person_id')
                ->leftJoin('0_members as head', 'head.person_id', '=', 'rab.project_head_id')
                ->leftJoin('0_debtors_master as dt', 'dt.debtor_no', '=', 'rab.debtor_no')
                ->leftJoin('0_hrm_employees as e', 'e.id', '=', 'rab.pc_user_id')
                ->leftJoin('users as u', 'u.id', '=', 'rab.created_by')
                ->select(
                    'rab.trans_no',
                    'rab.reference',
                    'rab.project_no',
                    'rab.project_code',
                    'p.name as project_name',
                    'm.name as project_manager',
                    'd.name as division_name',
                    'rab.sales_person',
                    'rab.project_value',
                    'rab.total_budget',
                    'rab.management_cost_pct',
                    'rab.cost_of_money_pct',
                    'rab.risk_management_pct',
                    'rab.risk_management',
                    'rab.debtor_no',
                    'dt.name as customer_name',
                    'rab.area_id',
                    'pa.name as area_name',
                    'head.name as project_head_name',
                    'e.name as pc_user_name',
                    'rab.work_start',
                    'rab.work_end',
                    'rab.remark',
                    'rab.created_at',
                    'cashflow.id as cashflow_id',
                    'u.name as created_by'
                )
                ->when($project_no != '', function ($query) use ($project_no) {
                    $query->where('rab.project_no', $project_no);
                })
                ->when($reference != '', function ($query) use ($reference) {
                    $query->where('rab.reference', $reference);
                })
                ->when($approval_user != 0, function ($query) use ($approval_user) {
                    $user_id = $this->user_old_id;
                    $person_id = $this->user_person_id;

                    if ($approval_user == 2) {
                        $query->whereRaw("rab.approval = 1 AND p.division_id IN (SELECT division_id FROM 0_user_dept WHERE user_id=$user_id)"); //DGM
                    } else if ($approval_user == 3) {
                        $query->whereRaw("rab.approval = 3 AND p.division_id IN (SELECT division_id FROM 0_user_divisions WHERE user_id=$user_id)"); //GM
                    } else if ($approval_user == 4 && $person_id == 0) {
                        $query->whereRaw("rab.approval = 4 AND p.division_id IN 
                            (
                                SELECT division_id FROM 0_user_project_control 
                            WHERE user_id=$user_id
                            )"); // BPC
                    } else if ($approval_user == 4 && $person_id > 0) { // Pak Moe (project value > 100jt)
                        // old query
                        // $query->whereRaw('rab.approval = 5');

                        // new query
                        $query->whereRaw("rab.approval = 4 AND p.division_id IN 
                            (
                                SELECT division_id FROM 0_user_project_control 
                            WHERE user_id=$user_id
                            )");
                    } else if ($approval_user == 41) {
                        $query->whereRaw('rab.approval = 41'); // DIRUT
                        // $query->whereRaw('rab.approval NOT IN (7,0,8)'); // DIRUT NEW CANCEL
                    } else if ($approval_user == 42) {
                        $query->whereRaw('rab.approval = 42'); // DIR OPS
                    } else if ($approval_user == 999) {
                        $query->whereRaw('rab.approval NOT IN (7,0,8)');
                    } else {
                        $query->whereRaw('rab.approval = -1');
                    }
                })
                ->get();
            foreach ($data as $val) {
                $risk_management = $val->project_value * $val->risk_management_pct;
                $management_cost = ($val->management_cost_pct * $val->project_value); /* project value * management cost*/
                $cost_of_money = self::calculated_interest($val->cashflow_id, $val->trans_no, $val->work_start);
                $total_expense =  $cost_of_money + $val->total_budget + $management_cost + $risk_management;  /* (total budget + management cost + cost of money)*/
                $margin_value = $val->project_value - ($val->total_budget + $management_cost + $risk_management + $cost_of_money); /* project value - (total budget + management cost + cost of money)*/
                // $margin_value = $val->project_value - $total_expense; /* (project value - total expense) ferry by excel*/
                $margin_pct = empty($margin_value) || $margin_value <= 0  ? 0 : ($margin_value / $val->project_value) * 100;

                $interestPct = ProjectBudgetController::get_interest_rab_val($val->work_start);

                $header = [];
                $header['trans_no'] = $val->trans_no;
                $header['reference'] = $val->reference;
                $header['project_no'] = $val->project_no;
                $header['project_code'] = $val->project_code;
                $header['project_name'] = $val->project_name;
                $header['project_manager'] = $val->project_manager;
                $header['division_name'] = $val->division_name;
                $header['sales_person'] = $val->sales_person;
                $header['project_value'] = $val->project_value;
                $header['total_budget'] = $val->total_budget;
                $header['management_cost_pct'] = $val->management_cost_pct;
                $header['management_cost'] = $management_cost;
                $header['cost_of_money_permonth'] = $cost_of_money;
                $header['cost_of_money_pct'] = $interestPct;
                $header['risk_management_pct'] = $val->risk_management_pct;
                $header['risk_management'] = $val->risk_management;
                $header['total_sales'] = $val->project_value;
                $header['total_expenses'] = $total_expense;
                $header['margin_pct'] = $margin_pct;
                $header['margin_value'] = $margin_value;
                $header['debtor_no'] = $val->debtor_no;
                $header['customer'] = $val->customer_name;
                $header['area_name'] = $val->area_name;
                $header['project_head_name'] = $val->project_head_name;
                $header['pc_user_name'] = $val->pc_user_name;
                $header['work_start'] = $val->work_start;
                $header['work_end'] = $val->work_end;
                $header['remark'] = $val->remark;
                $header['created_at'] = $val->created_at;
                $header['created_by'] = $val->created_by;

                $header['man_power'] = DB::table('0_project_submission_rab_man_power as rab')
                    ->leftJoin('0_project_cost_type_group as pct', 'pct.cost_type_group_id', '=', 'rab.type_category')
                    ->where('rab.trans_no', $val->trans_no)
                    ->select('rab.*', 'pct.name as type_name')->get();
                $header['vehicle'] = DB::table('0_project_submission_rab_vehicle_ops as rab')
                    ->leftJoin('0_project_cost_type_group as pct', 'pct.cost_type_group_id', '=', 'rab.type_category')
                    ->where('rab.trans_no', $val->trans_no)
                    ->select('rab.*', 'pct.name as type_name')->get();
                $header['procurement'] = DB::table('0_project_submission_rab_procurement as rab')
                    ->leftJoin('0_project_cost_type_group as pct', 'pct.cost_type_group_id', '=', 'rab.type_category')
                    ->where('rab.trans_no', $val->trans_no)
                    ->select('rab.*', 'pct.name as type_name')->get();
                $header['tools'] = DB::table('0_project_submission_rab_tools as rab')
                    ->leftJoin('0_project_cost_type_group as pct', 'pct.cost_type_group_id', '=', 'rab.type_category')
                    ->where('rab.trans_no', $val->trans_no)
                    ->select('rab.*', 'pct.name as type_name')->get();
                $header['training'] = DB::table('0_project_submission_rab_training as rab')
                    ->leftJoin('0_project_cost_type_group as pct', 'pct.cost_type_group_id', '=', 'rab.type_category')
                    ->where('rab.trans_no', $val->trans_no)
                    ->select('rab.*', 'pct.name as type_name')->get();
                $header['other_expenses'] = DB::table('0_project_submission_rab_other_expenses as rab')
                    ->leftJoin('0_project_cost_type_group as pct', 'pct.cost_type_group_id', '=', 'rab.type_category')
                    ->where('trans_no', $val->trans_no)
                    ->select('rab.*', 'pct.name as type_name')->get();
                $header['other_info'] = DB::table('0_project_submission_rab_other_information')->where('trans_no', $val->trans_no)->get();

                array_push($response, $header);
            }
            $user_id = $this->user_old_id;
            return response()->json([
                'success' => true,
                'data' => $response,
                'user_level' => $approval_user,
                'old_id' => $user_id
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }
    }

    public function update_project_budgetary(Request $request, $trans_no)
    {
        $user_level = $this->user_level;
        $data = DB::table('0_project_submission_rab as rab')
            ->leftJoin('0_project_submission_cash_flow as cashflow', 'cashflow.trans_no', '=', 'rab.trans_no')
            ->select(
                'rab.*',
                'cashflow.id as cashflow_id'
            )
            ->where('rab.trans_no', $trans_no)
            ->first();
        $status = $request->status;
        $risk_management = $data->project_value * $data->risk_management_pct;
        $management_cost = ($data->management_cost_pct * $data->project_value); /* project value * management cost*/
        $cost_of_money = self::calculated_interest($data->cashflow_id, $data->trans_no, $data->work_start);
        $margin_value = $data->project_value - ($data->total_budget + $management_cost + $risk_management + $cost_of_money); /* project value - (total budget + management cost + cost of money)*/
        $margin_pct = empty($margin_value) || $margin_value <= 0  ? 0 : ($margin_value / $data->project_value) * 100;
        if ($status == 1) {
            // if ($user_level == 41) {
            //     $next_approval = 7; //dir direct approved
            // } else {
                    
            // }

            switch ($data->approval) {
                case 1: //dgm jika divisi tss / non wl
                    $next_approval = 3;

                    break;

                case 3:  // gm
                    $next_approval = 4;

                    break;

                case 4: //bpc&ppmo
                    // $next_approval = 42;

                    if ($data->parent != null) {
                        $parent_rab = DB::table('0_project_submission_rab as rab')
                            ->leftJoin('0_project_submission_cash_flow as cashflow', 'cashflow.trans_no', '=', 'rab.trans_no')
                            ->select(
                                'rab.*',
                                'cashflow.id as cashflow_id'
                            )
                            ->where('rab.trans_no', $data->parent)
                            ->first();

                        $risk_management_parent = $parent_rab->project_value * $parent_rab->risk_management_pct;
                        $management_cost_parent = ($parent_rab->management_cost_pct * $parent_rab->project_value); /* project value * management cost*/
                        $cost_of_money_parent = self::calculated_interest($parent_rab->cashflow_id, $parent_rab->trans_no, $parent_rab->work_start);
                        $margin_value_parent = $parent_rab->project_value - ($parent_rab->total_budget + $management_cost_parent + $risk_management_parent + $cost_of_money_parent); /* project value - (total budget + management cost + cost of money)*/
                        $margin_pct_parent = empty($margin_value_parent) || $margin_value_parent <= 0  ? 0 : ($margin_value_parent / $parent_rab->project_value) * 100;

                        if (number_format($margin_pct, 2) >= number_format($margin_pct_parent, 2)) {
                            $next_approval = 7;
                        } else {

                            $next_approval = 42;
                        }
                    } else {

                        $next_approval = 42;
                    }
                    break;

                case 41: //dir
                    $next_approval = 7; //approved

                    break;

                case 42: //dirops
                    $next_approval = 41;

                    break;
            }
        } else if ($status == 2) {
            if ($user_level == 41 && $data->approval != 41) {
                return response()->json(['error' => [
                    'message' => 'Tidak bisa pending approval',
                    'status_code' => 403,
                ]], 403);
            }
            
            $next_approval = $data->approval;
        } else if ($status == 3) {
            $next_approval = 0;

            $info_creator = DB::table('users')->where('id', $data->created_by)->first();
            $details_send_mail = [
                'title' => 'Rejected RAB',
                'user' => $info_creator->name,
                'rab_no' => $data->reference,
                'reject_by' => $this->user_name
            ];

            // \Mail::to($info_creator->email)->send(new \App\Mail\RABRejectNotify($details_send_mail));
        }

        if ($next_approval == 0) {
            $statusx = 0;
        } else {
            $statusx = $status;
        }

        // $log_approval = ($data->approval == 1) ? 3 : $data->approval;

        $remark = $request->remark;
        $dataApprLog = $user_level == 41 ? 41 : $data->approval;

        DB::beginTransaction();
        try {

            DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                ->update(array(
                    'approval' => $next_approval,
                    'status_id' => $statusx,
                    'reject_history' => $this->user_name . " - " . $remark . " On : " . date('d-m-Y H:i:s')
                ));

            if ($next_approval == 0) {
                DB::table('0_project_submission_rab_logs')
                    ->where('trans_no',  $trans_no)
                    ->delete();
            } else {

                DB::table('0_project_submission_rab_logs')
                    ->insert(array(
                        'trans_no' => $trans_no,
                        'approval' => $dataApprLog,
                        'status_id' => $status,
                        'remark' => $remark,
                        'created_at' => Carbon::now(),
                        'created_by' =>  $this->user_id
                    ));
            }

            /*Hold */
            // if ($next_approval != 7) {
            //     dispatch(new RABNotification($trans_no, $next_approval));
            // }
            if ($next_approval == 7) {

                if ($data->parent == null) {
                    self::generate_budget_id_from_rab($trans_no);
                } else {
                    self::update_budget_already_created_man_power($trans_no, $data->parent);
                    self::update_budget_already_created_vehicle($trans_no, $data->parent);
                    self::uppdate_budget_already_created_procurement($trans_no, $data->parent);
                    self::update_budget_already_created_tools($trans_no, $data->parent);
                    self::update_budget_already_created_training($trans_no, $data->parent);
                    self::update_budget_already_created_otex($trans_no, $data->parent);
                    self::update_budget_penalty_cap_interest_mgmt_cost($trans_no, $data->parent);
                }

                // update unlock budget id
                DB::table('0_project_budgets')->where('rab_no', $trans_no)
                    ->update(array(
                        'inactive' => 0,
                        'updated_by' => $this->user_id,
                        'updated_date' => Carbon::now()
                    ));
            }

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

    public function delete_project_budgetary($trans_no)
    {
        DB::beginTransaction();
        try {
            DB::table('0_project_submission_rab_project_value')
                ->where('trans_no',  $trans_no)
                ->delete();

            $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->first();

            if (isset($cash_flow_info)) {
                DB::table('0_project_submission_cash_flow_in')
                    ->where('cash_flow_id',  $cash_flow_info->id)
                    ->delete();

                DB::table('0_project_submission_cash_flow_out')
                    ->where('cash_flow_id',  $cash_flow_info->id)
                    ->delete();

                DB::table('0_project_submission_cash_flow')
                    ->where('trans_no', $trans_no)
                    ->delete();
            }

            DB::table('0_project_submission_rab_man_power')
                ->where('trans_no',  $trans_no)
                ->delete();

            DB::table('0_project_submission_rab_vehicle_ops')
                ->where('trans_no',  $trans_no)
                ->delete();

            DB::table('0_project_submission_rab_procurement')
                ->where('trans_no',  $trans_no)
                ->delete();

            DB::table('0_project_submission_rab_tools')
                ->where('trans_no',  $trans_no)
                ->delete();

            DB::table('0_project_submission_rab_training')
                ->where('trans_no',  $trans_no)
                ->delete();

            DB::table('0_project_submission_rab_other_expenses')
                ->where('trans_no',  $trans_no)
                ->delete();

            DB::table('0_project_submission_rab_other_information')
                ->where('trans_no',  $trans_no)
                ->delete();

            DB::table('0_project_submission_rab')
                ->where('trans_no',  $trans_no)
                ->delete();

            // Commit Transaction
            DB::commit();

            return response()->json([
                'success' => true
            ]);
        } catch (Exception $e) {
            echo 'Terjadi kesalahan: ' . $e->getMessage();
            // Rollback Transaction
            DB::rollback();
        }
    }

    public function delete_detail_submission(Request $request)
    {
        $trans_no = $request->trans_no;
        $rab_category = $request->rab_category;
        $id = $request->id;

        DB::beginTransaction();

        try {

            $rab_info = DB::table('0_project_submission_rab')->where('trans_no', $trans_no)->first();
            if ($rab_info->revision == 0) {
                if ($rab_category == 2) {
                    // kurangi akumulasi cash out
                    $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->first();
                    $sum_amount_out = DB::table('0_project_submission_rab_man_power')->where('id',  $id)->first();
                    $total_amount_out = $cash_flow_info->total_cash_out - $sum_amount_out->total_amount;

                    DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)
                        ->update(array(
                            'total_cash_out' => $total_amount_out
                        ));

                    $total_amount_budget = $rab_info->total_budget  - $sum_amount_out->total_amount;
                    DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                        ->update(array(
                            'total_budget' => $total_amount_budget
                        ));

                    // delete cash out
                    DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category',  2)
                        ->where('detail_id', $id)
                        ->delete();

                    // delete detail rab
                    DB::table('0_project_submission_rab_man_power')
                        ->where('id',  $id)
                        ->delete();
                }

                if ($rab_category == 3) {
                    // kurangi akumulasi cash out
                    $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->first();
                    $sum_amount_out = DB::table('0_project_submission_rab_vehicle_ops')->where('id',  $id)->first();
                    $total_amount_out = $cash_flow_info->total_cash_out - $sum_amount_out->total_amount;

                    DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)
                        ->update(array(
                            'total_cash_out' => $total_amount_out
                        ));

                    $total_amount_budget = $rab_info->total_budget  - $sum_amount_out->total_amount;
                    DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                        ->update(array(
                            'total_budget' => $total_amount_budget
                        ));

                    // delete cash out
                    DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category',  3)
                        ->where('detail_id', $id)
                        ->delete();

                    // delete detail rab
                    DB::table('0_project_submission_rab_vehicle_ops')
                        ->where('id',  $id)
                        ->delete();
                }

                if ($rab_category == 4) {
                    // kurangi akumulasi cash out
                    $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->first();
                    $sum_amount_out = DB::table('0_project_submission_rab_procurement')->where('id',  $id)->first();
                    $total_amount_out = $cash_flow_info->total_cash_out - $sum_amount_out->total_amount;

                    DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)
                        ->update(array(
                            'total_cash_out' => $total_amount_out
                        ));

                    $total_amount_budget = $rab_info->total_budget  - $sum_amount_out->total_amount;
                    DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                        ->update(array(
                            'total_budget' => $total_amount_budget
                        ));

                    // delete cash out
                    DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category',  4)
                        ->where('detail_id', $id)
                        ->delete();

                    // delete detail rab
                    DB::table('0_project_submission_rab_procurement')
                        ->where('id',  $id)
                        ->delete();
                }

                if ($rab_category == 5) {
                    // kurangi akumulasi cash out
                    $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->first();
                    $sum_amount_out = DB::table('0_project_submission_rab_tools')->where('id',  $id)->first();
                    $total_amount_out = $cash_flow_info->total_cash_out - $sum_amount_out->total_amount;

                    DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)
                        ->update(array(
                            'total_cash_out' => $total_amount_out
                        ));

                    $total_amount_budget = $rab_info->total_budget  - $sum_amount_out->total_amount;
                    DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                        ->update(array(
                            'total_budget' => $total_amount_budget
                        ));

                    // delete cash out
                    DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category',  5)
                        ->where('detail_id', $id)
                        ->delete();

                    // delete detail rab
                    DB::table('0_project_submission_rab_tools')
                        ->where('id',  $id)
                        ->delete();
                }

                if ($rab_category == 6) {
                    // kurangi akumulasi cash out
                    $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->first();
                    $sum_amount_out = DB::table('0_project_submission_rab_training')->where('id',  $id)->first();
                    $total_amount_out = $cash_flow_info->total_cash_out - $sum_amount_out->total_amount;

                    DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)
                        ->update(array(
                            'total_cash_out' => $total_amount_out
                        ));

                    // delete cash out
                    DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category',  6)
                        ->where('detail_id', $id)
                        ->delete();

                    // delete detail rab
                    DB::table('0_project_submission_rab_training')
                        ->where('id',  $id)
                        ->delete();
                }

                if ($rab_category == 7) {
                    // kurangi akumulasi cash out
                    $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->first();
                    $sum_amount_out = DB::table('0_project_submission_rab_other_expenses')->where('id',  $id)->first();
                    $total_amount_out = $cash_flow_info->total_cash_out - $sum_amount_out->total_amount;

                    DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)
                        ->update(array(
                            'total_cash_out' => $total_amount_out
                        ));

                    $total_amount_budget = $rab_info->total_budget  - $sum_amount_out->total_amount;
                    DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                        ->update(array(
                            'total_budget' => $total_amount_budget
                        ));

                    // delete cash out
                    DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category',  7)
                        ->where('detail_id', $id)
                        ->delete();

                    // delete detail rab
                    DB::table('0_project_submission_rab_other_expenses')
                        ->where('id',  $id)
                        ->delete();
                }

                if ($rab_category == 8) {
                    // kurangi akumulasi cash out
                    $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->first();
                    $sum_amount_out = DB::table('0_project_submission_rab_other_information')->where('id',  $id)->first();
                    $total_amount_out = $cash_flow_info->total_cash_out - $sum_amount_out->total_amount;

                    DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)
                        ->update(array(
                            'total_cash_out' => $total_amount_out
                        ));

                    $total_amount_budget = $rab_info->total_budget  - $sum_amount_out->total_amount;
                    DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                        ->update(array(
                            'total_budget' => $total_amount_budget
                        ));

                    // delete cash out
                    DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category',  8)
                        ->where('detail_id', $id)
                        ->delete();

                    // delete detail rab
                    DB::table('0_project_submission_rab_other_information')
                        ->where('id',  $id)
                        ->delete();
                }
                // Commit Transaction
                DB::commit();

                return response()->json([
                    'success' => true
                ]);
            }
            return response()->json(['error' => [
                'message' => 'cannot delete detail rab revision',
                'status_code' => 400,
            ]], 400);
        } catch (Exception $e) {
            echo 'Terjadi kesalahan: ' . $e->getMessage();
            // Rollback Transaction
            DB::rollback();
        }
    }

    public function get_mp_position()
    {
        $response = [];

        $sql = "SELECT rab.position_id, pr.position, rab.salary_per_day, rab.salary_per_month FROM 0_project_rab_salary rab 
                LEFT JOIN 0_project_position_rab pr ON (rab.position_id = pr.id)";
        $uom = DB::select(DB::raw($sql));
        foreach ($uom as $data) {

            $tmp = [];
            $tmp['position_id'] = $data->position_id;
            $tmp['position'] = $data->position;
            $tmp['salary_per_day'] = $data->salary_per_day;
            $tmp['salary_per_month'] = $data->salary_per_month;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }


    public function create_rab_manpower(Request $request)
    {
        $all_request = $request->data;
        $trans_no = $request->trans_no;
        $total_amount_out = 0;
        $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->orderBy('id', 'desc')->first();

        if (empty($cash_flow_info->id)) {
            return response()->json(['error' => [
                'message' => 'Harap input cash in terlebih dahulu',
                'status_code' => 403,
            ]], 403);
        } else {
            DB::beginTransaction();
            try {
                $total_amount = 0;
                foreach ($all_request as $data) {
                    $total = $data['qty'] * $data['amount'] * $data['percentage'] * $data['duration'];
                    $detail_id = DB::table('0_project_submission_rab_man_power')
                        ->insertGetId(array(
                            'trans_no' => $trans_no,
                            'position_id' => $data['position_id'],
                            'emp_id' => $data['emp_id'],
                            'qty' => $data['qty'],
                            'percentage' => $data['percentage'],
                            'amount' => $data['amount'],
                            'duration' => $data['duration'],
                            'duration_type' => $data['duration_type'],
                            'total_amount' => $total,
                            'remark' => $data['remark']
                        ));
                    if (!empty($data['cash_out'])) {
                        foreach ($data['cash_out'] as $cash_out) {

                            DB::table('0_project_submission_cash_flow_out')
                                ->insert(
                                    [
                                        'cash_flow_id' => $cash_flow_info->id,
                                        'detail_id' => $detail_id,
                                        'cost_type_group_id' => 49,
                                        'rab_category' => 2,
                                        'amount' => $cash_out['amount'],
                                        'periode' => $cash_out['date']
                                    ]
                                );

                            $total_amount_out += $cash_out['amount'];
                        }

                        DB::table('0_project_submission_cash_flow')->where('id', $cash_flow_info->id)
                            ->update(array(
                                'total_cash_out' => DB::raw("total_cash_out+$total_amount_out")
                            ));
                    }

                    $total_amount += $total;
                }
                DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                    ->update(array(
                        'total_budget' => DB::raw("total_budget+$total_amount"),
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
    }

    function update_manpower(Request $request, $trans_no)
    {
        // get budget
        $budget = DB::table('0_project_submission_rab')->where('trans_no', $trans_no)->pluck('total_budget');
        $sum_amount = DB::table('0_project_submission_rab_man_power')->where('trans_no', $trans_no)->sum('total_amount');

        $all_request = $request->data;
        DB::beginTransaction();

        try {
            $total_amount = 0;
            foreach ($all_request as $data) {
                $total = $data['qty'] * $data['amount'] * $data['percentage']  * $data['duration'];
                DB::table('0_project_submission_rab_man_power')
                    ->where('id', $data['id'])
                    ->where('trans_no', $trans_no)
                    ->update(array(
                        'emp_id' => $data['emp_id'],
                        'qty' => $data['qty'],
                        'amount' =>  $data['amount'],
                        'percentage' =>  $data['percentage'],
                        'duration' =>  $data['duration'],
                        'duration_type' =>  $data['duration_type'],
                        'total_amount' =>  $total
                    ));
                
                if ($total == 0) {
                    $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->first();
                    $total_delete = DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category',  2)
                        ->where('detail_id', $data['id'])
                        ->sum('amount');

                    $total_amount -= $total;

                    DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category',  2)
                        ->where('detail_id', $data['id'])
                        ->delete();
                } else {
                    $total_amount += $total;
                }

                if ($data['cash_out'] != null) {

                    $total_out = 0;
                    $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->first();
                    $sum_amount_out = DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id', $cash_flow_info->id)
                        ->where('rab_category',  2)
                        ->where('detail_id', $data['id'])
                        ->sum('amount');

                    DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category',  2)
                        ->where('detail_id', $data['id'])
                        ->delete();


                    foreach ($data['cash_out'] as $cash_out) {

                        DB::table('0_project_submission_cash_flow_out')
                            ->insert(
                                [
                                    'cash_flow_id' => $cash_flow_info->id,
                                    'detail_id' => $data['id'],
                                    'cost_type_group_id' => 49,
                                    'rab_category' => 2,
                                    'amount' => $cash_out['amount'],
                                    'periode' => $cash_out['date']
                                ]
                            );
                        $total_out += $cash_out['amount'];
                    }

                    $total_amount_out = ($cash_flow_info->total_cash_out - $sum_amount_out) + $total_out;
                    DB::table('0_project_submission_cash_flow')->where('id', $cash_flow_info->id)
                        ->update(array(
                            'total_cash_out' => $total_amount_out
                        ));
                }
            }

            $total_budget = ($budget[0] - $sum_amount) + $total_amount;
            DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                ->update(array(
                    'total_budget' => $total_budget
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

    public function create_rab_procurement(Request $request)
    {
        $all_request = $request->data;
        $trans_no = $request->trans_no;
        DB::beginTransaction();
        try {
            $total_amount = 0;
            $total_amount_out = 0;
            $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->orderBy('id', 'desc')->first();

            if (empty($cash_flow_info->id)) {
                return response()->json(['error' => [
                    'message' => 'Harap input cash in terlebih dahulu',
                    'status_code' => 403,
                ]], 403);
            } else {
                foreach ($all_request as $data) {
                    $total = $data['qty'] * $data['price'];
                    $detail_id = DB::table('0_project_submission_rab_procurement')
                        ->insertGetId(array(
                            'trans_no' => $trans_no,
                            'type_category' => $data['type_category'],
                            'description' => $data['description'],
                            'qty' => $data['qty'],
                            'uom' => $data['uom'],
                            'price' => $data['price'],
                            'total_amount' => $total,
                            'remark' => $data['remark']
                        ));
                    $total_amount += $total;

                    if (!empty($data['cash_out'])) {
                        foreach ($data['cash_out'] as $cash_out) {

                            if ($data['type_category'] == 31) {
                                DB::table('0_project_submission_cash_flow_out')
                                    ->insert(
                                        [
                                            'cash_flow_id' => $cash_flow_info->id,
                                            'detail_id' => $detail_id,
                                            'cost_type_group_id' => $data['type_category'],
                                            'rab_category' => 4,
                                            'amount' => $cash_out['amount'],
                                            'periode' => $cash_out['date'],
                                            'expense_type' => 2
                                        ]
                                    );
                                $total_amount_out += $cash_out['amount'];
                            } else {
                                DB::table('0_project_submission_cash_flow_out')
                                    ->insert(
                                        [
                                            'cash_flow_id' => $cash_flow_info->id,
                                            'detail_id' => $detail_id,
                                            'cost_type_group_id' => $data['type_category'],
                                            'rab_category' => 4,
                                            'amount' => $cash_out['amount'],
                                            'periode' => $cash_out['date']
                                        ]
                                    );
                                $total_amount_out += $cash_out['amount'];
                            }
                        }

                        DB::table('0_project_submission_cash_flow')->where('id', $cash_flow_info->id)
                            ->update(array(
                                'total_cash_out' => DB::raw("total_cash_out+$total_amount_out")
                            ));
                    }
                }

                DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                    ->update(array(
                        'total_budget' => DB::raw("total_budget+$total_amount"),
                    ));

                // Commit Transaction
                DB::commit();
            }
            return response()->json([
                'success' => true
            ]);
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }

    function update_procurement(Request $request, $trans_no)
    {

        $budget = DB::table('0_project_submission_rab')->where('trans_no', $trans_no)->pluck('total_budget');
        $sum_amount = DB::table('0_project_submission_rab_procurement')->where('trans_no', $trans_no)->sum('total_amount');

        $all_request = $request->data;
        DB::beginTransaction();

        try {
            $total_amount = 0;
            foreach ($all_request as $data) {
                $total = $data['qty'] * $data['price'];
                DB::table('0_project_submission_rab_procurement')
                    ->where('id', $data['id'])
                    ->where('trans_no', $trans_no)
                    ->update(array(
                        'qty' => $data['qty'],
                        'uom' => $data['uom'],
                        'price' => $data['price'],
                        'total_amount' => $total
                    ));

                if ($total == 0) {
                    $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->first();
                    $total_delete = DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category',  4)
                        ->where('detail_id', $data['id'])
                        ->sum('amount');

                    $total_amount -= $total;

                    DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category',  4)
                        ->where('detail_id', $data['id'])
                        ->delete();
                } else {
                    $total_amount += $total;
                }

                if (!empty($data['cash_out'])) {

                    $total_out = 0;
                    $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->first();
                    $sum_amount_out = DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id', $cash_flow_info->id)
                        ->where('rab_category',  4)
                        ->where('detail_id', $data['id'])
                        ->sum('amount');

                    DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category',  4)
                        ->where('detail_id', $data['id'])
                        ->delete();

                    foreach ($data['cash_out'] as $cash_out) {
                        if ($data['type_category'] == 31) {
                            DB::table('0_project_submission_cash_flow_out')
                                ->insert(
                                    [
                                        'cash_flow_id' => $cash_flow_info->id,
                                        'detail_id' => $data['id'],
                                        'cost_type_group_id' => $data['type_category'],
                                        'rab_category' => 4,
                                        'amount' => $cash_out['amount'],
                                        'periode' => $cash_out['date'],
                                        'expense_type' => 2
                                    ]
                                );
                            $total_out += $cash_out['amount'];
                        } else {
                            DB::table('0_project_submission_cash_flow_out')
                                ->insert(
                                    [
                                        'cash_flow_id' => $cash_flow_info->id,
                                        'detail_id' => $data['id'],
                                        'cost_type_group_id' => $data['type_category'],
                                        'rab_category' => 4,
                                        'amount' => $cash_out['amount'],
                                        'periode' => $cash_out['date']
                                    ]
                                );
                            $total_out += $cash_out['amount'];
                        }
                    }

                    $total_amount_out = ($cash_flow_info->total_cash_out - $sum_amount_out) + $total_out;
                    DB::table('0_project_submission_cash_flow')->where('id', $cash_flow_info->id)
                        ->update(array(
                            'total_cash_out' => $total_amount_out
                        ));
                }
            }
            $total_budget = ($budget[0] - $sum_amount) + $total_amount;
            DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                ->update(array(
                    'total_budget' => $total_budget
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

    public function create_rab_vehicle_ops(Request $request)
    {
        $all_request = $request->data;
        $trans_no = $request->trans_no;
        DB::beginTransaction();
        try {
            $total_amount = 0;
            $total_amount_out = 0;
            $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->orderBy('id', 'desc')->first();

            if (empty($cash_flow_info->id)) {
                return response()->json(['error' => [
                    'message' => 'Harap input cash in terlebih dahulu',
                    'status_code' => 403,
                ]], 403);
            } else {
                foreach ($all_request as $data) {
                    $total = $data['qty'] * $data['amount'] * $data['duration'];
                    $detail_id = DB::table('0_project_submission_rab_vehicle_ops')
                        ->insertGetId(array(
                            'trans_no' => $trans_no,
                            'type_category' => $data['type_category'],
                            'qty' => $data['qty'],
                            'amount' => $data['amount'],
                            'duration' => $data['duration'],
                            'duration_type' => $data['duration_type'],
                            'total_amount' => $total,
                            'remark' => $data['remark']
                        ));
                    $total_amount += $total;

                    if (!empty($data['cash_out'])) {
                        foreach ($data['cash_out'] as $cash_out) {

                            DB::table('0_project_submission_cash_flow_out')
                                ->insert(
                                    [
                                        'cash_flow_id' => $cash_flow_info->id,
                                        'detail_id' => $detail_id,
                                        'cost_type_group_id' => $data['type_category'],
                                        'rab_category' => 3,
                                        'amount' => $cash_out['amount'],
                                        'periode' => $cash_out['date']
                                    ]
                                );

                            $total_amount_out += $cash_out['amount'];
                        }

                        DB::table('0_project_submission_cash_flow')->where('id', $cash_flow_info->id)
                            ->update(array(
                                'total_cash_out' => DB::raw("total_cash_out+$total_amount_out")
                            ));
                    }
                }

                DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                    ->update(array(
                        'total_budget' => DB::raw("total_budget+$total_amount"),
                    ));

                // Commit Transaction
                DB::commit();
            }

            return response()->json([
                'success' => true
            ]);
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }

    function update_vehicle_ops(Request $request, $trans_no)
    {

        $budget = DB::table('0_project_submission_rab')->where('trans_no', $trans_no)->pluck('total_budget');
        $sum_amount = DB::table('0_project_submission_rab_vehicle_ops')->where('trans_no', $trans_no)->sum('total_amount');

        $all_request = $request->data;
        DB::beginTransaction();

        try {
            $total_amount = 0;
            foreach ($all_request as $data) {
                $total = $data['qty'] * $data['amount'] * $data['duration'];
                DB::table('0_project_submission_rab_vehicle_ops')
                    ->where('id', $data['id'])
                    ->where('trans_no', $trans_no)
                    ->update(array(
                        'qty' => $data['qty'],
                        'amount' => $data['amount'],
                        'duration' => $data['duration'],
                        'duration_type' => $data['duration_type'],
                        'total_amount' => $total
                    ));

                if ($total == 0) {
                    $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->first();
                    $total_delete = DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category',  3)
                        ->where('detail_id', $data['id'])
                        ->sum('amount');

                    $total_amount -= $total;

                    DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category',  3)
                        ->where('detail_id', $data['id'])
                        ->delete();
                } else {
                    $total_amount += $total;
                }

                if (!empty($data['cash_out'])) {

                    $total_out = 0;
                    $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->first();
                    $sum_amount_out = DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id', $cash_flow_info->id)
                        ->where('rab_category',  3)
                        ->where('detail_id', $data['id'])
                        ->sum('amount');

                    DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category',  3)
                        ->where('detail_id', $data['id'])
                        ->delete();

                    foreach ($data['cash_out'] as $cash_out) {

                        DB::table('0_project_submission_cash_flow_out')
                            ->insert(
                                [
                                    'cash_flow_id' => $cash_flow_info->id,
                                    'detail_id' => $data['id'],
                                    'cost_type_group_id' => $data['type_category'],
                                    'rab_category' => 3,
                                    'amount' => $cash_out['amount'],
                                    'periode' => $cash_out['date']
                                ]
                            );
                        $total_out += $cash_out['amount'];
                    }

                    $total_amount_out = ($cash_flow_info->total_cash_out - $sum_amount_out) + $total_out;
                    DB::table('0_project_submission_cash_flow')->where('id', $cash_flow_info->id)
                        ->update(array(
                            'total_cash_out' => $total_amount_out
                        ));
                }
            }
            $total_budget = ($budget[0] - $sum_amount) + $total_amount;
            DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                ->update(array(
                    'total_budget' => $total_budget
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

    public function create_rab_training(Request $request)
    {
        $all_request = $request->data;
        $trans_no = $request->trans_no;
        DB::beginTransaction();
        try {
            $total_amount = 0;
            $total_amount_out = 0;
            $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->orderBy('id', 'desc')->first();

            if (empty($cash_flow_info->id)) {
                return response()->json(['error' => [
                    'message' => 'Harap input cash in terlebih dahulu',
                    'status_code' => 403,
                ]], 403);
            } else {
                foreach ($all_request as $data) {
                    $total = $data['qty'] * $data['price'];
                    $detail_id = DB::table('0_project_submission_rab_training')
                        ->insertGetId(array(
                            'trans_no' => $trans_no,
                            'type_category' => $data['type_category'],
                            'position' => $data['position'],
                            'qty' => $data['qty'],
                            'price' => $data['price'],
                            'total_amount' => $total,
                            'remark' => $data['remark']
                        ));

                    $total_amount += $total;

                    if (!empty($data['cash_out'])) {
                        foreach ($data['cash_out'] as $cash_out) {

                            DB::table('0_project_submission_cash_flow_out')
                                ->insert(
                                    [
                                        'cash_flow_id' => $cash_flow_info->id,
                                        'detail_id' => $detail_id,
                                        'cost_type_group_id' => $data['type_category'],
                                        'rab_category' => 6,
                                        'amount' => $cash_out['amount'],
                                        'periode' => $cash_out['date']
                                    ]
                                );

                            $total_amount_out += $cash_out['amount'];
                        }

                        DB::table('0_project_submission_cash_flow')->where('id', $cash_flow_info->id)
                            ->update(array(
                                'total_cash_out' => DB::raw("total_cash_out+$total_amount_out")
                            ));
                    }
                }

                DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                    ->update(array(
                        'total_budget' => DB::raw("total_budget+$total_amount"),
                    ));

                // Commit Transaction
                DB::commit();
            }
            return response()->json([
                'success' => true
            ]);
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }

    function update_training(Request $request, $trans_no)
    {

        $budget = DB::table('0_project_submission_rab')->where('trans_no', $trans_no)->pluck('total_budget');
        $sum_amount = DB::table('0_project_submission_rab_training')->where('trans_no', $trans_no)->sum('total_amount');

        $all_request = $request->data;
        DB::beginTransaction();

        try {
            $total_amount = 0;
            foreach ($all_request as $data) {
                $total = $data['qty'] * $data['price'];
                DB::table('0_project_submission_rab_training')
                    ->where('id', $data['id'])
                    ->where('trans_no', $trans_no)
                    ->update(array(
                        'qty' => $data['qty'],
                        'price' => $data['price'],
                        'total_amount' => $total
                    ));

                if ($total == 0) {
                    $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->first();
                    $total_delete = DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category',  6)
                        ->where('detail_id', $data['id'])
                        ->sum('amount');

                    $total_amount -= $total;

                    DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category',  6)
                        ->where('detail_id', $data['id'])
                        ->delete();
                } else {
                    $total_amount += $total;
                }


                if (!empty($data['cash_out'])) {

                    $total_out = 0;
                    $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->first();
                    $sum_amount_out = DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id', $cash_flow_info->id)
                        ->where('rab_category',  6)
                        ->where('detail_id', $data['id'])
                        ->sum('amount');

                    DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category',  6)
                        ->where('detail_id', $data['id'])
                        ->delete();

                    foreach ($data['cash_out'] as $cash_out) {

                        DB::table('0_project_submission_cash_flow_out')
                            ->insert(
                                [
                                    'cash_flow_id' => $cash_flow_info->id,
                                    'detail_id' => $data['id'],
                                    'cost_type_group_id' => $data['type_category'],
                                    'rab_category' => 6,
                                    'amount' => $cash_out['amount'],
                                    'periode' => $cash_out['date']
                                ]
                            );
                        $total_out += $cash_out['amount'];
                    }

                    $total_amount_out = ($cash_flow_info->total_cash_out - $sum_amount_out) + $total_out;
                    DB::table('0_project_submission_cash_flow')->where('id', $cash_flow_info->id)
                        ->update(array(
                            'total_cash_out' => $total_amount_out
                        ));
                }
            }
            $total_budget = ($budget[0] - $sum_amount) + $total_amount;
            DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                ->update(array(
                    'total_budget' => $total_budget
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

    public function create_rab_tools(Request $request)
    {
        $all_request = $request->data;
        $trans_no = $request->trans_no;
        DB::beginTransaction();
        try {
            $total_amount = 0;
            $total_amount_out = 0;
            $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->orderBy('id', 'desc')->first();

            if (empty($cash_flow_info->id)) {
                return response()->json(['error' => [
                    'message' => 'Harap input cash in terlebih dahulu',
                    'status_code' => 403,
                ]], 403);
            } else {
                foreach ($all_request as $data) {
                    $total = $data['qty'] * $data['amount'] * $data['duration'];
                    $detail_id = DB::table('0_project_submission_rab_tools')
                        ->insertGetId(array(
                            'trans_no' => $trans_no,
                            'type_category' => $data['type_category'],
                            'description' => $data['description'],
                            'qty' => $data['qty'],
                            'uom' => $data['uom'],
                            'amount' => $data['amount'],
                            'duration' => $data['duration'],
                            'duration_type' => $data['duration_type'],
                            'total_amount' => $total,
                            'remark' => $data['remark']
                        ));

                    $total_amount += $total;

                    if (!empty($data['cash_out'])) {
                        foreach ($data['cash_out'] as $cash_out) {

                            DB::table('0_project_submission_cash_flow_out')
                                ->insert(
                                    [
                                        'cash_flow_id' => $cash_flow_info->id,
                                        'detail_id' => $detail_id,
                                        'cost_type_group_id' => $data['type_category'],
                                        'rab_category' => 5,
                                        'amount' => $cash_out['amount'],
                                        'periode' => $cash_out['date']
                                    ]
                                );

                            $total_amount_out += $cash_out['amount'];
                        }

                        DB::table('0_project_submission_cash_flow')->where('id', $cash_flow_info->id)
                            ->update(array(
                                'total_cash_out' => DB::raw("total_cash_out+$total_amount_out")
                            ));
                    }
                }

                DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                    ->update(array(
                        'total_budget' => DB::raw("total_budget+$total_amount"),
                    ));

                // Commit Transaction
                DB::commit();
            }

            return response()->json([
                'success' => true
            ]);
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }

    function update_tools(Request $request, $trans_no)
    {

        $budget = DB::table('0_project_submission_rab')->where('trans_no', $trans_no)->pluck('total_budget');
        $sum_amount = DB::table('0_project_submission_rab_tools')->where('trans_no', $trans_no)->sum('total_amount');

        $all_request = $request->data;
        DB::beginTransaction();

        try {
            $total_amount = 0;
            foreach ($all_request as $data) {
                $total = $data['qty'] * $data['amount'] * $data['duration'];
                DB::table('0_project_submission_rab_tools')
                    ->where('id', $data['id'])
                    ->where('trans_no', $trans_no)
                    ->update(array(
                        'qty' => $data['qty'],
                        'uom' => $data['uom'],
                        'amount' => $data['amount'],
                        'duration' => $data['duration'],
                        'duration_type' => $data['duration_type'],
                        'total_amount' => $total
                    ));

                if ($total == 0) {
                    $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->first();
                    $total_delete = DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category',  5)
                        ->where('detail_id', $data['id'])
                        ->sum('amount');

                    $total_amount -= $total;

                    DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category', 5)
                        ->where('detail_id', $data['id'])
                        ->delete();
                } else {
                    $total_amount += $total;
                }

                if (!empty($data['cash_out'])) {

                    $total_out = 0;
                    $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->first();
                    $sum_amount_out = DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id', $cash_flow_info->id)
                        ->where('rab_category', 5)
                        ->where('detail_id', $data['id'])
                        ->sum('amount');

                    DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category', 5)
                        ->where('detail_id', $data['id'])
                        ->delete();

                    foreach ($data['cash_out'] as $cash_out) {

                        DB::table('0_project_submission_cash_flow_out')
                            ->insert(
                                [
                                    'cash_flow_id' => $cash_flow_info->id,
                                    'detail_id' => $data['id'],
                                    'cost_type_group_id' => $data['type_category'],
                                    'rab_category' => 5,
                                    'amount' => $cash_out['amount'],
                                    'periode' => $cash_out['date']
                                ]
                            );
                        $total_out += $cash_out['amount'];
                    }

                    $total_amount_out = ($cash_flow_info->total_cash_out - $sum_amount_out) + $total_out;
                    DB::table('0_project_submission_cash_flow')->where('id', $cash_flow_info->id)
                        ->update(array(
                            'total_cash_out' => $total_amount_out
                        ));
                }
            }
            $total_budget = ($budget[0] - $sum_amount) + $total_amount;
            DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                ->update(array(
                    'total_budget' => $total_budget
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

    public function create_rab_other_expenses(Request $request)
    {
        $all_request = $request->data;
        $trans_no = $request->trans_no;
        DB::beginTransaction();
        try {
            $total_amount = 0;
            $total_amount_out = 0;
            $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->orderBy('id', 'desc')->first();

            if (empty($cash_flow_info->id)) {
                return response()->json(['error' => [
                    'message' => 'Harap input cash in terlebih dahulu',
                    'status_code' => 403,
                ]], 403);
            } else {
                foreach ($all_request as $data) {
                    $total = $data['qty'] * $data['amount'];
                    $detail_id = DB::table('0_project_submission_rab_other_expenses')
                        ->insertGetId(array(
                            'trans_no' => $trans_no,
                            'type_category' => $data['type_category'],
                            'qty' => $data['qty'],
                            'amount' => $data['amount'],
                            'total_amount' => $total,
                            'remark' => $data['remark']
                        ));

                    $total_amount += $total;

                    if (!empty($data['cash_out'])) {
                        foreach ($data['cash_out'] as $cash_out) {

                            DB::table('0_project_submission_cash_flow_out')
                                ->insert(
                                    [
                                        'cash_flow_id' => $cash_flow_info->id,
                                        'detail_id' => $detail_id,
                                        'cost_type_group_id' => $data['type_category'],
                                        'rab_category' => 7,
                                        'amount' => $cash_out['amount'],
                                        'periode' => $cash_out['date']
                                    ]
                                );

                            $total_amount_out += $cash_out['amount'];
                        }

                        DB::table('0_project_submission_cash_flow')->where('id', $cash_flow_info->id)
                            ->update(array(
                                'total_cash_out' => DB::raw("total_cash_out+$total_amount_out")
                            ));
                    }
                }

                DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                    ->update(array(
                        'total_budget' => DB::raw("total_budget+$total_amount"),
                    ));

                // Commit Transaction
                DB::commit();
            }
            return response()->json([
                'success' => true
            ]);
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }

    function update_other_expenses(Request $request, $trans_no)
    {

        $budget = DB::table('0_project_submission_rab')->where('trans_no', $trans_no)->pluck('total_budget');
        $sum_amount = DB::table('0_project_submission_rab_other_expenses')->where('trans_no', $trans_no)->sum('total_amount');

        $all_request = $request->data;
        DB::beginTransaction();

        try {
            $total_amount = 0;
            foreach ($all_request as $data) {
                $total = $data['qty'] * $data['amount'];
                DB::table('0_project_submission_rab_other_expenses')
                    ->where('id', $data['id'])
                    ->where('trans_no', $trans_no)
                    ->update(array(
                        'qty' => $data['qty'],
                        'amount' => $data['amount'],
                        'total_amount' => $total
                    ));

                if ($total == 0) {
                    $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->first();
                    $total_delete = DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category',  7)
                        ->where('detail_id', $data['id'])
                        ->sum('amount');

                    $total_amount -= $total;

                    DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category', 7)
                        ->where('detail_id', $data['id'])
                        ->delete();
                } else {
                    $total_amount += $total;
                }

                if (!empty($data['cash_out'])) {

                    $total_out = 0;
                    $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->first();
                    $sum_amount_out = DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id', $cash_flow_info->id)
                        ->where('rab_category', 7)
                        ->where('detail_id', $data['id'])
                        ->sum('amount');

                    DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category', 7)
                        ->where('detail_id', $data['id'])
                        ->delete();

                    foreach ($data['cash_out'] as $cash_out) {

                        DB::table('0_project_submission_cash_flow_out')
                            ->insert(
                                [
                                    'cash_flow_id' => $cash_flow_info->id,
                                    'detail_id' => $data['id'],
                                    'cost_type_group_id' => $data['type_category'],
                                    'rab_category' => 7,
                                    'amount' => $cash_out['amount'],
                                    'periode' => $cash_out['date']
                                ]
                            );
                        $total_out += $cash_out['amount'];
                    }

                    $total_amount_out = ($cash_flow_info->total_cash_out - $sum_amount_out) + $total_out;
                    DB::table('0_project_submission_cash_flow')->where('id', $cash_flow_info->id)
                        ->update(array(
                            'total_cash_out' => $total_amount_out
                        ));
                }
            }
            $total_budget = ($budget[0] - $sum_amount) + $total_amount;
            DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                ->update(array(
                    'total_budget' => $total_budget
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

    public function create_rab_other_information(Request $request)
    {
        $all_request = $request->data;
        $trans_no = $request->trans_no;
        DB::beginTransaction();
        try {
            $total_amount = 0;
            $total_amount_out = 0;
            $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->orderBy('id', 'desc')->first();

            if (empty($cash_flow_info->id)) {
                return response()->json(['error' => [
                    'message' => 'Harap input cash in terlebih dahulu',
                    'status_code' => 403,
                ]], 403);
            } else {
                foreach ($all_request as $data) {
                    $total = $data['qty'] * $data['amount'];
                    $detail_id = DB::table('0_project_submission_rab_other_information')
                        ->insertGetId(array(
                            'trans_no' => $trans_no,
                            'type_category' => $data['type_category'],
                            'qty' => $data['qty'],
                            'amount' => $data['amount'],
                            'total_amount' => $total,
                            'remark' => $data['remark']
                        ));

                    $total_amount += $total;

                    if (!empty($data['cash_out'])) {
                        foreach ($data['cash_out'] as $cash_out) {

                            DB::table('0_project_submission_cash_flow_out')
                                ->insert(
                                    [
                                        'cash_flow_id' => $cash_flow_info->id,
                                        'detail_id' => $detail_id,
                                        'cost_type_group_id' => $data['type_category'],
                                        'rab_category' => 8,
                                        'amount' => $cash_out['amount'],
                                        'periode' => $cash_out['date']
                                    ]
                                );

                            $total_amount_out += $cash_out['amount'];
                        }

                        DB::table('0_project_submission_cash_flow')->where('id', $cash_flow_info->id)
                            ->update(array(
                                'total_cash_out' => DB::raw("total_cash_out+$total_amount_out")
                            ));
                    }
                }

                DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                    ->update(array(
                        'total_budget' => DB::raw("total_budget+$total_amount"),
                    ));

                // Commit Transaction
                DB::commit();
            }
            return response()->json([
                'success' => true
            ]);
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }

    function update_other_information(Request $request, $trans_no)
    {

        $budget = DB::table('0_project_submission_rab')->where('trans_no', $trans_no)->pluck('total_budget');
        $sum_amount = DB::table('0_project_submission_rab_other_information')->where('trans_no', $trans_no)->sum('total_amount');

        $all_request = $request->data;
        DB::beginTransaction();

        try {
            $total_amount = 0;
            foreach ($all_request as $data) {
                $total = $data['qty'] * $data['amount'];
                DB::table('0_project_submission_rab_other_information')
                    ->where('id', $data['id'])
                    ->where('trans_no', $trans_no)
                    ->update(array(
                        'qty' => $data['qty'],
                        'amount' => $data['amount'],
                        'total_amount' => $total
                    ));

                if ($total == 0) {
                    $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->first();
                    $total_delete = DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category',  8)
                        ->where('detail_id', $data['id'])
                        ->sum('amount');

                    $total_amount -= $total;

                    DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category', 8)
                        ->where('detail_id', $data['id'])
                        ->delete();
                } else {
                    $total_amount += $total;
                }

                if (!empty($data['cash_out'])) {

                    $total_out = 0;
                    $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->first();
                    $sum_amount_out = DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id', $cash_flow_info->id)
                        ->where('rab_category', 8)
                        ->where('detail_id', $data['id'])
                        ->sum('amount');

                    DB::table('0_project_submission_cash_flow_out')
                        ->where('cash_flow_id',  $cash_flow_info->id)
                        ->where('rab_category', 8)
                        ->where('detail_id', $data['id'])
                        ->delete();

                    foreach ($data['cash_out'] as $cash_out) {

                        DB::table('0_project_submission_cash_flow_out')
                            ->insert(
                                [
                                    'cash_flow_id' => $cash_flow_info->id,
                                    'detail_id' => $data['id'],
                                    'cost_type_group_id' => $data['type_category'],
                                    'rab_category' => 8,
                                    'amount' => $cash_out['amount'],
                                    'periode' => $cash_out['date']
                                ]
                            );
                        $total_out += $cash_out['amount'];
                    }

                    $total_amount_out = ($cash_flow_info->total_cash_out - $sum_amount_out) + $total_out;
                    DB::table('0_project_submission_cash_flow')->where('id', $cash_flow_info->id)
                        ->update(array(
                            'total_cash_out' => $total_amount_out
                        ));
                }
            }
            $total_budget = ($budget[0] - $sum_amount) + $total_amount;
            DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                ->update(array(
                    'total_budget' => $total_budget
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

    public function create_rab_project_value(Request $request)
    {
        $all_request = $request->data;
        $trans_no = $request->trans_no;
        DB::beginTransaction();
        try {
            $total_amount = 0;

            foreach ($all_request as $data) {
                $total = $data['qty'] * $data['price'];
                DB::table('0_project_submission_rab_project_value')
                    ->insert(array(
                        'trans_no' => $trans_no,
                        'site_no' => $data['site_no'],
                        'du_id' => $data['du_id'],
                        'sow' => $data['sow'],
                        'po_line' => $data['po_line'],
                        'qty' => $data['qty'],
                        'price' => $data['price'],
                        'total_amount' => $total,
                        'remark' => $data['remark'],
                        'revenue_type' => $data['revenue_type']
                    ));

                $total_amount += $total;
            }

            DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                ->update(array(
                    'project_value' => DB::raw("project_value+$total_amount"),
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

    public function upload_rab_project_value(Request $request)
    {
        $trans_no = $request->trans_no;
        $total_amount = 0;
        $loop_i = 0;

        DB::beginTransaction();
        try {
            $file = $request->file('file');
            $handle = fopen($file, 'r');
            $header = fgetcsv($handle);

            if ($file->getClientOriginalExtension() != 'csv') {
                return response()->json(['error' => [
                    'message' => 'Pastikan format file adalah CSV',
                    'status_code' => 403,
                ]], 403);
            }
            while (($row = fgetcsv($handle)) !== false) {
                $site = DB::table('0_project_site')->where('site_id', 'LIKE', '%' . $row[1] . '%')->orwhere('name', 'LIKE', '%' . $row[1] . '%')->first();

                if (empty($row[0])) {
                    return response()->json(['error' => [
                        'message' => 'du_id : Tidak boleh kosong!',
                        'status_code' => 403,
                    ]], 403);
                }

                if (empty($row[1])) {
                    return response()->json(['error' => [
                        'message' => 'Site ID : Tidak boleh kosong!',
                        'status_code' => 403,
                    ]], 403);
                }

                if (empty($site)) {
                    if (empty($row[2])) {
                        return response()->json(['error' => [
                            'message' => 'Site ID : ' . $row[1] . ' Tidak ditemukan! harap isi site name',
                            'status_code' => 403,
                        ]], 403);
                    } else {
                        DB::table('0_project_site')
                            ->insert(array(
                                'site_id' => $row[1],
                                'name' => $row[2]

                            ));

                        $site = DB::table('0_project_site')->where('site_id', 'LIKE', '%' . $row[1] . '%')->orwhere('name', 'LIKE', '%' . $row[1] . '%')->first();
                    }
                }

                if (empty($row[3])) {
                    return response()->json(['error' => [
                        'message' => 'SOW : Tidak boleh kosong!',
                        'status_code' => 403,
                    ]], 403);
                }


                if (empty($row[5])) {
                    return response()->json(['error' => [
                        'message' => 'QTY : Tidak boleh kosong!',
                        'status_code' => 403,
                    ]], 403);
                }

                if (empty($row[6])) {
                    return response()->json(['error' => [
                        'message' => 'Price : Tidak boleh kosong!',
                        'status_code' => 403,
                    ]], 403);
                }

                if (empty($row[8])) {
                    return response()->json(['error' => [
                        'message' => 'Type : Tidak boleh kosong!',
                        'status_code' => 403,
                    ]], 403);
                }

                $total = $row[5] * $row[6];
                DB::table('0_project_submission_rab_project_value')
                    ->insert(array(
                        'trans_no' => $trans_no,
                        'du_id' => $row[0],
                        'site_no' => $site->site_no,
                        'sow' => $row[3],
                        'po_line' => $row[4],
                        'qty' => $row[5],
                        'price' => $row[6],
                        'total_amount' => $total,
                        'remark' => $row[7],
                        'revenue_type' => $row[8]
                    ));

                $total_amount += $total;
                $loop_i += 1;
            }

            if ($loop_i == 0) {
                return response()->json(['error' => [
                    'message' => 'Data tidak boleh kosong!',
                    'status_code' => 403,
                ]], 403);
            }

            fclose($handle);

            DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                ->update(array(
                    'project_value' => DB::raw("project_value+$total_amount"),
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

    function update_project_value(Request $request, $trans_no)
    {

        $project_v = DB::table('0_project_submission_rab')->where('trans_no', $trans_no)->pluck('project_value');
        $sum_amount = DB::table('0_project_submission_rab_project_value')->where('trans_no', $trans_no)->sum('total_amount');

        $all_request = $request->data;
        DB::beginTransaction();

        try {
            $total_amount = 0;
            foreach ($all_request as $data) {
                $total = $data['qty'] * $data['price'];
                DB::table('0_project_submission_rab_project_value')
                    ->where('id', $data['id'])
                    ->where('trans_no', $trans_no)
                    ->update(array(
                        'site_no' => $data['site_no'],
                        'du_id' => $data['du_id'],
                        'sow' => $data['sow'],
                        'po_line' => $data['po_line'],
                        'qty' => $data['qty'],
                        'price' => $data['price'],
                        'total_amount' => $total,
                        'remark' => $data['remark'],
                        'revenue_type' => $data['revenue_type']
                    ));
                $total_amount += $total;
            }
            $project_value = ($project_v[0] - $sum_amount) + $total_amount;
            DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                ->update(array(
                    'project_value' => $project_value
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

    public function delete_project_value(Request $request)
    {
        $trans_no = $request->trans_no;
        $id = $request->id;

        DB::beginTransaction();

        try {

            // kurangi total amount project value
            $rab_info = DB::table('0_project_submission_rab')->where('trans_no', $trans_no)->first();
            $project_value_amount = DB::table('0_project_submission_rab_project_value')->where('id',  $id)->first();

            $project_value_total = $rab_info->project_value - $project_value_amount->total_amount;
            DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                ->update(array('project_value' => $project_value_total));

            // delete detail rab
            DB::table('0_project_submission_rab_project_value')
                ->where('id',  $id)
                ->delete();

            // Commit Transaction
            DB::commit();

            return response()->json([
                'success' => true
            ]);
        } catch (Exception $e) {
            echo 'Terjadi kesalahan: ' . $e->getMessage();
            // Rollback Transaction
            DB::rollback();
        }
    }

    public function delete_all_project_value(Request $request)
    {
        $trans_no = $request->trans_no;

        DB::beginTransaction();

        try {

            $cash_flow_info = DB::table('0_project_submission_cash_flow AS cf')
                ->leftJoin('0_project_submission_cash_flow_in AS cfin', 'cf.id', '=', 'cfin.cash_flow_id')
                ->where('cf.trans_no',  $trans_no)
                ->first();

            if ($cash_flow_info->id == "") {
                // kurangi total amount project value
                DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                    ->update(array('project_value' => 0));

                // delete detail rab
                DB::table('0_project_submission_rab_project_value')
                    ->where('trans_no',  $trans_no)
                    ->delete();
                // Commit Transaction
                DB::commit();

                return response()->json([
                    'success' => true
                ]);
            }

            return response()->json([
                'error' => array(
                    'message' => "cannot delete the project value because there is already cash in.",
                    'status_code' => 500
                )
            ], 500);
        } catch (Exception $e) {
            echo 'Terjadi kesalahan: ' . $e->getMessage();
            // Rollback Transaction
            DB::rollback();
        }
    }

    public function get_list_rab_project_value($trans_no)
    {
        $response = [];
        $sql = "SELECT
                    pv.id,
                    rab.trans_no,
                    pv.site_no,
                    pv.du_id,
                    st.name,
                    pv.sow,
                    pv.qty,
                    pv.price,
                    pv.total_amount,
                    pv.remark
                FROM 0_project_submission_rab_project_value pv
                LEFT JOIN 0_project_submission_rab rab ON (pv.trans_no = rab.trans_no)
                LEFT JOIN 0_project_site st ON (pv.site_no = st.site_no)
                WHERE pv.trans_no =" . $trans_no;

        $data = DB::select(DB::raw($sql));

        foreach ($data as $val) {
            $tmp = [];
            $tmp['id'] = $val->id;
            $tmp['trans_no'] = $trans_no;
            $tmp['du_id'] = $val->du_id;
            $tmp['site_no'] = $val->site_no;
            $tmp['site_name'] = $val->name;
            $tmp['sow'] = $val->sow;
            $tmp['qty'] = $val->qty;
            $tmp['price'] = $val->price;
            $tmp['total_amount'] = $val->total_amount;
            $tmp['remark'] = $val->remark;

            array_push($response, $tmp);
        }


        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public function create_cashflow_budgetary(Request $request)
    {
        $data = $request->data;
        $trans_no = $request->trans_no;
        $cash_flow_id = $request->cashflow_id;

        DB::beginTransaction();
        try {
            $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->first();

            if (isset($cash_flow_info)) {
                $cash_flow_id = $cash_flow_info->id;
            }

            if (isset($cash_flow_info)) {
                $total_amount_in = 0;
                foreach ($data['cash_in'] as $cash_in) {
                    DB::table('0_project_submission_cash_flow_in')
                        ->insert(
                            [
                                'cash_flow_id' => $cash_flow_id,
                                'item' => $cash_in['item'],
                                'amount' => $cash_in['amount'],
                                'periode' => $cash_in['date'],
                                'remark' => $cash_in['remark'],
                                'expense_type' => $cash_in['expense_type']
                            ]
                        );
                    $total_amount_in += $cash_in['amount'];
                }

                $cash_in_total = DB::table('0_project_submission_cash_flow_in')->where('cash_flow_id', $cash_flow_id)->where('item', 'PAID')->sum('amount'); // by paid amount

                DB::table('0_project_submission_cash_flow')->where('id', $cash_flow_id)
                    ->update(array(
                        'total_cash_in' => DB::raw("total_cash_in+$cash_in_total")
                    ));
            } else {

                $header = DB::table('0_project_submission_cash_flow')
                    ->insertGetId(
                        [
                            'trans_no' => $trans_no,
                            'total_cash_in' => 0,
                            'total_cash_out' => 0,
                            'cost_of_money' => 0,
                            'created_at' => Carbon::now(),
                            'created_by' => $this->user_id
                        ]
                    );
                $total_amount_in = 0;
                foreach ($data['cash_in'] as $cash_in) {
                    DB::table('0_project_submission_cash_flow_in')
                        ->insert(
                            [
                                'cash_flow_id' => $header,
                                'item' => $cash_in['item'],
                                'amount' => $cash_in['amount'],
                                'periode' => $cash_in['date'],
                                'remark' => $cash_in['remark'],
                                'expense_type' => $cash_in['expense_type']
                            ]
                        );
                    $total_amount_in += $cash_in['amount'];
                }

                $cash_in_total = DB::table('0_project_submission_cash_flow_in')->where('cash_flow_id', $header)->where('item', 'PAID')->sum('amount'); // by paid amount

                DB::table('0_project_submission_cash_flow')->where('id', $header)
                    ->update(array(
                        'total_cash_in' => DB::raw("total_cash_in+$cash_in_total")
                    ));
            }
            /*sisipkan cash_flow_out PENALTY*/

            $info_rab = DB::table('0_project_submission_rab')->where('trans_no', $trans_no)->first();
            DB::table('0_project_submission_cash_flow_out')
                ->insert(
                    [
                        'cash_flow_id' => $cash_flow_id,
                        'rab_category' => 0,
                        'detail_id' => 0,
                        'cost_type_group_id' => 710,
                        'amount' => ($info_rab->project_value * $info_rab->risk_management_pct),
                        'periode' => $info_rab->work_end,
                        'expense_type' => 1
                    ]
                );
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

    public function get_cashflow_budgetary(Request $request)
    {
        $trans_no = $request->trans_no;
        $response = [];
        $sql = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)
            ->orderBy('id', 'desc')
            ->limit(1)
            ->get();

        foreach ($sql as $data) {
            $tmp = [];
            $tmp['id'] = $data->id;
            $tmp['rab_no'] = $data->trans_no;
            $tmp['total_cash_in'] = DB::table('0_project_submission_cash_flow_in')
                ->where('cash_flow_id', $data->id)
                ->where('item', "INVOICE")
                ->sum('amount');
            $tmp['detail_cash_in'] =
                DB::table('0_project_submission_cash_flow_in')->where('cash_flow_id', $data->id)
                ->select(
                    'id AS cashin_id',
                    'item',
                    'amount',
                    'periode',
                    'remark',
                    'expense_type'
                )
                ->orderBy('item')
                ->get();
            $tmp['total_cash_out'] = DB::table('0_project_submission_cash_flow_out')->where('cash_flow_id', $data->id)->sum('amount');
            $tmp['detail_cash_out'] =
                DB::table('0_project_submission_cash_flow_out AS out')
                ->leftJoin('0_project_cost_type_group AS pct', 'pct.cost_type_group_id', '=', 'out.cost_type_group_id')
                ->where('out.cash_flow_id', $data->id)
                ->select(
                    'out.id AS cashout_id',
                    'out.detail_id',
                    'out.cost_type_group_id',
                    'pct.name AS cost_group_name',
                    'out.rab_category',
                    'out.amount',
                    'out.periode',
                    'expense_type'
                )
                ->get();
            $tmp['created_at'] = $data->created_at;

            array_push($response, $tmp);
        }
        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public function update_cashflow_budgetary(Request $request)
    {
        $body = $request->data;
        $cashflow_id = $request->id;

        DB::beginTransaction();
        try {

            foreach ($body['cash_in'] as $cashin) {
                DB::table('0_project_submission_cash_flow_in')->where('id', $cashin['cashin_id'])
                    ->update(array(
                        'item' => $cashin['item'],
                        'amount' => $cashin['amount'],
                        'periode' => $cashin['periode'],
                        'remark' => $cashin['remark'],
                        'expense_type' => $cashin['expense_type']
                    ));
            }

            DB::table('0_project_submission_cash_flow')->where('id', $cashflow_id)
                ->update(array(
                    'total_cash_in' => DB::table('0_project_submission_cash_flow_in')->where('cash_flow_id', $cashflow_id)->sum('amount')
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

    public function get_budgetary_cashout(Request $request)
    {
        $response = [];
        $detail_id = $request->detail_id;
        $rab_category = $request->rab_category;

        $sql =  DB::table('0_project_submission_cash_flow_out AS out')
            ->leftJoin('0_project_cost_type_group AS pct', 'pct.cost_type_group_id', '=', 'out.cost_type_group_id')
            ->where('out.detail_id', $detail_id)
            ->where('out.rab_category', $rab_category)
            ->select(
                'out.id AS cashout_id',
                'out.detail_id',
                'out.cost_type_group_id',
                'pct.name AS cost_group_name',
                'out.rab_category',
                'out.amount',
                'out.periode',
                'out.expense_type'
            )->get();

        foreach ($sql as $data) {
            $tmp = [];
            $tmp['cashout_id'] = $data->cashout_id;
            $tmp['detail_id'] = $data->detail_id;
            $tmp['cost_type_group_id'] = $data->cost_type_group_id;
            $tmp['rab_category'] = $data->rab_category;
            $tmp['amount'] = $data->amount;
            $tmp['periode'] = $data->periode;
            $tmp['expense_type'] = $data->expense_type;

            array_push($response, $tmp);
        }
        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    function update_budgetary_cashout(Request $request)
    {
        $all_request = $request->data;
        DB::beginTransaction();
        try {
            $total_out = 0;
            foreach ($all_request as $data) {
                $cash_flow_info = DB::table('0_project_submission_cash_flow')->where('id', $data['cashout_id'])->first();
                $sum_amount_out = DB::table('0_project_submission_cash_flow_out')
                    ->where('cash_flow_id', $data['cashout_id'])
                    ->where('rab_category',  $data['rab_category'])
                    ->where('detail_id', $data['detail_id'])
                    ->sum('amount');

                DB::table('0_project_submission_cash_flow_out')
                    ->where('cash_flow_id',  $data['cashout_id'])
                    ->where('rab_category',  $data['rab_category'])
                    ->where('detail_id', $data['detail_id'])
                    ->delete();

                foreach ($data['cash_out'] as $cash_out) {
                    if ($data['type_category'] == 31) {
                        DB::table('0_project_submission_cash_flow_out')
                            ->insert(
                                [
                                    'cash_flow_id' => $data['cashout_id'],
                                    'rab_category' => $data['rab_category'],
                                    'detail_id' => $data['detail_id'],
                                    'cost_type_group_id' => $data['type_category'],
                                    'amount' => $cash_out['amount'],
                                    'periode' => $cash_out['date'],
                                    'expense_type' => 2
                                ]
                            );
                    } else {
                        DB::table('0_project_submission_cash_flow_out')
                            ->insert(
                                [
                                    'cash_flow_id' => $data['cashout_id'],
                                    'rab_category' => $data['rab_category'],
                                    'detail_id' => $data['detail_id'],
                                    'cost_type_group_id' => $data['type_category'],
                                    'amount' => $cash_out['amount'],
                                    'periode' => $cash_out['date']
                                ]
                            );
                        $total_out += $cash_out['amount'];
                    }
                }

                $total_amount_out = ($cash_flow_info->total_cash_out - $sum_amount_out) + $total_out;
                DB::table('0_project_submission_cash_flow')->where('id', $cash_flow_info->id)
                    ->update(array(
                        'total_cash_out' => $total_amount_out
                    ));
            }
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


    public static function calculated_interest($cash_flow_id, $trans_no = 0, $work_start)
    {
        if ($cash_flow_id == null) {
            return 0;
        }

        $interestPct = ProjectBudgetController::get_interest_rab_val($work_start);

        $rab =  DB::table('0_project_submission_rab as rab')->where('rab.trans_no', $trans_no)
            ->leftJoin('0_project_submission_cash_flow as cashflow', 'cashflow.trans_no', '=', 'rab.trans_no')
            ->leftJoin('0_projects as p', 'p.project_no', '=', 'rab.project_no')
            ->leftJoin('0_hrm_divisions as d', 'd.division_id', '=', 'p.division_id')
            ->leftJoin('0_project_area as pa', 'pa.area_id', '=', 'rab.area_id')
            ->select(
                'rab.trans_no',
                'p.name as project_name',
                'd.name as division_name',
                'rab.work_start',
                'rab.work_end',
                'rab.total_budget',
                'rab.approval',
                'rab.old'
            )->first();

        /*
        * kondisi khusus TSP
        */
        if ($rab->old == 0) {
            if (strpos($rab->division_name, "TSP") !== false) {
                $work_start =  $rab->work_start;
                $work_end =  $rab->work_end;

                $date = array();

                $current = new DateTime($work_start);
                $last = new DateTime($work_end);
                while ($current <= $last) {
                    $current_month = $current->format('Y-m');
                    if (!in_array($current_month, $date)) {
                        $date[] = $current_month;
                    }
                    $current->modify('first day of next month');
                }

                return ($interestPct * $rab->total_budget * count($date));
            }
        }

        $date_range = [];
        $sql = "SELECT DATE_FORMAT(periode, '%Y-%m') AS periode FROM 0_project_submission_cash_flow_out
                WHERE cash_flow_id = $cash_flow_id
                UNION ALL 
                SELECT DATE_FORMAT(periode, '%Y-%m') AS periode FROM 0_project_submission_cash_flow_in
                WHERE cash_flow_id = $cash_flow_id";

        $exe = DB::connection('mysql')->select(DB::raw($sql));

        foreach ($exe as $data_date_range) {
            array_push($date_range, $data_date_range->periode);
        }
        $fixed_date_range = array_unique($date_range);

        $data = [];
        foreach (array_values($fixed_date_range) as $item) {
            $year = date('Y', strtotime($item));
            $month = date('m', strtotime($item));
            $payment =  DB::table('0_project_submission_cash_flow_in')->where('cash_flow_id', $cash_flow_id)->where('item', 'PAID')->whereRaw('YEAR(periode) = ? AND MONTH(periode) = ?', [$year, $month])->sum('amount');
            $cost = DB::table('0_project_submission_cash_flow_out')->where('cash_flow_id', $cash_flow_id)->whereRaw('YEAR(periode) = ? AND MONTH(periode) = ?', [$year, $month])->sum('amount');
            $tmp = [];
            $tmp['periode'] = $item;
            $tmp['payment'] = $payment;
            $tmp['cost'] = $cost;
            array_push($data, $tmp);
        }
        usort($data, function ($a, $b) {
            return strtotime($a['periode']) - strtotime($b['periode']);
        });
        $arrayLength = count($data);
        $accumulated_cost = 0;
        $accumulated_payment = 0;
        $calculated_interest = 0;

        foreach ($data as $key => $value) {
            $data[$key]["accumulated_cost"] = $accumulated_cost + $value["cost"];
            $accumulated_cost = $data[$key]["accumulated_cost"];

            $data[$key]["accumulated_payment"] = $accumulated_payment + $value["payment"];
            $accumulated_payment = $data[$key]["accumulated_payment"];
        }
        $fixed_data = array_slice($data, 1); // skip array pertama karna pasti tidak ada pembayaran
        $final_data = [];
        foreach ($fixed_data as $val) {
            $calculated_interest = (($val['accumulated_cost'] - $val['cost']) - $val['accumulated_payment']) * $interestPct;
            if ($calculated_interest > 0) {
                $interest = $calculated_interest;
            } else {
                $interest = 0;
            }
            array_push($final_data, $interest);
        }

        return array_sum($final_data);
    }

    public static function calculated_interest_internal($cash_flow_id, $work_start)
    {
        if ($cash_flow_id == null) {
            return 0;
        }

        $interestPct = ProjectBudgetController::get_interest_rab_val($work_start);

        $date_range = [];
        $sql = "SELECT DATE_FORMAT(periode, '%Y-%m') AS periode FROM 0_project_submission_cash_flow_out
                WHERE cash_flow_id = $cash_flow_id AND expense_type = 1
                UNION ALL 
                SELECT DATE_FORMAT(periode, '%Y-%m') AS periode FROM 0_project_submission_cash_flow_in
                WHERE cash_flow_id = $cash_flow_id AND expense_type = 1";

        $exe = DB::connection('mysql')->select(DB::raw($sql));

        foreach ($exe as $data_date_range) {
            array_push($date_range, $data_date_range->periode);
        }
        $fixed_date_range = array_unique($date_range);

        $data = [];
        foreach (array_values($fixed_date_range) as $item) {
            $year = date('Y', strtotime($item));
            $month = date('m', strtotime($item));
            $payment =  DB::table('0_project_submission_cash_flow_in')->where('cash_flow_id', $cash_flow_id)->where('item', 'PAID')->whereRaw('YEAR(periode) = ? AND MONTH(periode) = ?', [$year, $month])->where('expense_type', 1)->sum('amount');
            $cost = DB::table('0_project_submission_cash_flow_out')->where('cash_flow_id', $cash_flow_id)->whereRaw('YEAR(periode) = ? AND MONTH(periode) = ?', [$year, $month])->where('expense_type', 1)->sum('amount');
            $tmp = [];
            $tmp['periode'] = $item;
            $tmp['payment'] = $payment;
            $tmp['cost'] = $cost;
            array_push($data, $tmp);
        }
        usort($data, function ($a, $b) {
            return strtotime($a['periode']) - strtotime($b['periode']);
        });
        $arrayLength = count($data);
        $accumulated_cost = 0;
        $accumulated_payment = 0;
        $calculated_interest = 0;

        foreach ($data as $key => $value) {
            $data[$key]["accumulated_cost"] = $accumulated_cost + $value["cost"];
            $accumulated_cost = $data[$key]["accumulated_cost"];

            $data[$key]["accumulated_payment"] = $accumulated_payment + $value["payment"];
            $accumulated_payment = $data[$key]["accumulated_payment"];
        }
        $fixed_data = array_slice($data, 1); // skip array pertama karna pasti tidak ada pembayaran
        $final_data = [];
        foreach ($fixed_data as $val) {
            $calculated_interest = (($val['accumulated_cost'] - $val['cost']) - $val['accumulated_payment']) * $interestPct;
            if ($calculated_interest > 0) {
                $interest = $calculated_interest;
            } else {
                $interest = 0;
            }
            array_push($final_data, $interest);
        }
        return array_sum($final_data);
    }

    public static function calculated_interest_external($cash_flow_id, $work_start)
    {
        if ($cash_flow_id == null) {
            return 0;
        }

        $interestPct = ProjectBudgetController::get_interest_rab_val($work_start);
        
        $date_range = [];
        $sql = "SELECT DATE_FORMAT(periode, '%Y-%m') AS periode FROM 0_project_submission_cash_flow_out
                WHERE cash_flow_id = $cash_flow_id AND expense_type = 2
                UNION ALL 
                SELECT DATE_FORMAT(periode, '%Y-%m') AS periode FROM 0_project_submission_cash_flow_in
                WHERE cash_flow_id = $cash_flow_id AND expense_type = 2";

        $exe = DB::connection('mysql')->select(DB::raw($sql));

        foreach ($exe as $data_date_range) {
            array_push($date_range, $data_date_range->periode);
        }
        $fixed_date_range = array_unique($date_range);

        $data = [];
        foreach (array_values($fixed_date_range) as $item) {
            $year = date('Y', strtotime($item));
            $month = date('m', strtotime($item));
            $payment =  DB::table('0_project_submission_cash_flow_in')->where('cash_flow_id', $cash_flow_id)->where('item', 'PAID')->whereRaw('YEAR(periode) = ? AND MONTH(periode) = ?', [$year, $month])->where('expense_type', 2)->sum('amount');
            $cost = DB::table('0_project_submission_cash_flow_out')->where('cash_flow_id', $cash_flow_id)->whereRaw('YEAR(periode) = ? AND MONTH(periode) = ?', [$year, $month])->where('expense_type', 2)->sum('amount');
            $tmp = [];
            $tmp['periode'] = $item;
            $tmp['payment'] = $payment;
            $tmp['cost'] = $cost;
            array_push($data, $tmp);
        }
        usort($data, function ($a, $b) {
            return strtotime($a['periode']) - strtotime($b['periode']);
        });
        $arrayLength = count($data);
        $accumulated_cost = 0;
        $accumulated_payment = 0;
        $calculated_interest = 0;

        foreach ($data as $key => $value) {
            $data[$key]["accumulated_cost"] = $accumulated_cost + $value["cost"];
            $accumulated_cost = $data[$key]["accumulated_cost"];

            $data[$key]["accumulated_payment"] = $accumulated_payment + $value["payment"];
            $accumulated_payment = $data[$key]["accumulated_payment"];
        }
        $fixed_data = array_slice($data, 1); // skip array pertama karna pasti tidak ada pembayaran
        $final_data = [];
        foreach ($fixed_data as $val) {
            $calculated_interest = (($val['accumulated_cost'] - $val['cost']) - $val['accumulated_payment']) * $interestPct;
            if ($calculated_interest > 0) {
                $interest = $calculated_interest;
            } else {
                $interest = 0;
            }
            array_push($final_data, $interest);
        }
        return array_sum($final_data);
    }

    public static function generate_budget_id_from_rab($trans_no)
    {
        $rab_info = DB::table('0_project_submission_rab')->where('trans_no', $trans_no)->first();
        $project_info = DB::table('0_projects')->where('project_no', $rab_info->project_no)->first();

        if ($rab_info->site_no != 0) {
            $project_site = DB::table('0_project_site')->where('site_no', $rab_info->site_no)->orderBy('site_no')->first();
            $site_no = $project_site->site_no;
        } else {
            $site_no = 0;
        }

        DB::beginTransaction();
        try {
            /*
        * MAN POWER
        */
            $man_power = DB::table('0_project_submission_rab_man_power')->where('trans_no', $trans_no)
                ->select('type_category', DB::raw("SUM((CASE WHEN total_amount IS NULL THEN 0 ELSE total_amount END)) AS total_amount"))
                ->groupBy('type_category')
                ->get();
            if (!empty($man_power[0])) {
                foreach ($man_power as $data) {
                    $header = DB::table('0_project_budgets')
                        ->insertGetId(array(
                            'project_no' => $rab_info->project_no,
                            'budget_type_id' => $data->type_category,
                            'budget_name' => 'Man Power Generated From RAB System',
                            'rab_amount' => ceil($data->total_amount),
                            'amount' => 0,
                            'rab_no' => $trans_no,
                            'description' => 'Auto Generate By System',
                            'site_id' => $site_no,
                            'created_date' => Carbon::now(),
                            'created_by' => 1
                        ));

                    DB::table('0_project_budget_rab')
                        ->insert(array(
                            'budget_id' => $header,
                            'budget_type_id' => $data->type_category,
                            'project_no' => $project_info->project_no,
                            'amount' => ceil($data->total_amount),
                            'user_id' => 1,
                            'created_at' => Carbon::now()
                        ));
                }
            }

            /*
        * VEHICLE
        */
            $vehicle = DB::table('0_project_submission_rab_vehicle_ops')->where('trans_no', $trans_no)
                ->select('type_category', DB::raw("SUM((CASE WHEN total_amount IS NULL THEN 0 ELSE total_amount END)) AS total_amount"))
                ->groupBy('type_category')
                ->get();
            if (!empty($vehicle[0])) {
                foreach ($vehicle as $data) {
                    $header = DB::table('0_project_budgets')
                        ->insertGetId(array(
                            'project_no' => $rab_info->project_no,
                            'budget_type_id' => $data->type_category,
                            'budget_name' => 'Vehicle Generated From RAB System',
                            'rab_amount' => ceil($data->total_amount),
                            'amount' => 0,
                            'rab_no' => $trans_no,
                            'description' => 'Auto Generate By System',
                            'site_id' => $site_no,
                            'created_date' => Carbon::now(),
                            'created_by' => 1
                        ));

                    DB::table('0_project_budget_rab')
                        ->insert(array(
                            'budget_id' => $header,
                            'budget_type_id' => $data->type_category,
                            'project_no' => $project_info->project_no,
                            'amount' => ceil($data->total_amount),
                            'user_id' => 1,
                            'created_at' => Carbon::now()
                        ));
                }
            }

            /*
        * PROCUREMENT
        */
            $procurement = DB::table('0_project_submission_rab_procurement')->where('trans_no', $trans_no)
                ->select('type_category', DB::raw("SUM((CASE WHEN total_amount IS NULL THEN 0 ELSE total_amount END)) AS total_amount"))
                ->groupBy('type_category')
                ->get();
            if (!empty($procurement[0])) {
                foreach ($procurement as $data) {
                    $header = DB::table('0_project_budgets')
                        ->insertGetId(array(
                            'project_no' => $rab_info->project_no,
                            'budget_type_id' => $data->type_category,
                            'budget_name' => 'Procurement Generated From RAB System',
                            'rab_amount' => ceil($data->total_amount),
                            'amount' => 0,
                            'rab_no' => $trans_no,
                            'description' => 'Auto Generate By System',
                            'site_id' => $site_no,
                            'created_date' => Carbon::now(),
                            'created_by' => 1
                        ));

                    DB::table('0_project_budget_rab')
                        ->insert(array(
                            'budget_id' => $header,
                            'budget_type_id' => $data->type_category,
                            'project_no' => $project_info->project_no,
                            'amount' => ceil($data->total_amount),
                            'user_id' => 1,
                            'created_at' => Carbon::now()
                        ));
                }
            }

            /*
        * TOOLS
        */
            $tools = DB::table('0_project_submission_rab_tools')->where('trans_no', $trans_no)
                ->select('type_category', DB::raw("SUM((CASE WHEN total_amount IS NULL THEN 0 ELSE total_amount END)) AS total_amount"))
                ->groupBy('type_category')
                ->get();
            if (!empty($tools[0])) {
                foreach ($tools as $data) {
                    $header = DB::table('0_project_budgets')
                        ->insertGetId(array(
                            'project_no' => $rab_info->project_no,
                            'budget_type_id' => $data->type_category,
                            'budget_name' => 'Tools Generated From RAB System',
                            'rab_amount' => ceil($data->total_amount),
                            'amount' => 0,
                            'rab_no' => $trans_no,
                            'description' => 'Auto Generate By System',
                            'site_id' => $site_no,
                            'created_date' => Carbon::now(),
                            'created_by' => 1
                        ));

                    DB::table('0_project_budget_rab')
                        ->insert(array(
                            'budget_id' => $header,
                            'budget_type_id' => $data->type_category,
                            'project_no' => $project_info->project_no,
                            'amount' => ceil($data->total_amount),
                            'user_id' => 1,
                            'created_at' => Carbon::now()
                        ));
                }
            }

            /*
        * TRAINING
        */
            $training = DB::table('0_project_submission_rab_training')->where('trans_no', $trans_no)
                ->select('type_category', DB::raw("SUM((CASE WHEN total_amount IS NULL THEN 0 ELSE total_amount END)) AS total_amount"))
                ->groupBy('type_category')
                ->get();

            if (!empty($training[0])) {
                foreach ($training as $data) {
                    $header = DB::table('0_project_budgets')
                        ->insertGetId(array(
                            'project_no' => $rab_info->project_no,
                            'budget_type_id' => $data->type_category,
                            'budget_name' => 'Training Generated From RAB System',
                            'rab_amount' => ceil($data->total_amount),
                            'amount' => 0,
                            'rab_no' => $trans_no,
                            'description' => 'Auto Generate By System',
                            'site_id' => $site_no,
                            'created_date' => Carbon::now(),
                            'created_by' => 1
                        ));

                    DB::table('0_project_budget_rab')
                        ->insert(array(
                            'budget_id' => $header,
                            'budget_type_id' => $data->type_category,
                            'project_no' => $project_info->project_no,
                            'amount' => ceil($data->total_amount),
                            'user_id' => 1,
                            'created_at' => Carbon::now()
                        ));
                }
            }

            /*
        * OTHER EXPENSE
        */
            $otex = DB::table('0_project_submission_rab_other_expenses')->where('trans_no', $trans_no)
                ->select('type_category', DB::raw("SUM((CASE WHEN total_amount IS NULL THEN 0 ELSE total_amount END) ) AS total_amount"))
                ->groupBy('type_category')
                ->get();
            if (!empty($otex[0])) {
                foreach ($otex as $data) {
                    $header = DB::table('0_project_budgets')
                        ->insertGetId(array(
                            'project_no' => $rab_info->project_no,
                            'budget_type_id' => $data->type_category,
                            'budget_name' => 'Otex Generated From RAB System',
                            'rab_amount' => ceil($data->total_amount),
                            'amount' => 0,
                            'rab_no' => $trans_no,
                            'description' => 'Auto Generate By System',
                            'site_id' => $site_no,
                            'created_date' => Carbon::now(),
                            'created_by' => 1
                        ));

                    DB::table('0_project_budget_rab')
                        ->insert(array(
                            'budget_id' => $header,
                            'budget_type_id' => $data->type_category,
                            'project_no' => $project_info->project_no,
                            'amount' => ceil($data->total_amount),
                            'user_id' => 1,
                            'created_at' => Carbon::now()
                        ));
                }
            }

            /*
        *  penalty cap, cost of money sama management cost
        */
            $additional = DB::table('0_project_submission_rab AS rab')
                ->leftJoin('0_project_submission_cash_flow as cashflow', 'cashflow.trans_no', '=', 'rab.trans_no')
                ->where('rab.trans_no', $trans_no)
                ->select(
                    'rab.trans_no',
                    'rab.reference',
                    'rab.project_no',
                    'rab.project_code',
                    'rab.sales_person',
                    'rab.project_value',
                    'rab.total_budget',
                    'rab.management_cost_pct',
                    'rab.cost_of_money_pct',
                    'rab.risk_management_pct',
                    'rab.risk_management',
                    'rab.debtor_no',
                    'rab.area_id',
                    'rab.work_start',
                    'rab.work_end',
                    'rab.remark',
                    'rab.created_at',
                    'cashflow.id as cashflow_id'
                )->first();
            $amount_penalty = ($additional->project_value * $additional->risk_management_pct);
            $amount_interest = self::calculated_interest($additional->cashflow_id, $additional->trans_no, $additional->work_start);
            $amount_mgmt_cost = ($additional->project_value * $additional->management_cost_pct);

            $penalty = DB::table('0_project_budgets')
                ->insertGetId(array(
                    'project_no' => $rab_info->project_no,
                    'budget_type_id' => 710,
                    'budget_name' => 'Penalty Cap Generated From RAB System',
                    'rab_amount' => ceil($amount_penalty),
                    'amount' => 0,
                    'rab_no' => $trans_no,
                    'description' => 'Auto Generate By System',
                    'site_id' => $site_no,
                    'created_date' => Carbon::now(),
                    'created_by' => 1
                ));

            DB::table('0_project_budget_rab')
                ->insert(array(
                    'budget_id' => $penalty,
                    'budget_type_id' => 710,
                    'project_no' => $project_info->project_no,
                    'amount' => ceil($amount_penalty),
                    'user_id' => 1,
                    'created_at' => Carbon::now()
                ));

            $interest = DB::table('0_project_budgets')
                ->insertGetId(array(
                    'project_no' => $rab_info->project_no,
                    'budget_type_id' => 709,
                    'budget_name' => 'Interes Generated From RAB System',
                    'rab_amount' => ceil($amount_interest),
                    'amount' => 0,
                    'rab_no' => $trans_no,
                    'description' => 'Auto Generate By System',
                    'site_id' => $site_no,
                    'created_date' => Carbon::now(),
                    'created_by' => 1
                ));

            DB::table('0_project_budget_rab')
                ->insert(array(
                    'budget_id' => $interest,
                    'budget_type_id' => 709,
                    'project_no' => $project_info->project_no,
                    'amount' => ceil($amount_interest),
                    'user_id' => 1,
                    'created_at' => Carbon::now()
                ));

            $mgmt_cost = DB::table('0_project_budgets')
                ->insertGetId(array(
                    'project_no' => $rab_info->project_no,
                    'budget_type_id' => 708,
                    'budget_name' => 'Mgmt Cost Generated From RAB System',
                    'rab_amount' => ceil($amount_mgmt_cost),
                    'amount' => 0,
                    'rab_no' => $trans_no,
                    'description' => 'Auto Generate By System',
                    'site_id' => $site_no,
                    'created_date' => Carbon::now(),
                    'created_by' => 1
                ));

            DB::table('0_project_budget_rab')
                ->insert(array(
                    'budget_id' => $mgmt_cost,
                    'budget_type_id' => 708,
                    'project_no' => $project_info->project_no,
                    'amount' => ceil($amount_mgmt_cost),
                    'user_id' => 1,
                    'created_at' => Carbon::now()
                ));
            // Commit Transaction
            DB::commit();
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }

    function update_rab_revision($trans_no)
    {

        DB::beginTransaction();

        try {
            // update lock budget id
            DB::table('0_project_budgets')->where('rab_no', $trans_no)
                ->update(array(
                    'inactive' => 1
                ));

            // update status approval 8
            DB::table('0_project_submission_rab')->where('trans_no', $trans_no)
                ->update(array(
                    'approval' => 8
                ));

            $data_parent = DB::table('0_project_submission_rab')->where('trans_no', $trans_no)->first();
            $revision = $data_parent->revision + 1;
            // clone data parent
            $header = DB::table('0_project_submission_rab')
                ->insertGetId(array(
                    'reference' => $data_parent->reference,
                    'project_no' => $data_parent->project_no,
                    'project_code' => $data_parent->project_code,
                    'sales_person' => $data_parent->sales_person,
                    'project_value' => $data_parent->project_value,
                    'total_budget' => $data_parent->total_budget,
                    'management_cost_pct' => $data_parent->management_cost_pct,
                    'management_cost' => $data_parent->management_cost,
                    'cost_of_money_permonth' => $data_parent->cost_of_money_permonth,
                    'cost_of_money_pct' => $data_parent->cost_of_money_pct,
                    'risk_management_pct' => $data_parent->risk_management_pct,
                    'risk_management' => $data_parent->risk_management,
                    'total_sales' => $data_parent->total_sales,
                    'total_expenses' => $data_parent->total_expenses,
                    'margin_value' => $data_parent->margin_value,
                    'margin_pct' => $data_parent->margin_pct,
                    'debtor_no' => $data_parent->debtor_no,
                    'area_id' => $data_parent->area_id,
                    'project_head_id' => $data_parent->project_head_id,
                    'pc_user_id' => $data_parent->pc_user_id,
                    'work_start' => $data_parent->work_start,
                    'work_end' => $data_parent->work_end,
                    'remark' => $data_parent->remark,
                    'created_at' => Carbon::now(),
                    'created_by' => $this->user_id,
                    'updated_at' => Carbon::now(),
                    'updated_by' => $this->user_id,
                    'revision' => $revision,
                    'parent' => $trans_no
                ));

            $cashflow = DB::table('0_project_submission_cash_flow')->where('trans_no', $trans_no)->first();
            // clone data parent
            $header_cashflow = DB::table('0_project_submission_cash_flow')
                ->insertGetId(array(
                    'trans_no' => $header,
                    'total_cash_in' => $cashflow->total_cash_in,
                    'total_cash_out' => $cashflow->total_cash_out,
                    'cost_of_money' => $cashflow->cost_of_money,
                    'created_at' => Carbon::now(),
                    'created_by' => $this->user_id
                ));

            $cashflow_in = DB::table('0_project_submission_cash_flow_in')->where('cash_flow_id', $cashflow->id)->get();
            foreach ($cashflow_in as $data) {
                DB::table('0_project_submission_cash_flow_in')
                    ->insert(array(
                        'cash_flow_id' => $header_cashflow,
                        'item' => $data->item,
                        'amount' => $data->amount,
                        'periode' => $data->periode,
                        'remark' => $data->remark,
                        'expense_type' => $data->expense_type,
                    ));
            }

            $data_pv = DB::table('0_project_submission_rab_project_value')->where('trans_no', $trans_no)->get();
            foreach ($data_pv as $data) {
                DB::table('0_project_submission_rab_project_value')
                    ->insert(array(
                        'trans_no' => $header,
                        'site_no' => $data->site_no,
                        'du_id' => $data->du_id,
                        'sow' => $data->sow,
                        'qty' => $data->qty,
                        'price' => $data->price,
                        'total_amount' =>  $data->total_amount,
                        'remark' => $data->remark,
                        'revenue_type' => $data->revenue_type
                    ));
            }

            $sum_project_value = DB::table('0_project_submission_rab_project_value')->where('trans_no', $header)->sum('total_amount');
            DB::table('0_project_submission_rab')->where('trans_no', $header)
                ->update(array(
                    'project_value' => $sum_project_value
                ));

            // clone data child mp
            $data_mp = DB::table('0_project_submission_rab_man_power')->where('trans_no', $trans_no)->get();
            foreach ($data_mp as $data) {

                $id_mp = DB::table('0_project_submission_rab_man_power')
                    ->insertGetId(array(
                        'trans_no' => $header,
                        'type_category' => $data->type_category,
                        'position_id' => $data->position_id,
                        'emp_id' => $data->emp_id,
                        'qty' => $data->qty,
                        'amount' => $data->amount,
                        'percentage' => $data->percentage,
                        'duration' => $data->duration,
                        'duration_type' => $data->duration_type,
                        'total_amount' => $data->total_amount,
                        'remark' => $data->remark
                    ));

                $cashflow_out = DB::table('0_project_submission_cash_flow_out')->where('rab_category',  2)->where('detail_id',  $data->id)->get();
                foreach ($cashflow_out as $data) {
                    DB::table('0_project_submission_cash_flow_out')
                        ->insert(array(
                            'cash_flow_id' => $header_cashflow,
                            'cost_type_group_id' => $data->cost_type_group_id,
                            'rab_category' => $data->rab_category,
                            'detail_id' => $id_mp,
                            'amount' => $data->amount,
                            'periode' => $data->periode
                        ));
                }
            }

            $data_vhc = DB::table('0_project_submission_rab_vehicle_ops')->where('trans_no', $trans_no)->get();
            foreach ($data_vhc as $data) {
                $id_vhc = DB::table('0_project_submission_rab_vehicle_ops')
                    ->insertGetId(array(
                        'trans_no' => $header,
                        'type_category' => $data->type_category,
                        'qty' => $data->qty,
                        'amount' => $data->amount,
                        'duration' => $data->duration,
                        'duration_type' => $data->duration_type,
                        'total_amount' => $data->total_amount,
                        'remark' => $data->remark
                    ));

                $cashflow_out = DB::table('0_project_submission_cash_flow_out')->where('rab_category',  3)->where('detail_id',  $data->id)->get();
                foreach ($cashflow_out as $data) {
                    DB::table('0_project_submission_cash_flow_out')
                        ->insert(array(
                            'cash_flow_id' => $header_cashflow,
                            'cost_type_group_id' => $data->cost_type_group_id,
                            'rab_category' => $data->rab_category,
                            'detail_id' => $id_vhc,
                            'amount' => $data->amount,
                            'periode' => $data->periode
                        ));
                }
            }


            $data_pr = DB::table('0_project_submission_rab_procurement')->where('trans_no', $trans_no)->get();
            foreach ($data_pr as $data) {
                $id_pr = DB::table('0_project_submission_rab_procurement')
                    ->insertGetId(array(
                        'trans_no' => $header,
                        'type_category' => $data->type_category,
                        'description' => $data->description,
                        'qty' => $data->qty,
                        'uom' => $data->uom,
                        'price' => $data->price,
                        'total_amount' => $data->total_amount,
                        'remark' => $data->remark
                    ));

                $cashflow_out = DB::table('0_project_submission_cash_flow_out')->where('rab_category',  4)->where('detail_id',  $data->id)->get();
                foreach ($cashflow_out as $data) {
                    DB::table('0_project_submission_cash_flow_out')
                        ->insert(array(
                            'cash_flow_id' => $header_cashflow,
                            'cost_type_group_id' => $data->cost_type_group_id,
                            'rab_category' => $data->rab_category,
                            'detail_id' => $id_pr,
                            'amount' => $data->amount,
                            'periode' => $data->periode
                        ));
                }
            }

            $data_tl = DB::table('0_project_submission_rab_tools')->where('trans_no', $trans_no)->get();
            foreach ($data_tl as $data) {
                $id_tl = DB::table('0_project_submission_rab_tools')
                    ->insertGetId(array(
                        'trans_no' => $header,
                        'type_category' => $data->type_category,
                        'description' => $data->description,
                        'qty' => $data->qty,
                        'uom' => $data->uom,
                        'amount' => $data->amount,
                        'duration' => $data->duration,
                        'duration_type' => $data->duration_type,
                        'total_amount' => $data->total_amount,
                        'remark' => $data->remark
                    ));

                $cashflow_out = DB::table('0_project_submission_cash_flow_out')->where('rab_category',  5)->where('detail_id',  $data->id)->get();
                foreach ($cashflow_out as $data) {
                    DB::table('0_project_submission_cash_flow_out')
                        ->insert(array(
                            'cash_flow_id' => $header_cashflow,
                            'cost_type_group_id' => $data->cost_type_group_id,
                            'rab_category' => $data->rab_category,
                            'detail_id' => $id_tl,
                            'amount' => $data->amount,
                            'periode' => $data->periode
                        ));
                }
            }

            $data_tr = DB::table('0_project_submission_rab_training')->where('trans_no', $trans_no)->get();
            foreach ($data_tr as $data) {
                $id_tr = DB::table('0_project_submission_rab_training')
                    ->insertGetId(array(
                        'trans_no' => $header,
                        'type_category' => $data->type_category,
                        'position' => $data->position,
                        'qty' => $data->qty,
                        'price' => $data->price,
                        'total_amount' => $data->total_amount,
                        'remark' => $data->remark
                    ));

                $cashflow_out = DB::table('0_project_submission_cash_flow_out')->where('rab_category',  6)->where('detail_id',  $data->id)->get();
                foreach ($cashflow_out as $data) {
                    DB::table('0_project_submission_cash_flow_out')
                        ->insert(array(
                            'cash_flow_id' => $header_cashflow,
                            'cost_type_group_id' => $data->cost_type_group_id,
                            'rab_category' => $data->rab_category,
                            'detail_id' => $id_tr,
                            'amount' => $data->amount,
                            'periode' => $data->periode
                        ));
                }
            }

            $data_exps = DB::table('0_project_submission_rab_other_expenses')->where('trans_no', $trans_no)->get();
            foreach ($data_exps as $data) {
                $id_exps = DB::table('0_project_submission_rab_other_expenses')
                    ->insertGetId(array(
                        'trans_no' => $header,
                        'type_category' => $data->type_category,
                        'qty' => $data->qty,
                        'amount' => $data->amount,
                        'total_amount' => $data->total_amount,
                        'remark' => $data->remark
                    ));

                $cashflow_out = DB::table('0_project_submission_cash_flow_out')->where('rab_category',  7)->where('detail_id',  $data->id)->get();
                foreach ($cashflow_out as $data) {
                    DB::table('0_project_submission_cash_flow_out')
                        ->insert(array(
                            'cash_flow_id' => $header_cashflow,
                            'cost_type_group_id' => $data->cost_type_group_id,
                            'rab_category' => $data->rab_category,
                            'detail_id' => $id_exps,
                            'amount' => $data->amount,
                            'periode' => $data->periode
                        ));
                }
            }

            $data_inf = DB::table('0_project_submission_rab_other_information')->where('trans_no', $trans_no)->get();
            foreach ($data_inf as $data) {
                $id_inf = DB::table('0_project_submission_rab_other_information')
                    ->insertGetId(array(
                        'trans_no' => $header,
                        'type_category' => $data->type_category,
                        'qty' => $data->qty,
                        'amount' => $data->amount,
                        'total_amount' => $data->total_amount,
                        'remark' => $data->remark
                    ));

                $cashflow_out = DB::table('0_project_submission_cash_flow_out')->where('rab_category',  8)->where('detail_id',  $data->id)->get();
                foreach ($cashflow_out as $data) {
                    DB::table('0_project_submission_cash_flow_out')
                        ->insert(array(
                            'cash_flow_id' => $header_cashflow,
                            'cost_type_group_id' => $data->cost_type_group_id,
                            'rab_category' => $data->rab_category,
                            'detail_id' => $id_inf,
                            'amount' => $data->amount,
                            'periode' => $data->periode
                        ));
                }
            }

            $sum_cost = DB::table('0_project_submission_cash_flow_out')->where('cash_flow_id', $header_cashflow)->sum('amount');
            DB::table('0_project_submission_rab')->where('trans_no', $header)
                ->update(array(
                    'total_budget' => $sum_cost
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

    public static function update_budget_already_created_man_power($trans_no, $parent)
    {
        $new_rab_info = DB::table('0_project_submission_rab')->where('trans_no', $trans_no)->first();
        $project_info = DB::table('0_projects')->where('project_no', $new_rab_info->project_no)->first();

        $man_power_parent = DB::table('0_project_submission_rab_man_power')->where('trans_no', $parent)
            ->select('type_category', DB::raw("SUM((CASE WHEN total_amount IS NULL THEN 0 ELSE total_amount END)) AS total_amount"))
            ->groupBy('type_category')
            ->get()->toArray();

        $man_power_new = DB::table('0_project_submission_rab_man_power')->where('trans_no', $trans_no)
            ->select('type_category', DB::raw("SUM((CASE WHEN total_amount IS NULL THEN 0 ELSE total_amount END)) AS total_amount"))
            ->groupBy('type_category')
            ->get()->toArray();

        $typeAmountMap = [];
        foreach ($man_power_parent as $item) {
            $typeAmountMap[$item->type_category] = $item->total_amount;
        }

        $commonTypes = array_filter($man_power_new, function ($item) use ($typeAmountMap) {
            return isset($typeAmountMap[$item->type_category]);
        });



        foreach ($commonTypes as $commonItem) {
            $typeValue = $commonItem->type_category;
            $gap = $commonItem->total_amount - $typeAmountMap[$typeValue];

            DB::table('0_project_budgets')->updateOrInsert(
                [
                    'budget_type_id' => $typeValue,
                    'rab_no' => $parent
                ],
                [
                    'rab_amount' => DB::raw("rab_amount + $gap"),
                    'rab_no' => $trans_no
                ]
            );
        }

        $differentTypes = array_filter($man_power_new, function ($item) use ($typeAmountMap) {
            return !isset($typeAmountMap[$item->type_category]);
        });

        foreach ($differentTypes as $differentItem) {
            $typeValue = $differentItem->type_category;
            $amount = $differentItem->total_amount;

            $header = DB::table('0_project_budgets')
                ->insertGetId(array(
                    'project_no' => $new_rab_info->project_no,
                    'budget_type_id' => $typeValue,
                    'budget_name' => $new_rab_info->reference,
                    'rab_amount' => ceil($amount),
                    'amount' => 0,
                    'rab_no' => $trans_no,
                    'description' => 'Auto Generate By System',
                    'site_id' => $project_info->site_id,
                    'created_date' => Carbon::now(),
                    'created_by' => 1
                ));

            DB::table('0_project_budget_rab')
                ->insert(array(
                    'budget_id' => $header,
                    'budget_type_id' => $typeValue,
                    'project_no' => $project_info->project_no,
                    'amount' => ceil($amount),
                    'user_id' => 1,
                    'created_at' => Carbon::now()
                ));
        }
    }

    public static function update_budget_already_created_vehicle($trans_no, $parent)
    {
        $new_rab_info = DB::table('0_project_submission_rab')->where('trans_no', $trans_no)->first();
        $project_info = DB::table('0_projects')->where('project_no', $new_rab_info->project_no)->first();

        $man_power_parent = DB::table('0_project_submission_rab_vehicle_ops')->where('trans_no', $parent)
            ->select('type_category', DB::raw("SUM((CASE WHEN total_amount IS NULL THEN 0 ELSE total_amount END) ) AS total_amount"))
            ->groupBy('type_category')
            ->get()->toArray();

        $man_power_new = DB::table('0_project_submission_rab_vehicle_ops')->where('trans_no', $trans_no)
            ->select('type_category', DB::raw("SUM((CASE WHEN total_amount IS NULL THEN 0 ELSE total_amount END) ) AS total_amount"))
            ->groupBy('type_category')
            ->get()->toArray();

        $typeAmountMap = [];
        foreach ($man_power_parent as $item) {
            $typeAmountMap[$item->type_category] = $item->total_amount;
        }

        $commonTypes = array_filter($man_power_new, function ($item) use ($typeAmountMap) {
            return isset($typeAmountMap[$item->type_category]);
        });



        foreach ($commonTypes as $commonItem) {
            $typeValue = $commonItem->type_category;
            $gap = $commonItem->total_amount - $typeAmountMap[$typeValue];

            DB::table('0_project_budgets')->updateOrInsert(
                [
                    'budget_type_id' => $typeValue,
                    'rab_no' => $parent
                ],
                [
                    'rab_amount' => DB::raw("rab_amount + $gap"),
                    'rab_no' => $trans_no
                ]
            );
        }

        $differentTypes = array_filter($man_power_new, function ($item) use ($typeAmountMap) {
            return !isset($typeAmountMap[$item->type_category]);
        });

        foreach ($differentTypes as $differentItem) {
            $typeValue = $differentItem->type_category;
            $amount = $differentItem->total_amount;

            $header = DB::table('0_project_budgets')
                ->insertGetId(array(
                    'project_no' => $new_rab_info->project_no,
                    'budget_type_id' => $typeValue,
                    'budget_name' => $new_rab_info->reference,
                    'rab_amount' => ceil($amount),
                    'amount' => 0,
                    'rab_no' => $trans_no,
                    'description' => 'Auto Generate By System',
                    'site_id' => $project_info->site_id,
                    'created_date' => Carbon::now(),
                    'created_by' => 1
                ));

            DB::table('0_project_budget_rab')
                ->insert(array(
                    'budget_id' => $header,
                    'budget_type_id' => $typeValue,
                    'project_no' => $project_info->project_no,
                    'amount' => ceil($amount),
                    'user_id' => 1,
                    'created_at' => Carbon::now()
                ));
        }
    }

    public static function uppdate_budget_already_created_procurement($trans_no, $parent)
    {
        $new_rab_info = DB::table('0_project_submission_rab')->where('trans_no', $trans_no)->first();
        $project_info = DB::table('0_projects')->where('project_no', $new_rab_info->project_no)->first();

        $man_power_parent = DB::table('0_project_submission_rab_procurement')->where('trans_no', $parent)
            ->select('type_category', DB::raw("SUM((CASE WHEN total_amount IS NULL THEN 0 ELSE total_amount END) ) AS total_amount"))
            ->groupBy('type_category')
            ->get()->toArray();

        $man_power_new = DB::table('0_project_submission_rab_procurement')->where('trans_no', $trans_no)
            ->select('type_category', DB::raw("SUM((CASE WHEN total_amount IS NULL THEN 0 ELSE total_amount END) ) AS total_amount"))
            ->groupBy('type_category')
            ->get()->toArray();

        $typeAmountMap = [];
        foreach ($man_power_parent as $item) {
            $typeAmountMap[$item->type_category] = $item->total_amount;
        }

        $commonTypes = array_filter($man_power_new, function ($item) use ($typeAmountMap) {
            return isset($typeAmountMap[$item->type_category]);
        });



        foreach ($commonTypes as $commonItem) {
            $typeValue = $commonItem->type_category;
            $gap = $commonItem->total_amount - $typeAmountMap[$typeValue];

            DB::table('0_project_budgets')->updateOrInsert(
                [
                    'budget_type_id' => $typeValue,
                    'rab_no' => $parent
                ],
                [
                    'rab_amount' => DB::raw("rab_amount + $gap"),
                    'rab_no' => $trans_no
                ]
            );
        }

        $differentTypes = array_filter($man_power_new, function ($item) use ($typeAmountMap) {
            return !isset($typeAmountMap[$item->type_category]);
        });

        foreach ($differentTypes as $differentItem) {
            $typeValue = $differentItem->type_category;
            $amount = $differentItem->total_amount;

            $header = DB::table('0_project_budgets')
                ->insertGetId(array(
                    'project_no' => $new_rab_info->project_no,
                    'budget_type_id' => $typeValue,
                    'budget_name' => $new_rab_info->reference,
                    'rab_amount' => ceil($amount),
                    'amount' => 0,
                    'rab_no' => $trans_no,
                    'description' => 'Auto Generate By System',
                    'site_id' => $project_info->site_id,
                    'created_date' => Carbon::now(),
                    'created_by' => 1
                ));

            DB::table('0_project_budget_rab')
                ->insert(array(
                    'budget_id' => $header,
                    'budget_type_id' => $typeValue,
                    'project_no' => $project_info->project_no,
                    'amount' => ceil($amount),
                    'user_id' => 1,
                    'created_at' => Carbon::now()
                ));
        }
    }

    public static function update_budget_already_created_tools($trans_no, $parent)
    {
        $new_rab_info = DB::table('0_project_submission_rab')->where('trans_no', $trans_no)->first();
        $project_info = DB::table('0_projects')->where('project_no', $new_rab_info->project_no)->first();

        $man_power_parent = DB::table('0_project_submission_rab_tools')->where('trans_no', $parent)
            ->select('type_category', DB::raw("SUM((CASE WHEN total_amount IS NULL THEN 0 ELSE total_amount END) ) AS total_amount"))
            ->groupBy('type_category')
            ->get()->toArray();

        $man_power_new = DB::table('0_project_submission_rab_tools')->where('trans_no', $trans_no)
            ->select('type_category', DB::raw("SUM((CASE WHEN total_amount IS NULL THEN 0 ELSE total_amount END) ) AS total_amount"))
            ->groupBy('type_category')
            ->get()->toArray();

        $typeAmountMap = [];
        foreach ($man_power_parent as $item) {
            $typeAmountMap[$item->type_category] = $item->total_amount;
        }

        $commonTypes = array_filter($man_power_new, function ($item) use ($typeAmountMap) {
            return isset($typeAmountMap[$item->type_category]);
        });



        foreach ($commonTypes as $commonItem) {
            $typeValue = $commonItem->type_category;
            $gap = $commonItem->total_amount - $typeAmountMap[$typeValue];

            DB::table('0_project_budgets')->updateOrInsert(
                [
                    'budget_type_id' => $typeValue,
                    'rab_no' => $parent
                ],
                [
                    'rab_amount' => DB::raw("rab_amount + $gap"),
                    'rab_no' => $trans_no
                ]
            );
        }

        $differentTypes = array_filter($man_power_new, function ($item) use ($typeAmountMap) {
            return !isset($typeAmountMap[$item->type_category]);
        });

        foreach ($differentTypes as $differentItem) {
            $typeValue = $differentItem->type_category;
            $amount = $differentItem->total_amount;

            $header = DB::table('0_project_budgets')
                ->insertGetId(array(
                    'project_no' => $new_rab_info->project_no,
                    'budget_type_id' => $typeValue,
                    'budget_name' => $new_rab_info->reference,
                    'rab_amount' => ceil($amount),
                    'amount' => 0,
                    'rab_no' => $trans_no,
                    'description' => 'Auto Generate By System',
                    'site_id' => $project_info->site_id,
                    'created_date' => Carbon::now(),
                    'created_by' => 1
                ));

            DB::table('0_project_budget_rab')
                ->insert(array(
                    'budget_id' => $header,
                    'budget_type_id' => $typeValue,
                    'project_no' => $project_info->project_no,
                    'amount' => ceil($amount),
                    'user_id' => 1,
                    'created_at' => Carbon::now()
                ));
        }
    }

    public static function update_budget_already_created_training($trans_no, $parent)
    {
        $new_rab_info = DB::table('0_project_submission_rab')->where('trans_no', $trans_no)->first();
        $project_info = DB::table('0_projects')->where('project_no', $new_rab_info->project_no)->first();

        $man_power_parent = DB::table('0_project_submission_rab_training')->where('trans_no', $parent)
            ->select('type_category', DB::raw("SUM((CASE WHEN total_amount IS NULL THEN 0 ELSE total_amount END) ) AS total_amount"))
            ->groupBy('type_category')
            ->get()->toArray();

        $man_power_new = DB::table('0_project_submission_rab_training')->where('trans_no', $trans_no)
            ->select('type_category', DB::raw("SUM((CASE WHEN total_amount IS NULL THEN 0 ELSE total_amount END) ) AS total_amount"))
            ->groupBy('type_category')
            ->get()->toArray();

        $typeAmountMap = [];
        foreach ($man_power_parent as $item) {
            $typeAmountMap[$item->type_category] = $item->total_amount;
        }

        $commonTypes = array_filter($man_power_new, function ($item) use ($typeAmountMap) {
            return isset($typeAmountMap[$item->type_category]);
        });



        foreach ($commonTypes as $commonItem) {
            $typeValue = $commonItem->type_category;
            $gap = $commonItem->total_amount - $typeAmountMap[$typeValue];

            DB::table('0_project_budgets')->updateOrInsert(
                [
                    'budget_type_id' => $typeValue,
                    'rab_no' => $parent
                ],
                [
                    'rab_amount' => DB::raw("rab_amount + $gap"),
                    'rab_no' => $trans_no
                ]
            );
        }

        $differentTypes = array_filter($man_power_new, function ($item) use ($typeAmountMap) {
            return !isset($typeAmountMap[$item->type_category]);
        });

        foreach ($differentTypes as $differentItem) {
            $typeValue = $differentItem->type_category;
            $amount = $differentItem->total_amount;

            $header = DB::table('0_project_budgets')
                ->insertGetId(array(
                    'project_no' => $new_rab_info->project_no,
                    'budget_type_id' => $typeValue,
                    'budget_name' => $new_rab_info->reference,
                    'rab_amount' => ceil($amount),
                    'amount' => 0,
                    'rab_no' => $trans_no,
                    'description' => 'Auto Generate By System',
                    'site_id' => $project_info->site_id,
                    'created_date' => Carbon::now(),
                    'created_by' => 1
                ));

            DB::table('0_project_budget_rab')
                ->insert(array(
                    'budget_id' => $header,
                    'budget_type_id' => $typeValue,
                    'project_no' => $project_info->project_no,
                    'amount' => ceil($amount),
                    'user_id' => 1,
                    'created_at' => Carbon::now()
                ));
        }
    }

    public static function update_budget_already_created_otex($trans_no, $parent)
    {
        $new_rab_info = DB::table('0_project_submission_rab')->where('trans_no', $trans_no)->first();
        $project_info = DB::table('0_projects')->where('project_no', $new_rab_info->project_no)->first();

        $man_power_parent = DB::table('0_project_submission_rab_other_expenses')->where('trans_no', $parent)
            ->select('type_category', DB::raw("SUM((CASE WHEN total_amount IS NULL THEN 0 ELSE total_amount END)) AS total_amount"))
            ->groupBy('type_category')
            ->get()->toArray();

        $man_power_new = DB::table('0_project_submission_rab_other_expenses')->where('trans_no', $trans_no)
            ->select('type_category', DB::raw("SUM((CASE WHEN total_amount IS NULL THEN 0 ELSE total_amount END)) AS total_amount"))
            ->groupBy('type_category')
            ->get()->toArray();

        $typeAmountMap = [];
        foreach ($man_power_parent as $item) {
            $typeAmountMap[$item->type_category] = $item->total_amount;
        }

        $commonTypes = array_filter($man_power_new, function ($item) use ($typeAmountMap) {
            return isset($typeAmountMap[$item->type_category]);
        });



        foreach ($commonTypes as $commonItem) {
            $typeValue = $commonItem->type_category;
            $gap = $commonItem->total_amount - $typeAmountMap[$typeValue];

            DB::table('0_project_budgets')->updateOrInsert(
                [
                    'budget_type_id' => $typeValue,
                    'rab_no' => $parent
                ],
                [
                    'rab_amount' => DB::raw("rab_amount + $gap"),
                    'rab_no' => $trans_no
                ]
            );
        }

        $differentTypes = array_filter($man_power_new, function ($item) use ($typeAmountMap) {
            return !isset($typeAmountMap[$item->type_category]);
        });

        foreach ($differentTypes as $differentItem) {
            $typeValue = $differentItem->type_category;
            $amount = $differentItem->total_amount;

            $header = DB::table('0_project_budgets')
                ->insertGetId(array(
                    'project_no' => $new_rab_info->project_no,
                    'budget_type_id' => $typeValue,
                    'budget_name' => $new_rab_info->reference,
                    'rab_amount' => ceil($amount),
                    'amount' => 0,
                    'rab_no' => $trans_no,
                    'description' => 'Auto Generate By System',
                    'site_id' => $project_info->site_id,
                    'created_date' => Carbon::now(),
                    'created_by' => 1
                ));

            DB::table('0_project_budget_rab')
                ->insert(array(
                    'budget_id' => $header,
                    'budget_type_id' => $typeValue,
                    'project_no' => $project_info->project_no,
                    'amount' => ceil($amount),
                    'user_id' => 1,
                    'created_at' => Carbon::now()
                ));
        }
    }

    public static function update_budget_penalty_cap_interest_mgmt_cost($trans_no, $parent)
    {
        $additional = DB::table('0_project_submission_rab AS rab')
            ->leftJoin('0_project_submission_cash_flow as cashflow', 'cashflow.trans_no', '=', 'rab.trans_no')
            ->where('rab.trans_no', $trans_no)
            ->select(
                'rab.trans_no',
                'rab.reference',
                'rab.project_no',
                'rab.project_code',
                'rab.sales_person',
                'rab.project_value',
                'rab.total_budget',
                'rab.management_cost_pct',
                'rab.cost_of_money_pct',
                'rab.risk_management_pct',
                'rab.risk_management',
                'rab.debtor_no',
                'rab.area_id',
                'rab.work_start',
                'rab.work_end',
                'rab.remark',
                'rab.created_at',
                'cashflow.id as cashflow_id'
            )->first();
        $amount_penalty = ($additional->project_value * $additional->risk_management_pct);
        $amount_interest = self::calculated_interest($additional->cashflow_id, $additional->trans_no, $additional->work_start);
        $amount_mgmt_cost = ($additional->project_value * $additional->management_cost_pct);

        DB::table('0_project_budgets')->updateOrInsert(
            [
                'budget_type_id' => 710,
                'rab_no' => $parent
            ],
            [
                'rab_amount' => $amount_penalty,
                'rab_no' => $trans_no,
                'inactive' => 0
            ]
        );

        DB::table('0_project_budgets')->updateOrInsert(
            [
                'budget_type_id' => 709,
                'rab_no' => $parent
            ],
            [
                'rab_amount' => $amount_interest,
                'rab_no' => $trans_no,
                'inactive' => 0
            ]
        );

        DB::table('0_project_budgets')->updateOrInsert(
            [
                'budget_type_id' => 708,
                'rab_no' => $parent
            ],
            [
                'rab_amount' => $amount_mgmt_cost,
                'rab_no' => $trans_no,
                'inactive' => 0
            ]
        );
    }

    public function export_detail_rab($trans_no)
    {
        $response = [];

        try {
            $data =  DB::table('0_project_submission_rab as rab')
                ->leftJoin('0_project_submission_cash_flow as cashflow', 'cashflow.trans_no', '=', 'rab.trans_no')
                ->leftJoin('0_projects as p', 'p.project_no', '=', 'rab.project_no')
                ->leftJoin('0_hrm_divisions as d', 'd.division_id', '=', 'p.division_id')
                ->leftJoin('0_project_area as pa', 'pa.area_id', '=', 'rab.area_id')
                ->leftJoin('0_members as m', 'm.person_id', '=', 'p.person_id')
                ->leftJoin('0_members as head', 'head.person_id', '=', 'rab.project_head_id')
                ->leftJoin('0_debtors_master as dt', 'dt.debtor_no', '=', 'rab.debtor_no')
                ->leftJoin('0_hrm_employees as e', 'e.id', '=', 'rab.pc_user_id')
                ->leftJoin('users as u', 'u.id', '=', 'rab.created_by')
                ->select(
                    'rab.trans_no',
                    'rab.parent',
                    'rab.revision',
                    'rab.reference',
                    'rab.project_no',
                    'rab.project_code',
                    'p.name as project_name',
                    'm.name as project_manager',
                    'd.division_id',
                    'd.name as division_name',
                    'rab.sales_person',
                    'rab.project_value',
                    'rab.total_budget',
                    'rab.management_cost_pct',
                    'rab.cost_of_money_pct',
                    'rab.risk_management_pct',
                    'rab.risk_management',
                    'rab.debtor_no',
                    'dt.name as customer_name',
                    'rab.area_id',
                    'pa.name as area_name',
                    'head.name as project_head_name',
                    'e.name as pc_user_name',
                    'rab.work_start',
                    'rab.work_end',
                    'rab.remark',
                    'rab.approval',
                    DB::raw("CASE
                    WHEN rab.approval = 0 THEN 'New'
                    WHEN rab.approval = 1 THEN 'DGM'
                    WHEN rab.approval = 3 THEN 'GM'
                    WHEN rab.approval = 4 THEN 'BPC & PMO'
                    WHEN rab.approval = 42 THEN 'Director Ops.'
                    WHEN rab.approval = 41 THEN 'Director'
                    WHEN rab.approval = 7 THEN 'Approved' END AS approval_name"),
                    DB::raw("CASE
                    WHEN rab.status_id = 0 THEN 'Open'
                    WHEN rab.status_id = 1 THEN 'Approve'
                    WHEN rab.status_id = 2 THEN 'Pending'
                    WHEN rab.status_id = 3 THEN 'Disapprove' END AS status_name"),
                    'rab.status_id',
                    'rab.created_at',
                    'cashflow.id as cashflow_id',
                    'u.name as created_by',
                    'rab.created_by as creator_id'
                )
                ->where('rab.trans_no', $trans_no)
                ->get();

            foreach ($data as $val) {
                $risk_management = $val->project_value * $val->risk_management_pct;
                $management_cost = ($val->management_cost_pct * $val->project_value); /* project value * management cost*/
                $cost_of_money = self::calculated_interest($val->cashflow_id, $val->trans_no, $val->work_start);
                $total_expense =  $cost_of_money + $val->total_budget + $management_cost + $risk_management;  /* (total budget + management cost + cost of money)*/
                $margin_value = $val->project_value - ($val->total_budget + $management_cost + $risk_management + $cost_of_money); /* project value - (total budget + management cost + cost of money)*/
                // $margin_value = $val->project_value - $total_expense; /* (project value - total expense) ferry by excel*/
                $margin_pct = empty($margin_value) || $margin_value <= 0  ? 0 : ($margin_value / $val->project_value) * 100;

                $interestPct = ProjectBudgetController::get_interest_rab_val($val->work_start);

                $header = [];
                $header['trans_no'] = $val->trans_no;
                $header['reference'] = $val->reference;
                $header['project_no'] = $val->project_no;
                $header['project_code'] = $val->project_code;
                $header['project_name'] = $val->project_name;
                $header['project_manager'] = $val->project_manager;
                $header['division_name'] = $val->division_name;
                $header['sales_person'] = $val->sales_person;
                $header['project_value'] = $val->project_value;
                $header['total_budget'] = $val->total_budget;
                $header['management_cost_pct'] = $val->management_cost_pct;
                $header['management_cost'] = $management_cost;
                $header['cost_of_money_permonth'] = 0;
                $header['cost_of_money_pct'] = $interestPct;
                $header['risk_management_pct'] = $val->risk_management_pct;
                $header['risk_management'] = $risk_management;
                $header['total_sales'] = $val->project_value;
                $header['total_expenses'] = $total_expense;
                $header['margin_pct'] = $margin_pct;
                $header['margin_value'] = $margin_value;
                $header['debtor_no'] = $val->debtor_no;
                $header['customer'] = $val->customer_name;
                $header['area_name'] = $val->area_name;
                $header['project_head_name'] = $val->project_head_name;
                $header['pc_user_name'] = $val->pc_user_name;
                $header['work_start'] = $val->work_start;
                $header['work_end'] = $val->work_end;
                $header['remark'] = $val->remark;
                $header['approval'] = $val->approval;
                $header['approval_name'] = $val->approval_name;
                $header['status_id'] = $val->status_id;
                $header['status_name'] = $val->status_name;
                $header['created_at'] = $val->created_at;
                $header['created_by'] = $val->created_by;

                $header['man_power'] = DB::table('0_project_submission_rab_man_power as rab')
                    ->leftJoin('0_project_position_rab as pct', 'pct.id', '=', 'rab.position_id')
                    ->leftJoin('0_hrm_employees as emp', 'emp.emp_id', '=', 'rab.emp_id')
                    ->where('trans_no', $val->trans_no)
                    ->select('rab.*', 'pct.position', 'emp.name')->get();
                $header['vehicle'] = DB::table('0_project_submission_rab_vehicle_ops as rab')
                    ->leftJoin('0_project_cost_type_group as pct', 'pct.cost_type_group_id', '=', 'rab.type_category')
                    ->where('rab.trans_no', $val->trans_no)
                    ->select('rab.*', 'pct.name as type_name')->get();
                $header['procurement'] = DB::table('0_project_submission_rab_procurement as rab')
                    ->leftJoin('0_project_cost_type_group as pct', 'pct.cost_type_group_id', '=', 'rab.type_category')
                    ->where('rab.trans_no', $val->trans_no)
                    ->select('rab.*', 'pct.name as type_name')->get();
                $header['tools'] = DB::table('0_project_submission_rab_tools as rab')
                    ->leftJoin('0_project_cost_type_group as pct', 'pct.cost_type_group_id', '=', 'rab.type_category')
                    ->where('rab.trans_no', $val->trans_no)
                    ->select('rab.*', 'pct.name as type_name')->get();
                $header['training'] = DB::table('0_project_submission_rab_training as rab')
                    ->leftJoin('0_project_cost_type_group as pct', 'pct.cost_type_group_id', '=', 'rab.type_category')
                    ->where('rab.trans_no', $val->trans_no)
                    ->select('rab.*', 'pct.name as type_name')->get();

                $header['other_expenses'] = DB::table('0_project_submission_rab_other_expenses as rab')
                    ->leftJoin('0_project_cost_type_group as pct', 'pct.cost_type_group_id', '=', 'rab.type_category')
                    ->where('trans_no', $val->trans_no)
                    ->select('rab.*', 'pct.name as type_name')->get();

                $header['other_info'] = DB::table('0_project_submission_rab_other_information as rab')
                    ->leftJoin('0_project_cost_type_group as pct', 'pct.cost_type_group_id', '=', 'rab.type_category')
                    ->where('trans_no', $val->trans_no)
                    ->select('rab.*', 'pct.name as type_name')->get();

                $header['project_value_list'] = DB::table('0_project_submission_rab_project_value as rab')
                    ->leftJoin('0_project_site as pct', 'pct.site_no', '=', 'rab.site_no')
                    ->where('trans_no', $val->trans_no)
                    ->select('rab.*', 'pct.name as site_name')->get();

                $response[] = $header;
            }
            return Excel::download(new RABExport($response), "adwawdaw.xlsx");
        } catch (Exception $e) {
            return response()->json(['error' => [
                'message' => 'Something Wrong!',
                'status_code' => 403,
            ]], 403);
        }
    }


    public function create_new_spp(Request $request)
    {
        // $all_request = $request->data;
        $po_ref = $request->po_ref;

        DB::beginTransaction();
        try {

            $sql = "SELECT t2.po_detail_id, t2.line, sum(t2.termin_line_pct) as termin_line_pct  FROM 0_project_spp t1
                    INNER JOIN 0_project_spp_detail t2 ON t1.spp_id = t2.spp_id
                    WHERE t1.po_ref =$po_ref AND t1.status != 2 GROUP BY t2.po_detail_id";

            $data = DB::select(DB::raw($sql));
            foreach ($data as $items) {
                if (isset($items->po_detail_id)) {

                    foreach ($request->spp_details as $spp_detail) {
                        if ($spp_detail['po_detail_item'] == $items->po_detail_id && $request->percentage > (100 - $items->termin_line_pct)) {
                            return response()->json(['error' => [
                                'message' => 'Line ' . $items->line . ' : Persentase yang tersisa ' . (100 - $items->termin_line_pct) . '%',
                                'status_code' => 403,
                            ]], 403);
                        }
                        // print_r($spp_detail['po_detail_item']);
                    }
                }
            }

            $sql1 = "SELECT t1.termin, t2.line, t2.po_detail_id FROM 0_project_spp t1
            INNER JOIN 0_project_spp_detail t2 ON t1.spp_id = t2.spp_id
            WHERE t1.po_ref =$po_ref AND t1.status != 2";

            $data1 = DB::select(DB::raw($sql1));
            foreach ($data1 as $items) {
                if (isset($items->po_detail_id)) {

                    foreach ($request->spp_details as $spp_details) {
                        if ($spp_details['po_detail_item'] == $items->po_detail_id && $request->termin == $items->termin) {
                            return response()->json(['error' => [
                                'message' => 'Termin ' . $request->termin . ' dengan line ' . $items->line . ' sudah ada!',
                                'status_code' => 403,
                            ]], 403);
                        }
                    }
                }
            }

            // $spp_info = DB::table('0_project_spp')->where('po_ref', $po_ref)->orderBy('spp_id', 'desc')->get();

            $po_info = DB::table('0_purch_orders')->where('reference', $po_ref)->orderBy('order_no', 'desc')->first();
            // $cash_flow_info->id ;
            if (empty($po_ref)) {
                return response()->json(['error' => [
                    'message' => 'Harap input nomor po terlebih dahulu',
                    'status_code' => 403,
                ]], 403);
            } else {
                $total_line_po = 0;
                $total_po = 0;
                $total_line_spp = 0;
                $total_spp = 0;
                $deduction = 0;
                $total_deduction = 0;
                $qty_paid = 0;

                $term_pct = $request->percentage / 100;
                //header
                $spp_id = DB::table('0_project_spp')
                    ->insertGetId(array(
                        'spk_no' => $request->spk_no,
                        'po_ref' => $po_ref,
                        'order_no' => $request->po_customer,
                        'trans_date' => Carbon::now(),
                        'supplier_id' => $po_info->supplier_id,
                        'termin' => $request->termin,
                        'termin_pct' => $request->percentage,
                        'bank_name' => $request->bank_name,
                        'rekening' => $request->rekening,
                        'rek_name' => $request->rek_name,
                        'remark' => $request->remark,
                        'po_lines' => $request->po_lines,
                        'approval' => 1,
                        'created_at' => Carbon::now(),
                        'created_by' => $this->user_id
                    ));

                //details
                if (!empty($request->spp_details)) {
                    foreach ($request->spp_details as $spp_detail) {

                        $total_line_po = $spp_detail['unit_price'] * $spp_detail['qty_ordered'];
                        $total_line_spp =  $total_line_po * $term_pct;
                        $qty_paid = $spp_detail['qty_ordered'] * $term_pct;

                        DB::table('0_project_spp_detail')
                            ->insert(
                                [
                                    'spp_id' => $spp_id,
                                    'line' => $spp_detail['line'],
                                    'product_id' => $spp_detail['product_id'],
                                    'item_code' => $spp_detail['item_code'],
                                    'description' => $spp_detail['description'],
                                    'project_code' => $spp_detail['project_code'],
                                    'budget_id' => $spp_detail['budget_id'],
                                    'site_no' => $spp_detail['site_no'],
                                    'site_id' => $spp_detail['site_id'],
                                    'site_name' => $spp_detail['site_name'],
                                    'qty_ordered' => $spp_detail['qty_ordered'],
                                    'uom' => $spp_detail['uom'],
                                    'unit_price' => $spp_detail['unit_price'],
                                    'qty_invoice' => $spp_detail['qty_invoice'],
                                    'qty_paid' => $qty_paid,
                                    'total_line_spp' => $total_line_spp,
                                    'type_deduction' => $spp_detail['type_deduction'],
                                    'deduction' => $spp_detail['deduction'],
                                    'remark' => $spp_detail['remark'],
                                    'po_detail_id' => $spp_detail['po_detail_item'],
                                    'termin_line_pct' => $request->percentage
                                ]
                            );
                        $total_po += $total_line_po;
                        $deduction += $spp_detail['deduction'];
                    }
                }


                $total_deduction = $request->deduction == 0 ? $deduction : $request->deduction;
                $total_spp = $total_po * $term_pct;
                $net_spp = $total_spp - $total_deduction;

                if ($request->deduction != 0) { // deduction di header
                    DB::table('0_project_spp')->where('spp_id',  $spp_id)
                        ->update(array(
                            'total_po' => $total_po,
                            'total_spp' => $total_spp,
                            'total_deduction' => $total_deduction,
                            'net_spp' => $net_spp,

                            'deduction_remark' => $request->deduction_remark,
                            'type_deduction' => $request->type_deduction
                        ));
                } else { // deduction di detail
                    DB::table('0_project_spp')->where('spp_id',  $spp_id)
                        ->update(array(
                            'total_po' => $total_po,
                            'total_spp' => $total_spp,
                            'total_deduction' => $total_deduction,
                            'net_spp' => $net_spp
                        ));
                }

                $purchOrders = DB::table('0_purch_orders')->where('reference', $po_ref)
                    ->select('order_no')
                    ->first();
                DB::table('0_purch_order_terms')->where('order_no',  $purchOrders->order_no)->where('termin', $request->termin)
                    ->update(array(
                        'used' => 1
                    ));

                // Commit Transaction
                DB::commit();
            }
            return response()->json([
                'success' => true
            ]);
        } catch (Exception $e) {
            // echo 'Terjadi kesalahan: ' . $e->getMessage();

            return response()->json(['error' => [
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'status_code' => 400,
            ]], 400);
            // Rollback Transaction
            DB::rollback();
        }
    }

    function update_spp(Request $request, $spp_id)
    {
        $user_id = $this->user_id;

        DB::beginTransaction();
        try {
            if (isset($spp_id)) {
                $spp_info = DB::table('0_project_spp')->where('spp_id',  $spp_id)->first();
                $created_by = (int)$spp_info->created_by;

                if ($user_id != $created_by){
                    DB::rollback();
                    return response()->json(['error' => [
                        'message' => 'Update SPP tidak bisa dilakuakn oleh admin lain!',
                        'status_code' => 403,
                    ]], 403);
                }

                DB::table('0_project_spp')->where('spp_id',  $spp_id)
                    ->update(array(
                        'spk_no' => $request->spk_no,
                        'bank_name' => $request->bank_name,
                        'rekening' => $request->rekening,
                        'rek_name' => $request->rek_name
                    ));


                // Commit Transaction
                DB::commit();
            }

            return response()->json([
                'success' => true,
                'is_user_created' => $user_id == $created_by ? true : false
            ]);
        } catch (Exception $e) {
            // echo 'Terjadi kesalahan: ' . $e->getMessage();

            // Rollback Transaction
            DB::rollback();

            return response()->json(['error' => [
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'status_code' => 400,
            ]], 400);
        }
    }

    public function get_spp_list(Request $request)
    {

        $response = [];
        $search = $request->search;
        $approval = $request->approval;
        $status = $request->status;
        $user_id = $this->user_id;
        $approval_user = $this->user_level;
        $user_level = $this->user_level;

        try {

            $sql = "SELECT sp.spp_id, 
                            sp.spk_no,
                            sp.po_ref,
                            sp.order_no,
                            sp.trans_date,
                            supl.supp_name AS supplier_name,
                            sp.termin,
                            sp.termin_pct,
                            sp.po_lines,
                            sp.approval,
                            CASE
                                WHEN sp.approval = 1 AND sp.status = 0 THEN 'On PM' -- admin create, lanjut approval PM
                                WHEN sp.approval = 2 AND sp.status = 3 THEN 'Pending PM'
                                WHEN sp.approval = 2 AND sp.status = 1 THEN 'On GM' -- PM approved, lanjut approval GM
                                WHEN sp.approval = 3 AND sp.status = 3 THEN 'Pending GM'
                                WHEN sp.approval = 3 AND sp.status = 1 THEN 'On BPC' -- GM approved, lanjut approval BPC
                                WHEN sp.approval = 7 AND sp.status = 3 THEN 'Pending BPC'
                                WHEN sp.approval = 7 AND sp.status = 1 THEN 'APPROVED' -- change to On Finance
                                WHEN sp.approval = 8 AND sp.status = 3 THEN 'Pending Finance'
                                WHEN sp.approval = 8 AND sp.status = 4 THEN 'On Finance Review'
                                WHEN sp.approval = 8 AND sp.status = 5 THEN 'Incorrect Invoice'
                                WHEN sp.approval = 8 AND sp.status = 1 THEN 'Invoice Approved'
                                WHEN sp.status = 2 THEN 'DISAPPROVE'
                                ELSE 'unknown'
                            END AS pic,
                            sp.status,
                            sp.created_at,
                            u.name AS created_by,
                            spd.project_code
                        FROM 0_project_spp AS sp
                        JOIN 0_suppliers AS supl ON (supl.supplier_id = sp.supplier_id)
                        JOIN users AS u ON (u.id = sp.created_by)
                        JOIN 0_project_spp_detail AS spd ON (spd.spp_id = sp.spp_id)
                        WHERE sp.spp_id != -1";

            if ($approval != "") {
                $sql .= " AND sp.approval = $approval";
            }
            if ($status != "") {
                $sql .= " AND sp.status = $status";
            }
            if ($search != "") {
                $sql .= " AND (sp.po_ref LIKE '$search%' OR spd.project_code LIKE '$search%' OR supl.supp_name LIKE '$search%')";
            }
            if ($user_level == 111) {
                $sql .= " AND sp.created_by = $user_id";
            }

            $sql .= " GROUP BY sp.spp_id ORDER BY sp.created_at DESC LIMIT 120";

            $data = DB::select(DB::raw($sql));

            foreach ($data as $val) {

                $so_info = DB::table('0_sales_orders')->where('order_no', $val->order_no)->first();

                $header = [];
                $header['spp_id'] = $val->spp_id;
                $header['spk_no'] = $val->spk_no;
                $header['po_ref'] = $val->po_ref;
                $header['po_customer'] = $so_info->reference;
                $header['project_code'] = $val->project_code;
                $header['trans_date'] = $val->trans_date;
                $header['supplier_name'] = $val->supplier_name;
                $header['termin'] = $val->termin;
                $header['po_lines'] = $val->po_lines;
                $header['termin_pct'] = $val->termin_pct;
                $header['approval'] = $val->approval;
                $header['approval_name'] = $val->pic;
                $header['status'] = $val->status;
                $header['created_at'] = $val->created_at;
                $header['created_by'] = $val->created_by;
                array_push($response, $header);
            }

            return response()->json([
                'success' => true,
                'data' => $response
            ]);
        } catch (Exception $e) {
            echo 'Terjadi kesalahan: ' . $e->getMessage();

            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }
    }

    public function get_spp_by_id($spp_id)
    {
        $response = null;
        $get_spp_by_id = DB::table('0_project_spp as sp')
            ->select(
                'sp.spp_id',
                'sp.spk_no',
                'sp.po_ref',
                'sp.order_no',
                'sp.trans_date',
                'sp.termin',
                'sp.po_lines',
                'sp.termin_pct',
                'sp.bank_name',
                'sp.rekening',
                'sp.rek_name',
                'sp.total_spp',
                'sp.total_deduction',
                'sp.net_spp',
                'sp.type_deduction',
                'sp.deduction_remark',
                'sp.remark',
                'sp.approval',
                'sp.created_at',
                'supl.supp_name as supplier_name',
                'po.total as total_po',
                'u.name as created_by'
            )
            ->join('0_suppliers as supl', 'supl.supplier_id', '=', 'sp.supplier_id')
            ->join('users as u', 'u.id', '=', 'sp.created_by')
            ->join('0_purch_orders as po', 'po.reference', '=', 'sp.po_ref')
            ->leftJoin('0_project_spp_detail as spd', 'spd.spp_id', '=', 'sp.spp_id')
            ->leftJoin('0_projects as p', 'p.code', '=', 'spd.project_code')
            ->leftJoin('0_members as m', 'p.person_id', '=', 'm.person_id')
            ->where('sp.spp_id', $spp_id)
            ->limit(1)
            ->get();

        $pm = DB::table('0_project_spp_log AS aprv')
            ->leftJoin('users AS u', 'u.id', '=', 'aprv.person_id')
            ->where('aprv.spp_id', $spp_id)->where('aprv.approval', 1)->where('aprv.status', 1)
            ->select(
                'aprv.last_update',
                'u.name',
                'aprv.status',
                'aprv.remark',
                'u.signature AS signature_exist',
                DB::raw("CONCAT('http://192.168.0.5/storage/profiles/signature/',u.id,'.png') AS signature")
            )
            ->orderBy('aprv.id', 'desc')
            ->limit(1)
            ->get();

        foreach ($pm as $val => $key) {
            if ($key->signature_exist == 0) {
                return response()->json([
                    'error' => array(
                        'message' => $key->name . ' ' . "hasn't added a signature!",
                        'status_code' => 403
                    )
                ], 403);
            }
        }

        $gm = DB::table('0_project_spp_log AS aprv')
            ->leftJoin('users AS u', 'u.id', '=', 'aprv.person_id')
            ->where('aprv.spp_id', $spp_id)->where('aprv.approval', 2)->where('aprv.status', 1)
            ->select(
                'aprv.last_update',
                'u.name',
                'aprv.status',
                'aprv.remark',
                'u.signature AS signature_exist',
                DB::raw("CONCAT('http://192.168.0.5/storage/profiles/signature/',u.id,'.png') AS signature")
            )
            ->orderBy('aprv.id', 'desc')
            ->limit(1)
            ->get();

        foreach ($gm as $val => $key) {
            if ($key->signature_exist == 0) {
                return response()->json([
                    'error' => array(
                        'message' => $key->name . ' ' . "hasn't added a signature!",
                        'status_code' => 403
                    )
                ], 403);
            }
        }

        $bpc = DB::table('0_project_spp_log AS aprv')
            ->leftJoin('users AS u', 'u.id', '=', 'aprv.person_id')
            ->where('aprv.spp_id', $spp_id)->where('aprv.approval', 3)->where('aprv.status', 1)
            ->select(
                'aprv.last_update',
                'u.name',
                'aprv.status',
                'aprv.remark',
                'u.signature AS signature_exist',
                DB::raw("CONCAT('http://192.168.0.5/storage/profiles/signature/',u.id,'.png') AS signature")
            )
            ->orderBy('aprv.id', 'desc')
            ->limit(1)
            ->get();

        foreach ($bpc as $val => $key) {
            if ($key->signature_exist == 0) {
                return response()->json([
                    'error' => array(
                        'message' => $key->name . ' ' . "hasn't added a signature!",
                        'status_code' => 403
                    )
                ], 403);
            }
        }

        $log_spp = DB::table('0_project_spp_log AS log')
            ->leftJoin('users AS u', 'u.id', '=', 'log.person_id')
            ->where('log.spp_id', $spp_id)
            ->select(
                'log.id',
                'u.name',
                'log.approval',
                DB::raw("CASE
                WHEN log.approval = 1 THEN 'Project Manager'
                WHEN log.approval = 2 THEN 'GM'
                WHEN log.approval = 3 THEN 'BPC & PMO'
                WHEN log.approval = 7 THEN 'Finance' END AS approval_name"),
                'log.status',
                'log.remark',
                'log.last_update',
            )
            ->orderBy('log.id', 'asc')
            ->get();

        foreach ($get_spp_by_id as $data) {

            $so_info = DB::table('0_sales_orders')->where('order_no', $data->order_no)->first();

            $tmp = [];
            $tmp['spp_id'] = $data->spp_id;
            $tmp['supplier_name'] = $data->supplier_name;
            $tmp['bank_name'] = $data->bank_name;
            $tmp['rekening'] = $data->rekening;
            $tmp['rek_name'] = $data->rek_name;
            $tmp['spk_no'] = $data->spk_no;
            $tmp['po_ref'] = $data->po_ref;
            $tmp['po_customer'] = $so_info->customer_ref;
            $tmp['total_po'] = $data->total_po;
            $tmp['trans_date'] = $data->trans_date;
            $tmp['termin'] = $data->termin;
            $tmp['po_lines'] = $data->po_lines;
            $tmp['termin_pct'] = $data->termin_pct;
            $tmp['total_spp'] = $data->total_spp;
            $tmp['total_deduction'] = $data->total_deduction;
            $tmp['net_spp'] = $data->net_spp;
            $tmp['type_deduction'] = $data->type_deduction;
            $tmp['deduction_remark'] = $data->deduction_remark;
            $tmp['remark'] = $data->remark;
            $tmp['created_by'] = $data->created_by;

            $tmp['sign_pm'] = $pm;
            $tmp['sign_gm'] = $gm;
            $tmp['sign_bpc'] = $bpc;
            $tmp['log_spp'] = $log_spp;

            $sql1 = "SELECT * FROM 0_project_spp_detail
                     WHERE spp_id = $data->spp_id";

            $get_detail = DB::select(DB::raw($sql1));
            foreach ($get_detail as $data) {
                $items = [];
                $items['spp_id'] = $data->spp_id;
                $items['line'] = $data->line;
                $items['product_id'] = $data->product_id;
                $items['item_code'] = $data->item_code;
                $items['description'] = $data->description;
                $items['project_code'] = $data->project_code;
                $items['budget_id'] = $data->budget_id;
                $items['site_no'] = $data->site_no;
                $items['site_id'] = $data->site_id;
                $items['site_name'] = $data->site_name;
                $items['qty_ordered'] = $data->qty_ordered;
                $items['uom'] = $data->uom;
                $items['unit_price'] = $data->unit_price;
                $items['total_price'] = $data->qty_ordered * $data->unit_price;
                $items['qty_invoice'] = $data->qty_invoice;
                $items['qty_paid'] = $data->qty_paid;
                $items['total_line_spp'] = $data->total_line_spp;
                $items['type_deduction'] = $data->type_deduction;
                $items['deduction'] = $data->deduction;
                $items['remark'] = $data->remark;
                $items['po_detail_id'] = $data->po_detail_id;
                $tmp['spp_detail'][] = $items;
            }

            // array_push($response, $tmp);
            $response = $tmp;
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }


    public function get_spp_need_approve(Request $request)
    {
        $response = [];
        $search = $request->search;
        $level = $this->user_level;
        $person_id = $this->user_person_id;
        $user_id = $this->user_id;
        $old_id = $this->user_old_id;
        $emp_id = $this->user_emp_id;

        // Base query components
        $select = "SELECT
                sp.spp_id, 
                sp.spk_no,
                sp.po_ref,
                sp.order_no,
                sp.trans_date,
                supl.supp_name AS supplier_name,
                sp.termin,
                sp.termin_pct,
                sp.po_lines,
                sp.approval,
                CASE
                    WHEN sp.approval = 1 AND sp.status = 0 THEN 'On PM'
                    WHEN sp.approval = 2 AND sp.status = 3 THEN 'Pending PM'
                    WHEN sp.approval = 2 AND sp.status = 1 THEN 'On GM'
                    WHEN sp.approval = 3 AND sp.status = 3 THEN 'Pending GM'
                    WHEN sp.approval = 3 AND sp.status = 1 THEN 'On BPC'
                    WHEN sp.approval = 7 AND sp.status = 3 THEN 'Pending BPC'
                    WHEN sp.approval = 7 AND sp.status = 1 THEN 'APPROVED' -- change to On Finance
                    WHEN sp.approval = 8 AND sp.status = 3 THEN 'Pending Finance'
                    WHEN sp.approval = 8 AND sp.status = 4 THEN 'On Finance Review'
                    WHEN sp.approval = 8 AND sp.status = 5 THEN 'Incorrect Invoice'
                    WHEN sp.approval = 8 AND sp.status = 1 THEN 'Invoice Approved'
                    WHEN sp.status = 2 THEN 'DISAPPROVE'
                    ELSE 'unknown'
                END AS pic,
                sp.status,
                p.person_id,
                sp.created_at,
                sp.attachments,
                u.name AS created_by,
                spd.project_code
            FROM 0_project_spp AS sp 
            JOIN 0_suppliers AS supl ON supl.supplier_id = sp.supplier_id
            JOIN users AS u ON u.id = sp.created_by
            JOIN 0_project_spp_detail AS spd ON spd.spp_id = sp.spp_id
            LEFT JOIN 0_projects AS p ON p.code = spd.project_code
            LEFT JOIN 0_members AS m ON p.person_id = m.person_id";

        $searchQuery = $search ? " AND (sp.po_ref LIKE '$search%' OR spd.project_code LIKE '$search%' OR supl.supp_name LIKE '$search%')" : "";

        // Conditions and queries based on $level
        switch ($level) {
            case 1:
                $query = $select . " WHERE CASE 
                            WHEN ((sp.approval = 1 AND sp.status = 0) OR (sp.approval = 2 AND sp.status = 3)) 
                                THEN m.person_id = $person_id 
                            ELSE sp.spp_id = -1 
                        END" . $searchQuery . " GROUP BY sp.spp_id ORDER BY sp.created_at DESC";
                break;

            case 2:
            case 3:
                $query = $select . " WHERE CASE 
                            WHEN ((sp.approval = 1 AND sp.status = 0) OR (sp.approval = 2 AND sp.status = 3)) 
                                THEN m.person_id = $person_id 
                            WHEN ((sp.approval = 2 AND sp.status = 1) OR (sp.approval = 3 AND sp.status = 3)) 
                                THEN p.division_id IN (SELECT division_id FROM 0_user_divisions WHERE user_id = $old_id) 
                            ELSE sp.spp_id = -1 
                        END" . $searchQuery . " GROUP BY sp.spp_id ORDER BY sp.created_at DESC";
                break;

            case 4:
                $query = $select . " WHERE CASE 
                            WHEN ((sp.approval = 3 AND sp.status = 1) OR (sp.approval = 7 AND sp.status = 3)) 
                                THEN p.division_id IN (SELECT division_id FROM 0_user_project_control WHERE user_id = $old_id) 
                            ELSE sp.spp_id = -1 
                        END" . $searchQuery . " GROUP BY sp.spp_id ORDER BY sp.created_at DESC";
                break;

            case 999:
                $query = $select . " WHERE ((sp.approval <= 3 AND sp.status IN (0,1)) OR (sp.approval <= 7 AND sp.status = 3))" . $searchQuery . " GROUP BY sp.spp_id ORDER BY sp.created_at DESC LIMIT 20";
                break;

            default:
                $query = "SELECT * FROM 0_project_spp WHERE spp_id = ?";
                break;
        }

        // return $query;

        $spp_need_approve = DB::select($query);

        foreach ($spp_need_approve as $data) {


            $so_info = DB::table('0_sales_orders')->where('order_no', $data->order_no)->first();

            $tmp = [];
            $tmp['spp_id'] = $data->spp_id;
            $tmp['spk_no'] = $data->spk_no;
            $tmp['po_ref'] = $data->po_ref;
            $tmp['po_customer'] = $so_info->reference;
            $tmp['project_code'] = $data->project_code;
            $tmp['trans_date'] = $data->trans_date;
            $tmp['supplier_name'] = $data->supplier_name;
            $tmp['termin'] = $data->termin;
            $tmp['po_lines'] = $data->po_lines;
            $tmp['termin_pct'] = $data->termin_pct;
            $tmp['approval'] = $data->approval;
            $tmp['approval_name'] = $data->pic;
            $tmp['status'] = $data->status;
            $tmp['created_at'] = $data->created_at;
            $tmp['created_by'] = $data->created_by;
            $tmp['attachments'] = [];

            $attachments = explode(';', $data->attachments);

            foreach ($attachments as $file) {
                $url_file = URL::to("/storage/e-spp/attachments/$file");
                array_push($tmp['attachments'], $url_file);
            }

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    function update_spp_approved(Request $request, $spp_id)
    {

        $level = $this->user_level;
        $person_id = $this->user_person_id;
        $user_id = $this->user_id;
        $old_id = $this->user_old_id;
        $emp_id = $this->user_emp_id;
        $status = $request->status;

        // DB::beginTransaction();
        try {

            switch ($level) {
                case 1:
                    $spp_info = DB::table('0_project_spp')->where('spp_id', $spp_id)->first();
                    $approvedLog = 1;
                    $approved = 2; // telah di approve PM

                    if ($status == 2) {
                        $info_creator = DB::table('users')->where('id', $spp_info->created_by)->first();
                        $details_send_mail = [
                            'title' => 'Rejected SPP',
                            'user' => $info_creator->name,
                            'spp_id' => $spp_info->spp_id,
                            'po_ref' => $spp_info->po_ref,
                            'termin' => $spp_info->termin,
                            'trans_date' => $spp_info->trans_date,
                            'po_lines' => $spp_info->po_lines,
                            'reject_by' => $this->user_name
                        ];

                        // \Mail::to($info_creator->email)->send(new \App\Mail\RABRejectNotify($details_send_mail));

                        // restore termin used
                        $po_order_no = DB::table('0_purch_orders')->where('reference', $spp_info->po_ref)->value('order_no');
                        DB::table('0_purch_order_terms')->where('order_no', $po_order_no)
                            ->where('termin', $spp_info->termin)
                            ->update(['used' => 0]);
                    }

                    DB::table('0_project_spp')->where('spp_id', $spp_id)
                        ->update(array(
                            'approval' => $approved,
                            'status' =>   $status,
                            'updated_at' => Carbon::now(),
                            'updated_by' => $user_id
                        ));

                    DB::table('0_project_spp_log')->insert(array(
                        'spp_id' => $spp_id,
                        'approval' => $approvedLog,
                        'status' => $status,
                        'remark' => $request->remark,
                        'person_id' => Auth::guard()->user()->id,
                        'last_update' => Carbon::now()
                    ));

                    return response()->json([
                        'success' => true
                    ], 200);

                    break;
                case 2:
                case 3:

                    $spp_info = DB::table('0_project_spp')->where('spp_id', $spp_id)->first();

                    // jika gm melakukan approve sebagai PM maka next approval = 2
                    // jika gm melakukan approval sebagai GM maka next approval = 3
                    $isPM = false;

                    if($spp_info->approval == 1 && $spp_info->status == 0) {
                        $isPM = true;
                    } else if($spp_info->approval == 2 && $spp_info->status == 3) {
                        $isPM = true;
                    }

                    $approvedLog = $isPM ? 1 : 2;
                    $approved = $isPM ? 2 : 3;

                    if ($status == 2) {
                        $info_creator = DB::table('users')->where('id', $spp_info->created_by)->first();
                        $details_send_mail = [
                            'title' => 'Rejected SPP',
                            'user' => $info_creator->name,
                            'spp_id' => $spp_info->spp_id,
                            'po_ref' => $spp_info->po_ref,
                            'termin' => $spp_info->termin,
                            'trans_date' => $spp_info->trans_date,
                            'po_lines' => $spp_info->po_lines,
                            'reject_by' => $this->user_name
                        ];

                        // \Mail::to($info_creator->email)->send(new \App\Mail\RABRejectNotify($details_send_mail));

                        // restore termin used
                        $po_order_no = DB::table('0_purch_orders')->where('reference', $spp_info->po_ref)->value('order_no');
                        DB::table('0_purch_order_terms')->where('order_no', $po_order_no)
                            ->where('termin', $spp_info->termin)
                            ->update(['used' => 0]);
                    }


                    DB::table('0_project_spp')->where('spp_id', $spp_id)
                        ->update(array(
                            'approval' => $approved,
                            'status' =>   $status,
                            'updated_at' => Carbon::now(),
                            'updated_by' => $user_id
                        ));

                    DB::table('0_project_spp_log')->insert(array(
                        'spp_id' => $spp_id,
                        'approval' => $approvedLog,
                        'status' => $status,
                        'remark' => $request->remark,
                        'person_id' => Auth::guard()->user()->id,
                        'last_update' => Carbon::now()
                    ));

                    return response()->json([
                        'success' => true
                    ], 200);

                    break;
                case 4:

                    $spp_info = DB::table('0_project_spp')->where('spp_id', $spp_id)->first();
                    $approvedLog = 3;
                    $approved = 7; // telah di approve BPC

                    if ($status == 2) {
                        $info_creator = DB::table('users')->where('id', $spp_info->created_by)->first();
                        $details_send_mail = [
                            'title' => 'Rejected SPP',
                            'user' => $info_creator->name,
                            'spp_id' => $spp_info->spp_id,
                            'po_ref' => $spp_info->po_ref,
                            'termin' => $spp_info->termin,
                            'trans_date' => $spp_info->trans_date,
                            'po_lines' => $spp_info->po_lines,
                            'reject_by' => $this->user_name
                        ];

                        // \Mail::to('rian.pambudi@adyawinsa.com')->send(new \App\Mail\RABRejectNotify($details_send_mail));

                        // restore termin used
                        $po_order_no = DB::table('0_purch_orders')->where('reference', $spp_info->po_ref)->value('order_no');
                        DB::table('0_purch_order_terms')->where('order_no', $po_order_no)
                            ->where('termin', $spp_info->termin)
                            ->update(['used' => 0]);
                    }

                    DB::table('0_project_spp')->where('spp_id', $spp_id)
                        ->update(array(
                            'approval' => $approved,
                            'status' =>   $status,
                            'updated_at' => Carbon::now(),
                            'updated_by' => $user_id
                        ));

                    DB::table('0_project_spp_log')->insert(array(
                        'spp_id' => $spp_id,
                        'approval' => $approvedLog,
                        'status' => $status,
                        'remark' => $request->remark,
                        'person_id' => Auth::guard()->user()->id,
                        'last_update' => Carbon::now()
                    ));

                    // $po_info = DB::table('0_purch_orders as po')
                    //     ->Join('0_purch_order_details as pod', 'po.order_no', '=', 'pod.order_no')
                    //     ->where('po.reference', $spp_info->po_ref)
                    //     ->select(
                    //         'pod.item_code',
                    //         'pod.product_id',
                    //         'pod.quantity_spp AS project_code'
                    //     )
                    //     ->get();

                    // foreach ($po_info as $key) {

                    //     DB::table('0_purch_orders as po')
                    //         ->Join('0_purch_order_details as pod', 'po.order_no', '=', 'pod.order_no')
                    //         ->where('order_no', $spp_id)
                    //         ->update(array(
                    //             'approval' => $approval,
                    //             'status' =>   $request->status,
                    //             'updated_at' => Carbon::now(),
                    //             'updated_by' => $user_id
                    //         ));
                    // }

                    return response()->json([
                        'success' => true
                    ], 200);

                    break;
                case 999:

                    $spp_info = DB::table('0_project_spp')->where('spp_id', $spp_id)->first();
                    $appoved = 1;

                    if ($spp_info->approval == 1){
                        $appoved = 2;
                    }

                    if ($spp_info->approval == 2){
                        $appoved = 3;
                    }

                    if ($spp_info->approval == 3){
                        $appoved = 7;
                    }

                    if ($status == 2) {
                        $info_creator = DB::table('users')->where('id', $spp_info->created_by)->first();
                        $details_send_mail = [
                            'title' => 'Rejected SPP',
                            'user' => $info_creator->name,
                            'spp_id' => $spp_info->spp_id,
                            'po_ref' => $spp_info->po_ref,
                            'termin' => $spp_info->termin,
                            'trans_date' => $spp_info->trans_date,
                            'po_lines' => $spp_info->po_lines,
                            'reject_by' => $this->user_name
                        ];

                        // \Mail::to($info_creator->email)->send(new \App\Mail\SppRejectNotify($details_send_mail));

                        // restore termin used
                        $po_order_no = DB::table('0_purch_orders')->where('reference', $spp_info->po_ref)->value('order_no');
                        DB::table('0_purch_order_terms')->where('order_no', $po_order_no)
                            ->where('termin', $spp_info->termin)
                            ->update(['used' => 0]);
                    }

                    DB::table('0_project_spp')->where('spp_id', $spp_id)
                        ->update(array(
                            'approval' => $appoved,
                            'status' =>   $status,
                            'updated_at' => Carbon::now(),
                            'updated_by' => $user_id
                        ));

                    DB::table('0_project_spp_log')->insert(array(
                        'spp_id' => $spp_id,
                        'approval' => $spp_info->approval,
                        'status' => $status,
                        'remark' => $request->remark,
                        'person_id' => Auth::guard()->user()->id,
                        'last_update' => Carbon::now()
                    ));

                    return response()->json([
                        'success' => true
                    ], 200);

                    break;
            }

            // DB::commit();

        } catch (Exception $e) {
            return response()->json(['error' => [
                'message' => 'Terjadi keasalahan :' . $e->getMessage(),
                'status_code' => 400
            ]]);

            // DB::rollBack();
        }
    }

    public function get_spp_need_check_fa(Request $request)
    {
        $response = [];
        $search = $request->search;
        $level = $this->user_level;
        $person_id = $this->user_person_id;
        $user_id = $this->user_id;
        $old_id = $this->user_old_id;
        $emp_id = $this->user_emp_id;

        // Base query components
        $select = "SELECT
                sp.spp_id, 
                sp.spk_no,
                sp.po_ref,
                sp.order_no,
                sp.trans_date,
                supl.supp_name AS supplier_name,
                sp.termin,
                sp.termin_pct,
                sp.po_lines,
                sp.approval,
                CASE
                    WHEN sp.approval = 1 AND sp.status = 0 THEN 'On PM'
                    WHEN sp.approval = 2 AND sp.status = 3 THEN 'Pending PM'
                    WHEN sp.approval = 2 AND sp.status = 1 THEN 'On GM'
                    WHEN sp.approval = 3 AND sp.status = 3 THEN 'Pending GM'
                    WHEN sp.approval = 3 AND sp.status = 1 THEN 'On BPC'
                    WHEN sp.approval = 7 AND sp.status = 3 THEN 'Pending BPC'
                    WHEN sp.approval = 7 AND sp.status = 1 THEN 'APPROVED' -- change to On Finance
                    WHEN sp.approval = 8 AND sp.status = 3 THEN 'Pending Finance'
                    WHEN sp.approval = 8 AND sp.status = 4 THEN 'On Finance Review'
                    WHEN sp.approval = 8 AND sp.status = 5 THEN 'Incorrect Invoice'
                    WHEN sp.approval = 8 AND sp.status = 1 THEN 'Invoice Approved'
                    WHEN sp.status = 2 THEN 'DISAPPROVE'
                    ELSE 'unknown'
                END AS pic,
                sp.status,
                p.person_id,
                sp.created_at,
                sp.attachments,
                u.name AS created_by,
                spd.project_code
            FROM 0_project_spp AS sp 
            JOIN 0_suppliers AS supl ON supl.supplier_id = sp.supplier_id
            JOIN users AS u ON u.id = sp.created_by
            JOIN 0_project_spp_detail AS spd ON spd.spp_id = sp.spp_id
            LEFT JOIN 0_projects AS p ON p.code = spd.project_code
            LEFT JOIN 0_members AS m ON p.person_id = m.person_id";

        $searchQuery = $search ? " AND (sp.po_ref LIKE '$search%' OR spd.project_code LIKE '$search%' OR supl.supp_name LIKE '$search%')" : "";

        // Conditions and queries based on $level
        switch ($level) {
            case 999:
            case 5:
                $query = $select . " WHERE ((sp.approval = 7 AND sp.status = 1) OR (sp.approval = 8 AND sp.status IN (3,4,5)))" . $searchQuery . " GROUP BY sp.spp_id ORDER BY sp.created_at DESC";
                break;

            default:
                $query = "SELECT * FROM 0_project_spp WHERE spp_id = ?";
                break;
        }

        // return $query;

        $spp_need_cek_fa = DB::select($query);

        foreach ($spp_need_cek_fa as $data) {


            $so_info = DB::table('0_sales_orders')->where('order_no', $data->order_no)->first();

            $tmp = [];
            $tmp['spp_id'] = $data->spp_id;
            $tmp['spk_no'] = $data->spk_no;
            $tmp['po_ref'] = $data->po_ref;
            $tmp['po_customer'] = $so_info->reference;
            $tmp['project_code'] = $data->project_code;
            $tmp['trans_date'] = $data->trans_date;
            $tmp['supplier_name'] = $data->supplier_name;
            $tmp['termin'] = $data->termin;
            $tmp['po_lines'] = $data->po_lines;
            $tmp['termin_pct'] = $data->termin_pct;
            $tmp['approval'] = $data->approval;
            $tmp['approval_name'] = $data->pic;
            $tmp['status'] = $data->status;
            $tmp['created_at'] = $data->created_at;
            $tmp['created_by'] = $data->created_by;
            $tmp['attachments'] = [];

            $attachments = explode(';', $data->attachments);

            foreach ($attachments as $file) {
                $url_file = URL::to("/storage/e-spp/attachments/$file");
                array_push($tmp['attachments'], $url_file);
            }

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    function update_spp_check_fa(Request $request, $spp_id)
    {

        $level = $this->user_level;
        $person_id = $this->user_person_id;
        $user_id = $this->user_id;
        $old_id = $this->user_old_id;
        $emp_id = $this->user_emp_id;
        $status = $request->status;

        // DB::beginTransaction();
        try {

            switch ($level) {
                case 999:
                case 5:
                    $spp_info = DB::table('0_project_spp')->where('spp_id', $spp_id)->first();
                    $approvedLog = 7;
                    $approved = 8; // telah di process FA

                    DB::table('0_project_spp')->where('spp_id', $spp_id)
                        ->update(array(
                            'approval' => $approved,
                            'status' =>   $status,
                            'updated_at' => Carbon::now(),
                            'updated_by' => $user_id
                        ));

                    DB::table('0_project_spp_log')->insert(array(
                        'spp_id' => $spp_id,
                        'approval' => $approvedLog,
                        'status' => $status,
                        'remark' => $request->remark,
                        'person_id' => Auth::guard()->user()->id,
                        'last_update' => Carbon::now()
                    ));

                    return response()->json([
                        'success' => true
                    ], 200);

                    break;
            }

            // DB::commit();

        } catch (Exception $e) {
            echo 'Terjadi keasalahan :' . $e->getMessage();

            return response()->json(['error' => [
                'message' => 'Terjadi keasalahan :' . $e->getMessage(),
                'status_code' => 400
            ]]);

            // DB::rollBack();
        }
    }

    public function export_spp(Request $request)
    {
        $myExport = ProjectBudgetController::export_spp($request->from, $request->to);
        return $myExport;
    }

    public function upload_attachments_spp(Request $request)
    {
        $spp = DB::table('0_project_spp')->orderBy('spp_id', 'desc')->limit(1)->value('spp_id');

        $files = $request->file('file');

        $arrName = [];

        DB::beginTransaction();
        try {

            if ($files) {
                foreach ($files as $file) {
                    $randomNumber = sprintf('%05d', mt_rand(1, 99999));
                    $ext = $file->getClientOriginalExtension();

                    $customName = $randomNumber . '.' . $ext;
                    $destination = public_path("/storage/e-spp/attachments");
                    $file->move($destination, $customName);

                    array_push($arrName, $customName);
                }

                DB::table('0_project_spp')->where('spp_id', $spp)
                    ->update(array(
                        'attachments' => implode(';', $arrName)
                    ));
            }
            DB::commit();

            return response()->json([
                'success' => true
            ]);
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }

    public function upload_budget_salary(Request $request)
    {
        $requestData = $request->json()->all();

        $data = $requestData['data'];

        DB::beginTransaction();
        try {

            $total_per_budgets = [];
            foreach ($data as $item) {
                $budget_id = $item['budget_id'];
                $amount = $item['salary'];

                if (!isset($total_per_budgets[$budget_id])) {
                    $total_per_budgets[$budget_id] = 0;
                }

                $total_per_budgets[$budget_id] += $amount;
            }

            /**  Process Cek Budget */
            foreach ($total_per_budgets as $budget_id => $total_amount) {
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
                }
            }

            foreach ($data as $key => $item) {
                $project_info = DB::table('0_projects')->where('code', $item['project_code'])->first();
                $dataExist = DB::table('0_project_salary_budget')->where('budget_id', $item['budget_id'])->where('date', $item['date'])->first();

                if(!isset($project_info->project_no)){
                    DB::rollback();
                    return response()->json([
                        'error' => array(
                            'message' => 'Kode Project ' . $item['project_code'] . ' pada line' . ($key + 1) . " salah!",
                            'status_code' => 403
                        )
                    ], 403);
                }

                if(isset($dataExist->project_salary_id)){
                    DB::table('0_project_salary_budget')->where('project_salary_id', $dataExist->project_salary_id)->update(
                        [
                            'date' => $item['date'],
                            'project_no' => $project_info->project_no,
                            'budget_id' => $item['budget_id'],
                            'actual' => $item['actual']
                        ]
                    );
                } else {
                    DB::table('0_project_salary_budget')->insert(
                        [
                            'date' => $item['date'],
                            'project_no' => $project_info->project_no,
                            'salary' => $item['salary'],
                            'budget_id' => $item['budget_id'],
                            'description' => $item['description'],
                            'actual' => $item['actual'],
                            'created_by' => $this->user_old_id,
                            'created_date' => Carbon::now()
                        ]
                    );
                }

            }

            // Commit Transaction
            DB::commit();

            return response()->json([
                'success' => true
            ]);
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();

            return response()->json([
                'error' => array(
                    'message' => 'Gagal submit: ' . $e->getMessage(),
                    'status_code' => 400
                )
            ], 400);
        }
    }

    public function upload_project_rent_tools(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048', // max 2mb
        ]);

        $file = $request->file('csv_file');

        if (($handle = fopen($file, 'r')) !== false
        ) {
            $firstRow = true;
            DB::beginTransaction();
            try {

                $total_per_budgets = [];
                $clear_data = [];

                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    if ($firstRow) {
                        $firstRow = false;
                        continue;
                    }

                    $budget_id = $data[10];
                    $amount = $data[8];

                    if (!isset($total_per_budgets[$budget_id])) {
                        $total_per_budgets[$budget_id] = 0;
                    }

                    $total_per_budgets[$budget_id] += $amount;


                    $tmp = [];
                    $tmp['doc_no'] = $data[0];
                    $tmp['tran_date'] = $data[1];
                    $tmp['close_date'] = $data[2];
                    $tmp['asset_id'] = $data[3];
                    $tmp['asset_name'] = $data[4];
                    $tmp['person'] = $data[5];
                    $tmp['duration'] = $data[6];
                    $tmp['rate'] = $data[7];
                    $tmp['total'] = $data[8];
                    $tmp['status'] = $data[9];
                    $tmp['budget_id'] = $data[10];
                    $tmp['created_at'] = Carbon::now();
                    $tmp['created_by'] = $this->user_id;

                    array_push($clear_data, $tmp);
                }

                /**  Process Cek Budget */
                foreach ($total_per_budgets as $budget_id => $total_amount) {
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
                    }
                }

                foreach ($clear_data as $item) {
                    DB::table('0_project_rent_tools')
                        ->insert(array(
                            'doc_no' => $item['doc_no'],
                            'tran_date' => $item['tran_date'],
                            'close_date' => $item['close_date'],
                            'asset_id' => $item['asset_id'],
                            'asset_name' => $item['asset_name'],
                            'person' => $item['person'],
                            'duration' => $item['duration'],
                            'rate' => $item['rate'],
                            'total' => $item['total'],
                            'status' => $item['status'],
                            'budget_id' => $item['budget_id'],
                            'created_at' => $item['created_at'],
                            'created_by' => $item['created_by']
                        ));
                }

                // Commit Transaction
                DB::commit();

                return response()->json([
                    'success' => true
                ]);
            } catch (Exception $e) {
                // Rollback Transaction
                DB::rollback();
            }

            fclose($handle);
        } else {
            return response()->json([
                'error' => array(
                    'message' => 'Gagal membuka file CSV.',
                    'status_code' => 403
                )
            ], 403);
        }
    }

    public function upload_project_rent_vehicle(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048', // max 2mb
        ]);

        $file = $request->file('csv_file');

        if (($handle = fopen($file, 'r')) !== false
        ) {
            $firstRow = true;
            DB::beginTransaction();
            try {

                $total_per_budgets = [];
                $clear_data = [];

                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    if ($firstRow) {
                        $firstRow = false;
                        continue;
                    }

                    $budget_id = $data[6];
                    $amount = $data[5];

                    if (!isset($total_per_budgets[$budget_id])) {
                        $total_per_budgets[$budget_id] = 0;
                    }

                    $total_per_budgets[$budget_id] += $amount;


                    $tmp = [];
                    $tmp['vehicle_number'] = $data[0];
                    $tmp['vehicle_type'] = $data[1];
                    $tmp['person'] = $data[2];
                    $tmp['project_code'] = $data[3];
                    $tmp['periode'] = $data[4];
                    $tmp['amount'] = $data[5];
                    $tmp['budget_id'] = $data[6];
                    $tmp['created_at'] = Carbon::now();
                    $tmp['created_by'] = $this->user_id;

                    array_push($clear_data, $tmp);
                }


                /**  Process Cek Budget */
                foreach ($total_per_budgets as $budget_id => $total_amount) {
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
                    }
                }

                foreach ($clear_data as $item) {
                    DB::table('0_project_rent_vehicle')
                        ->insert(array(
                            'vehicle_number' => $item['vehicle_number'],
                            'vehicle_type' => $item['vehicle_type'],
                            'person' => $item['person'],
                            'project_code' => $item['project_code'],
                            'periode' => $item['periode'],
                            'amount' => $item['amount'],
                            'budget_id' => $item['budget_id'],
                            'created_at' => $item['created_at'],
                            'created_by' => $item['created_by']
                        ));
                }

                // Commit Transaction
                DB::commit();

                return response()->json([
                    'success' => true
                ]);
            } catch (Exception $e) {
                // Rollback Transaction
                DB::rollback();
            }

            fclose($handle);
        } else {
            return response()->json([
                'error' => array(
                    'message' => 'Gagal membuka file CSV.',
                    'status_code' => 403
                )
            ], 403);
        }
    }
}
