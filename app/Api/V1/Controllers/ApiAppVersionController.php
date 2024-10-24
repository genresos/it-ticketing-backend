<?php

namespace App\Api\V1\Controllers;

use JWTAuth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\AppVersionContoller;
use Illuminate\Http\Request;
use Auth;
use App\Modules\PaginationArr;


class ApiAppVersionController extends Controller
{
    public function checkversion(Request $request)
    {
        $data = AppVersionContoller::checkversion();

        return $data;
    }
}
