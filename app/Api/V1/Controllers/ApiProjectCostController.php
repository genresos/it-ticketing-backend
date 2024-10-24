<?php

namespace App\Api\V1\Controllers;

use JWTAuth;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ProjectListController;
use App\Http\Controllers\ProjectCostController;
use Illuminate\Http\Request;
use Auth;
use App\Modules\PaginationArr;
use App\Modules\InputList;
use App\Query\QueryProjectBudget;


class ApiProjectCostController extends Controller
{

    public function __construct()
    {
        $currentUser = JWTAuth::parseToken()->authenticate();

        $this->user_id = Auth::guard()->user()->id;
        $this->user_level = Auth::guard()->user()->approval_level;
        $this->user_division = Auth::guard()->user()->division_id;
    }
    public function get_project_performance_summary(Request $request)
    {
        $project_no = $request->project_no;
        $myData = ProjectCostController::project_performance_summary($project_no);
        return $myData;
    }

    public function get_project_performance_summary_monthly(Request $request)
    {
        $project_no = $request->project_no;
        $myData = ProjectCostController::project_performance_summary_monthly($project_no);
        return $myData;
    }

    public function cost_detail_usage(Request $request)
    {
        $myData = QueryProjectBudget::cost_detail_usage($request->project_no);
        return response()->json([
            'success' => true,
            'data' => $myData
        ], 200);
    }
}
