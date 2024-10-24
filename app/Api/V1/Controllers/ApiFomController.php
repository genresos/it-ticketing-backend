<?php

namespace App\Api\V1\Controllers;

use JWTAuth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FomController;
use Illuminate\Http\Request;
use Auth;
use App\Modules\PaginationArr;
use Illuminate\Support\Facades\DB;

class ApiFomController extends Controller
{

    public function __construct()
    {
        $currentUser = JWTAuth::parseToken()->authenticate();

        $this->user_id = Auth::guard()->user()->id;
        $this->user_old_id = Auth::guard()->user()->old_id;
        $this->user_level = Auth::guard()->user()->approval_level;
        $this->person_id = Auth::guard()->user()->person_id;
        $this->user_division = Auth::guard()->user()->division_id;
    }

    public function index(Request $request)
    {
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

        if (!empty($request->reference)) {
            $reference = $request->reference;
        } else {
            $reference = '';
        }

        $myArray = FomController::get_fom_data(
            $from_date,
            $to_date,
            $reference
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

    public function new_fom(Request $request)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['user_id'] = $this->user_id;
        $myQuery = FomController::store($myArray);
        return $myQuery;
    }

    public function fom_need_approval(Request $request)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['person_id'] = $this->person_id;

        $myQuery = FomController::need_approve($myArray);
        return $myQuery;
    }

    public function update_fom(Request $request, $fom_id)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['user_id'] = $this->user_id;

        $myQuery = FomController::update_fom($myArray, $fom_id);
        return $myQuery;
    }


    public function edit_fom(Request $request)
    {
        $myArray = [];
        $myArray['params'] = $request->all();
        $myArray['user_id'] = $this->user_id;

        $myQuery = FomController::edit_fom($myArray);
        return $myQuery;
    }

    public function delete_fom(Request $request)
    {
        $fom_id = $request->fom_id;
        DB::beginTransaction();

        try {

            DB::table('0_order_materials')->where('fom_id', $fom_id)->delete();
            DB::table('0_order_material_details')->where('fom_id', $fom_id)->delete();
            DB::table('0_order_material_log')->where('fom_id', $fom_id)->delete();

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

    public function get_items(Request $request)
    {
        if (!empty($request->name)) {
            $name = $request->name;
        } else {
            $name = '';
        }

        $sql = DB::table('0_stock_master')
            ->when($name != '', function ($query) use ($name) {
                $query->where('stock_id', 'LIKE', '%' . $name . '%')
                    ->orWhere('description', 'LIKE', '%' . $name . '%')
                    ->orWhere('long_description', 'LIKE', '%' . $name . '%');
            })
            ->select(
                'stock_id AS item_code',
                'description AS item_name',
                'units AS uom'
            )
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sql
        ]);
    }
}
