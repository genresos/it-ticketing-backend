<?php

namespace App\Api\V1\Controllers;
use JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Carbon\Carbon;
use Auth;
use App\CashAdvance;
use App\Http\Controllers\FinanceController;

use Symfony\Component\HttpKernel\Exception\ValidationAmountCADHttpException;

class EmployeesController extends Controller
{
    //
    use Helpers;

    /*
     *
     * 
     */
//==================================================================== FUNCTION CA LIST User =============================================================\\  
    public function employees(){

        $sql = "SELECT * FROM 0_hrm_employees WHERE inactive = 0";
        $employees = DB::select( DB::raw($sql));
        $response = [];

        foreach($employees as $data){
            $tmp = [];
            $tmp['emp_no'] = $data->id;
            $tmp['emp_id'] = $data->emp_id;
            $tmp['name'] = $data->name;

            array_push($response,$tmp);

        }

        return response()->json([
            'success' => true,
            'data' => $response 
        ],200);
    }
}
