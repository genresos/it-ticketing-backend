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
use Storage;
use App\Image;

class FomController extends Controller
{
    public static function get_fom_data($from_date, $to_date, $doc_no)
    {
        $response = [];
        $sql =
            DB::table('0_order_materials AS om')
            ->leftJoin(
                '0_projects as p',
                'om.project_no',
                '=',
                'p.project_no'
            )
            ->leftJoin('0_project_site AS ps', 'om.site_no', '=', 'ps.site_no')
            ->leftJoin(
                'users as u',
                'om.created_by',
                '=',
                'u.id'
            )
            ->select(
                'om.fom_id',
                'om.order_ref',
                'om.reference AS doc_no',
                'om.date',
                'p.code AS project_code',
                'ps.site_id AS site_id',
                'ps.name AS site_name',
                'om.approval_id',
                DB::raw("(CASE WHEN om.approval_id = 0 THEN 'Project Manager' 
                WHEN om.approval_id = 1 THEN 'CLOSE'
                WHEN om.approval_id = 2 THEN 'DISAPPROVE'
                ELSE om.approval_id END) AS approval_status"),
                'om.status_id',
                DB::raw("(CASE WHEN om.status_id = 0 THEN 'NEW' 
                WHEN om.status_id = 1 THEN 'Approved'
                WHEN om.status_id = 2 THEN 'Rejected'
                ELSE om.status_id END) AS status_name"),
                'u.name AS requestor'
            )
            ->whereBetween('om.date', [$from_date, $to_date])
            ->when($doc_no != '', function ($query) use ($doc_no) {
                $query->where('om.reference', 'LIKE', '%' . $doc_no . '%');
            })
            ->where('om.status_id', '<', 2)
            ->get();
        foreach ($sql as $data) {
            $tmp = [];
            $tmp['fom_id'] = $data->fom_id;
            $tmp['order_ref'] = $data->order_ref;
            $tmp['doc_no'] = $data->doc_no;
            $tmp['date'] = $data->date;
            $tmp['project_code'] = $data->project_code;
            $tmp['site_id'] = $data->site_id;
            $tmp['site_name'] = $data->site_name;
            $tmp['approval_id'] = $data->approval_id;
            $tmp['approval_status'] = $data->approval_status;
            $tmp['status_id'] = $data->status_id;
            $tmp['status_name'] = $data->status_name;
            $tmp['requestor'] = $data->requestor;

            $detail = DB::table('0_order_material_details')->where('fom_id', $data->fom_id)->get();
            $logs = DB::table('0_order_material_log AS log')
                ->leftJoin('users AS u', 'log.person_id', '=', 'u.id')
                ->select(
                    'log.id',
                    'log.fom_id',
                    'log.person_id',
                    'u.name',
                    'log.remark',
                    'log.created_at'
                )
                ->where('log.fom_id', $data->fom_id)->get();

            $tmp['details'][] = $detail;
            $tmp['logs'][] = $logs;

            array_push($response, $tmp);
        }
        return $response;
    }

    public static function store($myArr)
    {
        $params = $myArr['params'];

        $order_ref = $params['order_ref'];
        $date = $params['date'];
        $project_no = $params['project_no'];
        $site_no = $params['site_no'];
        $address = $params['address'];
        $remark = $params['remark'];
        $details = $params['details'];

        $next_ref = DB::table('0_order_materials')->orderBy('fom_id', 'desc')->first();
        $ref = ++$next_ref->reference;
        $user_id = $myArr['user_id'];
        DB::beginTransaction();
        try {
            $header = DB::table('0_order_materials')
                ->insertGetId(
                    [
                        'order_ref' => $order_ref,
                        'reference' => $ref,
                        'date' => $date,
                        'project_no' => $project_no,
                        'site_no' => $site_no,
                        'delivery_address' => $address,
                        'remark' => $remark,
                        'created_by' => $user_id,
                        'created_at' => Carbon::now()
                    ]
                );

            foreach ($details as $val => $key) {
                DB::table('0_order_material_details')
                    ->insert(array(
                        'fom_id' => $header,
                        'item_code' => $key['item_code'],
                        'item_name' => $key['item_name'],
                        'qty' => $key['qty'],
                        'uom' => $key['uom'],
                        'comment' => $key['comment'],
                        'created_at' => Carbon::now()

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

    public static function need_approve($myArr)
    {
        $person_id = $myArr['person_id'];

        $response = [];
        $sql =
            DB::table('0_order_materials AS om')
            ->leftJoin(
                '0_projects as p',
                'om.project_no',
                '=',
                'p.project_no'
            )
            ->leftJoin('0_project_site AS ps', 'om.site_no', '=', 'ps.site_no')
            ->leftJoin(
                'users as u',
                'om.created_by',
                '=',
                'u.id'
            )
            ->select(
                'om.fom_id',
                'om.order_ref',
                'om.reference AS doc_no',
                'om.date',
                'p.code AS project_code',
                'ps.site_id AS site_id',
                'ps.name AS site_name',
                'om.approval_id',
                DB::raw("(CASE WHEN om.approval_id = 0 THEN 'Porject Manager' 
                WHEN om.approval_id = 1 THEN 'CLOSE'
                WHEN om.approval_id = 2 THEN 'DISAPPROVE'
                ELSE om.approval_id END) AS approval_status"),
                'om.status_id',
                DB::raw("(CASE WHEN om.status_id = 0 THEN 'NEW' 
                WHEN om.status_id = 1 THEN 'Approved'
                WHEN om.status_id = 2 THEN 'Rejected'
                ELSE om.status_id END) AS status_name"),
                'u.name AS requestor'
            )
            ->where('om.approval_id', 0)
            ->where('p.person_id', $person_id)
            ->get();
        foreach ($sql as $data) {
            $tmp = [];
            $tmp['fom_id'] = $data->fom_id;
            $tmp['order_ref'] = $data->order_ref;
            $tmp['doc_no'] = $data->doc_no;
            $tmp['date'] = $data->date;
            $tmp['project_code'] = $data->project_code;
            $tmp['site_id'] = $data->site_id;
            $tmp['site_name'] = $data->site_name;
            $tmp['approval_id'] = $data->approval_id;
            $tmp['approval_status'] = $data->approval_status;
            $tmp['status_id'] = $data->status_id;
            $tmp['status_name'] = $data->status_name;
            $tmp['requestor'] = $data->requestor;

            $detail = DB::table('0_order_material_details')->where('fom_id', $data->fom_id)->where('status_id', '<', 2)->get();
            $logs = DB::table('0_order_material_log AS log')
                ->leftJoin('users AS u', 'log.person_id', '=', 'u.id')
                ->select(
                    'log.id',
                    'log.fom_id',
                    'log.person_id',
                    'u.name',
                    'log.remark',
                    'log.created_at'
                )
                ->where('log.fom_id', $data->fom_id)->get();

            $tmp['details'][] = $detail;
            $tmp['logs'][] = $logs;

            array_push($response, $tmp);
        }
        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public static function update_fom($myArr, $fom_id)
    {
        $params = $myArr['params'];
        $details = $params['details'];

        $approval_id = $params['approval_id'];
        $status_id = $params['status_id'];
        $remark = $params['remark'];
        $user_id = $myArr['user_id'];

        DB::beginTransaction();
        try {

            foreach ($details as $val => $key) {
                DB::table('0_order_material_details')->where('fom_detail_id', $key['fom_detail_id'])
                    ->update(array(
                        'status_id' => $key['status_id'],
                        'comment' => $key['comment'],
                        'updated_at' => Carbon::now()

                    ));
            }
            DB::table('0_order_materials')->where('fom_id', $fom_id)
                ->update(
                    [
                        'approval_id' => $approval_id,
                        'status_id' => $status_id,
                        'remark' => $remark,
                        'updated_by' => $user_id,
                        'updated_at' => Carbon::now()
                    ]
                );

            DB::table('0_order_material_log')
                ->insert(
                    [
                        'fom_id' => $fom_id,
                        'person_id' => $user_id,
                        'approval_id' => $approval_id,
                        'remark' => ($approval_id < 2 ? 'Approved' : 'Disapprove'),
                        'created_at' => Carbon::now()
                    ]
                );

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

    public static function edit_fom($myArr)
    {
        $params = $myArr['params'];
        $data = $params['data'];
        DB::beginTransaction();
        try {

            foreach ($data as $val => $key) {
                DB::table('0_order_material_details')->where('fom_detail_id', $key['fom_detail_id'])
                    ->update(array(
                        'item_code' => $key['item_code'],
                        'item_name' => $key['item_name'],
                        'qty' => $key['qty'],
                        'uom' => $key['uom'],
                        'updated_at' => Carbon::now()
                        
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
}
