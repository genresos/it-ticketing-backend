<?php

namespace App\Api\V1\Controllers;

use JWTAuth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\AppVersionContoller;
use Illuminate\Http\Request;
use Auth;
use App\Modules\PaginationArr;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\InventoryInternalUseController;

class ApiGrnController extends Controller
{
    public function download_qr_grn(Request $request)
    {
        $old_receipt = empty($request->old) ? 0 : 1;
        return InventoryInternalUseController::download_qr_grn($request->grn_batch_id, $old_receipt);
    }
}
