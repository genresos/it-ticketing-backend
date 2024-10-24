<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Dingo\Api\Routing\Helpers;
use Carbon\Carbon;
use Validator, Redirect, Response, File;
use Auth;
use URL;
use QrCode;
use DateTime;
use App\Image;
use App\Modules\InputList;
use App\Query\QueryProjectTask;
use App\Http\Controllers\ProjectListController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProjectDailyPlanController;
use Symfony\Component\HttpKernel\Exception\CheckInValidationHttpException;
use SiteHelper;

class ProjectTaskController extends Controller
{
    public static function view_task_by_project($project_no)
    {
        $response = [];
        $sql = QueryProjectTask::view_task_by_project($project_no);
        $projects = DB::select(DB::raw($sql));
        foreach ($projects as $data) {
            $is_ti_non_wireless = ($data->division_id == 24) ? 1 : 0;
            $tmp = [];
            $tmp['project_task_id'] = $data->project_task_id;
            $du_id = explode(',', $data->du_id);
            $tmp['du_id'] = $du_id;
            $site = QueryProjectTask::get_site_for_cico($data->site_no);
            $tmp['site'] = $site;
            $tmp['plan_date'] = $data->plan_date;
            $tmp['sow'] = $data->sow;
            $tmp['title'] = $data->title;
            $tmp['project_no'] = $data->project_no;
            $tmp['project_code'] = $data->project_code;
            $tmp['ti_non_wireless'] = $is_ti_non_wireless;
            $tmp['area'] = $data->area;
            $tmp['customer'] = $data->customer;
            $tmp['qty_plan'] = $data->qty_task;
            $tmp['qty_actual'] = QueryProjectTask::get_qty_actual($data->project_task_id);
            $tmp['uom'] = $data->uom_task;
            $tmp['remark'] = $data->remark;
            $tmp['status'] = $data->status;
            $tmp['surtug_id'] = $data->surtug_id;
            $tmp['surtug_doc_no'] = $data->surtug_doc_no;

            $sql1 = QueryProjectTask::project_task_body_1($data->project_task_id);

            $get_team = DB::select(DB::raw($sql1));

            foreach ($get_team as $key => $team) {
                if (empty($get_team) || is_null($get_team)) {
                    $tmp['teams'][] = "HAHA";
                } else {
                    $tmp['teams'][] = $team;
                }
            }

            $sql2 = QueryProjectTask::project_task_body_2($data->project_task_id, 0);
            $get_cico = DB::select(DB::raw($sql2));

            foreach ($get_cico as $keys) {
                $list = [];
                $list['id_cico'] = $keys->id;
                $list['cico_date'] = $keys->date;
                $list['check_in'] = $keys->check_in;
                $list['lat_in'] = $keys->lat_in;
                $list['long_in'] = $keys->long_in;
                $list['start_time'] = $keys->start_time;
                $list['check_out'] = $keys->check_out;
                $list['lat_out'] = $keys->lat_out;
                $list['long_out'] = $keys->long_out;
                $list['end_time'] = $keys->end_time;
                $list['team_name'] = $keys->user;



                $sql3 = "SELECT COUNT(pp.file_path) AS file_count FROM 0_project_progress_photos pp
                     WHERE pp.project_task_id = $keys->project_task_id AND pp.created_by = $keys->user_id";

                $file = DB::table('0_project_progress_photos')
                    ->where('project_task_id', $keys->project_task_id)
                    ->where('created_by', '<=', $keys->user_id)
                    ->get();
                $count_file = $file->count();
                $list['file_count'] = $count_file;

                if (empty($keys->id)) {
                    $tmp['history_report'][] = [];
                } else {
                    $tmp['history_report'][] = $list;
                }
            }

            $tmp['creator'] = $data->creator;
            $tmp['created_at'] = date('H:i:s d F Y', strtotime($data->created_at));

            array_push($response, $tmp);
        }
        return $response;
    }

    public static function view_task_tree_by_project($project_no)
    {
        $response = [];
        $sql = QueryProjectTask::view_task_tree_by_project($project_no);
        $response = DB::select(DB::raw($sql));
        return $response;
    }

    public static function create_new_task($myArr)
    {
        $params = $myArr['params'];

        // $parent_task = DB::table('')
        $project_info = ProjectListController::get_project_info($params['project_code']);
        $project_no = $project_info->project_no;
        $project_name = $project_info->name;
        $division_name =  ProjectListController::get_project_division_name($project_info->division_id);
        $title = $params['title'];
        $date_now = date('Y-m-d', strtotime($params['plan_start']));
        $lat = "-";
        $long = "-";
        $remark = $params['description'];
        $qty = $params['qty'];
        $uom = $params['uom'];
        $user_id = $myArr['user_id'];
        $creator_name = $myArr['user_name'];
        $creator_nik = $myArr['user_emp_id'];
        $pm_emp_id = UserController::get_emp_id_pm($params['project_code']);
        $creator_position = UserController::get_position_user($pm_emp_id);

        $transdate = date('Y-m-d', time());
        $date = date('d', strtotime($transdate));
        $month = date('m', strtotime($transdate));
        $year = date('Y', strtotime($transdate));
        $get_roman = ProjectDailyPlanController::numberToRomanRepresentation($month);
        $times = date("His");

        $task_id = QueryProjectTask::get_latest_project_task_id();
        $project_task_id = $task_id + 1;
        DB::beginTransaction();
        try {
            DB::table('0_project_task')
                ->insert(array(
                    'du_id' => $params['du_id'],
                    'project_no' => $project_no,
                    'site_no' => $params['site_no'],
                    'title' => $title,
                    'date' => $date_now,
                    'lat' => $lat,
                    'long' => $long,
                    'remark' => $remark,
                    'qty' => $qty,
                    'uom' => $uom,
                    'created_at' => Carbon::now(),
                    'created_by' => $user_id
                ));
            $employee_id = explode(',', $params['emp_id']);
            foreach ($employee_id as $surtug) {

                $emp_info = DB::table('0_hrm_employees')->where('emp_id', $surtug)
                    ->first();
                $emp_id = $emp_info->emp_id;
                $emp_name = $emp_info->name;
                $get_emp_phone = UserController::get_user_phone_number($emp_id);
                $reference = "$date-$times/ATE/" . $division_name . "/$get_roman/$year";
                $daily_task = (!empty($params['daily_task'])) ? $params['daily_task'] : '-';
                $address = (!empty($params['address'])) ? $params['address'] : '-';

                DB::table('0_project_daily_plan')
                    ->insert(array(
                        'type' => $params['type'],
                        'task_id' => $project_task_id,
                        'reference' => $reference,
                        'creator_nik' => $creator_nik,
                        'division' => $division_name,
                        'creator_position' => $creator_position,
                        'emp_name' => $emp_name,
                        'emp_id' => $emp_id,
                        'phone_number' => $get_emp_phone,
                        'emp_position' => '-',
                        'daily_task' => $daily_task,
                        'project_name' => $project_name,
                        'project_code' => $params['project_code'],
                        'plan_start' => $params['plan_start'],
                        'plan_end' => $params['plan_end'],
                        'du_id' => $params['du_id'],
                        'site_id' => QueryProjectTask::get_site_for_create_task($params['site_no']),
                        'sow' => $params['sow'],
                        'address' => $address,
                        'remark' => $remark,
                        'created_at' => Carbon::now(),
                        'created_by' => $creator_name
                    ));
            }
            $project_task_id = DB::table('0_project_task')
                ->latest()
                ->first();

            foreach ($employee_id as $emp_task) {
                $emp_info = DB::table('0_hrm_employees')->where('emp_id', $emp_task)
                    ->first();
                $emp_no = $emp_info->id;

                DB::table('0_project_task_employees')
                    ->insert(array(
                        'project_task_id' => $project_task_id->id,
                        'emp_no' => $emp_no,
                        'created' => Carbon::now()
                    ));
            }
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

    public static function task_assignee_user($myArr)
    {
        $params = $myArr['params'];

        foreach ($params['assignee'] as $values) {

            DB::beginTransaction();
            try {
                DB::table('0_project_task_employees')
                    ->insert(array(
                        'project_task_id' => $values['task_id'],
                        'emp_no' => $values['emp_no'],
                        'created_by' => $myArr['user_id']
                    ));
                // Commit Transaction
                DB::commit();
            } catch (Exception $e) {
                // Rollback Transaction
                DB::rollback();
            }
        }
        return response()->json([
            'success' => true
        ], 200);
    }

    public static function project_task($user_id, $user_emp_no, $user_person_id)
    {
        $response = [];
        $sql = QueryProjectTask::project_task_head($user_emp_no);
        $projects = DB::select(DB::raw($sql));
        foreach ($projects as $data) {
            $url = URL::to("/storage/qr-code/$data->qrcode.png");

            $is_ti_non_wireless = ($data->division_id == 24) ? 1 : 0;
            $tmp = [];
            $tmp['id'] = $data->id;
            $tmp['project_task_id'] = $data->project_task_id;
            $du_id = explode(',', $data->du_id);
            $tmp['du_id'] = $du_id;
            $site = QueryProjectTask::get_site_for_cico($data->site_no);
            $tmp['site'] = $site;
            $tmp['plan_date'] = $data->plan_date;
            $tmp['sow'] = $data->sow;
            $tmp['title'] = $data->title;
            $tmp['project_no'] = $data->project_no;
            $tmp['project_code'] = $data->project_code;
            $tmp['area'] = $data->area;
            $tmp['customer'] = $data->customer;
            $tmp['qty_plan'] = $data->qty_task;
            $tmp['qty_actual'] = QueryProjectTask::get_qty_actual($data->project_task_id);
            $tmp['uom'] = $data->uom_task;
            $tmp['remark'] = $data->remark;
            $tmp['status'] = $data->status;
            $tmp['surtug_id'] = $data->surtug_id;
            $tmp['surtug_doc_no'] = $data->surtug_doc_no;
            $tmp['surtug_qr'] = $url;


            $sql1 = QueryProjectTask::project_task_body_1($data->project_task_id);

            $get_team = DB::select(DB::raw($sql1));

            foreach ($get_team as $key) {
                $items = [];
                $items['name'] = $key->name;
                $tmp['teams'][] = $items;
            }

            $sql2 = QueryProjectTask::project_task_body_2($data->project_task_id, $user_id);
            $get_cico = DB::select(DB::raw($sql2));

            foreach ($get_cico as $keys) {
                $list = [];
                $list['id_cico'] = $keys->id;
                $list['cico_date'] = $keys->date;
                $list['check_in'] = $keys->check_in;
                $list['lat_in'] = $keys->lat_in;
                $list['long_in'] = $keys->long_in;
                $list['start_time'] = $keys->start_time;
                $list['check_out'] = $keys->check_out;
                $list['lat_out'] = $keys->lat_out;
                $list['long_out'] = $keys->long_out;
                $list['end_time'] = $keys->end_time;
                $list['team_name'] = $keys->user;



                $sql3 = "SELECT COUNT(pp.file_path) AS file_count FROM 0_project_progress_photos pp
                     WHERE pp.project_task_id = $keys->project_task_id AND pp.created_by = $keys->user_id";

                $file = DB::table('0_project_progress_photos')
                    ->join('0_project_task_progress', '0_project_progress_photos.progress_id', '=', '0_project_task_progress.id')
                    ->where('0_project_progress_photos.project_task_id', $keys->project_task_id)
                    ->where('0_project_progress_photos.created_by', '=', $keys->user_id)
                    ->where('0_project_task_progress.id_cico', '=', $keys->id)
                    ->get();
                $count_file = $file->count();
                $list['file_count'] = $count_file;
                $tmp['history_report'][] = $list;
            }
            $tmp['ti_non_wireless'] = $is_ti_non_wireless;

            array_push($response, $tmp);
        }
        return $response;
    }
    public static function project_task_detail($id, $user_id)
    { //$id = id cico
        $response = [];

        $sql = QueryProjectTask::project_task_detail_head($id);
        $cico = DB::select(DB::raw($sql));
        foreach ($cico as $data) {

            $path_image_in = $data->image_in;
            $path_image_out = $data->image_out;
            $url_image_in = URL::to("/storage/project_task/images/$path_image_in.jpg");
            $url_image_out = URL::to("/storage/project_task/images/$path_image_out.jpg");

            $tmp = [];
            $tmp['id_cico'] = $data->id;
            $tmp['date'] = $data->date;
            $tmp['start_time'] = $data->start_time;
            $tmp['status_in'] = $data->status_in;
            $tmp['image_in'] = $url_image_in;
            $tmp['end_time'] = $data->end_time;
            $tmp['status_out'] = $data->status_out;
            $tmp['image_out'] = $url_image_out;


            $sql1 = QueryProjectTask::project_task_detail_body($data->id, $user_id);
            $details = DB::select(DB::raw($sql1));

            foreach ($details as $key) {

                $site_no = ($key->site_no > 0) ? $key->site_no : 0;

                if ($site_no > 0) {
                    $get_site = InputList::site_list_row($site_no, '');
                    $data_site_id = json_decode(json_encode($get_site), true);
                    $collect_data = collect($data_site_id['original']['data'])
                        ->all();

                    $site_id =  $collect_data[0]['site_id'];
                } else {
                    $site_id = '';
                }

                $progress = DB::table('0_project_task_progress')
                    ->where('id_cico', $id)
                    ->sum('qty');
                $total_progress = ($progress * 100) / $key->task_qty;
                $items = [];
                $items['id_progress'] = $key->id;
                $items['site_id'] = $site_id;
                $items['description'] = $key->description;
                $items['remark_ext'] = $key->ext_description;
                $items['sow'] = $key->sow;
                $items['du_id'] = $key->du_id;
                $items['qty'] = $key->qty;
                $items['created_at'] = $key->created_at;

                $sql2 = "SELECT pp.file_path
                         FROM 0_project_progress_photos pp
                         WHERE pp.progress_id = $key->id
                         GROUP BY pp.file_path";
                $get_image = DB::select(DB::raw($sql2));

                foreach ($get_image as $item) {

                    $path = $item->file_path;
                    $url = URL::to("/storage/project_task/images/$path.jpg");

                    $list = [];
                    $list['photo'] = $url;
                    $items['file_path'][] = $list;
                }

                $fat_info = QueryProjectTask::get_fat_info($key->id);
                $fat_id = (!empty($fat_info[0]->id)) ? $fat_info[0]->id : 0;
                $fat_details_before = DB::select(DB::raw(QueryProjectTask::get_fat_details($fat_id, 0)));
                $fat_details_after = DB::select(DB::raw(QueryProjectTask::get_fat_details($fat_id, 1)));
                $type_fat_fdt = DB::table('0_project_task_fat_details')->where('fat_id', $fat_id)->first();
                if ($type_fat_fdt != null || !empty($type_fat_fdt)) {
                    $items['type'] = mb_substr($type_fat_fdt->photo1, 0, 3);
                } else {
                    $items['type'] = null;
                }
                $items['info'] = (!empty($fat_info)) ? $fat_info : null;
                $items['details']['before'] = $fat_details_before;
                $items['details']['after'] = $fat_details_after;

                $tmp['progress'] = $total_progress . "%";
                $tmp['detail_reports'][] = $items;
            }

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function project_task_check_in(
        $id,
        $user_id,
        $site_no,
        $latitude,
        $longitude,
        $photo
    ) {  //$id = project_task_id

        $timenow = date('Y-m-d');

        $sql = "SELECT ptc.id, ptc.project_task_id FROM 0_project_task_cico ptc
                WHERE ptc.project_task_id = $id AND user_id = $user_id AND DATE(ptc.date) = DATE('$timenow') ORDER BY ptc.id DESC LIMIT 1";

        $validation_check_in = DB::select(DB::raw($sql));

        if ($validation_check_in == null) {
            $clock_late_ti = '10:01';
            $clock_now = date('H:i');

            $sql_ti_no = "SELECT p.division_id FROM 0_project_task pt
                      LEFT OUTER JOIN 0_projects p ON (pt.project_no = p.project_no)
                      WHERE pt.id = $id LIMIT 1";
            $division_of_this_project = SiteHelper::collectSingleJsonValue(DB::select(DB::raw($sql_ti_no)), 'division_id');
            if ($division_of_this_project == 24) {
                if ($clock_now >= $clock_late_ti) {
                    return SiteHelper::error_msg(403, 'Maksimal dibawah jam 09:00 untuk melakukan check in !');
                } else {
                    if ($checkin = $photo) {
                        $person_id = self::get_person_id_task($id);
                        $filename = "CI" . $user_id . date('Ymd') . rand(1, 9999999999);
                        $destination = public_path("/storage/project_task/images");
                        $checkin->move($destination, $filename . ".jpg");

                        DB::table('0_project_task_cico')->insert(array(
                            'project_task_id' => $id,
                            'site_no' => $site_no,
                            'person_id' => $person_id,
                            'type' => 1,
                            'attendance_id' => 12,
                            'date' => date('Y-m-d'),
                            'start_time' => date('H:i:s'),
                            'check_in' => 1,
                            'lat_in' => $latitude,
                            'long_in' => $longitude,
                            'image_in' => $filename,
                            'user_id' => $user_id
                        ));

                        DB::table('0_project_task')->where('id', $id)
                            ->update(array(
                                'status' => 10,
                                'cico_status' => 1,
                                'updated_at' => Carbon::now()
                            ));

                        return response()->json([
                            'success' => true
                        ], 200);
                    }
                }
            } else {
                if ($checkin = $photo) {
                    $person_id = self::get_person_id_task($id);
                    $filename = "CI" . $user_id . date('Ymd') . rand(1, 9999999999);
                    $destination = public_path("/storage/project_task/images");
                    $checkin->move($destination, $filename . ".jpg");

                    DB::table('0_project_task_cico')->insert(array(
                        'project_task_id' => $id,
                        'site_no' => $site_no,
                        'person_id' => $person_id,
                        'type' => 1,
                        'attendance_id' => 12,
                        'date' => date('Y-m-d'),
                        'start_time' => date('H:i:s'),
                        'check_in' => 1,
                        'lat_in' => $latitude,
                        'long_in' => $longitude,
                        'image_in' => $filename,
                        'user_id' => $user_id
                    ));

                    DB::table('0_project_task')->where('id', $id)
                        ->update(array(
                            'status' => 10,
                            'cico_status' => 1,
                            'updated_at' => Carbon::now()
                        ));

                    return response()->json([
                        'success' => true
                    ], 200);
                }
            }
        } else {
            throw new CheckInValidationHttpException();
        }
    }

    public static function project_task_check_out(
        $id,
        $user_id,
        $latitude,
        $longitude,
        $photo
    ) {  //$id = cico_id
        $time = date('H:i:s');
        $clock_home_ti = '17:00';
        $clock_now = date('H:i');

        $sql_cico = "SELECT ptc.project_task_id FROM 0_project_task_cico ptc
                         WHERE ptc.id = $id ORDER BY ptc.id DESC LIMIT 1";
        $cico_info = SiteHelper::collectSingleJsonValue(DB::select(DB::raw($sql_cico)), 'project_task_id');

        $sql_ti_no = "SELECT p.division_id FROM 0_project_task pt
                      LEFT OUTER JOIN 0_projects p ON (pt.project_no = p.project_no)
                      WHERE pt.id = $cico_info LIMIT 1";
        $division_of_this_project = SiteHelper::collectSingleJsonValue(DB::select(DB::raw($sql_ti_no)), 'division_id');

        if ($division_of_this_project == 24) {
            if ($clock_now <= $clock_home_ti) {
                return SiteHelper::error_msg(403, 'Minimal jam 17:00 untuk melakukan check out !');
            } else {
                if ($photo) {
                    $filename = "CO" . $user_id . date('Ymd') . rand(1, 9999999999);
                    $destination = public_path("/storage/project_task/images");
                    $photo->move($destination, $filename . ".jpg");

                    DB::table('0_project_task_cico')->where('id', $id)
                        ->update(array(
                            'check_out' => 1,
                            'lat_out' => $latitude,
                            'long_out' => $longitude,
                            'image_out' => $filename,
                            'end_time' => $time
                        ));
                }
            }
        } else {
            if ($photo) {
                $filename = "CO" . $user_id . date('Ymd') . rand(1, 9999999999);
                $destination = public_path("/storage/project_task/images");
                $photo->move($destination, $filename . ".jpg");

                DB::table('0_project_task_cico')->where('id', $id)
                    ->update(array(
                        'check_out' => 1,
                        'lat_out' => $latitude,
                        'long_out' => $longitude,
                        'image_out' => $filename,
                        'end_time' => $time
                    ));
            }
        }

        return response()->json([
            'success' => true
        ], 200);
    }

    public static function show_project_du_id($myArr)
    {
        $params = $myArr['params'];
        if (empty($params['du_id'])) {
            $du_id = 0;
        } else {
            $du_id = $params['du_id'];
        }

        $response = [];

        $sql = QueryProjectTask::show_du_id($du_id);
        $query = DB::select(DB::raw($sql));

        foreach ($query as $data) {

            $tmp = [];
            $tmp['du_id'] = $data->du_id;

            $sql1 = QueryProjectTask::show_du_id_site($data->site_no);
            $query1 = DB::select(DB::raw($sql1));

            foreach ($query1 as $lists) {

                $list = [];
                $list['site_no'] = $lists->site_no;
                $list['site_id'] = $lists->site_id;
                $list['site_name'] = $lists->name;


                $tmp['site'][] = $list;

                // $list['site_id'] = $data->site_id;
            }

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    public static function create_du_id($myArr)
    {
        $params = $myArr['params'];

        $user_id = $myArr['user_id'];
        $response = [];

        DB::beginTransaction();
        try {
            DB::table('0_project_du_id')
                ->insert(array(
                    'du_id' => $params['du_id'],
                    'site_no' => $params['site_no'],
                    'created_by' => $user_id,
                    'created_at' => Carbon::now(),
                    'updated_by' => $user_id,
                    'updated_at' => Carbon::now()

                ));
            // Commit Transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $response
            ], 200);
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }

    public static function show_attendance_employee($myArr)
    {
        $params = $myArr['params'];

        $task_id = $params['task_id'];
        $emp_id = $params['emp_id'];
        $from = $params['from'];
        $to = $params['to'];

        $response = [];

        $begin = new DateTime("$from");
        $end   = new DateTime("$to");

        for ($i = $begin; $i <= $end; $i->modify('+1 day')) {
            $date = $i->format("Y-m-d");
            $tmp = [];
            $tmp['date'] = $date;
            $sql = QueryProjectTask::task_attendance($task_id, $emp_id, $date);
            $query = DB::select(DB::raw($sql));
            foreach ($query as $data) {

                if (!empty($data->id)) {
                    $tmp['attendance'] = 1;
                }
            }

            array_push($response, $tmp);
        }
        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }


    public static function get_person_id_task($id)
    {
        $get_person_id = DB::table('0_project_task')->where('0_project_task.id', $id)
            ->join('0_projects', '0_projects.project_no', '=', '0_project_task.project_no')
            ->select('0_projects.person_id')->get();

        $data = response()->json([
            'data' => $get_person_id
        ]);

        $person_id = collect(json_decode(json_encode($data), true)['original']['data'])
            ->all();

        return $person_id[0]['person_id'];
    }

    public static function delete_task($task_id)
    {
        DB::beginTransaction();

        try {
            DB::table('0_project_task')->where('id', $task_id)->update(array(
                'deleted' => 1,
                'deleted_by' => Auth::guard()->user()->id,
                'deleted_at' => Carbon::now()
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

    public static function get_fat_by_site($myArr)
    {
        $params = $myArr['params'];

        $site_no = $params['site_no'];
        $response = [];

        $sql = QueryProjectTask::get_fat_by_site($site_no);
        $query = DB::select(DB::raw($sql));

        foreach ($query as $data) {

            $tmp = [];
            $tmp['site_id'] = $data->site_id;
            $tmp['site_name'] = $data->name;
            $tmp['fat_id'] = $data->fat_id;
            $tmp['fat_no'] = $data->fat_no;
            $tmp['fat_photo'] = URL::to("/storage/project_task/images/$data->fat_photo" . ".jpg");
            $tmp['latitude'] = $data->lat;
            $tmp['longitude'] = $data->long;

            $sql1 = QueryProjectTask::get_fat_by_site_details($data->id);
            foreach ($sql1 as $lists) {

                $list = [];
                $list['port'] = $lists->port;
                $list['lamda1'] = $lists->lamda1;
                $list['lamda2'] = $lists->lamda2;
                $list['photo1'] = URL::to("/storage/project_task/images/$lists->photo1" . ".jpg");
                $list['photo2'] = URL::to("/storage/project_task/images/$lists->photo2" . ".jpg");
                $tmp['details'][] = $list;
            }

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }
}
