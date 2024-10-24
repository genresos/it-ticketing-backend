<?php

namespace App\Api\V1\Controllers;

use JWTAuth;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ProjectListController;
use Illuminate\Http\Request;
use Auth;
use App\Modules\PaginationArr;
use App\Modules\InputList;
use Illuminate\Support\Facades\DB;

class ApiProjectListController extends Controller
{

    public function __construct()
    {
        $currentUser = JWTAuth::parseToken()->authenticate();

        $this->user_id = Auth::guard()->user()->id;
        $this->user_level = Auth::guard()->user()->approval_level;
        $this->user_division = Auth::guard()->user()->division_id;
    }

    public function project_list(Request $request)
    {
        if (!empty($request->inactive)) {
            $inactive = $request->inactive;
        } else {
            $inactive = 0;
        }

        if (!empty($request->project_code)) {
            $project_code = $request->project_code;
        } else {
            $project_code = '';
        }

        if (!empty($request->realtime_cost)) {
            $realtime_cost = $request->realtime_cost;
        } else {
            $realtime_cost = 0;
        }

        $myArray = ProjectListController::list($inactive, $project_code, $realtime_cost);
        return $myArray;
    }

    public function update_project_value(Request $request, $project_no)
    {
        $project_value = $request->project_value;
        DB::beginTransaction();
        try {
            // $p_info = ApiProjectOverviewController::project_info($project_no);
            // if ($p_info[0]->project_value != null) {
            //     return response()->json(['error' => [
            //         'message' => 'It cannot be changed, because there is already a rab system',
            //         'status_code' => 403,
            //     ]], 403);
            // }    

            DB::table('0_projects')->where('project_no', $project_no)
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

    public function get_project(Request $request, $project_no)
    {
        $myArray = ProjectListController::project($project_no);
        return $myArray;
    }

    public function division_list(Request $request)
    {
        if (!empty($request->show_project_only)) {
            $show_project_only = $request->show_project_only;
        } else {
            $show_project_only = 0;
        }
        $myArray = InputList::division_list_row($show_project_only);
        return $myArray;
    }

    public function project_type_list(Request $request)
    {
        if (!empty($request->project_type_id)) {
            $project_type_id = $request->project_type_id;
        } else if (empty($request->project_type_id)) {
            $project_type_id = 0;
        }

        if (!empty($request->division_id)) {
            $division_id = $request->division_id;
        } else if (empty($request->division_id)) {
            $division_id = 0;
        }
        $myArray = InputList::project_type_list_row($project_type_id, $division_id);
        return $myArray;
    }

    public function customer_list(Request $request)
    {
        if (!empty($request->debtor_no)) {
            $debtor_no = $request->debtor_no;
        } else {
            $debtor_no = 0;
        }
        $myArray = InputList::customer_list_row($debtor_no);
        return $myArray;
    }

    public function sow_list(Request $request)
    {
        if (!empty($request->sow_id)) {
            $sow_id = $request->sow_id;
        } else {
            $sow_id = 0;
        }
        $myArray = InputList::sow_list_row($sow_id);
        return $myArray;
    }

    public function po_category_list(Request $request)
    {
        if (!empty($request->po_category_id)) {
            $po_category_id = $request->po_category_id;
        } else {
            $po_category_id = 0;
        }
        $myArray = InputList::po_category_list_row($po_category_id);
        return $myArray;
    }

    public function site_list(Request $request)
    {
        if (!empty($request->site_no)) {
            $site_no = $request->site_no;
        } else if (empty($request->site_no)) {
            $site_no = 0;
        }

        if (!empty($request->site_name)) {
            $site_name = $request->site_name;
        } else if (empty($request->site_name)) {
            $site_name = '';
        }
        $myArray = InputList::site_list_row($site_no, $site_name);
        return $myArray;
    }

    public function site_office(Request $request)
    {
        if (!empty($request->site_no)) {
            $site_no = $request->site_no;
        } else if (empty($request->site_no)) {
            $site_no = 0;
        }

        if (!empty($request->site_name)) {
            $site_name = $request->site_name;
        } else if (empty($request->site_name)) {
            $site_name = '';
        }
        $myArray = InputList::site_list_office_row($site_no, $site_name);
        return $myArray;
    }

    public function project_manager_list(Request $request)
    {
        if (!empty($request->person_id)) {
            $person_id = $request->person_id;
        } else {
            $person_id = 0;
        }
        $myArray = InputList::project_manager_list_row($person_id);
        return $myArray;
    }

    public function project_area_list(Request $request)
    {
        if (!empty($request->area_id)) {
            $area_id = $request->area_id;
        } else {
            $area_id = 0;
        }
        $myArray = InputList::project_area_list_row($area_id);
        return $myArray;
    }

    public function project_payment_term_list(Request $request)
    {
        if (!empty($request->term_id)) {
            $term_id = $request->term_id;
        } else {
            $term_id = 0;
        }
        $myArray = InputList::project_payment_term_list_row($term_id);
        return $myArray;
    }

    public function currencies_list(Request $request)
    {
        if (!empty($request->curr_abrev)) {
            $curr_abrev = $request->curr_abrev;
        } else {
            $curr_abrev = '';
        }
        $myArray = InputList::currencies_list_row($curr_abrev);
        return $myArray;
    }

    public function project_po_status_list(Request $request)
    {
        if (!empty($request->status_id)) {
            $status_id = $request->status_id;
        } else {
            $status_id = '';
        }
        $myArray = InputList::project_po_status_list_row($status_id);
        return $myArray;
    }

    public function project_status_list(Request $request)
    {
        if (!empty($request->status_id)) {
            $status_id = $request->status_id;
        } else {
            $status_id = '';
        }
        $myArray = InputList::project_status_list_row($status_id);
        return $myArray;
    }

    public function project_parent_list(Request $request)
    {
        if (!empty($request->code)) {
            $code = $request->code;
        } else {
            $code = '';
        }
        $myArray = InputList::project_parent_list_row($code);
        return $myArray;
    }

    public function create_project(Request $request)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['user_id'] = $this->user_id;
        $myQuery = ProjectListController::add_new_project($myArray);
        return $myQuery;
    }

    public function update_project(Request $request, $project_no)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['user_id'] = $this->user_id;
        $myQuery = ProjectListController::update_project($myArray, $project_no);
        return $myQuery;
    }

    public function management_fee(Request $request)
    {
        if (!empty($request->fee_id)) {
            $fee_id = $request->fee_id;
        } else {
            $fee_id = 0;
        }
        $myArray = InputList::project_management_fee($fee_id);
        return $myArray;
    }

    public function delete_project($project_no)
    {
        $myQuery = ProjectListController::delete_project($project_no);
        return $myQuery;
    }

    public function export_project()
    {
        $myQuery = ProjectListController::export_project_list();
        return $myQuery;
    }

    public function project_managers(Request $request)
    {
        if (!empty($request->name)) {
            $name = $request->name;
        } else {
            $name = '';
        }
        $myArray = ProjectListController::project_manager_list(
            $name
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

    public function add_project_managers(Request $request)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['user_id'] = $this->user_id;

        $myQuery = ProjectListController::add_pm($myArray);
        return $myQuery;
    }

    public function update_project_managers(Request $request)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['user_id'] = $this->user_id;

        $myQuery = ProjectListController::update_pm($myArray);
        return $myQuery;
    }
}
