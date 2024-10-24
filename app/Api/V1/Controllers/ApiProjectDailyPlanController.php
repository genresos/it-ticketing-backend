<?php

namespace App\Api\V1\Controllers;

use JWTAuth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ProjectDailyPlanController;
use Illuminate\Http\Request;
use Auth;
use App\Modules\PaginationArr;
use App\Modules\InputList;
use Storage;
use Symfony\Component\HttpKernel\Exception\QtyProgressNeededMatchHttpException;

class ApiProjectDailyPlanController extends Controller
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
        $this->user_emp_no = Auth::guard()->user()->emp_no;
        $this->user_emp_id = Auth::guard()->user()->emp_id;
    }

    public function approval_daily_plan(Request $request)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['user_id'] = $this->user_id;
        $myArray['user_old_id'] = $this->old_id;
        $myArray['emp_id'] = $this->user_emp_id;
        $myArray['level'] = $this->user_level;
        $myArray['person_id'] = $this->user_person_id;

        $myQuery = ProjectDailyPlanController::need_approval_daily_plan(
            $myArray
        );

        return $myQuery;
    }

    public function get_daily_plan(Request $request)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['user_id'] = $this->user_id;
        $myArray['level'] = $this->user_level;
        $myArray['person_id'] = $this->user_person_id;
        $myArray['emp_id'] = $this->user_emp_id;

        $myArray = ProjectDailyPlanController::get_project_daily_plan(
            $myArray
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

    public function search_daily_plan(Request $request)
    {
        $myArray = [];
        $myArray['doc_no'] = $request->doc_no;
        $myArray['user_id'] = $this->user_id;
        $myArray['level'] = $this->user_level;

        $myQuery = ProjectDailyPlanController::search($myArray);
        return $myQuery;
    }

    public function status_daily_plan(Request $request)
    {
        $myArray = [];
        $myArray['params'] = $request->all();

        $myArray = ProjectDailyPlanController::status(
            $myArray
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

    public function task_list(Request $request)
    {
        if (!empty($request->task_id)) {
            $task_id = $request->task_id;
        } else {
            $task_id = 0;
        }
        $myArray = InputList::project_task_list_row(
            $task_id
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

    public function my_project_task(Request $request)
    {
        $myArray = ProjectTaskController::project_task(
            $this->user_id,
            $this->user_emp_no,
            $this->user_person_id
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

    public function detail_reports($id)
    {
        $myArray = ProjectTaskController::project_task_detail($id, $this->user_id);

        return $myArray;
    }

    public function check_in(Request $request, $id)
    {
        $myArray = ProjectTaskController::project_task_check_in(
            $id,
            $this->user_id,
            $request->latitude,
            $request->longitude,
            $request->file('photo')
        );
        return $myArray;
    }

    public function check_out(Request $request, $id)
    {  //$id = cico_id


        $myArray = ProjectTaskController::project_task_check_out(
            $id,
            $this->user_id,
            $request->latitude,
            $request->longitude,
            $request->file('photo')
        );
        return $myArray;
    }

    public function export_surtug(Request $request, $id)
    {  
        $url = "https://192.168.0.70/reporting/prn_redirect?PARAM_0=$id&PARAM_1=0&PARAM_2=&PARAM_3=&PARAM_4=&PARAM_5=0&REP_ID=161";
        return Redirect::to($url);
    }
    public function add_daily_plan(Request $request)
    {
        $myArray = [];
        $myArray['params'] = $request->data;
        $myArray['task'] = $request->task;
        $myArray['project_task'] = $request->task;
        $myArray['creator_name'] = $this->user_name;
        $myArray['creator_nik'] = $this->user_emp_id;

        $myQuery = ProjectDailyPlanController::create_daily_plan($myArray);

        return $myQuery;
    }

    public function update_task(Request $request, $id)
    {
        $myArray = [];

        $files = $request->file();
        $sql = "SELECT * FROM 0_project_task_cico WHERE id = $id";
        $validation_cico = DB::select(DB::raw($sql));

        foreach ($validation_cico as $data) {
            if ($data->check_out == 1) {
                $msg = "You've been check out!";
                return response()->json([
                    'status' => false,
                    'data' => $msg
                ], 403);
            } else if ($data->check_out < 1) {

                $progress_qty_ongoing = DB::table('0_project_task_progress')
                    ->where('id_cico', $id)
                    ->sum('qty');

                $qty_task_needed = DB::table('0_project_task')
                    ->where('id', $data->project_task_id)
                    ->sum('qty');

                $validation_qty_progress = $progress_qty_ongoing + $request->qty;
                if ($validation_qty_progress <= $qty_task_needed) {
                    DB::table('0_project_task_progress')->insert(array(
                        'id_cico' => $id,
                        'project_task_id' => $data->project_task_id,
                        'date' => date('Y-m-d'),
                        'description' => $request->description,
                        'qty' => $request->qty,
                        'updated_by' => $this->user_id
                    ));
                } else if ($validation_qty_progress > $qty_task_needed) {
                    throw new QtyProgressNeededMatchHttpException();
                }
            }

            foreach ($files as $file) {
                $tmp = [];

                $filename = "PROGRESS" . $this->user_id . date('Ymd') . rand(1, 9999999999);
                $destination = public_path("/storage/project_task/images");
                $file->move($destination, $filename . ".jpg");

                $tmp['filename'] = $filename;

                $get_progress_id = DB::table('0_project_task_progress')->orderBy('id', 'DESC')->first();
                $progress_id = $get_progress_id->id;
                DB::beginTransaction();

                try {
                    DB::table('0_project_progress_photos')->insert(array(
                        'project_task_id' => $data->project_task_id,
                        'progress_id' => $progress_id,
                        'file_path' => $filename,
                        'created_by' => $this->user_id
                    ));
                    DB::commit();
                } catch (Exception $e) {
                    // Rollback Transaction
                    DB::rollback();
                }

                array_push($myArray, $tmp);
            }
            return response()->json([
                'success' => true
            ]);
        }
        return response()->json([
            'success' => true,
            'data' => $myArray
        ]);
    }
}
