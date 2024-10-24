<?php

namespace App\Api\V1\Controllers;

use JWTAuth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\EmployeesController;
use Illuminate\Http\Request;
use Auth;
use App\Modules\PaginationArr;
use App\Modules\InputList;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SummaryExitClearenceExport;


class ApiEmployeesController extends Controller
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

    public function emp_list(Request $request)
    {
        if (!empty($request->emp_no)) {
            $emp_no = $request->emp_no;
        } else {
            $emp_no = 0;
        }
        $myArray = InputList::emp_list_row($emp_no);
        return $myArray;
    }

    public function employees(Request $request)
    {
        if (!empty($request->emp_id)) {
            $emp_id = $request->emp_id;
        } else {
            $emp_id = '';
        }
        if (!empty($request->emp_name)) {
            $emp_name = $request->emp_name;
        } else {
            $emp_name = '';
        }
        if (!empty($request->client_id)) {
            $client_id = $request->client_id;
        } else {
            $client_id = 0;
        }
        if (!empty($request->division_id)) {
            $division_id = $request->division_id;
        } else {
            $division_id = 0;
        }
        if (!empty($request->position_id)) {
            $position_id = $request->position_id;
        } else {
            $position_id = 0;
        }
        if (!empty($request->location_id)) {
            $location_id = $request->location_id;
        } else {
            $location_id = 0;
        }
        if (!empty($request->type_id)) {
            $type_id = $request->type_id;
        } else {
            $type_id = 0;
        }
        if (!empty($request->status_id)) {
            $status_id = $request->status_id;
        } else {
            $status_id = 0;
        }

        $myArray = EmployeesController::show_employees(
            $emp_id,
            $emp_name,
            $client_id,
            $division_id,
            $position_id,
            $location_id,
            $type_id,
            $status_id
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

    public function company_list(Request $request)
    {
        if (!empty($request->client_id)) {
            $client_id = $request->client_id;
        } else {
            $client_id = 0;
        }

        $data = InputList::company_list_row($client_id);

        return $data;
    }

    public function employee_level_list(Request $request)
    {
        if (!empty($request->level_id)) {
            $level_id = $request->level_id;
        } else {
            $level_id = 0;
        }

        $data = InputList::employee_level_list_row($level_id);

        return $data;
    }

    public function employee_type_list(Request $request)
    {
        if (!empty($request->employee_type_id)) {
            $employee_type_id = $request->employee_type_id;
        } else {
            $employee_type_id = 0;
        }

        $data = InputList::employee_type_list_row($employee_type_id);

        return $data;
    }

    public function employee_status_list(Request $request)
    {
        if (!empty($request->status_id)) {
            $status_id = $request->status_id;
        } else {
            $status_id = 0;
        }

        $data = InputList::employee_status_list_row($status_id);

        return $data;
    }

    public function employee_detail(Request $request)
    {
        if (!empty($request->id)) {
            $id = $request->id;
        } else {
            $id = '';
        }

        $data = EmployeesController::show_employees_details($id);

        return $data;
    }

    public function employee_detail_hardware(Request $request)
    {
        if (!empty($request->id)) {
            $id = $request->id;
        } else {
            $id = '';
        }

        $data = EmployeesController::show_employees_detail_hardware($id);

        return $data;
    }

    public function employee_detail_tools(Request $request)
    {
        if (!empty($request->id)) {
            $id = $request->id;
        } else {
            $id = '';
        }

        $data = EmployeesController::show_employees_detail_tools($id);

        return $data;
    }

    public function employee_detail_ca(Request $request)
    {
        if (!empty($request->id)) {
            $id = $request->id;
        } else {
            $id = '';
        }

        $data = EmployeesController::show_employees_detail_ca($id);

        return $data;
    }

    public function create_exit_clearence(Request $request)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['user_id'] = $this->user_id;

        return
            EmployeesController::create_ec($myArray);
    }

    public function show_exit_clearence(Request $request)
    {
        if (!empty($request->from_date)) {
            $from_date = $request->from_date;
        } else {
            $from_date =
                date("Y-m-d", strtotime(date(
                    "Y-m-d",
                    strtotime(date("Y-m-d"))
                ) . "-1 year"));
        }
        if (!empty($request->to_date)) {
            $to_date = $request->to_date;
        } else {
            $to_date =
                date("Y-m-d", strtotime(date(
                    "Y-m-d",
                    strtotime(date("Y-m-d"))
                ) . "+1 day"));
        }
        if (!empty($request->emp_id)) {
            $emp_id = $request->emp_id;
        } else {
            $emp_id = '';
        }

        if (!empty($request->emp_name)) {
            $emp_name = $request->emp_name;
        } else {
            $emp_name = '';
        }

        if (!empty($request->ec_status)) {
            $ec_status = $request->ec_status;
        } else {
            $ec_status = 0;
        }

        $myArray = EmployeesController::show_ec(
            0,
            $ec_status,
            $from_date,
            $to_date,
            $emp_id,
            $emp_name
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

    public function exit_clearence_need_approve(Request $request)
    {
        $myData = EmployeesController::ec_need_approve(
            $this->user_level,
            $this->user_person_id,
            $this->user_division

        );

        return $myData;
    }
    public function add_attachment_ec(Request $request)
    {
        $request->validate([
            'attachments' => 'required',
            'attachments.*' => 'mimes:doc,pdf,docx,png,jpg,jpeg,xls,xlsx'
        ]);

        if ($request->hasfile('attachments')) {

            foreach ($request->file('attachments') as $file) {
                $filename = "EC" . date('Ymd') . rand(1, 9999999999);
                $name = $file->getClientOriginalName();
                $file_ext = $file->getClientOriginalExtension();
                $destination = public_path("/storage/hrm/ec");
                $file->move($destination, $filename . ".$file_ext");

                DB::table('0_hrm_ec_attachments')
                    ->insert(array(
                        'ec_id' => $request->ec_id,
                        'filename' => $filename . ".$file_ext",
                        'uploaded_by' => $this->user_id,
                        'created_at' => Carbon::now()
                    ));
            }
            return response()->json([
                'success' => true,
            ], 200);
        }
    }

    public function approve_ec(Request $request)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['user_id'] = $this->user_id;
        $myArray['user_level'] = $this->user_level;
        $myArray['person_id'] = $this->user_person_id;
        $myArray['division_id'] = $this->user_division;
        $myQuery = EmployeesController::ec_approve($myArray);
        return $myQuery;
    }

    public function show_ec_history(Request $request)
    {
        $myData = EmployeesController::ec_history(
            $request->ec_id

        );

        return $myData;
    }

    public function edit_project_manager_user(Request $request)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['user_id'] = $this->user_id;
        $myArray['user_level'] = $this->user_level;
        $myArray['person_id'] = $this->user_person_id;
        $myArray['division_id'] = $this->user_division;
        $myQuery = EmployeesController::edit_pm_ec($myArray);
        return $myQuery;
    }

    public function show_ec_need_pm(Request $request)
    {
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
        if (!empty($request->emp_id)) {
            $emp_id = $request->emp_id;
        } else {
            $emp_id = '';
        }
        if (!empty($request->emp_name)) {
            $emp_name = $request->emp_name;
        } else {
            $emp_name = '';
        }
        $myArray = EmployeesController::show_ec(
            1,
            0,
            $from_date,
            $to_date,
            $emp_id,
            $emp_name
        );
        return response()->json([
            'success' => true,
            'data' => $myArray

        ]);
        // $myPage = $request->page;
        // $myUrl = $request->url();
        // $query = $request->query();

        // if (empty($request->perpage)) {
        //     $perPage = 10;
        // } else {
        //     $perPage = $request->perpage;
        // }

        // return PaginationArr::arr_pagination(
        //     $myArray,
        //     $myPage,
        //     $perPage,
        //     $myUrl,
        //     $query
        // );
    }

    public function ec_reason_list(Request $request)
    {
        if (!empty($request->id)) {
            $id = $request->id;
        } else {
            $id = 0;
        }

        $myArray = EmployeesController::reason_list($id);
        return $myArray;
    }

    public function export_exit_clearence(Request $request)
    {
        $emp_id = $request->emp_id;

        return
            EmployeesController::export_ec(
                $emp_id
            );
    }

    public function export_summary_exit_clearence(Request $request)
    {
        $filename = "Summary Exit Clearences";
        return Excel::download(new SummaryExitClearenceExport, "$filename.xlsx");
    }

    public function cancel_exit_clearernce(Request $request)
    {
        $ec_id = $request->id;
        $user_id = $this->user_id;
        $myQuery = EmployeesController::cancel_ec($ec_id, $user_id);
        return $myQuery;
    }

    public function edit_ec_pm(Request $request)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['user_id'] = $this->user_id;
        $myArray['user_level'] = $this->user_level;
        $myArray['person_id'] = $this->user_person_id;
        $myArray['division_id'] = $this->user_division;
        $myQuery = EmployeesController::edit_pm_ec($myArray);
        return $myQuery;
    }

    public function close_exit_clearence_manual(Request $request)
    {
        $ec_id = $request->id;
        $myQuery = EmployeesController::close_ec_manual($ec_id);
        return $myQuery;
    }

    public function edit_exit_clearences_history(Request $request)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['user_id'] = $this->user_id;
        $myArray['user_level'] = $this->user_level;
        $myArray['person_id'] = $this->user_person_id;
        $myArray['division_id'] = $this->user_division;
        $myQuery = EmployeesController::edit_ec_history($myArray);
        return $myQuery;
    }
}
