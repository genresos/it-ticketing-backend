<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use JWTAuth;
use Auth;
use Illuminate\Support\Facades\DB;
use Dingo\Api\Routing\Helpers;
use Carbon\Carbon;
use Validator, Redirect, Response, File;

class UserController extends Controller
{
    public static function get_user_area()
    {
        $currentUser = JWTAuth::parseToken()->authenticate();
        $user_id = Auth::guard()->user()->old_id;

        $get_user_area = DB::table('0_users')
            ->where('id', $user_id)
            ->first();
        $area_id = $get_user_area->area_id;

        return $area_id;
    }

    public static function get_emp_no($emp_id)
    {

        $sql = DB::table('0_hrm_employees')
            ->where('emp_id', $emp_id)
            ->first();
        $emp_no = $sql->id;

        return $emp_no;
    }
    public static function get_info_user_devosa($emp_id)
    {

        $users = DB::connection('pgsql')->table('adm_user')
            ->where('employee_id', $emp_id)->where('active', true)->first();

        return $users;
    }

    public static function get_user_info($id)
    {

        $user_id = explode(',', $id);
        $users = DB::table('users')
            ->whereIn('id', $user_id)->first();

        return $users;
    }

    public static function get_emp_id_by_person_id($person_id)
    {
        // special case
        if($person_id == 190){
            return '4830-0185'; // emp_id bay syawala karena person id sebenarnya adalah 213
        }

        $users = DB::table('users')
            ->where('person_id', $person_id)->first();

        if (empty($users)) {
            return null;
        }

        return $users->emp_id;
    }


    public static function emp_detail_devosa($emp_id)
    {
        $detail_emp = DB::connection('pgsql')->table('hrd_employee')
            ->where('employee_id', $emp_id)->first();

        return $detail_emp;
    }

    public static function get_position_user($emp_id)
    {
        $user_info = self::emp_detail_devosa($emp_id);
        $position_code = (!empty($user_info->position_code)) ? $user_info->position_code : 'TSS-PM-S';

        $position = DB::connection('pgsql')->table('hrd_position')
            ->where('position_code', $position_code)->first();
        return $position->position_name;
    }

    public static function get_emp_id_pm($code)
    {
        $sql = "SELECT u.emp_id FROM 0_projects p
                LEFT OUTER JOIN users u ON (p.person_id = u.person_id)
                WHERE p.code = '$code'";
        $exe = DB::select(DB::raw($sql));

        foreach ($exe as $data) {
            $emp_id = $data->emp_id;
            return $emp_id;
        }
    }

    public static function get_user_phone_number($emp_id)
    {
        $sql = DB::table('users')
            ->where('emp_id', $emp_id)->first();

        $phone = (!empty($sql->phone_number)) ? $sql->phone_number : '-';
        return $phone;
    }
}
