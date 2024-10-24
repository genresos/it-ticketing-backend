<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Auth;
use JWTAuth;

class AppVersionContoller extends Controller
{
    public static function checkversion()
    {
        $currentUser = JWTAuth::parseToken()->authenticate();
        $response = [];

        $sql = "SELECT id,app_version FROM app_version
                WHERE inactive = 0
                ORDER BY id DESC
                LIMIT 1";

        $version = DB::select(DB::raw($sql));

        foreach ($version as $data) {
            $ver = str_replace(".", "", $data->app_version);
            $filename = "https://ate.adyawinsa.com/share/adyawinsa-app-v$ver.apk";
            $tmp = [];
            $tmp['app_version'] = $data->app_version;
            $tmp['url'] = $filename;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }
}
