<?php

namespace App\Utilities;

use Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Auth;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\User;

class SiteHelper
{
    static function exec_query($data)
    {
        return DB::select(DB::raw($data));
    }

    static function convertJson($data)
    {
        return response()->json([
            'success' => true,
            'data' => $data
        ], 200);
    }

    static function createLogs($ip, $method, $url, $header, $body)
    {
        $user_id = Auth::guard()->user()->id;
        $get_ip = "$ip" . "(user:$user_id)";
        $data_body = join(", ", $body);

        DB::beginTransaction();
        try {

            DB::table('logs_api')
                ->insert(array(
                    'ip' => $get_ip,
                    'method' => $method,
                    'url' => $url,
                    'headers' => $header,
                    'body' => $data_body,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ));

            // Commit Transaction
            DB::commit();
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }

    public static function arr_pagination($myArray, $page, $perPage, $url, $query)
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $total = count($myArray);
        $currentpage = $page;
        $offset = ($currentpage * $perPage) - $perPage;
        $itemstoshow = array_slice($myArray, $offset, $perPage);
        $dataArray = new LengthAwarePaginator(
            $itemstoshow,
            $total,
            $perPage,
            $page,
            ['path' => $url, 'query' => $query]

        );

        return response()->json($dataArray, 200);
    }

    public static function Pagination($arr, $page, $perPage, $url, $query)
    {

        return self::arr_pagination(
            $arr,
            $page,
            $perPage,
            $url,
            $query
        );
    }


    public static function path_file($folder, $file_name)
    {
        $path = "http://127.0.0.1:8000/storage/$folder/images/$file_name.jpg";

        return $path;
    }

    public static function error_msg($code, $message)
    {
        return response()->json(['error' => [
            'message' => $message,
            'status_code' => $code,
        ]], $code);
    }

    public static function makeJson($data)
    {
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public static function ec_can_close_manualy($id)
    {
        $data = DB::table('0_hrm_exit_clearences')
            ->where('id', $id)
            ->first();

        if ($data->dept_check == 2 && $data->am_check == 2 && $data->ga_check == 2 && $data->fa_check == 2 && $data->pc_check == 2 && $data->hr_check == 2 && $data->payroll_check == 2) {
            return true;
        } else {
            return false;
        }
    }

    public static function collectSingleJsonValue($data, $value)
    {
        $toJson = response()->json([
            'success' => true,
            'data' => $data
        ]);

        $encode_data = json_decode(json_encode($toJson), true);
        $get_data = collect($encode_data['original']['data'])
            ->all();

        $this_data =  $get_data[0][$value];
        return $this_data;
    }

    public static function notification($data)
    {
        // ***** USAGE
        // $myArray = [
        //     'recipients' => explode(",", $recipients->firebase_token),
        //     'title' => 'Ini judul',
        //     'body' => 'Ini body'
        // ];


        fcm()
            ->to($data['recipients'])
            ->priority('high')
            ->timeToLive(0)
            ->notification([
                'title' => $data['title'],
                'body' => $data['body'],
            ])
            ->send();
    }

    static function update_last_login($user_id)
    {
        DB::beginTransaction();
        try {

            DB::table('users')->where('id', $user_id)
                ->update(array(
                    'last_login' => Carbon::now()
                ));

            // Commit Transaction
            DB::commit();
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }
    public static function ca_notification($level, $trans_no)
    {
        //usage
        //
        // send notification
        // SiteHelper::ca_notification($approval_update, $cashadvance->trans_no);
        //
        //
        //
        // ---------------------------------- //
        //
        //
        //
        $ca_info = DB::table('0_cashadvance')->where('trans_no', $trans_no)->first();
        $division = DB::table('0_projects')->where('project_no', $ca_info->project_no)->first();
        switch ($level) {
            case 3:
                $user_division = DB::table('0_user_divisions')->where('division_id', $division->division_id)
                    ->select(DB::raw("GROUP_CONCAT(user_id SEPARATOR ',') AS user_id"))
                    ->first();
                $explode_data = explode(",", $user_division->user_id);
                $recipients = DB::table('users')->whereIn('old_id', $explode_data)
                    ->select(DB::raw("GROUP_CONCAT(firebase_token SEPARATOR ',') AS firebase_token"))
                    ->first();

                $myArray = [
                    'recipients' => explode(",", $recipients->firebase_token),
                    'title' => '[CA] CASHADVANCE APPROVAL',
                    'body' => 'CA ' . $ca_info->reference . ' NEED YOUR APPROVAL'
                ];
                self::notification($myArray);
                break;

            case 4:
                $user_project_control = DB::table('0_user_project_control')->where('division_id', $division->division_id)
                    ->select(DB::raw("GROUP_CONCAT(user_id SEPARATOR ',') AS user_id"))
                    ->first();
                $explode_data = explode(",", $user_project_control->user_id);
                $recipients = DB::table('users')->whereIn('old_id', $explode_data)
                    ->select(DB::raw("GROUP_CONCAT(firebase_token SEPARATOR ',') AS firebase_token"))
                    ->first();
                $myArray = [
                    'recipients' => explode(",", $recipients->firebase_token),
                    'title' => '[CA] CASHADVANCE APPROVAL',
                    'body' => 'CA ' . $ca_info->reference . ' NEED YOUR APPROVAL'
                ];
                self::notification($myArray);
                break;

            case 41:
                $recipients = DB::table('users')->where('approval_level', 41)
                    ->first();
                $myArray = [
                    'recipients' => array($recipients->firebase_token),
                    'title' => '[CA] CASHADVANCE APPROVAL',
                    'body' => 'CA ' . $ca_info->reference . ' NEED YOUR APPROVAL'
                ];
                self::notification($myArray);
                break;

            case 42:
                $recipients = DB::table('users')->where('approval_level', 41)
                    ->first();
                $myArray = [
                    'recipients' => array($recipients->firebase_token),
                    'title' => '[CA] CASHADVANCE APPROVAL',
                    'body' => 'CA ' . $ca_info->reference . ' NEED YOUR APPROVAL'
                ];
                self::notification($myArray);
                break;
        }
    }
}
