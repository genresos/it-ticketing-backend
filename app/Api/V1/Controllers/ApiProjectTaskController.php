<?php

namespace App\Api\V1\Controllers;

use JWTAuth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\CashAdvanceController;
use App\Http\Controllers\ProjectTaskController;
use Illuminate\Http\Request;
use Auth;
use App\Modules\PaginationArr;
use App\Modules\InputList;
use Storage;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\QtyProgressNeededMatchHttpException;

class ApiProjectTaskController extends Controller
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

    public function view_task(Request $request, $project_no)
    {
        $myArray = ProjectTaskController::view_task_by_project(
            $project_no
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

    public function buildTree($items)
    {

        $childs = array();

        foreach ($items as $item)
            $childs[$item->project][] = $item;

        foreach ($items as $item) if (isset($childs[$item->id]))
            $item->childs = $childs[$item->id];

        return $childs[0];
    }

    public function view_tree_task(Request $request, $project_no)
    {

        $tasks = ProjectTaskController::view_task_tree_by_project(
            $project_no
        );

        $response = $this->buildTree($tasks);
        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public function create_task(Request $request)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['user_id'] = $this->user_id;
        $myArray['user_name'] = $this->user_name;
        $myArray['user_emp_id'] = $this->user_emp_id;
        $myQuery = ProjectTaskController::create_new_task($myArray);
        return $myQuery;
    }

    public function task_assignee(Request $request)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['user_id'] = $this->user_id;
        $myQuery = ProjectTaskController::task_assignee_user($myArray);
        return $myQuery;
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
            0
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
            $request->site_no,
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

                $qty_finished = $progress_qty_ongoing + $request->qty;
                if ($validation_qty_progress <= $qty_task_needed) {
                    DB::table('0_project_task_progress')->insert(array(
                        'id_cico' => $id,
                        'project_task_id' => $data->project_task_id,
                        'date' => date('Y-m-d'),
                        'description' => $request->description,
                        'ext_description' => $request->remark_ext,
                        'qty' => $request->qty,
                        'sow' => $request->sow,
                        'du_id' => $request->du_id,
                        'site_no' => $request->site_no,
                        'lat' => $request->latitude,
                        'long' => $request->longitude,
                        'updated_by' => $this->user_id
                    ));

                    DB::table('0_project_task')->where('id', $data->project_task_id)
                        ->update(array(
                            'qty_finished' => $qty_finished
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

    public function create_project_du_id(Request $request)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['user_id'] = $this->user_id;
        $myQuery = ProjectTaskController::create_du_id($myArray);
        return $myQuery;
    }

    public function project_du_id(Request $request)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['user_id'] = $this->user_id;
        $myQuery = ProjectTaskController::show_project_du_id($myArray);
        return $myQuery;
    }

    public function attendance_emp(Request $request)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['user_id'] = $this->user_id;
        $myQuery = ProjectTaskController::show_attendance_employee($myArray);
        return $myQuery;
    }

    public function upload_fat_details_before(Request $request)
    {
        $file_type = ($request->type == 1) ? "FAT_BEFORE" : "FDT_BEFORE";
        $key_name = $request->opm_before;
        $progress_id = DB::table('0_project_task_progress')->where('id_cico', $request->cico_id)->orderBy('id', 'DESC')->first();

        DB::beginTransaction();

        try {
            DB::table('0_project_task_fat')->insert(array(
                'fat_id' => $request->fat_id,
                'fat_no' => $request->fat_no,
                'progress_id' => $progress_id->id,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'created_by' => $this->user_id,
                'created_at' => Carbon::now()
            ));
            DB::commit();
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
        $get_latest = DB::table('0_project_task_fat')->where('fat_id', $request->fat_id)->orderBy('id', 'DESC')->first();
        $fat_id = $get_latest->id;
        foreach ($key_name as $item => $key) {

            $data = explode(',', $key);
            $first_file = "first_opm";
            $second_file = "second_opm";

            $first_photo_key = $first_file . $data[0];
            $first_photo = $request->file("$first_photo_key");

            $second_photo_key = $second_file . $data[0];
            $second_photo = $request->file("$second_photo_key");

            if ($first_photo) {
                $filename_first = $file_type . $this->user_id . date('Ymd') . rand(1, 9999999999);
                $destination = public_path("/storage/project_task/images");
                $first_photo->move($destination, $filename_first . ".jpg");
            }
            if ($second_photo) {
                $filename_second = $file_type . $this->user_id . date('Ymd') . rand(1, 9999999999);
                $destination = public_path("/storage/project_task/images");
                $second_photo->move($destination, $filename_second . ".jpg");
            }

            DB::beginTransaction();

            try {
                DB::table('0_project_task_fat_details')->insert(array(
                    'fat_id' => $fat_id,
                    'port' => $data[0],
                    'lamda1' => $data[1],
                    'lamda2' => $data[2],
                    'photo1' => $filename_first,
                    'photo2' => $filename_second,
                    'created_by' => $this->user_id,
                    'created_at' => Carbon::now()
                ));
                DB::commit();
            } catch (Exception $e) {
                // Rollback Transaction
                DB::rollback();
            }
        }

        return response()->json([
            'success' => true
        ]);
    }


    public function upload_fat_details_after(Request $request)
    {
        $file_type = ($request->type == 1) ? "FAT_AFTER" : "FDT_AFTER";
        $key_name = $request->opm_after;
        $get_latest = DB::table('0_project_task_fat')->where('fat_id', $request->fat_id)->orderBy('id', 'DESC')->first();
        $fat_id = $get_latest->id;
        foreach ($key_name as $item => $key) {

            $data = explode(',', $key);
            $first_file = "first_opm";
            $second_file = "second_opm";

            $first_photo_key = $first_file . $data[0];
            $first_photo = $request->file("$first_photo_key");

            $second_photo_key = $second_file . $data[0];
            $second_photo = $request->file("$second_photo_key");

            if ($first_photo) {
                $filename_first = $file_type . $this->user_id . date('Ymd') . rand(1, 9999999999);
                $destination = public_path("/storage/project_task/images");
                $first_photo->move($destination, $filename_first . ".jpg");
            }
            if ($second_photo) {
                $filename_second = $file_type . $this->user_id . date('Ymd') . rand(1, 9999999999);
                $destination = public_path("/storage/project_task/images");
                $second_photo->move($destination, $filename_second . ".jpg");
            }

            DB::beginTransaction();

            try {
                DB::table('0_project_task_fat_details')->insert(array(
                    'fat_id' => $fat_id,
                    'port' => $data[0],
                    'lamda1' => $data[1],
                    'lamda2' => $data[2],
                    'photo1' => $filename_first,
                    'photo2' => $filename_second,
                    'created_by' => $this->user_id,
                    'created_at' => Carbon::now()
                ));
                DB::commit();
            } catch (Exception $e) {
                // Rollback Transaction
                DB::rollback();
            }
        }

        return response()->json([
            'success' => true
        ]);
    }

    public function delete_project_task($task_id)
    {
        return ProjectTaskController::delete_task($task_id);
    }

    public function fat_by_site(Request $request)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['user_id'] = $this->user_id;
        $myQuery = ProjectTaskController::get_fat_by_site($myArray);
        return $myQuery;
    }
}
