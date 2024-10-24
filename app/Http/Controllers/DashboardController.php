<?php

namespace App\Http\Controllers;

use JWTAuth;
use App\Http\Controllers\Controller;
use App\Http\Controllers\TestingController;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\UserController;
use App\Image;
use App\Modules\InputList;
use App\Query\QueryDashboard;
class DashboardController extends Controller
{
    public static function get_pnl_summary_data($year,$divison_id){
        $response = [];

        $sql = QueryDashboard::pnl_summary_data($year,$divison_id);
        $exec = DB::select(DB::raw($sql));

        $income_amount = $cost_amount = 0;
        foreach ($exec as $data) {

            $income_amount += $data->income;
            $cost_amount += $data->cost;
            
            array_push($response,
            array('month' => $data->imonth, 'sales'=>$income_amount, 'cost'=> $cost_amount));

        }

        return $response;
    }

    public static function get_pnl_data_by_month($year,$divison_id){
        $response = [];

        $sql = QueryDashboard::pnl_summary_data($year,$divison_id);
        $exec = DB::select(DB::raw($sql));

        $income_amount = $cost_amount = 0;
        foreach ($exec as $data) {

            array_push($response,
            array('month' => $data->imonth, 'sales'=>$data->income, 'cost'=> $data->cost));

        }

        return $response;
    }


    public static function get_pnl_by_division_data($year){
        $response = [];

        $sql = QueryDashboard::pnl_by_division_data($year);
        $exec = DB::select(DB::raw($sql));

        $income_amount = $cost_amount = 0;
        foreach ($exec as $data) {

            array_push($response, 
            array('division' => $data->division_name, 
            'revenue'=>$data->income, 'cost'=> $data->cost));
        }

        return $response;
    }

    public static function get_pnl_total_data($year,$divison_id){
        $response = [];

        $sql = QueryDashboard::pnl_total_data($year,$divison_id);
        $exec = DB::select(DB::raw($sql));

        $income_amount = $cost_amount = 0;
        foreach ($exec as $data) {

            array_push($response, 
            array('totalRevenue' => $data->income, 
            'totalCost'=>$data->cost, 'totalProfit'=> $data->income - $data->cost));
        }

        return $response;
    }


    public static function get_current_asset_data($year){
        $response = [];

        $sql = QueryDashboard::current_asset_data($year);
        $exec = DB::select(DB::raw($sql));

        $income_amount = $cost_amount = $profit_amount = 0 ;
        foreach ($exec as $data) {

            array_push($response, array('label'=>$data->name, 
            'value'=> round(($data->amount/1000000000),2)));
        }

        return $response;
    }

    public static function get_current_asset_total_data($year){
        $response = [];

        $sql = QueryDashboard::current_asset_total_data($year);
        $exec = DB::select(DB::raw($sql));

        $income_amount = $cost_amount = $profit_amount = 0 ;
        foreach ($exec as $data) {

            array_push($response, array('amount'=>round($data->amount,2)));
        }

        return $response;
    }

    public static function get_non_current_asset_data($year){
        $response = [];

        $sql = QueryDashboard::non_current_asset_data($year);
        $exec = DB::select(DB::raw($sql));

        $income_amount = $cost_amount = $profit_amount = 0 ;
        foreach ($exec as $data) {

            array_push($response, array('label'=>$data->name, 
            'value'=> round(($data->amount/1000000000),2)));
        }

        return $response;
    }

    public static function get_non_current_asset_total_data($year){
        $response = [];

        $sql = QueryDashboard::non_current_asset_total_data($year);
        $exec = DB::select(DB::raw($sql));

        $income_amount = $cost_amount = $profit_amount = 0 ;
        foreach ($exec as $data) {

            array_push($response, array('amount'=>round($data->amount,2)));
        }

        return $response;
    }

    public static function get_current_liabilities_data($year){
        $response = [];

        $sql = QueryDashboard::current_liabilities_data($year);
        $exec = DB::select(DB::raw($sql));

        $income_amount = $cost_amount = $profit_amount = 0 ;
        foreach ($exec as $data) {

            array_push($response, array('label'=>$data->name, 
            'value'=> round(($data->amount/1000000000),2)));
        }

        return $response;
    }

    public static function get_current_liabilities_total_data($year){
        $response = [];

        $sql = QueryDashboard::current_liabilities_total_data($year);
        $exec = DB::select(DB::raw($sql));

        $income_amount = $cost_amount = $profit_amount = 0 ;
        foreach ($exec as $data) {

            array_push($response, array('amount'=>round($data->amount,2)));
        }

        return $response;
    }

    public static function get_non_current_liabilities_data($year){
        $response = [];

        $sql = QueryDashboard::non_current_liabilities_data($year);
        $exec = DB::select(DB::raw($sql));

        $income_amount = $cost_amount = $profit_amount = 0 ;
        foreach ($exec as $data) {

            array_push($response, array('label'=>$data->name, 
            'value'=> round(($data->amount/1000000000),2)));
        }

        return $response;
    }

    public static function get_non_current_liabilities_total_data($year){
        $response = [];

        $sql = QueryDashboard::non_current_liabilities_total_data($year);
        $exec = DB::select(DB::raw($sql));

        $income_amount = $cost_amount = $profit_amount = 0 ;
        foreach ($exec as $data) {

            array_push($response, array('amount'=>round($data->amount,2)));
        }

        return $response;
    }
}
