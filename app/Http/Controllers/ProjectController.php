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

class ProjectController extends Controller
{
    public static function qr_code($doc_no, $filename)
    {

        $code = QrCode::size('255')
            ->format('png')
            ->generate($doc_no);
        $output_file = 'storage/qr-code/' . $filename . '.png';
        Storage::disk('public')->put($output_file, $code);
    }

    public static function get_po_balance_by_project($project_budget_id)
    {

        $sql = "SELECT (if(b.po_amount IS NULL, 0, b.po_amount)) AS po_balance
                FROM
                (
                    SELECT pb.amount AS budget_amount, prj.code AS project_code, pb.budget_name,
                    (
                        SELECT
                            SUM(pod.quantity_ordered * pod.unit_price * pod.rate)
                        FROM 0_purch_order_details pod
                        INNER JOIN 0_purch_orders po ON (po.order_no = pod.order_no)
                        WHERE po.status_id =0 AND pod.project_budget_id = pb.project_budget_id
                    ) AS po_amount
                    FROM 0_project_budgets pb
                    LEFT JOIN 0_projects prj ON (pb.project_no = prj.project_no)
                    WHERE pb.project_budget_id= $project_budget_id
                )b";

        $get_po_balance_by_project = DB::select(DB::raw($sql));

        foreach ($get_po_balance_by_project as $data) {
            return $data->po_balance;
        }
    }

    public static function get_cashadvance_balance_by_project($project_budget_id)
    {
        $sql = "SELECT (if(b.ca_amount IS NULL, 0, b.ca_amount)) AS ca_balance
                FROM
                (
                    SELECT pb.amount AS budget_amount, prj.code AS project_code, pb.budget_name,
                    (
                        SELECT SUM(cd.amount)
                        FROM 0_cashadvance c
                        LEFT JOIN 0_cashadvance_details cd ON (c.trans_no = cd.trans_no)
                        WHERE cd.project_budget_id = pb.project_budget_id AND c.ca_type_id IN (1, 4, 6, 10) and cd.status_id<2
                    ) AS ca_amount
                    FROM 0_project_budgets pb
                    LEFT JOIN 0_projects prj ON (pb.project_no = prj.project_no)
                    WHERE pb.project_budget_id=$project_budget_id
                )b";

        $get_cashadvance_balance_by_project = DB::select(DB::raw($sql));

        foreach ($get_cashadvance_balance_by_project as $data) {
            return $data->ca_balance;
        }
    }
    public static function get_gl_balance_by_project($project_budget_id)
    {
        $sql = "SELECT (if(b.gl_amount IS NULL, 0, b.gl_amount)) AS gl_balance
                FROM
                (
                    SELECT pb.amount AS budget_amount, prj.code AS project_code, pb.budget_name,
                    (
                        SELECT SUM(gl.amount)
                        FROM 0_gl_trans gl
                        WHERE gl.project_budget_id = pb.project_budget_id AND gl.type='1'
                    ) AS gl_amount
                    FROM 0_project_budgets pb
                    LEFT JOIN 0_projects prj ON (pb.project_no = prj.project_no)
                    WHERE pb.project_budget_id=$project_budget_id
                )b";

        $get_gl_balance_by_project = DB::select(DB::raw($sql));

        foreach ($get_gl_balance_by_project as $data) {
            return $data->gl_balance;
        }
    }
    public static function project_budgets($project_budget_id)
    {

        $sql = "SELECT pb.project_budget_id,
				   pb.budget_name,
				   pbt.name AS budget_type_name, 
				   st.name AS site_name,
				   pb.amount,
				   pb.description,
				   pb.inactive
			FROM 0_project_budgets pb
			LEFT JOIN 0_project_cost_type_group pbt ON (pb.budget_type_id = pbt.cost_type_group_id)
			LEFT JOIN 0_project_site st ON (pb.site_id = st.site_no)		
			WHERE pb.project_budget_id = $project_budget_id
			ORDER BY pb.created_date DESC";

        $project_budget = DB::select(DB::raw($sql));

        foreach ($project_budget as $data) {
            return $data->amount;
        }
    }


    public static function project_cost_type_group_lists($project_code = '')
    {
        $response = [];

        $sql = "SELECT pct.cost_type_group_id, pct.name, pct.account_code,cm.account_name FROM 0_project_cost_type_group pct
                LEFT OUTER JOIN 0_chart_master cm ON (cm.account_code = pct.account_code)
                WHERE pct.inactive = 0";

        // $cek_kopro = strpos(strtoupper($project_code), 'OFC');
        // if ($cek_kopro !== false) {
        //     $sql .= " AND pct.account_code LIKE '6%'";
        // } else {
        //     $sql .= " AND pct.account_code LIKE '5%'";
        // }

        $project_cost_type_group = DB::select(DB::raw($sql));
        foreach ($project_cost_type_group as $data) {

            $tmp = [];
            $tmp['cost_type_id'] = $data->cost_type_group_id;
            $tmp['name'] = $data->name;
            $tmp['account'] = $data->account_code;
            $tmp['account_name'] = $data->account_name;

            array_push($response, $tmp);
        }

        return response()->json([
            'success' => true,
            'data' => $response
        ], 200);
    }
}
