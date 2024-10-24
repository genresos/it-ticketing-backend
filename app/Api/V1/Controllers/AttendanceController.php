<?php

namespace App\Api\V1\Controllers;

use JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Dingo\Api\Routing\Helpers;
use Carbon\Carbon;
use Validator, Redirect, Response, File;
use Auth;
use URL;
use App\Image;
use App\Query\QueryAttendance;
use App\Modules\InputList;
use App\Modules\PaginationArr;
use Symfony\Component\HttpKernel\Exception\CheckInValidationHttpException;
use App\Http\Controllers\ProjectListController;
use App\Http\Controllers\UserController;
use App\Exports\AttendanceExport;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    //
    use Helpers;

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
    //==================================================================== VIEW USER ATTENDANCE =============================================================\\
    public function view(Request $request)
    {
        $response = [];

        if (empty($request->emp_no)) {
            $emp_no = 0;
        } else {
            $emp_no = $request->emp_no;
        }
        if (empty($request->type)) {
            $type = 0;
        } else {
            $type = $request->type;
        }
        if (empty($request->division)) {
            $division = '';
        } else {
            $division = $request->division;
        }
        $sql = QueryAttendance::index(
            $emp_no,
            $type,
            $request->from_date,
            $request->to_date,
            $division
        );

        $attendance = DB::select(DB::raw($sql));
        foreach ($attendance as $data) {
            $url_image_in = URL::to("/storage/project_task/images/$data->image_in.jpg");
            $absence_leave_duration = DB::table('0_absence_leave')->where('absence_id', $data->id)->orderBy('id', 'desc')->first();

            $url_image_out = URL::to("/storage/project_task/images/$data->image_out.jpg");
            $attachment = URL::to("/storage/project_task/images/$data->attachment");

            $tmp = [];
            $tmp['id'] = $data->id;
            $tmp['emp_no'] = $data->emp_no;
            $tmp['division'] = $data->division_name;
            $tmp['emp_id'] = $data->emp_id;
            $tmp['emp_name'] = $data->emp_name;

            $tmp['attendance_type'] = $data->attendance_type;
            $tmp['attendance_type_code'] = $data->code;
            $tmp['date'] = $data->date;
            $tmp['start_time'] = $data->start_time;

            $tmp['site_id'] = $data->site_id;
            $tmp['lat_site'] = $data->lat_site;
            $tmp['long_site'] = $data->long_site;

            $tmp['lat_in'] = $data->lat_in;
            $tmp['long_in'] = $data->long_in;
            $tmp['image_in'] = $url_image_in;

            $tmp['end_time'] = $data->end_time;
            $tmp['lat_out'] = $data->lat_out;
            $tmp['long_out'] = $data->long_out;
            $tmp['image_out'] = $url_image_out;
            $tmp['attachment'] = $attachment;

            $tmp['remark'] = $data->remark;

            $tmp['start_date'] = empty($absence_leave_duration->start_date) ? null : $absence_leave_duration->start_date;
            $tmp['end_date'] = empty($absence_leave_duration->end_date) ? null : $absence_leave_duration->end_date;


            $tmp['status'] = $data->status;


            array_push($response, $tmp);
        }

        $myArray = $response;
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

        // $time_in = Carbon::parse($key->time_in)->format('c');
        // $time_out = Carbon::parse($key->time_out)->format('c');


    }


    public function need_approval_attendance(Request $request)
    {
        $response = [];

        if (empty($request->page)) {
            $page = 1;
        } else {
            $page = $request->page;
        }

        if (empty($request->perpage)) {
            $perPage = 10;
        } else {
            $perPage = $request->perpage;
        }
        $sql = QueryAttendance::need_approval($this->old_id, $this->user_level, $this->user_person_id, $page, $perPage);

        $count = DB::select(DB::raw($sql["count"]));
        $approval = DB::select(DB::raw($sql["list"]));

        foreach ($approval as $data) {

            $url_image_in = URL::to("/storage/project_task/images/$data->image_in.jpg");

            $tmp = [];
            $tmp['id'] = $data->id;
            $tmp['attendance_type'] = $data->attendance_type;
            $tmp['emp_name'] = $data->name;
            $tmp['emp_id'] = $data->emp_id;
            $tmp['date'] = $data->date;
            $tmp['time'] = $data->start_time;
            $tmp['latitude'] = $data->lat_in;
            $tmp['longitude'] = $data->long_in;
            $tmp['photo'] = $url_image_in;

            $absence_leave_duration = DB::table('0_absence_leave')->where('absence_id', $data->id)->orderBy('id', 'desc')->first();

            $tmp['start_date'] = empty($absence_leave_duration->start_date) ? null : $absence_leave_duration->start_date;
            $tmp['end_date'] = empty($absence_leave_duration->end_date) ? null : $absence_leave_duration->end_date;

            array_push($response, $tmp);
        }

        $myArray = $response;
        $myUrl = $request->url();
        $query = $request->query();
        $grandTotal = $count[0]->grand_total;

        return PaginationArr::arr_new_pagination(
            $myArray,
            $grandTotal,
            $page,
            $perPage,
            $myUrl,
            $query
        );
    }


    //==================================================================== LIST ATTENDANCE ZONE =============================================================\\
    public function list_zone()
    {
        $currentUser = JWTAuth::parseToken()->authenticate();
        $level = Auth::guard()->user()->approval_level;
        $session = Auth::guard()->user()->id;
        $response = [];
        $data1 = "SELECT GROUP_CONCAT(id SEPARATOR ',') FROM users WHERE approval_level IN (111,999)";
        if ($level == 111 || $level == 999) {
            $sql = "SELECT * FROM attendance_zone WHERE inactive = 0 AND zone_level = 2";
        } else if ($level != 111 || $level != 999) {
            $sql = "SELECT * FROM attendance_zone WHERE inactive = 0 AND created_by IN ($data1) OR created_by IN ($session)";
        }
        $data = DB::select(DB::raw($sql));

        foreach ($data as $key) {
            $item = [];
            $item['id'] = $key->id;
            $item['zone_level'] = $key->zone_level;
            $item['name_zone'] = $key->name;
            $item['lattitude'] = $key->lat;
            $item['longitude'] = $key->lng;
            $response['zone'][] = $item;

            array_push($response);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    //==================================================================== ADD ATTENDANCE ZONE =============================================================\\
    public function add_zone(Request $request)
    {
        $currentUser = JWTAuth::parseToken()->authenticate();
        $level = Auth::guard()->user()->approval_level;
        $session = Auth::guard()->user()->id;

        if ($level == 111 || $level == 999) {
            DB::table('attendance_zone')->insert(array(
                'name' => $request->name,
                'zone_level' => 2,
                'lat' => $request->latitude,
                'lng' => $request->longtitude,
                'created_by' => $request->$session
            ));
        }
        if ($level == 111 || $level == 999) {
            DB::table('attendance_zone')->insert(array(
                'name' => $request->name,
                'zone_level' => 1,
                'lat' => $request->latitude,
                'lng' => $request->longtitude,
                'created_by' => $request->$session
            ));
        }

        return response()->json([
            'success' => true,
        ], 200);
    }

    //===================================================================== NOC CHECK =============================================================\\
    
    public function is_user_noc($emp_id) {
 
        $sql = "SELECT CASE
            WHEN (boq IS NOT NULL) OR (boq != '') THEN 'NOC'
            ELSE 'NOT NOC'
        END as is_noc FROM 0_hrm_employees WHERE emp_id = '$emp_id'";

        $data = DB::select(DB::raw($sql));

        foreach ($data as $val) {

            $header = [];
            $header['is_users'] = $val->is_noc;

            $response = $header;
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }
    
    //==================================================================== ATTENDANCE IN =============================================================\\
    public function attendance_in(Request $request)
    {
        $id = 0;
        $user_id = Auth::guard()->user()->id;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $photo = $request->file('photo');
        $attachment = $request->file('attachment');
        $attendance_id = $request->attendance_id;
        $remark = $request->remark;
        $site_no = $request->site_no;
        $project_no = $request->project_no;
        // $get_person_id = DB::table('0_projects')->where('project_no', $project_no)->first();
        $person_id = $request->person_id;

        if ($person_id == "" && !isset($person_id)) {
            return response()->json(['error' => [
                'message' => 'Person ID not found',
                'status_code' => 403,
            ]], 403);
        }

        $timenow = Carbon::now();

        $arr_cuti = array(
            2, 3, 4, 5, 6, 7, 8, 13, 19, 20, 21, 22
        );
        $sql = "SELECT ptc.id FROM 0_project_task_cico ptc
                WHERE ptc.project_task_id = $id AND user_id = $user_id AND DATE(ptc.date) = DATE('$timenow') ORDER BY ptc.id DESC LIMIT 1";

        $validation_check_in = DB::select(DB::raw($sql));

        if ($validation_check_in == null) {
            if ($checkin = $photo) {

               if (!is_null($attachment) && $attachment->isValid()) {
                    $fileSize = $attachment->getSize();
                    // Maksimum ukuran file yang diizinkan (25 MB)
                    $maxFileSize = 25 * 1024 * 1024; // 25 MB dalam bytes
                    $inMegaByte = $maxFileSize / 1024 / 1024;
                    // Memeriksa apakah ukuran file melebihi batas maksimum
                    if ($fileSize > $maxFileSize) {
                        return response()->json([
                        'error' => array(
                            'message' => "Maksimal File attachment $inMegaByte MB.",
                            'status_code' => 403
                        )
                    ], 403);
                    }
                    $file_attachment_name = $attachment->getClientOriginalName();
                    $destination = public_path("/storage/project_task/images");
                    $attachment->move($destination, $file_attachment_name);
                } else {
                    $file_attachment_name = '';
                }

                $filename = "CI" . $user_id . date('Ymd') . rand(1, 9999999999);
                $destination = public_path("/storage/project_task/images");
                $checkin->move($destination, $filename . ".jpg");

                DB::beginTransaction();
                try {
                    if (in_array(
                        $attendance_id,
                        $arr_cuti
                    )) {
                        $header = DB::table('0_project_task_cico')->insertGetId(array(
                            'project_task_id' => $id,
                            'site_no' => $site_no,
                            'type' => 2,
                            'attendance_id' => $attendance_id,
                            'person_id' => $person_id,
                            'date' => date('Y-m-d'),
                            'start_time' => date('H:i:s'),
                            'check_in' => 1,
                            'lat_in' => $latitude,
                            'long_in' => $longitude,
                            'image_in' => $filename,
                            'remark' => $remark,
                            'attachment' => $file_attachment_name,
                            'status' => 0,
                            'check_out' => 1,
                            'user_id' => $user_id
                        ));

                        DB::table('0_absence_leave')->insertGetId(array(
                            'absence_id' => $header,
                            'start_date' => $request->start_date,
                            'end_date' => $request->end_date
                        ));
                    } else {
                        $header = DB::table('0_project_task_cico')->insertGetId(array(
                            'project_task_id' => $id,
                            'site_no' => $site_no,
                            'type' => 2,
                            'attendance_id' => $attendance_id,
                            'person_id' => $person_id,
                            'date' => date('Y-m-d'),
                            'start_time' => date('H:i:s'),
                            'check_in' => 1,
                            'lat_in' => $latitude,
                            'long_in' => $longitude,
                            'image_in' => $filename,
                            'remark' => $remark,
                            'attachment' => $file_attachment_name,
                            'status' => 0,
                            'user_id' => $user_id
                        ));

                        $detail = DB::table('0_project_task_cico')->where('id', $header)->first();

                         // $for_noc = array(
                        //     25, // OFC NOC
                        //     26, // ONSITE NOC
                        //     27 // WFH NOC
                        // );

                        // if (in_array(
                        //     $detail->attendance_id,
                        //     $for_noc
                        // )) {
                        //     DB::table('0_project_task_cico')->where('id', $header)->update(array(
                        //         'person_id' => 204,
                        //         'check_out' => 0,
                        //         'status' => 1,
                        //         'approved_by' => 1498
                        //     ));
                        // }
                    }


                    DB::commit();

                    return response()->json([
                        'success' => true
                    ]);
                } catch (Exception $e) {
                    // Rollback Transaction
                    DB::rollback();
                }
            }
        } else {
            throw new CheckInValidationHttpException();
        }
    }

    //==================================================================== ATTENDANCE OUT =============================================================\\
    public function attendance_out(Request $request, $id)
    {

        $user_id = Auth::guard()->user()->id;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $photo = $request->file('photo');
        $time = date('H:i:s');

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

        return response()->json([
            'success' => true
        ], 200);
    }


    public function attendance_type_list(Request $request)
    {

        if (!empty($request->attendance_id)) {
            $attendance_id = $request->attendance_id;
        } else {
            $attendance_id = 0;
        }
        $myArray = InputList::attendance_type(
            $attendance_id
        );
        $myPage = $request->page;
        $myUrl = $request->url();
        $query = $request->query();

        return $myArray;
    }

    public function approve_attendance(Request $request)
    {
        $cico_id = $request->cico_id;
        $get_co_time = DB::table('0_project_task_cico')->where('id', $cico_id)->first();

        $check_out_if_null = ($get_co_time->end_time == '00:00:00') ? '17:30:00' : $get_co_time->end_time;
        DB::beginTransaction();

        try {

            DB::table('0_project_task_cico')->where('id', $cico_id)
                ->update(array(
                    'status' => 1,
                    'end_time' => $check_out_if_null,
                    'check_out' => 1,
                    'approved_by' => $this->user_id,
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

    public function disapprove_attendance(Request $request)
    {
        $cico_id = $request->cico_id;

        DB::beginTransaction();

        try {

            DB::table('0_project_task_cico')->where('id', $cico_id)
                ->update(array(
                    'status' => 2,
                    'approved_by' => $this->user_id,
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

    public function sync_to_devosa(Request $request)
    {
        $user_creator_devosa = UserController::get_info_user_devosa($this->user_emp_id);
        $attendance_id = "$request->attendance_id";
        $request_date = date('Y-m-d');

        $sql = "SELECT * FROM 0_project_task_cico
                WHERE id IN ('$attendance_id') AND status = 1";

        $exec = DB::select(DB::raw($sql));

        foreach ($exec as $data) {

            $date_from = $data->date;
            $date_thru = $data->date;

            $user_info = UserController::get_user_info($data->user_id);
            $emp_id_atasan = UserController::get_emp_id_by_person_id($data->person_id);
            if (empty($emp_id_atasan)) {
                return response()->json([
                    'error' => array(
                        'message' => "PM tidak ditemukan!",
                        'status' => 403
                    )
                ], 403);
            }

            $devosa_atasan = UserController::get_info_user_devosa($emp_id_atasan);

            if (empty($devosa_atasan->id_adm_user)) {
                return response()->json([
                    'error' => array(
                        'message' => "PM belum mempunyai akun Devosa!",
                        'status' => 403
                    )
                ], 403);
            }

            $id_employee = $user_info->emp_no;

            $get_attendance_type_code = DB::table('0_attendance_type')
                ->where('id', $data->attendance_id)
                ->first();

            $absence_type_code = $get_attendance_type_code->code;
            $leave_duration = ($absence_type_code == 'CT') ? 1 : 0;

            $note = $data->remark;

            // KONDISI KALAU DATA SUDAH ADA / GENERATE ALPHA BY SYSTEM DEVOSA
            $existing_data = DB::connection('pgsql')->table('hrd_absence')->where('id_employee', $id_employee)->where('date_from', $date_from)->first();
            $absence_devosa_existing = (!empty($existing_data)) ? 1 : 0;

            switch ($absence_devosa_existing) {
                case 0:
                    DB::beginTransaction();
                    try {

                        DB::table('0_project_task_cico')->where('id', $data->id)
                            ->update(array(
                                'already_sync' => 1
                            ));
                        DB::connection('pgsql')->table('hrd_absence')
                            ->insert(array(
                                'id_employee' => $id_employee,
                                'request_date' => $request_date,
                                'date_from' => $date_from,
                                'date_thru' => $date_thru,
                                'absence_type_code' => $absence_type_code,
                                'duration' => 1,
                                'leave_duration' => $leave_duration,
                                'note' => $note,
                                'status' => 2,
                                'approved_by' => $devosa_atasan->id_adm_user,
                                'approved_time' => Carbon::now(),
                                'created_by' => $user_creator_devosa->id_adm_user,
                                'modified_by' => $user_creator_devosa->id_adm_user
                            ));

                        $get_id_absence_latest = DB::connection('pgsql')->table('hrd_absence')
                            ->where('id_employee', $id_employee)->where('date_from', $date_from)->first();

                        $id_absence = $get_id_absence_latest->id;


                        DB::connection('pgsql')->table('hrd_absence_detail')
                            ->insert(array(
                                'id_absence' => $id_absence,
                                'id_employee' => $id_employee,
                                'absence_date' => $date_from,
                                'absence_type' => $absence_type_code,
                                'created_by' => $user_creator_devosa->id_adm_user,
                                'modified_by' => $user_creator_devosa->id_adm_user
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
                    break;
                case 1:
                    DB::beginTransaction();
                    try {

                        DB::table('0_project_task_cico')->where('id', $data->id)
                            ->update(array(
                                'already_sync' => 1
                            ));
                        DB::connection('pgsql')->table('hrd_absence')->where('id', $existing_data->id)
                            ->update(array(
                                'id_employee' => $id_employee,
                                'request_date' => $request_date,
                                'date_from' => $date_from,
                                'date_thru' => $date_thru,
                                'absence_type_code' => $absence_type_code,
                                'duration' => 1,
                                'leave_duration' => $leave_duration,
                                'note' => $note,
                                'status' => 2,
                                'approved_by' => $devosa_atasan->id_adm_user,
                                'approved_time' => Carbon::now(),
                                'created_by' => $user_creator_devosa->id_adm_user,
                                'modified_by' => $user_creator_devosa->id_adm_user
                            ));

                        DB::connection('pgsql')->table('hrd_absence_detail')->where('id_absence', $existing_data->id)
                            ->update(array(
                                'id_employee' => $id_employee,
                                'absence_date' => $date_from,
                                'absence_type' => $absence_type_code,
                                'created_by' => $user_creator_devosa->id_adm_user,
                                'modified_by' => $user_creator_devosa->id_adm_user
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
                    break;
            }
        }
    }

    public static function delete_attendance(Request $request)
    {
        $attendance_id = $request->id;
        $myString = "$attendance_id";
        $myArray = explode(',', $myString);
        DB::beginTransaction();

        try {

            DB::table('0_project_task_cico')->whereIn('id', $myArray)->delete();

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


    public static function export_attendance(Request $request)
    {
        if (empty($request->emp_no)) {
            $emp_no = 0;
        } else {
            $emp_no = $request->emp_no;
        }
        if (empty($request->type)) {
            $type = 0;
        } else {
            $type = $request->type;
        }
        if (empty($request->division)) {
            $division = '';
        } else {
            $division = $request->division;
        }
        $filename = 'Attendance';
        return Excel::download(new AttendanceExport(
            $request->from_date,
            $request->to_date,
            $emp_no,
            $type,
            $division
        ), "$filename.xlsx");
    }
}
