<?php

namespace App\Http\Controllers;

use JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Carbon\Carbon;
use Validator, Redirect, Response, File;
use Auth;
use URL;
use Symfony\Component\HttpKernel\Exception\ProjectCodeExistHttpException;

class SalesOrderController extends Controller
{
    public static function get_sql_for_sales_orders_detail_by_project($project_code)
    {
        $response = [];

        $sql = "SELECT
                    sorder.order_no,
                    sorder.project_code,
                    sorder.customer_ref,
                    line.site_id,
                    line.site_name,
                    line.description,
                    line.qty_ordered,
                    line.unit,
                    line.unit_price,
                    (line.qty_ordered * line.unit_price) AS line_amount,
                    (line.qty_delivered * line.unit_price) AS line_verified,
                    sorder.curr_code,
                    sorder.reference
                FROM 0_sales_orders AS sorder, 0_sales_order_details AS line, 0_groups scategory
                WHERE sorder.order_no = line.order_no
                AND sorder.sales_category_id = scategory.id AND sorder.project_code LIKE '%$project_code%'";
        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {

            $tmp = [];
            $tmp['doc_no'] = $data->reference;
            $tmp['project'] = $data->project_code;
            $tmp['order_ref'] = $data->customer_ref;
            $tmp['site_id'] = $data->site_id;
            $tmp['site_name'] = $data->site_name;
            $tmp['description'] = $data->description;
            $tmp['qty_ordered'] = $data->qty_ordered;
            $tmp['unit'] = $data->unit;
            $tmp['unit_price'] = $data->unit_price;
            $tmp['line_amount'] = $data->line_amount;
            $tmp['line_verified'] = $data->line_verified;
            $tmp['curr_code'] = $data->curr_code;

            array_push($response, $tmp);
        }

        return $response;
    }

    public static function get_total_so($project_code, $waiting_po=0)
    {
        $response = [];

        $sql = "SELECT SUM(line.qty_ordered * line.unit_price) AS amount,
                sorder.curr_code
                FROM 0_sales_orders AS sorder, 0_sales_order_details AS line, 0_groups scategory
                WHERE sorder.order_no = line.order_no
                AND sorder.sales_category_id = scategory.id AND sorder.project_code LIKE '%$project_code%'";

        if($waiting_po == 1){
            $sql .= " AND sorder.customer_ref LIKE '%Waiting%'";
        }else{
            $sql .= " AND sorder.customer_ref NOT LIKE '%Waiting%'";
        }
        $query = DB::select(DB::raw($sql));
        foreach ($query as $data) {

            $tmp = [];
            $tmp['amount'] = $data->amount;
            $tmp['curr'] = $data->curr_code;
            array_push($response, $tmp);
        }

        return $response;
    }
}
