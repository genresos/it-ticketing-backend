<?php

namespace App\Http\Controllers;

use JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Carbon\Carbon;
use Validator, Redirect, Response, File;
use Auth;
use Maatwebsite\Excel\Concerns\ToArray;
use URL;
use App\Query\QueryProjectBudget;
use Symfony\Component\HttpKernel\Exception\BudgetAmountException;
use Symfony\Component\HttpKernel\Exception\RabAmountException;
use Symfony\Component\HttpKernel\Exception\AddBudgetReqException;
use App\Exports\ProjectBudgetUseExport;
use App\Exports\ProjectSPPExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\ProjectListController;
use Symfony\Component\HttpKernel\Exception\RabBudgetExistHttpException;
use App\Jobs\ProjectBudgetNotification;
use App\Jobs\ProjectBudgetUpdateCost;
use Illuminate\Support\Facades\Bus;
use DateTime;

class ProjectBudgetController extends Controller
{
    public static function show_budgets($project_no, $budget_id, $search)
    {
        $response = [];
        $sql = QueryProjectBudget::show_budgets($project_no, $budget_id, $search);

        $project_budgets = DB::select(DB::raw($sql));
        DB::beginTransaction();
        try {
            foreach ($project_budgets as $data) {

                $tmp = [];

                $po_balance = self::budget_use_po($data->project_budget_id);
                $po_balance_real = self::budget_use_po_real($data->project_budget_id);
                $ca_balance = self::budget_use_ca($data->project_budget_id);
                $ca_balance_real = self::budget_use_ca_real($data->project_budget_id);
                $gl_balance = self::budget_use_gl($data->project_budget_id);
                $salary_balance = self::budget_use_salary($data->project_budget_id);
                $gl_tmp_balance = self::budget_use_gl_tmp($data->project_budget_id);
                $budget_reverse = self::budget_reverse($data->project_budget_id);
                $spk_balance = self::budget_use_spk($data->project_budget_id);

                /*Additional From Tools Upload*/
                $tools_balance = self::budget_use_tools($data->project_budget_id);
                $vehicle_balance = self::budget_use_vehicle($data->project_budget_id);

                $used_amount = $po_balance + $ca_balance + $gl_balance + $gl_tmp_balance + $salary_balance + $spk_balance + $tools_balance + $vehicle_balance - $budget_reverse;
                $used_amount_real = $po_balance + $ca_balance_real + $gl_balance + $gl_tmp_balance + $salary_balance + $spk_balance + $tools_balance + $vehicle_balance - $budget_reverse;
                $used_amount_realization = $po_balance_real + $ca_balance_real + $gl_balance + $gl_tmp_balance + $salary_balance + $spk_balance + $tools_balance + $vehicle_balance - $budget_reverse;


                $remain_amount = $data->amount -  $used_amount;
                $tmp['budget_id'] = $data->project_budget_id;
                $tmp['budget_name'] = $data->budget_name;
                $tmp['budget_type'] = $data->budget_type_name;
                $tmp['budget_type_id'] = $data->budget_type_id;
                $tmp['site_no'] = $data->site_no;
                $tmp['site_id'] = $data->site_id;
                $tmp['site_name'] = $data->site_name;
                $tmp['rab_id'] = $data->rab_id;
                $tmp['rab_amount'] = $data->rab_amount;
                $tmp['amount'] = $data->amount;
                $tmp['used_amount'] = $used_amount;
                $tmp['real_used_amount'] = $used_amount_real;
                $tmp['realization_amount'] = 0;
                $tmp['remain_amount'] = $remain_amount;
                $tmp['description'] = $data->description;
                $tmp['inactive'] = $data->inactive;

                array_push($response, $tmp);

                // DB::table('0_project_budgets')->where('project_budget_id', $data->project_budget_id)
                //     ->update(array(
                //         'used_amount' => $used_amount,
                //         'real_used_amount' => $used_amount_real
                //     ));
            }

            // Commit Transaction
            DB::commit();

            return $response;
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }

    public static function show_budgets_acc($project_no, $budget_id, $search)
    {
        $response = [];
        $sql = QueryProjectBudget::show_budgets_acc($project_no, $budget_id, $search);

        $project_budgets = DB::select(DB::raw($sql));
        DB::beginTransaction();
        try {
            foreach ($project_budgets as $data) {

                // $bugdget_acc = DB::table('0_project_budget_acc')->where('project_no', $project_no)->where('budget_type_id', $data->budget_type_id)->first();
                // $real_amount = empty($bugdget_acc) ? 0 : $bugdget_acc->real_amount;
                // $cost_allocation = empty($bugdget_acc) ? 0 : $bugdget_acc->cost_allocation;

                $tmp = [];
                $tmp['#'] = $data->id;
                $tmp['type'] = $data->type;
                $tmp['trans_no'] = $data->trans_no;
                $tmp['reference'] = $data->reference;
                $tmp['amount'] = $data->real_amount;
                $tmp['cost_allocation'] = $data->cost_allocation;
                // $tmp['site_id'] = $data->site_id;
                // $tmp['site_name'] = $data->site_name;
                // $tmp['rab_id'] = $data->rab_id;
                // $tmp['rab_amount'] = $data->rab_amount;
                // $tmp['amount'] = $real_amount;
                // $tmp['real_used_amount'] = $cost_allocation;
                // $tmp['cost_alloc'] = $data->cost_allocation;
                // $tmp['remain_amount'] = $data->remain_amount;
                // $tmp['description'] = $data->description;
                // $tmp['inactive'] = $data->inactive;

                array_push($response, $tmp);

                // DB::table('0_project_budgets')->where('project_budget_id', $data->project_budget_id)
                //     ->update(array(
                //         'used_amount' => $used_amount,
                //         'real_used_amount' => $used_amount_real
                //     ));
            }

            // Commit Transaction
            DB::commit();

            return $response;
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }

    public static function edit_budget($myArr, $project_budget_id)
    {
        $params = $myArr['params'];
        $amount = $params['amount'];
        $budget_name = $params['budget_name'];
        $site = $params['site_id'];
        $budget_type_id = $params['budget_type_id'];
        $description = $params['description'];
        $inactive = $params['inactive'];
        $user_id = $myArr['user_id'];

        $po_balance = self::budget_use_po($project_budget_id);
        $ca_balance = self::budget_use_ca($project_budget_id);
        $gl_balance = self::budget_use_gl($project_budget_id);
        $salary_balance = self::budget_use_salary($project_budget_id);

        $budget_detail = self::budget_detail($project_budget_id);
        $data_budget = json_decode(json_encode($budget_detail), true);
        $get_amount = collect($data_budget['original']['data'])
            ->all();

        $beginning_amount =  $get_amount[0]['amount'];

        if ($params['amount'] == 0) {
            DB::beginTransaction();

            try {

                DB::table('0_project_budgets')->where('project_budget_id', $project_budget_id)
                    ->update(array(
                        'budget_name' => $budget_name,
                        'site_id' => $site,
                        'budget_type_id' => $budget_type_id,
                        'amount' => $amount,
                        'description' => $description,
                        'inactive' => $inactive,
                        'updated_date' => Carbon::now(),
                        'updated_by' => $user_id
                    ));
                DB::table('0_project_budget_details')
                    ->insert(array(
                        'project_budget_id' => $get_amount[0]['project_budget_id'],
                        'tanggal_req' => Carbon::now(),
                        'tanggal_approve' => Carbon::now(),
                        'amount_req' => 0,
                        'amount_approve' => 0,
                        'remark_req' => "Edit Budget",
                        'remark_approve' => "Edit Budget",
                        'jenis_data' => 3,
                        'user_req' => $user_id,
                        'user_approve' => $user_id,
                        'status_req' => 1
                    ));

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
        } else {

            $selisih_amount =  $amount - $beginning_amount;
            $used_amount = $po_balance + $ca_balance + $gl_balance;

            if ($used_amount <= $amount) {
                DB::beginTransaction();

                try {

                    DB::table('0_project_budgets')->where('project_budget_id', $project_budget_id)
                        ->update(array(
                            'budget_name' => $budget_name,
                            'site_id' => $site,
                            'budget_type_id' => $budget_type_id,
                            'amount' => $amount,
                            'description' => $description,
                            'inactive' => $inactive,
                            'updated_date' => Carbon::now(),
                            'updated_by' => $user_id
                        ));
                    DB::table('0_project_budget_details')
                        ->insert(array(
                            'project_budget_id' => $get_amount[0]['project_budget_id'],
                            'tanggal_req' => Carbon::now(),
                            'tanggal_approve' => Carbon::now(),
                            'amount_req' => $selisih_amount,
                            'amount_approve' => $selisih_amount,
                            'remark_req' => "Edit Budget",
                            'remark_approve' => "Edit Budget",
                            'jenis_data' => 3,
                            'user_req' => $user_id,
                            'user_approve' => $user_id,
                            'status_req' => 1
                        ));

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
            } else if ($used_amount > $amount) {
                throw new BudgetAmountException();
            }
        }
    }

    public static function add_budget($myArr)
    {
        $params = $myArr['params'];

        $project_no = $params['project_no'];
        $budget_type_id = $params['budget_type_id'];
        $budget_name = $params['budget_name'];
        $amount = $params['amount'];
        $rab_amount = $params['rab_amount'];
        $description = $params['description'];
        $site_id = $params['site_id'];
        $user_id = $myArr['user_id'];
        $last_budget_id = DB::table('0_project_budgets')
            ->select('project_budget_id')
            ->orderBy('project_budget_id', 'DESC')
            ->limit(1)
            ->first();
        $projectinfo = DB::table('0_projects')->where('project_no', $project_no)->select('code')->first();
        $last_id = $last_budget_id->project_budget_id + 1;
        $no_rab_need = array(49, 33, 69, 72, 40, 27, 72, 32, 38, 58, 43);
        if (!in_array($budget_type_id, $no_rab_need) && $rab_amount == 0) {
            return response()->json(
                [
                    'error' => array(
                        'message' => 'RAB tidak boleh 0',
                        'status_code' => 403
                    )
                ],
                403
            );
        }
        DB::beginTransaction();
        try {
            if ((strstr($projectinfo->code, 'OFC') !== false)) {
                /*
                * Untuk kopro office tidak lgsg ke generate budget
                *
                */
                $act_amount = 0;
                DB::table('0_project_budget_details')
                    ->insert(array(
                        'project_budget_id' => $last_id,
                        'tanggal_req' => Carbon::now(),
                        'amount_req' => $rab_amount,
                        'remark_req' => 'New',
                        'jenis_data' => 2,
                        'user_req' => $user_id,
                        'status_req' => 0
                    ));
            } else {
                DB::table('0_project_budget_details')
                    ->insert(array(
                        'project_budget_id' => $last_id,
                        'tanggal_req' => Carbon::now(),
                        'tanggal_approve' => Carbon::now(),
                        'amount_req' => $amount,
                        'amount_approve' => $amount,
                        'remark_req' => "Create New Budget",
                        'remark_approve' => "Create New Budget",
                        'jenis_data' => 1,
                        'user_req' => $user_id,
                        'user_approve' => $user_id,
                        'status_req' => 1
                    ));

                $act_amount = $amount;
            }

            DB::table('0_project_budgets')
                ->insert(array(
                    'project_budget_id' => $last_id,
                    'project_no' => $project_no,
                    'budget_type_id' => $budget_type_id,
                    'budget_name' => $budget_name,
                    'rab_amount' => $rab_amount,
                    'amount' => $act_amount,
                    'description' => $description,
                    'site_id' => $site_id,
                    'created_date' => Carbon::now(),
                    'created_by' => $user_id
                ));

            DB::table('0_project_budget_rab')
                ->insert(array(
                    'budget_id' => $last_id,
                    'budget_type_id' => $budget_type_id,
                    'project_no' => $project_no,
                    'amount' => $rab_amount,
                    'user_id' => $user_id,
                    'created_at' => Carbon::now()
                ));

            // Commit Transaction
            DB::commit();

            $latest_rab_id = DB::table('0_project_budget_rab')->orderBy('id', 'DESC')->first();
            DB::table('0_project_budget_rab_details')
                ->insert(array(
                    'rab_id' => $latest_rab_id->id,
                    'from_rab' => 0,
                    'amount' => $rab_amount,
                    'remark' => "New RAB",
                    'user_id' => $user_id,
                    'created_at' => Carbon::now()
                ));
            // Semua proses benar

            return response()->json([
                'success' => true
            ]);
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }

    public static function req_add_budget($myArr, $budget_id)
    {
        $params = $myArr['params'];

        $amount_req = $params['amount_req'];
        $remark_req = $params['remark_req'];
        $user_id = $myArr['user_id'];
        $detail_budget =
            DB::table('0_project_budgets')
            ->where('project_budget_id', $budget_id)->first();
        $limitation = $detail_budget->rab_amount - $detail_budget->amount;
        $declare_division = DB::table('0_project_budgets')->join('0_projects', '0_project_budgets.project_no', '=', '0_projects.project_no')
            ->join('0_hrm_divisions', '0_projects.division_id', '=', '0_hrm_divisions.division_id')
            ->select('0_hrm_divisions.division_group_id')
            ->where('0_project_budgets.project_budget_id', $budget_id)->first();

        if ($declare_division->division_group_id == 1) {
            DB::beginTransaction();
            try {
                $budget = DB::table('0_project_budget_details')
                    ->insertGetId(array(
                        'project_budget_id' => $budget_id,
                        'tanggal_req' => Carbon::now(),
                        'amount_req' => $amount_req,
                        'remark_req' => $remark_req,
                        'jenis_data' => 2,
                        'user_req' => $user_id,
                        'status_req' => 0
                    ));

                // dispatch(new ProjectBudgetNotification($budget));

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
        } else {
            if ($amount_req <= $limitation) {
                DB::beginTransaction();
                try {
                    $budget = DB::table('0_project_budget_details')
                        ->insertGetId(array(
                            'project_budget_id' => $budget_id,
                            'tanggal_req' => Carbon::now(),
                            'amount_req' => $amount_req,
                            'remark_req' => $remark_req,
                            'jenis_data' => 2,
                            'user_req' => $user_id,
                            'status_req' => 0
                        ));

                    // dispatch(new ProjectBudgetNotification($budget));

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
            } else {
                throw new AddBudgetReqException();
            }
        }
    }

    public static function detail_budget_approve($myArr, $budget_detail_id)
    {
        $params = $myArr['params'];

        $amount_approve = $params['amount_approve'];
        $remark_approve = "Approved";
        $user_id = $myArr['user_id'];
        $sql = DB::table('0_project_budget_details')
            ->where('project_budget_detail_id', $budget_detail_id)->first();
        $get_amount_req = $sql->amount_req;

        $sql1 = DB::table('0_project_budgets')
            ->where('project_budget_id', $sql->project_budget_id)->first();
        $get_amount_budget = $sql1->amount;
        if ($get_amount_req >= $amount_approve) {
            DB::beginTransaction();
            try {
                DB::table('0_project_budget_details')->where('project_budget_detail_id', $budget_detail_id)
                    ->update(array(
                        'tanggal_approve' => Carbon::now(),
                        'amount_approve' => $amount_approve,
                        'remark_approve' => $remark_approve,
                        'status_req' => 1,
                        'user_approve' => $user_id
                    ));
                DB::table('0_project_budgets')->where('project_budget_id', $sql->project_budget_id)
                    ->update(array(
                        'amount' => $get_amount_budget + $amount_approve
                    ));
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
        } else {
            return response()->json([
                'success' => false
            ], 401);
        }
    }

    public static function detail_budget_disapprove($myArr, $budget_detail_id)
    {
        $params = $myArr['params'];

        $remark_disapprove = $params['remark_disapprove'];
        $user_id = $myArr['user_id'];

        DB::beginTransaction();
        try {
            DB::table('0_project_budget_details')->where('project_budget_detail_id', $budget_detail_id)
                ->update(array(
                    'tanggal_approve' => Carbon::now(),
                    'amount_approve' => 0,
                    'remark_approve' => $remark_disapprove,
                    'status_req' => 2,
                    'user_approve' => $user_id
                ));
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
    }

    public static function budget_detail($budget_id)
    {
        $response = [];
        $sql = QueryProjectBudget::budget_detail($budget_id);

        $query = DB::select(DB::raw($sql));

        $po_balance = self::budget_use_po($budget_id);
        $ca_balance = self::budget_use_ca($budget_id);
        $gl_balance = self::budget_use_gl($budget_id);
        $salary_balance = self::budget_use_salary($budget_id);

        $used_amount = $po_balance + $ca_balance + $gl_balance + $salary_balance;
        foreach ($query as $data) {

            $remain_amount = $data->amount - $used_amount;
            $tmp = [];
            $tmp['project_budget_id'] = $data->project_budget_id;
            $tmp['project_code'] = $data->code;
            $tmp['rab_no'] = $data->rab_no;
            $tmp['budget_name'] = $data->budget_name;
            $tmp['budget_type_name'] = $data->budget_type_name;
            $tmp['budget_type_id'] = $data->budget_type_id;
            $tmp['site_id'] = $data->site_id;
            $tmp['site_name'] = $data->site_name;
            $tmp['amount'] = $data->amount;
            $tmp['rab_amount'] = $data->rab_amount;
            $tmp['po_amount'] = $po_balance;
            $tmp['ca_amount'] = $ca_balance;
            $tmp['gl_amount'] = $gl_balance;
            $tmp['remain_amount'] = $remain_amount;
            $tmp['description'] = $data->description;
            $tmp['creator'] = $data->creator;
            $tmp['inactive'] = $data->inactive;


            $sql1 = QueryProjectBudget::budget_detail_inside($data->project_budget_id);

            $query1 = DB::select(DB::raw($sql1));
            foreach ($query1 as $item) {
                $items = [];
                $items['project_budget_detail_id'] =  $item->project_budget_detail_id;
                $items['tanggal_req'] =  date('d-m-Y', strtotime($item->tanggal_req));
                $items['amount'] =  $item->amount_req;
                $items['remark'] =  $item->remark_req;
                $items['budget_detail_type'] =  $item->budget_detail_type;
                $items['status_id'] =  $item->status_id;
                $items['status_name'] =  $item->status_name;
                $items['user'] =  $item->user;

                $tmp['budget_detail'][] = $items;
            }
            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function budget_use_po($budget_id)
    {
        $sql_po = QueryProjectBudget::budget_use_po($budget_id);

        $po_balance = DB::select(DB::raw($sql_po));
        foreach ($po_balance as $po_data) {
            $balance = $po_data->balance;
        }

        return $balance;
    }

    public static function budget_use_po_real($budget_id)
    {
        $sql_po = QueryProjectBudget::budget_use_po_real($budget_id);

        $exe = DB::connection('mysql')->select(DB::raw($sql_po));

        $amount = array();
        foreach ($exe as $data) {
            array_push($amount, $data->Total);
        }

        return array_sum($amount);
    }

    public static function budget_use_ca($budget_id)
    {
        $sql_ca = QueryProjectBudget::budget_use_ca($budget_id);

        $ca_balance = DB::select(DB::raw($sql_ca));
        foreach ($ca_balance as $ca_data) {
            $balance = $ca_data->balance;
        }

        return $balance;
    }

    public static function budget_use_ca_real($budget_id)
    {
        $sql_ca = QueryProjectBudget::budget_use_ca_real($budget_id);

        $ca_balance  = DB::select(DB::raw($sql_ca));

        foreach ($ca_balance as $ca_data) {
            $balance = $ca_data->balance;
        }

        return $balance;
    }

    public static function budget_use_gl($budget_id)
    {
        $sql_gl = QueryProjectBudget::budget_use_gl($budget_id);

        $gl_balance = DB::select(DB::raw($sql_gl));
        foreach ($gl_balance as $gl_data) {
            $balance = $gl_data->balance;
        }

        return $balance;
    }

    public static function budget_use_gl_tmp($budget_id)
    {
        $sql_gl_tmp = QueryProjectBudget::budget_use_gl_tmp($budget_id);

        $gl_tmp_balance = DB::select(DB::raw($sql_gl_tmp));
        foreach ($gl_tmp_balance as $gl_data) {
            $balance = $gl_data->balance;
        }

        return $balance;
    }

    public static function budget_reverse($budget_id)
    {
        $sql = QueryProjectBudget::budget_reverse($budget_id);

        $data = DB::select(DB::raw($sql));
        foreach ($data as $item) {
            $balance = $item->balance;
        }

        return $balance;
    }

    //   Additional salary cost 
    //   Wawan : 20220411
    public static function budget_use_salary($budget_id)
    {
        $sql_salary = QueryProjectBudget::budget_use_salary($budget_id);

        $salary_balance = DB::select(DB::raw($sql_salary));
        foreach ($salary_balance as $salary_data) {
            $balance = $salary_data->balance;
        }

        return $balance;
    }

    public static function budget_use_spk($budget_id)
    {
        $sql_salary = QueryProjectBudget::budget_use_spk($budget_id);

        $spk_balance = DB::select(DB::raw($sql_salary));
        foreach ($spk_balance as $spk_data) {
            $balance = $spk_data->balance;
        }

        return $balance;
    }

    public static function budget_use_tools($budget_id)
    {
        $sql_tools = DB::table('0_project_rent_tools')->where('budget_id', $budget_id)->sum('total');
        return $sql_tools;
    }

    public static function budget_use_vehicle($budget_id)
    {
        $sql_vehicle = DB::table('0_project_rent_vehicle')->where('budget_id', $budget_id)->sum('amount');
        return $sql_vehicle;
    }

    public static function budget_need_approve($user_level, $old_id, $budget_id, $project_code)
    {
        $response = [];

        $sql = QueryProjectBudget::budget_need_approve($user_level, $old_id, $budget_id, $project_code);

        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {
            $tmp = [];
            $tmp['current_budget'] = $data->current_budget;
            $tmp['project_budget_detail_id'] = $data->project_budget_detail_id;
            $tmp['budget_id'] = $data->project_budget_id;
            $tmp['budget_rab'] = $data->budget_rab;
            $tmp['date'] = date('d-m-Y H:i:s', strtotime($data->tanggal_req));
            $tmp['budget_name'] = $data->budget_name;
            $tmp['budget_type'] = $data->budget_type;
            $tmp['project_code'] = $data->project_code;
            $tmp['project_name'] = $data->project_name;
            $tmp['amount'] = $data->amount_req;
            $tmp['remark'] = $data->remark_req;
            $tmp['requestor'] = $data->requestor;


            array_push($response, $tmp);
        }

        return $response;
    }

    public static function show_used_budget_amount($project_budget_id)
    {
        $response = [];
        $used_ca = QueryProjectBudget::ca_used_budget_amount($project_budget_id);

        $query_ca = DB::select(DB::raw($used_ca));
        foreach ($query_ca as $data_ca) {
            $tmp_ca = [];
            $tmp_ca['doc_type'] = "Cash Advance";
            $tmp_ca['doc_no'] = $data_ca->doc_no;
            $tmp_ca['used_amount'] = $data_ca->used_amount;
            // $response['used_ca'][] = $tmp_ca;
            array_push($response, $tmp_ca);
        }

        $used_po = QueryProjectBudget::po_used_budget_amount($project_budget_id);

        $query_po = DB::select(DB::raw($used_po));
        foreach ($query_po as $data_po) {
            $tmp_po = [];
            $tmp_po['doc_type'] = "Purchase Order";
            $tmp_po['doc_no'] = $data_po->doc_no;
            $tmp_po['used_amount'] = $data_po->used_amount;
            // $response['used_po'][] = $tmp_po;
            array_push($response, $tmp_po);
        }

        $used_gl_tmp = QueryProjectBudget::gl_used_budget_amount_tmp($project_budget_id);

        $query_gl = DB::select(DB::raw($used_gl_tmp));
        foreach ($query_gl as $data_gl_tmp) {
            $tmp_gl_tmp = [];
            $tmp_gl_tmp['doc_type'] = "Bank Payment Temporary";
            $tmp_gl_tmp['doc_no'] = $data_gl_tmp->doc_no;
            $tmp_gl_tmp['used_amount'] = $data_gl_tmp->used_amount;
            array_push($response, $tmp_gl_tmp);
        }

        $used_gl = QueryProjectBudget::gl_used_budget_amount($project_budget_id);

        $query_gl = DB::select(DB::raw($used_gl));
        foreach ($query_gl as $data_gl) {
            $tmp_gl = [];
            $tmp_gl['doc_type'] = "Bank Payment";
            $tmp_gl['doc_no'] = $data_gl->doc_no;
            $tmp_gl['used_amount'] = $data_gl->used_amount;
            array_push($response, $tmp_gl);
        }

        $sused_salary = DB::select(DB::raw(
            "SELECT *
                        FROM 0_project_salary_budget
                        WHERE budget_id=$project_budget_id GROUP BY date"
        ));

        foreach ($sused_salary as $data_salary) {
            $tmp_salary = [];
            $tmp_salary['doc_type'] = "Salary";
            $tmp_salary['doc_no'] = $data_salary->date;
            $tmp_salary['used_amount'] = $data_salary->salary;
            // $response['used_po'][] = $tmp_salary;
            array_push($response, $tmp_salary);
        }

        $used_spk = QueryProjectBudget::spk_used_budget_amount($project_budget_id);

        $query_spk = DB::select(DB::raw($used_spk));
        foreach ($query_spk as $data_spk) {
            $tmp_spk = [];
            $tmp_spk['doc_type'] = "SPK";
            $tmp_spk['doc_no'] = $data_spk->doc_no;
            $tmp_spk['used_amount'] = $data_spk->used_amount;
            array_push($response, $tmp_spk);
        }

        $used_tools = QueryProjectBudget::tools_used_budget_amount($project_budget_id);

        $query_tools = DB::select(DB::raw($used_tools));
        foreach ($query_tools as $data_tools) {
            $tmp_tools = [];
            $tmp_tools['doc_type'] = "Rent Tools";
            $tmp_tools['doc_no'] = $data_tools->doc_no;
            $tmp_tools['used_amount'] = $data_tools->used_amount;
            array_push($response, $tmp_tools);
        }

        $used_vehicle = QueryProjectBudget::vehicle_used_budget_amount($project_budget_id);

        $query_vehicle = DB::select(DB::raw($used_vehicle));
        foreach ($query_vehicle as $data_) {
            $tmp_vehicle = [];
            $tmp_vehicle['doc_type'] = "Rent Tools";
            $tmp_vehicle['doc_no'] = $data_->doc_no;
            $tmp_vehicle['used_amount'] = $data_->used_amount;
            array_push($response, $tmp_vehicle);
        }

        array_push($response);

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function test()
    {
        $response = [];

        $sql = "SELECT sorder.order_no,
                    sorder.project_code,
                    sorder.customer_ref,
                    line.site_id,
                    line.site_name,
                    line.description,
                    line.qty_ordered,
                    line.unit,
                    line.unit_price,
                    (line.qty_ordered * line.unit_price) AS line_amount,
                    (line.qty_delivered * line.unit_price) AS line_verified,
                    sorder.curr_code,
                    sorder.reference
                FROM 0_sales_orders AS sorder, 0_sales_order_details AS line, 0_groups scategory
                WHERE sorder.order_no = line.order_no
                AND sorder.sales_category_id = scategory.id
                AND sorder.project_code = '20CONS11000001'";

        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {
            $tmp = [];
            $tmp['doc_no'] = $data->order_no;
            $tmp['project_code'] = $data->project_code;
            $tmp['customer_ref'] = $data->customer_ref;
            $tmp['site_id'] = $data->site_id;
            $tmp['site_name'] = $data->site_name;
            $tmp['description'] = $data->description;
            $tmp['qty_ordered'] = $data->qty_ordered;
            $tmp['unit'] = $data->unit;
            $tmp['unit_price'] = $data->unit_price;
            $tmp['line_amount'] = $data->line_amount;
            $tmp['line_verified'] = $data->line_verified;
            $tmp['curr_code'] = $data->curr_code;

            array_push($response, $tmp);
        }

        return $response;
    }

    public static function actual_cost_po_budget_graph($project_no, $year, $month)
    {
        $sql = QueryProjectBudget::actual_cost_po_budget_graph($project_no, $year, $month);

        $exe = DB::select(DB::raw($sql));
        foreach ($exe as $data) {
            return $data->used_amount;
        }
    }


    public static function actual_cost_ca_budget_graph($project_no, $year, $month)
    {
        $sql = QueryProjectBudget::actual_cost_ca_budget_graph($project_no, $year, $month);

        $exe = DB::select(DB::raw($sql));
        foreach ($exe as $data) {
            return $data->used_amount;
        }
    }

    public static function actual_cost_gl_budget_graph($code, $year, $month)
    {
        $sql = QueryProjectBudget::actual_cost_gl_budget_graph($code, $year, $month);

        $exe = DB::select(DB::raw($sql));
        foreach ($exe as $data) {
            return $data->used_amount;
        }
    }

    public static function query_prev_all_cost($inactive, $code, $year)
    {
        $sql = QueryProjectBudget::act_cost_prev_total($inactive, $code, $year);

        return $sql;
    }

    public static function prev_all_cost_po($inactive, $code, $year)
    {
        $sql = self::query_prev_all_cost($inactive, $code, $year);

        $exe = DB::select(DB::raw($sql['po']));
        foreach ($exe as $data) {
            return $data->used_amount;
        }
    }

    public static function prev_all_cost_ca($inactive, $code, $year)
    {
        $sql = self::query_prev_all_cost($inactive, $code, $year);

        $exe = DB::select(DB::raw($sql['ca']));
        foreach ($exe as $data) {
            return $data->used_amount;
        }
    }

    public static function prev_all_cost_gl($inactive, $code, $year)
    {
        $sql = self::query_prev_all_cost($inactive, $code, $year);

        $exe = DB::select(DB::raw($sql['gl']));
        foreach ($exe as $data) {
            return $data->used_amount;
        }
    }

    public static function total_prev_all_cost($inactive, $code, $year)
    {
        $prev_cost_act_po = self::prev_all_cost_po($inactive, $code, $year);
        $prev_cost_act_ca = self::prev_all_cost_ca($inactive, $code, $year);
        $prev_cost_act_gl = self::prev_all_cost_gl($inactive, $code, $year);

        $prev_total = $prev_cost_act_ca + $prev_cost_act_po + $prev_cost_act_gl;

        return $prev_total;
    }

    public static function curve_graph_total_budget($year, $project_no)
    {
        $response = [];
        $sql = QueryProjectBudget::curve_graph_total_budget($year, $project_no);
        $exe = DB::select(DB::raw($sql));

        if (empty($exe) || $exe == null) {
            foreach ($exe as $data) {
                $tmp = [];
                $tmp['budget'] = 0;
                $tmp['act_cost'] = 0;

                $tmp['imonth'] = date('Y');
                $tmp['iyear'] = date('Y');
                array_push($response, $tmp);
            }
        } else {
            foreach ($exe as $data) {
                $tmp = [];
                $project_code = ProjectListController::get_project_code($project_no);

                $cost_act_po = self::actual_cost_po_budget_graph($project_no, $data->iyear, $data->imonth);
                $cost_act_ca = self::actual_cost_ca_budget_graph($project_no, $data->iyear, $data->imonth);
                $cost_act_gl = self::actual_cost_gl_budget_graph($project_code, $data->iyear, $data->imonth);

                $total = $cost_act_ca + $cost_act_po + $cost_act_gl;
                $tmp['budget'] = $data->amount;
                $tmp['act_cost'] = $total;

                $tmp['imonth'] = $data->imonth;
                $tmp['iyear'] = $data->iyear;
                array_push($response, $tmp);
            }
        }

        return $response;
    }

    public static function sum_total_budget($project_no)
    {
        $data = QueryProjectBudget::total_amount_budget($project_no);

        return $data;
    }

    public static function sum_total_rab($project_no)
    {
        $data = QueryProjectBudget::total_amount_rab($project_no);

        return $data;
    }
    public static function sum_total_prev_budget($year, $project_no)
    {
        $sql = QueryProjectBudget::total_prev_budget($year, $project_no);

        $exe = DB::select(DB::raw($sql));

        // if ($exe == null || empty($exe)) {
        //     return 0;
        // } else {
        foreach ($exe as $data) {
            return $data->amount;
        }
        // }
    }

    public static function sum_total_cost_budget($project_no)
    {
        $data = QueryProjectBudget::total_cost_budget($project_no);

        $exe = DB::select(DB::raw($data));

        foreach ($exe as $data) {
            $total = ($data->po_amount * $data->rate / 100) + $data->ca_amount + $data->rmb_amount + $data->bp_2020 + $data->bp_2021 + $data->po + $data->stk_atk + $data->salary + $data->bpd_2020 + $data->bpd_2021 + $data->vehicle_rental + $data->tool_laptop + $data->deduction_2020 + $data->deduction_2021;
            return $total;
        }

        return $data;
    }

    public static function export_used_budget($project_budget_id)
    {
        $filename = "PROJECT BUDGET USE";

        return Excel::download(new ProjectBudgetUseExport($project_budget_id), "$filename.xlsx");
    }

    public function getPublishedAt8601Attribute()
    {
        return $this->published_at->format('c');
    }

    public static function rab_adjustment($myArr, $project_budget_id)
    {
        $response = [];
        $budget_detail = DB::table('0_project_budgets')->where('project_budget_id', $project_budget_id)->first();
        $budget_rab_detail = DB::table('0_project_budget_rab')->where('budget_id', $project_budget_id)->first();

        $po_balance = self::budget_use_po($project_budget_id);
        $ca_balance = self::budget_use_ca($project_budget_id);
        $gl_balance = self::budget_use_gl($project_budget_id);
        $salary_balance = self::budget_use_salary($project_budget_id);
        $gl_tmp_balance = self::budget_use_gl_tmp($project_budget_id);

        $used_amount = $po_balance + $ca_balance + $gl_balance + $gl_tmp_balance + $salary_balance;

        $remain_rab_amount = $budget_detail->rab_amount - $used_amount;

        $params = $myArr['params'];
        $total = $params['total_amount'];

        if ($remain_rab_amount >= $total) {
            DB::beginTransaction();
            try {

                foreach ($params['data'] as $data => $key) {
                    $description = (empty($key['description'])) ? '' : $key['description'];
                    $detail_data_budget = DB::table('0_project_budgets')->where('project_budget_id', $key['budget_id'])->first();
                    $check_data_rab = DB::table('0_project_budget_rab')->where('budget_id', $key['budget_id'])->first();
                    if (empty($check_data_rab)) {
                        DB::table('0_project_budget_rab')
                            ->insert(array(
                                'budget_id' => $key['budget_id'],
                                'budget_type_id' => $detail_data_budget->budget_type_id,
                                'project_no' => $detail_data_budget->project_no,
                                'amount' => $key['amount'],
                                'user_id' => $myArr['user_id']
                            ));
                    }

                    $detail_data_rab = DB::table('0_project_budget_rab')->where('budget_id', $key['budget_id'])->first();

                    DB::table('0_project_budgets')->where('project_budget_id', $key['budget_id'])
                        ->update(array(
                            'rab_amount' => $detail_data_budget->rab_amount + $key['amount']
                            // 'amount' => $detail_data_budget->amount + $key['amount'] // tidak menambah amount
                        ));

                    DB::table('0_project_budget_rab')->where('budget_id', $key['budget_id'])
                        ->update(array(
                            'amount' => $detail_data_budget->rab_amount + $key['amount']
                        ));

                    // DB::table('0_project_budget_details')
                    // ->insert(array(
                    // 'project_budget_id' => $key['budget_id'],
                    // 'tanggal_req' => Carbon::now(),
                    // 'tanggal_approve' => Carbon::now(),
                    // 'amount_req' => $key['amount'],
                    // 'amount_approve' => $key['amount'],
                    // 'remark_req' => '(' . "From " . "Budget " . $project_budget_id . ') ' . $description,
                    // 'remark_approve' => "-",
                    // 'jenis_data' => 2,
                    // 'user_req' => $myArr['user_id'],
                    // 'user_approve' => $myArr['user_id'],
                    // 'status_req' => 1
                    // ));

                    DB::table('0_project_budget_rab_details')
                        ->insert(array(
                            'rab_id' => $detail_data_rab->id,
                            'from_rab' => $project_budget_id,
                            'amount' => $key['amount'],
                            'remark' => "Nominal Addition",
                            'description' => $description,
                            'type' => 2,
                            'user_id' => $myArr['user_id'],
                            'created_at' => Carbon::now()
                        ));

                    DB::table('0_project_budget_rab_details')
                        ->insert(array(
                            'rab_id' => $budget_rab_detail->id,
                            'from_rab' => 0,
                            'amount' => -abs($key['amount']),
                            'remark' => "Nominal Transfer to " . $key['budget_id'],
                            'description' => $description,
                            'type' => 2,
                            'user_id' => $myArr['user_id'],
                            'created_at' => Carbon::now()
                        ));
                }

                DB::table('0_project_budgets')->where('project_budget_id', $project_budget_id)
                    ->update(array('rab_amount' => $budget_detail->rab_amount - $total));

                // DB::table('0_project_budget_details')
                // ->insert(array(
                // 'project_budget_id' => $project_budget_id,
                // 'tanggal_req' => Carbon::now(),
                // 'tanggal_approve' => Carbon::now(),
                // 'amount_req' => -abs($total),
                // 'amount_approve' => 0,
                // 'remark_req' => '(' . "Adjustment RAB" . ') ' . $description,
                // 'remark_approve' => "-",
                // 'jenis_data' => 3,
                // 'user_req' => $myArr['user_id'],
                // 'user_approve' => $myArr['user_id'],
                // 'status_req' => 1
                // ));

                DB::table('0_project_budget_rab')->where('budget_id', $project_budget_id)
                    ->update(array('amount' => $budget_detail->rab_amount - $total));

                // Commit Transaction
                DB::commit();

                return response()->json([
                    'success' => true
                ]);
            } catch (Exception $e) {
                // Rollback Transaction
                DB::rollback();
            }
        } else {
            throw new RabAmountException();
        }
    }

    public static function rab_history($rab_id = 0)
    {
        $response = [];
        $sql = QueryProjectBudget::rab_history($rab_id);

        $project_rab = DB::select(DB::raw($sql));
        foreach ($project_rab as $data) {

            $tmp = [];
            $tmp['id'] = $data->id;
            $tmp['project_code'] = $data->code;
            $tmp['amount'] = $data->amount;
            $tmp['remark'] = $data->remark;
            $tmp['description'] = (empty($data->description)) ? '' : $data->description;
            $tmp['creator'] = $data->creator;
            $tmp['created_at'] = $data->created_at;

            array_push($response, $tmp);
        }

        return $response;
    }

    public static function update_cost_budget_jobs()
    {
        // ->where('p.code', 'NOT LIKE', '%OFC%')

        $xbudget =  DB::table('0_project_budgets AS pb')
            ->leftJoin('0_projects AS p', 'pb.project_no', '=', 'p.project_no')
            ->select('pb.project_budget_id', 'pb.amount', 'pb.used_amount')
            ->where('pb.inactive', 0)
            ->where('p.code', 'TIICN221511NW-1')
            ->get();
        try {

            foreach ($xbudget as $data) {
                $cost_jobs = new ProjectBudgetUpdateCost($data->project_budget_id);
                Bus::dispatch($cost_jobs);
            }
            return response()->json([
                'success' => true
            ]);
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }

    public static function get_interest_rab_val(string $date): float {
        $start_date = new DateTime($date);
        $newInterest = new DateTime("2024-01-01");
    
        if($start_date >= $newInterest){
            return 0.01;
        } 

        return 0.0108;
    }
    // public static function rab_index($project_no, $type_name)
    // {
    //     $response = [];

    //     $sql = QueryProjectBudget::view_rab($project_no, $type_name);

    //     $query = DB::select(DB::raw($sql));
    //     foreach ($query as $data) {
    //         $query_usage = QueryProjectBudget::view_rab_cost($data->project_no, $data->budget_type_id);
    //         $usage = DB::select(DB::raw($query_usage));
    //         $cost = (empty($usage[0]->cost)) ? 0 : $usage[0]->cost;
    //         $tmp = [];
    //         $tmp['id'] = $data->id;
    //         $tmp['created'] = date('d-m-Y', strtotime($data->created_at));
    //         $tmp['budget_type_id'] = $data->budget_type_id;
    //         $tmp['budget_type_name'] = $data->budget_name;
    //         $tmp['project_code'] = $data->project_code;
    //         $tmp['amount'] = $data->amount;
    //         $tmp['rab_usage'] = $cost;
    //         $tmp['rab_remain'] = $data->amount - $cost;
    //         $tmp['creator'] = $data->creator;

    //         $sql_detail = QueryProjectBudget::rab_history($data->id);
    //         $query_detail = DB::select(DB::raw($sql_detail));

    //         foreach ($query_detail as $history) {
    //             $item = [];
    //             $item['id_detail'] = $history->id;
    //             $item['date'] = date('d-m-Y', strtotime($history->created_at));
    //             $item['amount_detail'] = $history->amount;
    //             $item['remark_detail'] = $history->remark;
    //             $item['status'] = $history->status;
    //             $item['type'] = $history->type;
    //             $item['creator'] = $history->creator;

    //             $tmp['details'][] = $item;
    //         }

    //         array_push($response, $tmp);
    //     }

    //     return $response;
    // }

    // public static function add_rab($myArr, $project_no)
    // {
    //     $params = $myArr['params'];

    //     $budget_type_id = $params['budget_type_id'];
    //     $amount = $params['amount'];
    //     $remark = $params['remark'];
    //     $time = Carbon::now();
    //     $user_id = $myArr['user_id'];

    //     $find_exist = DB::table('0_project_budget_rab')->where('project_no', $project_no)->where('budget_type_id', $budget_type_id)->first();

    //     if (!empty($find_exist)) {
    //         throw new RabBudgetExistHttpException();
    //     }

    //     DB::table('0_project_budget_rab')
    //         ->insert(array(
    //             'budget_type_id' => $budget_type_id,
    //             'project_no' => $project_no,
    //             'amount' => 0,
    //             'user_id' => $user_id,
    //             'created_at' => $time
    //         ));


    //     $lastID = DB::table('0_project_budget_rab')->where('project_no', $project_no)->orderBy('id', 'DESC')->first();

    //     DB::table('0_project_budget_rab_details')
    //         ->insert(array(
    //             'rab_id' => $lastID->id,
    //             'amount' => $amount,
    //             'remark' => $remark,
    //             'created_by' => $user_id,
    //             'created_at' => $time
    //         ));

    //     // Semua proses benar

    //     return response()->json([
    //         'success' => true
    //     ]);
    // }

    // public static function rab_approve($user_id)
    // {
    //     $response = [];

    //     $sql = QueryProjectBudget::view_rab_approve($user_id);

    //     $query = DB::select(DB::raw($sql));
    //     foreach ($query as $data) {
    //         $tmp = [];
    //         $tmp['id_detail'] = $data->id;
    //         $tmp['rab_id'] = $data->rab_id;
    //         $tmp['amount'] = $data->amount;
    //         $tmp['rab_budget_name'] = $data->budget_name;
    //         $tmp['project_code'] = $data->project_code;
    //         $tmp['requestor'] = $data->creator;
    //         $tmp['time'] = $data->created_at;

    //         array_push($response, $tmp);
    //     }

    //     return $response;
    // }


    // public static function approve_rab($myArr)
    // {
    //     $params = $myArr['params'];

    //     $id_detail = $params['id_detail'];
    //     $rab_id = $params['rab_id'];
    //     $amount = $params['amount'];
    //     $remark = $params['remark'];
    //     $status = $params['status'];
    //     $time = Carbon::now();
    //     $user_id = $myArr['user_id'];

    //     $find_exist = DB::table('0_project_budget_rab')->where('id', $rab_id)->first();

    //     DB::beginTransaction();
    //     try {
    //         DB::table('0_project_budget_rab')->where('id', $rab_id)
    //             ->update(array(
    //                 'updated_at' => Carbon::now(),
    //                 'amount' => $amount + $find_exist->amount
    //             ));


    //         DB::table('0_project_budget_rab_details')->where('id', $id_detail)
    //             ->update(array(
    //                 'updated_at' => $time,
    //                 'approval_remark' => $remark,
    //                 'status' => $status,
    //                 'approved_by' => $user_id,
    //             ));
    //         // Commit Transaction
    //         DB::commit();

    //         // Semua proses benar

    //         return response()->json([
    //             'success' => true
    //         ]);
    //     } catch (Exception $e) {
    //         // Rollback Transaction
    //         DB::rollback();
    //     }
    // }


    // public static function edit_rab($myArr)
    // {
    //     $params = $myArr['params'];
    //     $rab_id = $params['rab_id'];
    //     $amount = $params['amount'];
    //     $remark = $params['remark'];
    //     $time = Carbon::now();
    //     $user_id = $myArr['user_id'];

    //     $find_exist = DB::table('0_project_budget_rab')->where('id', $rab_id)->first();

    //     $beginning_amount = $find_exist->amount;

    //     if ($amount >= $beginning_amount) {
    //         $selisih_amount = $amount - $beginning_amount;
    //     } else if ($amount <= $beginning_amount) {
    //         $selisih_amount = $amount - $beginning_amount;
    //     }

    //     DB::beginTransaction();
    //     try {
    //         DB::table('0_project_budget_rab')->where('id', $rab_id)
    //             ->update(array(
    //                 'updated_at' => Carbon::now(),
    //                 'updated_by' => $user_id,
    //                 'amount' => $amount
    //             ));


    //         DB::table('0_project_budget_rab_details')
    //             ->insert(array(
    //                 'updated_at' => $time,
    //                 'rab_id' => $rab_id,
    //                 'amount' => $selisih_amount,
    //                 'remark' => $remark,
    //                 'status' => 1,
    //                 'type' => 2,
    //                 'created_by' => $user_id,
    //             ));
    //         // Commit Transaction
    //         DB::commit();

    //         // Semua proses benar

    //         return response()->json([
    //             'success' => true
    //         ]);
    //     } catch (Exception $e) {
    //         // Rollback Transaction
    //         DB::rollback();
    //     }
    // }

    // public static function add_req_rab($myArr)
    // {
    //     $params = $myArr['params'];
    //     $rab_id = $params['rab_id'];
    //     $amount = $params['amount'];
    //     $remark = $params['remark'];
    //     $time = Carbon::now();
    //     $user_id = $myArr['user_id'];

    //     DB::beginTransaction();
    //     try {
    //         DB::table('0_project_budget_rab_details')
    //             ->insert(array(
    //                 'created_at' => $time,
    //                 'rab_id' => $rab_id,
    //                 'amount' => $amount,
    //                 'remark' => $remark,
    //                 'status' => 0,
    //                 'type' => 1,
    //                 'created_by' => $user_id,
    //             ));
    //         // Commit Transaction
    //         DB::commit();

    //         // Semua proses benar

    //         return response()->json([
    //             'success' => true
    //         ]);
    //     } catch (Exception $e) {
    //         // Rollback Transaction
    //         DB::rollback();
    //     }
    // }

    public static function export_spp($from, $to)
    {
        $time = time();
        $filename = "SPP-LIST_" . $time;
        return Excel::download(new ProjectSPPExport($from, $to), "$filename.xlsx");
    }
}
