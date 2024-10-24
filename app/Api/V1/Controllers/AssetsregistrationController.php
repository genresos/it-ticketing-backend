<?php

namespace App\Api\V1\Controllers;

use JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Carbon\Carbon;
use Validator, Redirect, Response, File;
use Auth;
use URL;
use App\Image;
use App\Modules\PaginationArr;
use App\Exports\AssetRegistrationExport;
use Maatwebsite\Excel\Facades\Excel;

class AssetsregistrationController extends Controller
{
    //
    use Helpers;

    //==================================================================== ASSETS REGISTRATION NEED APPROVAL (PM) =============================================================\\
    public function needApproval(Request $request)
    {
        $currentUser = JWTAuth::parseToken()->authenticate();
        $session = Auth::guard()->user()->person_id;
        $level = Auth::guard()->user()->approval_level;
        $user_id = Auth::guard()->user()->id;
        $date = Carbon::now();
        $from_date = $request->from_date;
        $to_date = $request->to_date;

        $wherePeriode = "";

        if(empty($from_date) || empty($to_date)){
            $wherePeriode = " AND MONTH(ar.trx_date) = $date->month AND YEAR(ar.trx_date) = $date->year";
        } else {
            $wherePeriode = " AND (ar.trx_date BETWEEN '$from_date' AND '$to_date')";
        }

        $response = [];
        if ($level == 1 || $level == 3 || $level == 2 || $level == 4) {
            $sql = "SELECT ar.*, count(ar.file_path) as file_uploaded, pa.name AS area_name, e.name AS emp_name, e.emp_new_id AS emp_id,  GROUP_CONCAT('/storage/images/', ar.file_path, '.jpg' SEPARATOR ';') AS file_path FROM asset_registration ar
                LEFT OUTER JOIN 0_projects p ON (ar.project_code = p.code)
                LEFT JOIN 0_project_area pa ON (pa.area_id = p.area_id)
                LEFT OUTER JOIN users u ON (u.id = ar.user_id)
                LEFT OUTER JOIN 0_hrm_employees e ON (u.emp_no = e.id)
                WHERE ar.approval_status = 0 AND ar.approval_position = 1" . $wherePeriode . " AND p.person_id = $session
                GROUP BY ar.uniq_id ORDER BY ar.trx_date DESC";
        } else if ($level == 999) {
            $sql = "SELECT ar.*, count(ar.file_path) as file_uploaded, pa.name AS area_name, e.name AS emp_name, e.emp_new_id AS emp_id,  GROUP_CONCAT('/storage/images/', ar.file_path, '.jpg' SEPARATOR ';') AS file_path FROM asset_registration ar
            LEFT OUTER JOIN 0_projects p ON (ar.project_code = p.code)
                LEFT JOIN 0_project_area pa ON (pa.area_id = p.area_id)
            LEFT OUTER JOIN users u ON (u.id = ar.user_id)
            LEFT OUTER JOIN 0_hrm_employees e ON (u.emp_no = e.id)
            WHERE ar.approval_status < 2 AND ar.approval_position < 3" . $wherePeriode . " 
            GROUP BY ar.uniq_id ORDER BY ar.trx_date DESC";
        } else if ($level == 555 && in_array($user_id, [2415, 2584])) { // khusus mobil (ar.asset_type_id = 4)
            $sql = "SELECT ar.*, count(ar.file_path) as file_uploaded, pa.name AS area_name, e.name AS emp_name, e.emp_new_id AS emp_id,  GROUP_CONCAT('/storage/images/', ar.file_path, '.jpg' SEPARATOR ';') AS file_path FROM asset_registration ar
            LEFT OUTER JOIN 0_projects p ON (ar.project_code = p.code)
                LEFT JOIN 0_project_area pa ON (pa.area_id = p.area_id)
            LEFT OUTER JOIN users u ON (u.id = ar.user_id)
            LEFT OUTER JOIN 0_hrm_employees e ON (u.emp_no = e.id)
            WHERE ar.asset_type_id IN (3,4) AND ar.approval_status < 2 AND ar.approval_position <= 2" . $wherePeriode . "
            GROUP BY ar.uniq_id ORDER BY ar.trx_date DESC";
        } else if ($level == 555) {
            $sql = "SELECT ar.*, count(ar.file_path) as file_uploaded, pa.name AS area_name, e.name AS emp_name, e.emp_new_id AS emp_id,  GROUP_CONCAT('/storage/images/', ar.file_path, '.jpg' SEPARATOR ';') AS file_path FROM asset_registration ar
            LEFT OUTER JOIN 0_projects p ON (ar.project_code = p.code)
                LEFT JOIN 0_project_area pa ON (pa.area_id = p.area_id)
            LEFT OUTER JOIN users u ON (u.id = ar.user_id)
            LEFT OUTER JOIN 0_hrm_employees e ON (u.emp_no = e.id)
            WHERE ar.approval_status < 2 AND ar.approval_position = 2" . $wherePeriode . " 
            GROUP BY ar.uniq_id ORDER BY ar.trx_date DESC";
        } else if ($level == 52 || $level == 51 || $level == 43 || $level == 42 || $level == 41 || $level == 5 || $level == 4 || $level == 3 || $level == 0) {
            $sql = "SELECT ar.*, count(ar.file_path) as file_uploaded, pa.name AS area_name, e.name AS emp_name, e.emp_new_id AS emp_id,  GROUP_CONCAT('/storage/images/', ar.file_path, '.jpg' SEPARATOR ';') AS file_path FROM asset_registration ar
            LEFT OUTER JOIN 0_projects p ON (ar.project_code = p.code)
                LEFT JOIN 0_project_area pa ON (pa.area_id = p.area_id)
            LEFT OUTER JOIN users u ON (u.id = ar.user_id)
            LEFT OUTER JOIN 0_hrm_employees e ON (u.emp_no = e.id)
            WHERE ar.approval_status = 99
            GROUP BY ar.uniq_id ORDER BY ar.trx_date DESC";
        }
        $view_registered_asset = DB::select(DB::raw($sql));
        foreach ($view_registered_asset as $data) {

            $tmp = [];
            $tmp['uniq_id'] = $data->uniq_id;
            $tmp['emp_name'] = $data->emp_name;
            $tmp['emp_id'] = $data->emp_id;
            $tmp['asset_type_id'] = $data->asset_type_id;
            $tmp['asset_type_name'] = $data->asset_type_name;
            $tmp['asset_group_name'] = $data->asset_group_name;
            $tmp['asset_no'] = $data->asset_no;
            $tmp['asset_id'] = $data->asset_id;
            $tmp['trx_date'] = $data->trx_date;
            $tmp['project_code'] = $data->project_code;
            $tmp['area'] = $data->area_name;
            $tmp['remark'] = $data->remark;
            $tmp['status'] = $data->approval_status;
            $tmp['file_uploaded'] = $data->file_uploaded;
            $tmp['file_path'] = $data->file_path;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    //==================================================================== VIEW ASSETS REGISTRATION USER =============================================================\\
    public function view()
    {
        $currentUser = JWTAuth::parseToken()->authenticate();
        $session = Auth::guard()->user()->id;

        $level = Auth::guard()->user()->approval_level;
        $sql = "SELECT ar.*, COUNT(ar.file_path) AS file, 
            CASE WHEN ar.approval_status = 0 THEN 'Open'
                WHEN ar.approval_status = 1 THEN 'Approved'
                WHEN ar.approval_status = 2 THEN 'Disapprove' ELSE 'Unknown' END as approval_status,
            ar.approval_position as approval_id,
            CASE WHEN ar.approval_position = 1 THEN 'PM'
                WHEN ar.approval_position = 2 THEN 'AM'
                WHEN ar.approval_position = 3 THEN 'Completed' ELSE 'Unknown' END as approval_position
            FROM asset_registration ar";
        if ($level < 999) {
            $sql .= " WHERE ar.user_id = $session AND DATE(ar.trx_date) BETWEEN DATE_SUB(NOW(), INTERVAL 3 MONTH) AND CURRENT_DATE() GROUP BY ar.uniq_id ORDER BY ar.trx_date DESC";
        } else if ($level = 999) {
            $sql .= " GROUP BY ar.uniq_id ORDER BY ar.trx_date DESC LIMIT 100";
        }

        $view = [];
        $view_registered_asset = DB::select(DB::raw($sql));
        foreach ($view_registered_asset as $data) {

            $tmp = [];
            $localized_date = Carbon::parse($data->trx_date)->format('d-m-Y');
            $tmp['uniq_id'] = $data->uniq_id;
            $tmp['asset_type_id'] = $data->asset_type_id;
            $tmp['asset_type_name'] = $data->asset_type_name;
            $tmp['asset_group_name'] = $data->asset_group_name;
            $tmp['asset_no'] = $data->asset_no;
            $tmp['asset_id'] = $data->asset_id;
            $tmp['trx_date'] = $localized_date;
            $tmp['project_code'] = $data->project_code;
            $tmp['remark'] = $data->remark;
            $tmp['file_uploaded'] = $data->file;
            if ($data->approval_status == 1) {
                $tmp['status'] = $data->approval_status;
            } else {
                $tmp['status'] = $data->approval_status . ' (' . $data->remark_disapprove . ')';
            }
            $tmp['approval_position'] = $data->approval_id == 3 ? 'Close' : 'Outstanding' . "($data->approval_position)";

            $sql = "SELECT file_path, trx_date FROM asset_registration WHERE user_id = $data->user_id AND uniq_id = '$data->uniq_id' GROUP BY file_path";

            $path = DB::select(DB::raw($sql));
            foreach ($path as $items) {
                $data = [];
                $path = $items->file_path;
                $url = URL::to("/storage/images/$path.jpg");
                $data['photo'] = $url;
                $tmp['file_path'][] = $data;
            }

            array_push($view, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $view
        ], 200);
    }

    //==================================================================== UPLOAD ASSETS =============================================================\\
    public function upload(Request $request)
    {

        $currentUser = JWTAuth::parseToken()->authenticate();
        $user_id = Auth::guard()->user()->id;


        $uniq_id = "AR" . date('Ymd') . rand(1, 9999999999);

        $date = date('Y-m-d');
        $fileupload = $request->all();
        $data = ['fileupload'];
        $rand = date('Ymd') . rand(1, 9999999999);
        // $area_id = DB::table('0_projects')->select('area_id')->where('code',$request->project_code);

        $tmp = [];

        if ($data1 = $request->file('photo1')) {
            $file1 = [];
            $file1['name_file'] = $user_id . date('Ymd') . rand(1, 9999999999);
            $file1['asset_type_name'] = $request->asset_type_name;
            $tmp['list1'][] = $file1;

            $destination = public_path("/storage/images");
            $data1->move($destination, $file1['name_file'] . ".jpg");

            if ($request->asset_type_id == 3 || $request->asset_type_id == 4) {
                $next_approval = 1;
            } else {
                $next_approval = 1;
            }
            DB::table('asset_registration')->insert(array(
                'asset_type_id' => $request->asset_type_id,
                'uniq_id' => $uniq_id,
                'asset_type_name' => $request->asset_type_name,
                'asset_group_name' => $request->asset_group_name,
                'asset_id' => $request->asset_id,
                'trx_date' => Carbon::now(),
                'file_path' => $file1['name_file'],
                'project_code' => $request->project_code,
                'lat' => $request->latitude,
                'lng' => $request->longitude,
                'user_id' => $user_id,
                'approval_position' => $next_approval,
                'remark' => $request->remark
            ));
        }

        if ($data2 = $request->file('photo2')) {
            $file2 = [];
            $file2['name_file'] = $user_id . date('Ymd') . rand(1, 9999999999);
            $file2['asset_type_name'] = $request->asset_type_name;
            $tmp['list2'][] = $file2;

            $destination = public_path("/storage/images");
            $data2->move($destination, $file2['name_file'] . ".jpg");

            if ($request->asset_type_id == 3 || $request->asset_type_id == 4) {
                $next_approval = 1;
            }
            DB::table('asset_registration')->insert(array(
                'asset_type_id' => $request->asset_type_id,
                'uniq_id' => $uniq_id,
                'asset_type_name' => $request->asset_type_name,
                'asset_group_name' => $request->asset_group_name,
                'asset_id' => $request->asset_id,
                'trx_date' => Carbon::now(),
                'file_path' => $file2['name_file'],
                'project_code' => $request->project_code,
                'lat' => $request->latitude,
                'lng' => $request->longitude,
                'user_id' => $user_id,
                'approval_position' => $next_approval,
                'remark' => $request->remark
            ));
        }

        if ($data3 = $request->file('photo3')) {
            $file3 = [];
            $file3['name_file'] = $user_id . date('Ymd') . rand(1, 9999999999);
            $file3['asset_type_name'] = $request->asset_type_name;
            $tmp['list3'][] = $file3;

            $destination = public_path("/storage/images");
            $data3->move($destination, $file3['name_file'] . ".jpg");

            if ($request->asset_type_id == 3 || $request->asset_type_id == 4) {
                $next_approval = 1;
            }
            DB::table('asset_registration')->insert(array(
                'asset_type_id' => $request->asset_type_id,
                'uniq_id' => $uniq_id,
                'asset_type_name' => $request->asset_type_name,
                'asset_group_name' => $request->asset_group_name,
                'asset_id' => $request->asset_id,
                'trx_date' => Carbon::now(),
                'file_path' => $file3['name_file'],
                'project_code' => $request->project_code,
                'lat' => $request->latitude,
                'lng' => $request->longitude,
                'user_id' => $user_id,
                'approval_position' => $next_approval,
                'remark' => $request->remark
            ));
        }

        if ($data4 = $request->file('photo4')) {
            $file4 = [];
            $file4['name_file'] = $user_id . date('Ymd') . rand(1, 9999999999);
            $file4['asset_type_name'] = $request->asset_type_name;
            $tmp['list4'][] = $file4;

            $destination = public_path("/storage/images");
            $data4->move($destination, $file4['name_file'] . ".jpg");

            if ($request->asset_type_id == 3 || $request->asset_type_id == 4) {
                $next_approval = 1;
            }
            DB::table('asset_registration')->insert(array(
                'asset_type_id' => $request->asset_type_id,
                'uniq_id' => $uniq_id,
                'asset_type_name' => $request->asset_type_name,
                'asset_group_name' => $request->asset_group_name,
                'asset_id' => $request->asset_id,
                'trx_date' => Carbon::now(),
                'file_path' => $file4['name_file'],
                'project_code' => $request->project_code,
                'lat' => $request->latitude,
                'lng' => $request->longitude,
                'user_id' => $user_id,
                'approval_position' => $next_approval,
                'remark' => $request->remark
            ));
        }

        if ($data5 = $request->file('photo5')) {
            $file5 = [];
            $file5['name_file'] = $user_id . date('Ymd') . rand(1, 9999999999);
            $file5['asset_type_name'] = $request->asset_type_name;
            $tmp['list5'][] = $file5;

            $destination = public_path("/storage/images");
            $data5->move($destination, $file5['name_file'] . ".jpg");

            if ($request->asset_type_id == 3 || $request->asset_type_id == 4) {
                $next_approval = 1;
            }
            DB::table('asset_registration')->insert(array(
                'asset_type_id' => $request->asset_type_id,
                'uniq_id' => $uniq_id,
                'asset_type_name' => $request->asset_type_name,
                'asset_group_name' => $request->asset_group_name,
                'asset_id' => $request->asset_id,
                'trx_date' => Carbon::now(),
                'file_path' => $file5['name_file'],
                'project_code' => $request->project_code,
                'lat' => $request->latitude,
                'lng' => $request->longitude,
                'user_id' => $user_id,
                'approval_position' => $next_approval,
                'remark' => $request->remark
            ));
        }

        if ($data6 = $request->file('photo6')) {
            $file6 = [];
            $file6['name_file'] = $user_id . date('Ymd') . rand(1, 9999999999);
            $file6['asset_type_name'] = $request->asset_type_name;
            $tmp['list6'][] = $file6;

            $destination = public_path("/storage/images");
            $data6->move($destination, $file6['name_file'] . ".jpg");

            if ($request->asset_type_id == 3 || $request->asset_type_id == 4) {
                $next_approval = 1;
            }
            DB::table('asset_registration')->insert(array(
                'asset_type_id' => $request->asset_type_id,
                'uniq_id' => $uniq_id,
                'asset_type_name' => $request->asset_type_name,
                'asset_group_name' => $request->asset_group_name,
                'asset_id' => $request->asset_id,
                'trx_date' => Carbon::now(),
                'file_path' => $file6['name_file'],
                'project_code' => $request->project_code,
                'lat' => $request->latitude,
                'lng' => $request->longitude,
                'user_id' => $user_id,
                'approval_position' => $next_approval,
                'remark' => $request->remark
            ));
        }

        array_push($data, $tmp);

        return response()->json([
            'success' => true,
            'data' => $data
        ], 200);
    }
    //==================================================================== Approve AR =============================================================\\
    public function approve_ar($uniq_id)
    {
        $currentUser = JWTAuth::parseToken()->authenticate();
        $session = Auth::guard()->user()->person_id;
        $name_approval = Auth::guard()->user()->name;
        $level = Auth::guard()->user()->approval_level;
        $user_id = Auth::guard()->user()->id;
        $date = Carbon::now();

        $response = [];
        $sql = "SELECT ar.*, count(ar.file_path) as file_uploaded, e.name AS emp_name, e.emp_new_id AS emp_id FROM asset_registration ar
                LEFT OUTER JOIN 0_projects p ON (ar.project_code = p.code)
                LEFT OUTER JOIN users u ON (u.id = ar.user_id)
                LEFT OUTER JOIN 0_hrm_employees e ON (u.emp_no = e.id)
                WHERE ar.uniq_id = '$uniq_id'
                GROUP BY ar.uniq_id";
        $view_registered_asset = DB::select(DB::raw($sql));
        foreach ($view_registered_asset as $data) {

            $tmp = [];
            $tmp['uniq_id'] = $data->uniq_id;
            $tmp['emp_name'] = $data->emp_name;
            $tmp['emp_id'] = $data->emp_id;
            $tmp['asset_type_name'] = $data->asset_type_name;
            $tmp['asset_group_name'] = $data->asset_group_name;
            $tmp['asset_no'] = $data->asset_no;
            $tmp['asset_id'] = $data->asset_id;
            $tmp['trx_date'] = $data->trx_date;
            $tmp['project_code'] = $data->project_code;
            $tmp['remark'] = $data->remark;
            $tmp['status'] = $data->approval_status;
            $tmp['approval_position'] = $data->approval_position;
            $tmp['file_uploaded'] = $data->file_uploaded;

            if (in_array($level, [1, 2, 3, 4])) {
                DB::table('asset_registration')->where('uniq_id', $uniq_id)
                    ->update(array(
                        'approval_status' => 1,                //========================================//
                        'approval_position' =>  2,
                        'update_history' => "($date) $name_approval Approve;"
                    ));  //========================================//
            } else if ($level == 555) {
                DB::table('asset_registration')->where('uniq_id', $uniq_id)
                    ->update(array(
                        'approval_status' => 1,                //========================================//
                        'approval_position' =>  3,
                        'update_history' => "$data->update_history ($date) $name_approval Approve; "
                    ));  //========================================//
            }

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }

    //==================================================================== Disapprove AR =============================================================\\
    public function disapprove_ar(Request $request, $uniq_id)
    {
        $currentUser = JWTAuth::parseToken()->authenticate();
        $session = Auth::guard()->user()->person_id;
        $remark_disapprove = empty($request->comment) ? '' : $request->comment;
        $name_approval = Auth::guard()->user()->name;
        $date = Carbon::now();

        $response = [];
        $sql = "SELECT ar.*, count(ar.file_path) as file_uploaded, e.name AS emp_name, e.emp_new_id AS emp_id FROM asset_registration ar
                LEFT OUTER JOIN 0_projects p ON (ar.project_code = p.code)
                LEFT OUTER JOIN users u ON (u.id = ar.user_id)
                LEFT OUTER JOIN 0_hrm_employees e ON (u.emp_no = e.id)
                WHERE ar.uniq_id = '$uniq_id'
                GROUP BY ar.uniq_id";
        $view_registered_asset = DB::select(DB::raw($sql));
        foreach ($view_registered_asset as $data) {

            $tmp = [];
            $tmp['uniq_id'] = $data->uniq_id;
            $tmp['emp_name'] = $data->emp_name;
            $tmp['emp_id'] = $data->emp_id;
            $tmp['asset_type_name'] = $data->asset_type_name;
            $tmp['asset_group_name'] = $data->asset_group_name;
            $tmp['asset_no'] = $data->asset_no;
            $tmp['asset_id'] = $data->asset_id;
            $tmp['trx_date'] = $data->trx_date;
            $tmp['project_code'] = $data->project_code;
            $tmp['remark'] = $data->remark;
            $tmp['status'] = $data->approval_status;
            $tmp['approval_position'] = $data->approval_position;
            $tmp['file_uploaded'] = $data->file_uploaded;

            DB::table('asset_registration')->where('uniq_id', $uniq_id)
                ->update(array(
                    'approval_status' => 2,                //========================================//
                    'approval_position' =>  3,
                    'remark_disapprove' =>  $remark_disapprove,
                    'update_history' => "($date) $name_approval Disapprove;"
                ));  //========================================//

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }


    public static function view_all(Request $request)
    {
        $currentUser = JWTAuth::parseToken()->authenticate();
        $session = Auth::guard()->user()->person_id;
        $name_approval = Auth::guard()->user()->name;
        $response = [];
        $date = date('Y-m-d');
        if (empty($request->from_date)) {
            $from_date = date('Y-m-d', strtotime($date . ' -1 months'));
        } else {
            $from_date = $request->from_date;
        }
        if (empty($request->to_date)) {
            $to_date = $date;
        } else {
            $to_date = $request->to_date;
        }
        if (empty($request->asset_no)) {
            $asset_no = '';
        } else {
            $asset_no = $request->asset_no;
        }
        $sql = "SELECT
						ar.id,
						ar.uniq_id,
						ar.asset_type_name,
						ar.asset_group_name,
                        ar.asset_type_id,
                        ar.asset_no,
                        ar.asset_id,
                        ar.remark AS asset_condition,
						CONCAT(ar.trx_date) AS registration_period,
						e.emp_id,
						e.name AS employee_name,
						ar.project_code,
						a.name as area_name,
						CASE WHEN ((ar.asset_type_name='Kendaraan Mobil' || ar.asset_type_name='Kendaraan Motor') AND ar.approval_position < 3) THEN 'Need Approval AM' ELSE
						CASE WHEN (ar.approval_position = 1) THEN 'Need Approval PM'
						WHEN (ar.approval_position = 2) THEN 'Need Approval AM'
						WHEN (ar.approval_position= 3) THEN 'Close' END END approval_status_name,
                        CASE WHEN (ar.approval_status = 1) THEN CONCAT_WS(' ','Approved', ar.remark_disapprove)
						WHEN (ar.approval_status = 2) THEN CONCAT_WS(' ','Disapproved', ar.remark_disapprove) END approval_status,
                        ar.remark_disapprove,
						COUNT(file_path) as file_count,
						ai.doc_no,
						CASE WHEN ((ar.asset_type_name='Kendaraan Mobil' || ar.asset_type_name='Kendaraan Motor') AND ar.approval_position<3) THEN 2 ELSE ar.approval_position END approval_position,
						YEAR(ar.trx_date) AS year,
						MONTH(ar.trx_date) AS month,
                GROUP_CONCAT('/storage/images/', ar.file_path, '.jpg' SEPARATOR ';') AS file_path,
                ar.update_history
				FROM asset_registration ar
				LEFT OUTER JOIN 0_projects p ON (ar.project_code = p.code)
                LEFT JOIN 0_project_area a ON (a.area_id = p.area_id)
                LEFT OUTER JOIN users u ON (u.id = ar.user_id)
                LEFT OUTER JOIN 0_hrm_employees e ON (u.emp_no = e.id)
				LEFT JOIN 0_am_issues ai ON (ai.issue_id = ar.issue_id)
				where ar.id !=''";

        if ($asset_no != '') {
            $sql .= " AND ar.asset_id LIKE '%$asset_no%'";
        } else {
            if ($request->emp_id != '') {
                $sql .= " AND e.emp_id='$request->emp_id'";
            }

            if ($request->type_id != 0) {
                $sql .= " AND ar.asset_type_id=$request->type_id";
            }
            if ($from_date != '' && $to_date != '') {
                $sql .= " AND DATE(ar.trx_date) BETWEEN '$from_date' AND '$to_date'";
            }
            if ($request->area_id != 0) {
                $sql .= " AND a.area_id = $request->area_id";
            }
        }
        $sql .= " GROUP BY ar.uniq_id";

        $data = DB::select(DB::raw($sql));
        foreach ($data as $val) {

            $tmp = [];
            $tmp['#'] = $val->id;
            $tmp['uniq_id'] = $val->uniq_id;
            $tmp['asset_type_id'] = $val->asset_type_id;
            $tmp['asset_type_name'] = $val->asset_type_name;
            $tmp['asset_group_name'] = $val->asset_group_name;
            $tmp['asset_no'] = $val->asset_no;
            $tmp['asset_id'] = $val->asset_id;
            $tmp['asset_condition'] = strtoupper($val->asset_condition);
            $tmp['registration_period'] = $val->registration_period;
            $tmp['emp_id'] = $val->emp_id;
            $tmp['employee_name'] = $val->employee_name;
            $tmp['project_code'] = $val->project_code;
            $tmp['area_name'] = $val->area_name;
            $tmp['approval_status_name'] = $val->approval_status_name . ' ' . ' (' . $val->approval_status . ')';
            $tmp['file_count'] = $val->file_count;
            $tmp['file_path'] = $val->file_path;
            $tmp['update_history'] = $val->update_history;

            array_push($response, $tmp);
        }

        $myPage = $request->page;
        $myUrl = $request->url();
        $query = $request->query();

        if (empty($request->perpage)) {
            $perPage = 10;
        } else {
            $perPage = $request->perpage;
        }

        return PaginationArr::arr_pagination(
            $response,
            $myPage,
            $perPage,
            $myUrl,
            $query
        );
    }

    public function export_asset_registration(Request $request)
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
        $emp_id = (empty($request->emp_id)) ? 0 : $request->emp_id;
        $type_id = (empty($request->type_id)) ? 0 : $request->type_id;
        $area_id = (empty($request->area_id)) ? 0 : $request->area_id;
        $filename = "ASSET REGISTRATION";

        return Excel::download(new AssetRegistrationExport($from_date, $to_date, $emp_id, $type_id, $area_id), "$filename.xlsx");
    }
}
