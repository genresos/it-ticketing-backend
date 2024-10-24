<?php

namespace App\Api\V1\Controllers;

use JWTAuth;
use App\Http\Controllers\Controller;
use App\Http\Controllers\TestingController;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\UserController;
use App\Image;
use App\Modules\InputList;
use App\Http\Controllers\DashboardController;
use App\Modules\PaginationArr;
class ApiDashboardController extends Controller
{

    public function __construct()
    {
        $this->user_id = Auth::guard()->user()->id;
        $this->user_emp_id = Auth::guard()->user()->emp_id;
        $this->user_level = Auth::guard()->user()->approval_level;
        $this->user_division = Auth::guard()->user()->division_id;
    }

    public function index(Request $request){

        if (!empty($request->year)) {
            $year_bl = date("$request->year-m-d");
            $year_pnl = $request->year;
        } else {
            $year_bl = date('Y-m-d');
            $year_pnl = date('Y');
        }

        if (!empty($request->division_id)) {
            $division_id = $request->division_id;
        } else {
            $division_id = 0;
        }

        if (!empty($request->byMonth)) {
            $byMonth = $request->byMonth;
        } else {
            $byMonth = 0;
        }
        
        $response = [];

        $tmp1 =[];

        if($byMonth == 1){
            $tmp1['all_division'] = DashboardController::get_pnl_data_by_month($year_pnl, $division_id);
        }else{
            $tmp1['all_division'] = DashboardController::get_pnl_summary_data($year_pnl, $division_id);

        }
        $tmp1['per_division'] = DashboardController::get_pnl_by_division_data($year_pnl, $division_id);
        $tmp1['total'] = DashboardController::get_pnl_total_data($year_pnl, $division_id);
        $response['profit_n_loss'] = $tmp1;

        $tmp2 =[];
        $tmp2['current_asset']['data'] = DashboardController::get_current_asset_data($year_bl);
        $tmp2['current_asset']['total'] = DashboardController::get_current_asset_total_data($year_bl);

        $tmp2['non_current_asset']['data'] = DashboardController::get_non_current_asset_data($year_bl);
        $tmp2['non_current_asset']['total'] = DashboardController::get_non_current_asset_total_data($year_bl);

        $tmp2['current_liabilities']['data'] = DashboardController::get_current_liabilities_data($year_bl);
        $tmp2['current_liabilities']['total'] = DashboardController::get_current_liabilities_total_data($year_bl);

        $tmp2['non_current_liabilities']['data'] = DashboardController::get_non_current_liabilities_data($year_bl);
        $tmp2['non_current_liabilities']['total'] = DashboardController::get_non_current_liabilities_total_data($year_bl);
;
        $response['balance_sheet'] = $tmp2;


        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }
}

