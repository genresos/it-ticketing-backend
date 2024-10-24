<?php

namespace App\Query;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ProjectListController;

class QueryProjectBudget
{
    public static function show_budgets($project_no, $budget_id, $search)
    {
        $sql = "SELECT pb.project_budget_id,
                        SUBSTR(pb.budget_name, 1, 150) as budget_name,
                        pbt.name AS budget_type_name, 
                        pb.site_id AS site_no,
                        st.site_id,
                        st.name AS site_name,
                        rab.id AS rab_id,
                        pb.rab_amount,
                        pb.amount,
                        pb.used_amount,
                        pb.real_used_amount,
                        pb.amount - (pb.used_amount + pb.sharing_amount) AS remain_amount,
                        pb.sharing_amount,
                        SUBSTR(pb.description, 1, 140) as description,
                        pb.inactive,
                        u.real_name as creator,
                        pb.budget_type_id,
                        pb.created_date,
                        pb.updated_date
                FROM 0_project_budgets pb
                LEFT JOIN 0_project_cost_type_group pbt ON (pb.budget_type_id = pbt.cost_type_group_id)
                LEFT OUTER JOIN 0_project_budget_rab rab ON (pb.project_budget_id = rab.budget_id)
                LEFT JOIN 0_project_site st ON (pb.site_id = st.site_no)	
                LEFT JOIN 0_users u ON (pb.created_by = u.id)	
                WHERE pb.project_no = $project_no";
        if ($search != '') {
            $sql .= " AND pb.project_budget_id LIKE '%$search%'";
        }
        $sql .= " OR pb.project_no = $project_no AND pbt.name LIKE '%$search%'";
        $sql .= " OR pb.project_no = $project_no AND pb.budget_name LIKE '%$search%'";

        $sql .= "  GROUP BY pb.project_budget_id ORDER BY pb.project_budget_id DESC";


        return $sql;
    }

    public static function show_budgets_acc($project_no, $budget_id, $search)
    {
        $sql = "SELECT b.id, b.type, b.trans_no, r.reference, b.real_amount, b.cost_allocation as cost_allocation FROM 0_project_budget_acc b
                LEFT OUTER JOIN 0_refs r ON (r.type = b.type AND r.id = b.trans_no)
                WHERE b.project_no = $project_no
                GROUP BY b.id DESC";
        // if ($search != '') {
        //     $sql .= " AND pb.project_budget_id LIKE '%$search%'";
        // }
        // $sql .= " OR pb.project_no = $project_no AND pbt.name LIKE '%$search%'";
        // $sql .= " OR pb.project_no = $project_no AND pb.budget_name LIKE '%$search%'";

        // $sql .= "  GROUP BY pb.project_budget_id ORDER BY pb.project_budget_id DESC";


        return $sql;
    }

    public static function budget_detail($budget_id)
    {
        $sql = "SELECT pb.project_budget_id,
                        p.code,
                        IFNULL(rab.reference, '') as rab_no,
                        SUBSTR(pb.budget_name, 1, 150) AS budget_name,
                        pbt.name AS budget_type_name, 
                        pb.site_id,
                        st.name AS site_name,
                        pb.amount,
                        pb.rab_amount,
                        SUBSTR(pb.description, 1, 140) AS description,
                        pb.inactive,
                        u.real_name AS creator,
                        pb.budget_type_id,
                        pb.created_date,
                        pb.updated_date
                FROM 0_project_budgets pb
                LEFT OUTER JOIN 0_projects p ON (pb.project_no = p.project_no)
                LEFT JOIN 0_project_submission_rab rab ON (rab.trans_no = pb.rab_no)
                LEFT JOIN 0_project_cost_type_group pbt ON (pb.budget_type_id = pbt.cost_type_group_id)
                LEFT JOIN 0_project_site st ON (pb.site_id = st.site_no)	
                LEFT JOIN 0_users u ON (pb.created_by = u.id)	
                WHERE pb.project_budget_id =$budget_id";

        return $sql;
    }

    public static function budget_detail_inside($project_budget_id)
    {
        $sql = "SELECT pbd.project_budget_detail_id,
                pbd.tanggal_req,
                pbd.amount_req,
                pbd.remark_req,
                CASE
                WHEN pbd.jenis_data = 1 THEN 'New Budget'
                WHEN pbd.jenis_data = 2 THEN 'AddBudget'
                WHEN pbd.jenis_data = 3 THEN 'Edit Budget'
                END AS budget_detail_type,
                pbd.status_req AS status_id,
                CASE
                WHEN pbd.status_req = 0 THEN 'Need Approval'
                WHEN pbd.status_req = 1 THEN 'Approved'
                WHEN pbd.status_req = 2 THEN 'Disapproved'
                END AS status_name,
                u.real_name AS user
                FROM 0_project_budget_details pbd
                LEFT JOIN 0_users u ON (pbd.user_req = u.id)
                WHERE pbd.project_budget_id = $project_budget_id
                ORDER BY pbd.tanggal_req DESC";

        return $sql;
    }

    public static function budget_use_po($budget_id)
    {
        $sql_po = "SELECT (if(b.po_amount IS NULL, 0, b.po_amount)) AS balance
			FROM
			(
				SELECT pb.amount AS budget_amount, prj.code AS project_code, pb.budget_name,
				(
					SELECT
						SUM(pod.quantity_ordered * pod.unit_price * pod.rate) - SUM((pod.quantity_ordered * pod.unit_price * pod.rate) * pod.discount_percent)
					FROM 0_purch_order_details pod
					INNER JOIN 0_purch_orders po ON (po.order_no = pod.order_no)
					WHERE po.status_id =0 AND pod.project_budget_id = pb.project_budget_id
				) AS po_amount
                FROM 0_project_budgets pb
    			LEFT JOIN 0_projects prj ON (pb.project_no = prj.project_no)
				WHERE pb.project_budget_id= $budget_id
			) b";

        return $sql_po;
    }


    public static function budget_use_po_real($budget_id)
    {
        $po_order = DB::table('0_purch_order_details')->select(DB::raw("GROUP_CONCAT(order_no SEPARATOR ',') AS order_no"))->where('project_budget_id', $budget_id)->first();
        $order_no = empty($po_order->order_no) ? 0 : $po_order->order_no;

        $sql = "SELECT DISTINCT 0_supp_trans.trans_no, 0_supp_trans.type,
                    ov_amount AS Total,
                    0_supp_trans.tran_date
                    FROM 0_supp_trans, 0_supp_invoice_items, 0_purch_order_details, 0_purch_orders
                    WHERE 0_supp_invoice_items.supp_trans_no = 0_supp_trans.trans_no
                    AND 0_supp_invoice_items.supp_trans_type = 0_supp_trans.type
                    AND 0_supp_invoice_items.po_detail_item_id = 0_purch_order_details.po_detail_item
                    AND 0_purch_orders.supplier_id = 0_supp_trans.supplier_id
                    AND 0_purch_orders.order_no = 0_purch_order_details.order_no
                    AND 0_purch_order_details.order_no IN ($order_no)";
        return $sql;
    }

    public static function budget_use_ca($budget_id)
    {
        $sql_ca = "SELECT (if(b.ca_amount IS NULL, 0, b.ca_amount)) AS balance
                FROM
                (
                    SELECT pb.amount AS budget_amount, prj.code AS project_code, pb.budget_name,
                    (
                        SELECT SUM(cd.act_amount)
                        FROM 0_cashadvance c
                        LEFT JOIN 0_cashadvance_details cd ON (c.trans_no = cd.trans_no)
                        WHERE cd.project_budget_id = pb.project_budget_id AND c.ca_type_id IN 
                        (
                        SELECT ca_type_id FROM 0_cashadvance_types
                        WHERE type_group_id IN (1,2)
                        ) 
                        AND cd.status_id<2 AND cd.spk_no = ''
                    ) AS ca_amount
                    FROM 0_project_budgets pb
                    LEFT JOIN 0_projects prj ON (pb.project_no = prj.project_no)
                    WHERE pb.project_budget_id= $budget_id
                ) b";

        return $sql_ca;
    }

    public static function budget_use_ca_real($budget_id)
    {
        $sql_ca = "SELECT (IF(b.ca_amount IS NULL, 0, b.ca_amount)) AS balance
                    FROM
                    (
                        SELECT pb.amount AS budget_amount, prj.code AS project_code, pb.budget_name,
                        (
                            SELECT SUM(cd.amount)
                            FROM 0_cashadvance c
                            LEFT JOIN 0_cashadvance_details cd ON (c.trans_no = cd.trans_no)
                            INNER JOIN 0_cashadvance_stl stl ON (stl.ca_trans_no = c.trans_no)
                            WHERE cd.project_budget_id = pb.project_budget_id AND c.ca_type_id IN (1, 3, 4, 6, 8, 10, 11, 12, 13) AND cd.status_id<2
                        ) AS ca_amount
                        FROM 0_project_budgets pb
                        LEFT JOIN 0_projects prj ON (pb.project_no = prj.project_no)
                        WHERE pb.project_budget_id= $budget_id
                    ) b";

        return $sql_ca;
    }

    public static function budget_use_gl($budget_id)
    {
        $sql_gl = "SELECT (if(b.gl_amount IS NULL, 0, b.gl_amount)) AS balance
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
                    WHERE pb.project_budget_id= $budget_id
                ) b";

        return $sql_gl;
    }


    public static function budget_use_gl_tmp($budget_id)
    {
        $sql_gl_tmp = "SELECT (if(b.gl_amount IS NULL, 0, b.gl_amount)) AS balance
                FROM
                (
                    SELECT pb.amount AS budget_amount, prj.code AS project_code, pb.budget_name,
                    (
                        SELECT SUM(gl.amount)
                        FROM 0_gl_trans_tmp gl
                        WHERE gl.project_budget_id = pb.project_budget_id AND gl.type='1' AND gl.approval = 0
                    ) AS gl_amount
                    FROM 0_project_budgets pb
                    LEFT JOIN 0_projects prj ON (pb.project_no = prj.project_no)
                    WHERE pb.project_budget_id= $budget_id
                ) b";

        return $sql_gl_tmp;
    }
    public static function budget_reverse($budget_id)
    {
        $sql_gl_tmp = "SELECT (IF(b.amount_reverse IS NULL, 0, b.amount_reverse)) AS balance
                FROM
                (
                    SELECT pb.amount AS budget_amount, prj.code AS project_code, pb.budget_name,
                    (
                        SELECT SUM(pbr.amount)
                        FROM 0_project_budgets_reverse pbr
                        WHERE pbr.project_budget_id = pb.project_budget_id 
                    ) AS amount_reverse
                    FROM 0_project_budgets pb
                    LEFT JOIN 0_projects prj ON (pb.project_no = prj.project_no)
                    WHERE pb.project_budget_id= $budget_id
                ) b";

        return $sql_gl_tmp;
    }

    public static function budget_use_salary($budget_id)
    {
        $sql_salary = "SELECT 
                        CASE WHEN SUM(salary) IS NULL THEN 0 ELSE SUM(salary) END balance
                   FROM 0_project_salary_budget
                   WHERE budget_id=" . $budget_id;

        return $sql_salary;
    }

    public static function budget_use_spk($budget_id)
    {
        $sql_spk =
            "SELECT CASE WHEN (SUM(spkd.subtotal_price_subcont) - SUM(spkd.remain)) IS NULL THEN 0 ELSE (SUM(spkd.subtotal_price_subcont) - SUM(spkd.remain)) END balance 
            FROM 0_project_spk spk
            INNER JOIN 0_project_spk_details spkd ON (spkd.spk_id = spk.spk_id)
			WHERE spk.budget_id = $budget_id AND spkd.paid=0";

        return $sql_spk;
    }

    public static function budget_need_approve($user_level, $old_id, $budget_id, $project_code)
    {
        $sql = "SELECT pbd.*, pb.budget_name AS budget_name, pb.amount AS current_budget, pb.rab_amount AS budget_rab, pctg.name AS budget_type, u.name AS requestor, p.code as project_code,p.name AS project_name
                FROM 0_project_budget_details pbd
                LEFT OUTER JOIN 0_project_budgets pb ON (pbd.project_budget_id = pb.project_budget_id)
                LEFT OUTER JOIN 0_projects p ON (pb.project_no = p.project_no)
                LEFT OUTER JOIN 0_project_cost_type_group  pctg ON (pb.budget_type_id = pctg.cost_type_group_id)
                LEFT OUTER JOIN users u ON (pbd.user_req = u.old_id)
                WHERE pbd.jenis_data = 2 AND pbd.status_req = 0 AND YEAR(pbd.tanggal_req) IN (2023,2024)";

        if ($user_level == 999 && $old_id == 1) {
            $sql .= " AND pbd.amount_req > 0";
        } else if ($user_level == 43 && $old_id == 24) {
            $sql .= " AND p.code LIKE '%OFC%'";
        } else if ($user_level == 4 && $old_id == 108) {
            $sql .= " AND p.code NOT LIKE '%OFC%'";
        } else {
            $sql .= " AND pbd.amount_req = -1";
        }

        if ($budget_id > 0)
            $sql .= " AND pb.project_budget_id = $budget_id";

        if ($project_code != '')
            $sql .= " AND p.code LIKE '%$project_code%'";

        $sql .= " GROUP BY project_budget_detail_id ORDER BY pbd.tanggal_req DESC";

        return $sql;
    }

    public static function ca_used_budget_amount($project_budget_id)
    {
        $sql = "SELECT ca.reference AS doc_no, SUM(cad.act_amount) as used_amount,ca.tran_date FROM 0_cashadvance_details cad
                    LEFT JOIN 0_cashadvance ca ON (cad.trans_no = ca.trans_no)
                    WHERE cad.project_budget_id = $project_budget_id AND ca.ca_type_id IN 
                    (
                      SELECT ca_type_id FROM 0_cashadvance_types
                      WHERE type_group_id IN (1,2)
                    ) 
                    AND cad.status_id < 2 AND cad.spk_no = ''
                    GROUP BY cad.trans_no";

        return $sql;
    }

    public static function po_used_budget_amount($project_budget_id)
    {
        $sql = "SELECT po.reference AS doc_no, SUM(pod.unit_price * pod.quantity_ordered * pod.rate)-SUM((pod.unit_price * pod.quantity_ordered * pod.rate) * pod.discount_percent) 
                AS used_amount, po.ord_date 
                FROM 0_purch_order_details pod
                LEFT JOIN 0_purch_orders po ON (pod.order_no = po.order_no)
                WHERE pod.project_budget_id = $project_budget_id
                GROUP BY pod.order_no";

        return $sql;
    }

    public static function gl_used_budget_amount($project_budget_id)
    {
        $code = self::define_projectcode_budget($project_budget_id);
        $sql = "SELECT r.reference AS doc_no, SUM(gl.amount) AS used_amount ,gl.tran_date
                FROM 0_gl_trans gl
                LEFT JOIN 0_refs r ON (gl.type_no = r.id AND r.type =1)
                WHERE gl.type = 1 AND gl.project_code = '$code' AND gl.project_budget_id = $project_budget_id
                GROUP BY gl.type_no";

        return $sql;
    }

    public static function spk_used_budget_amount($project_budget_id)
    {
        $code = self::define_projectcode_budget($project_budget_id);
        $sql = "SELECT spk.spk_no AS doc_no, (SUM(spkd.subtotal_price_subcont) - SUM(spkd.remain)) AS used_amount, spk.created_at AS tran_date FROM 0_project_spk spk
					INNER JOIN 0_project_spk_details spkd ON (spkd.spk_id = spk.spk_id)
					WHERE spk.project_code = '$code' AND spk.budget_id = $project_budget_id AND spkd.paid=0
                    GROUP BY spk.spk_no";

        return $sql;
    }

    public static function tools_used_budget_amount($project_budget_id)
    {
        $sql = "SELECT doc_no, total AS used_amount, close_date FROM 0_project_rent_tools
                WHERE budget_id = $project_budget_id AND total > 0";

        return $sql;
    }

    public static function vehicle_used_budget_amount($project_budget_id)
    {
        $sql = "SELECT vehicle_number AS doc_no, amount AS used_amount, periode FROM 0_project_rent_vehicle
                WHERE budget_id = $project_budget_id AND amount > 0";

        return $sql;
    }

    public static function gl_used_budget_amount_tmp($project_budget_id)
    {
        $code = self::define_projectcode_budget($project_budget_id);
        $sql = "SELECT r.reference AS doc_no, SUM(gl.amount) AS used_amount ,gl.tran_date
                FROM 0_gl_trans_tmp gl
                LEFT JOIN 0_refs r ON (gl.type_no = r.id AND r.type =1)
                WHERE gl.approval = 0 AND gl.type = 1 AND gl.project_code = '$code' AND gl.project_budget_id = $project_budget_id
                GROUP BY gl.type_no";

        return $sql;
    }


    public static function curve_graph_total_budget($year, $project_no)
    {

        $sql = "SELECT SUM(pbd.amount_approve) AS amount, MONTH(pbd.tanggal_req) AS imonth, YEAR(pbd.tanggal_req) AS iyear FROM 0_project_budget_details pbd
                LEFT JOIN 0_project_budgets pb ON (pbd.project_budget_id = pb.project_budget_id)
                WHERE pb.project_no = $project_no AND pbd.status_req = 1 AND YEAR(pbd.tanggal_req) = $year
                GROUP BY MONTH(pbd.tanggal_req), YEAR(pbd.tanggal_req)
                ORDER BY YEAR(pbd.tanggal_req) ,MONTH(pbd.tanggal_req) ASC";

        return $sql;
    }

    public static function actual_cost_po_budget_graph($project_no, $year, $month)
    {
        $sql = "SELECT SUM(pod.unit_price * pod.quantity_ordered * pod.rate)-SUM((pod.unit_price * pod.quantity_ordered * pod.rate) * pod.discount_percent) 
                AS used_amount
                FROM 0_purch_order_details pod
                LEFT JOIN 0_purch_orders po ON (pod.order_no = po.order_no)
                LEFT OUTER JOIN 0_projects p ON (pod.project_no = p.project_no)
                WHERE p.project_no = $project_no AND YEAR(po.ord_date) = $year AND MONTH(po.ord_date) = $month";

        return $sql;
    }

    public static function actual_cost_ca_budget_graph($project_no, $year, $month)
    {
        $sql = "SELECT SUM(cad.amount) AS used_amount FROM 0_cashadvance_details cad
                LEFT JOIN 0_cashadvance ca ON (cad.trans_no = ca.trans_no)
                LEFT OUTER JOIN 0_projects p ON (cad.project_no = p.project_no)
                WHERE p.project_no = $project_no AND cad.status_id < 2  AND YEAR(ca.tran_date) = $year AND MONTH(ca.tran_date) = $month";

        return $sql;
    }

    public static function actual_cost_gl_budget_graph($code, $year, $month)
    {
        $sql = "SELECT SUM(gl.amount) AS used_amount
                FROM 0_gl_trans gl
                LEFT OUTER JOIN 0_projects p ON (gl.project_code = p.code OR gl.project_no = p.project_no)
                WHERE gl.type = 1 AND gl.amount > 0 AND gl.project_code = '$code' AND YEAR(gl.tran_date) = $year AND MONTH(gl.tran_date) = $month";

        return $sql;
    }

    public static function act_cost_prev_total($inactive, $code, $year)
    {
        $data = [];
        $get_project_no = ProjectListController::list($inactive, $code);
        $encode_data = json_decode(json_encode($get_project_no), true);
        $get_data = collect($encode_data['original']['data'])
            ->all();

        $project_no =  $get_data[0]['project_no'];
        $next_year = $year + 1;
        $sql_po = "SELECT SUM(pod.unit_price * pod.quantity_ordered * pod.rate)-SUM((pod.unit_price * pod.quantity_ordered * pod.rate) * pod.discount_percent) 
                AS used_amount
                FROM 0_purch_order_details pod
                LEFT JOIN 0_purch_orders po ON (pod.order_no = po.order_no)
                LEFT OUTER JOIN 0_projects p ON (pod.project_no = p.project_no)
                WHERE p.project_no = $project_no AND YEAR(po.ord_date) < $year";
        $sql_gl = "SELECT SUM(gl.amount) AS used_amount
                FROM 0_gl_trans gl
                LEFT OUTER JOIN 0_projects p ON (gl.project_code = p.code OR gl.project_no = p.project_no)
                WHERE gl.type = 1 AND gl.amount > 0 AND gl.project_code = '$code' AND YEAR(gl.tran_date) < $year";
        $sql_ca = "SELECT SUM(cad.amount) AS used_amount FROM 0_cashadvance_details cad
                LEFT JOIN 0_cashadvance ca ON (cad.trans_no = ca.trans_no)
                LEFT OUTER JOIN 0_projects p ON (cad.project_no = p.project_no)
                WHERE p.project_no = $project_no AND cad.status_id < 2  AND YEAR(ca.tran_date) < $year";

        // $data = array($sql_ca, $sql_po, $sql_gl);

        $data['po'] = $sql_po;
        $data['ca'] = $sql_ca;
        $data['gl'] = $sql_gl;

        return $data;
    }

    public static function total_amount_budget($project_no)
    {
        $sql = DB::table('0_project_budgets')->where('project_no', $project_no)
            ->sum('amount');

        return $sql;
    }

    public static function total_amount_rab($project_no)
    {
        $sql = DB::table('0_project_budgets')->where('project_no', $project_no)
            ->sum('rab_amount');

        return $sql;
    }

    public static function total_prev_budget($year, $project_no)
    {
        $next_year = $year + 1;

        $sql = "SELECT SUM(amount) AS amount FROM 0_project_budgets WHERE project_no = $project_no AND YEAR(created_date) < $year";

        return $sql;
    }

    public static function total_cost_budget($project_no)
    {
        $sql = "SELECT
				p.project_no,
				(
					SELECT 
						CASE WHEN SUM(sod.qty_ordered * sod.unit_price) IS NULL THEN 0 ELSE SUM(sod.qty_ordered * sod.unit_price) END 
						FROM 0_sales_orders so
						INNER JOIN 0_sales_order_details sod ON (so.order_no = sod.order_no)		
					WHERE sod.qty_ordered > 0 AND so.project_no=p.project_no    		
				) po_amount,
				(
					SELECT 
						CASE WHEN SUM(pb.amount) IS NULL THEN 0 ELSE SUM(pb.amount) END
					FROM 0_project_budgets pb
					WHERE pb.project_no = p.project_no
				) project_budget,
				(
					SELECT 
						CASE WHEN SUM(cad.act_amount) IS NULL THEN 0 ELSE SUM(cad.act_amount) END
					FROM 0_cashadvance ca 
					INNER JOIN 0_cashadvance_details cad ON (ca.trans_no = cad.trans_no)
					WHERE ca.ca_type_id IN (1,3,8,11,12,13)			
					AND cad.status_id<2 AND cad.project_no=p.project_no
					AND cad.approval=7
				)  ca_amount,
				(
					SELECT 
						CASE WHEN SUM(cad.act_amount) IS NULL THEN 0 ELSE SUM(cad.act_amount) END
					FROM 0_cashadvance ca 
					INNER JOIN 0_cashadvance_details cad ON (ca.trans_no = cad.trans_no)
					WHERE ca.ca_type_id IN (4)			
					AND cad.status_id<2 AND cad.project_no=p.project_no
					AND cad.approval=7
				) rmb_amount,
				(
					SELECT 
						CASE WHEN SUM(amount) IS NULL THEN 0 ELSE SUM(amount) END
					FROM 0_cost_sheet
					WHERE project_code= p.code
					AND amount > 0
					AND TYPE=1 AND ACCOUNT NOT IN ('501012','501006', '601033')
				) bp_2020,
				(
					SELECT 
						CASE WHEN SUM(gl.amount) IS NULL THEN 0 ELSE SUM(gl.amount) END
					FROM 0_gl_trans gl		
							WHERE gl.project_code=p.code
							AND gl.amount > 0
							AND gl.type=1 AND gl.account NOT IN ('501012','501006', '601033')        	 		
				) bp_2021,
				(
					SELECT 
						CASE WHEN SUM(pod.quantity_ordered * pod.unit_price * pod.rate) IS NULL THEN 0 ELSE 
						SUM(pod.quantity_ordered * pod.unit_price * pod.rate) END 
					FROM 0_purch_orders po 
					INNER JOIN 0_purch_order_details pod ON (po.order_no = pod.order_no)
					WHERE po.doc_type_id NOT IN (4008,4009) AND po.status_id=0 AND pod.quantity_ordered > 0
					AND pod.project_code=p.code
				) po,
				(
					SELECT 
						CASE WHEN SUM(-1 * sm.qty * sm.standard_cost) IS NULL THEN 0 ELSE SUM(-1 * sm.qty * sm.standard_cost) END
					FROM 0_stock_moves sm
					WHERE sm.type=15
					AND sm.stock_id IN
					(
						SELECT stock_id FROM 0_stock_master
						WHERE category_id=10
					) AND sm.project_code=p.code
				) stk_atk,
				(
						SELECT 
							CASE WHEN SUM(ps.salary) IS NULL THEN 0 ELSE SUM(ps.salary) END
						FROM 0_project_salary_budget ps
						WHERE ps.project_no=p.project_no
				) salary,
				(
						SELECT 
							CASE WHEN SUM(amount) IS NULL THEN 0 ELSE SUM(amount) END
						FROM 0_cost_sheet 
						WHERE ACCOUNT='501012' 
						AND amount>0 
						AND TYPE IN (1)
						AND project_code=p.code
				) bpd_2020,
				(
						SELECT 				
							CASE WHEN SUM(amount) IS NULL THEN 0 ELSE SUM(amount) END
						FROM 0_gl_trans 
						WHERE ACCOUNT='501012' 
						AND amount>0 
						AND TYPE NOT IN (2003, 2004)
						AND project_code=p.code
				) bpd_2021,
				(
					SELECT 
						CASE WHEN SUM(a.monthly_rate) IS NULL THEN 0 ELSE
						SUM(a.monthly_rate) END
					FROM 0_am_vehicles av
					LEFT JOIN 0_am_vehicle_details avd ON (av.order_no = avd.order_no)
					LEFT JOIN 0_am_assets a ON (avd.vehicle_no = a.asset_name)
					WHERE avd.project_no=p.project_no			
					AND av.is_use_monthly_rate=1
				) vehicle_rental,
				(
					SELECT 
					SUM(
						CASE WHEN i.close_date IS NULL THEN (DATEDIFF(CURDATE(), i.trx_date) * rate) 
						ELSE (DATEDIFF(i.close_date, i.trx_date) * rate) END )			
					FROM 0_am_issues i
						LEFT JOIN 0_am_assets a ON (i.object_id = a.asset_id)
						WHERE i.project_code=p.code
				) tool_laptop,
				(
					SELECT 			
						CASE WHEN SUM(i.ov_discount * i.rate) IS NULL THEN 0 ELSE SUM(i.ov_discount * i.rate) END
					FROM 0_debtor_trans_2020 i			
					LEFT JOIN 0_sales_orders so ON (i.order_ = so.order_no)
					WHERE i.type=10 AND i.ov_discount > 0 AND so.project_no=p.project_no	
				) deduction_2020,
				(
					SELECT 			
						CASE WHEN SUM(i.ov_discount * i.rate) IS NULL THEN 0 ELSE SUM(i.ov_discount * i.rate) END
					FROM 0_debtor_trans i			
					LEFT JOIN 0_sales_orders so ON (i.order_ = so.order_no)
					WHERE i.type=10 AND i.ov_discount > 0 AND so.project_no=p.project_no		
				) deduction_2021,
				mf.rate											
			FROM 0_projects p			
			LEFT JOIN 0_project_management_fee mf ON (mf.id = p.management_fee_id)
			WHERE p.project_no=$project_no";

        return $sql;
    }

    public static function get_first_budget_created_date($project_no)
    {
        $sql = DB::select(DB::raw(
            "SELECT * FROM 0_project_budgets WHERE project_no = $project_no AND created_date IS NOT NULL ORDER BY created_date ASC LIMIT 1"
        ));
        $data = ($sql == null || empty($sql)) ? 0 : 1;

        if ($data == 1) {
            $to_json =  response()->json([
                'success' => true,
                'data' => $sql
            ], 200);


            $get_data = json_decode(json_encode($to_json), true);
            $collect_data = collect($get_data['original']['data'])
                ->all();

            $parse_data = $collect_data[0]['created_date'];
        } else {
            $parse_data = 0;
        }



        return $parse_data;
    }

    public static function cost_detail_usage($project_no)
    {
        $get_project_code = ProjectListController::project($project_no);
        $data_project_code = json_decode(json_encode($get_project_code), true);
        $project_code = collect($data_project_code['original']['data'])
            ->all();
        $code = $project_code[0]['project_code'];
        $management_fee_rate = $project_code[0]['management_fee_rate'];
        $response = [];

        $sql_so = DB::select(DB::raw("SELECT 
        				so.reference AS data,
                        so.ord_date, 
                        CASE WHEN SUM(sod.qty_ordered * sod.unit_price) IS NULL THEN 0 ELSE SUM(sod.qty_ordered * sod.unit_price) END AS amount,
                        GROUP_CONCAT(so.customer_ref SEPARATOR ', ') as description
        				FROM 0_sales_orders so
        				INNER JOIN 0_sales_order_details sod ON (so.order_no = sod.order_no)		
        			WHERE sod.qty_ordered > 0 AND so.project_no=$project_no
                    GROUP BY sod.order_no"));

        foreach ($sql_so as $data_so) {
            $tmp_so = [];
            $tmp_so['doc_type'] = "Management Cost";
            $tmp_so['data'] = $data_so->data;
            $tmp_so['date'] = $data_so->ord_date;
            $tmp_so['amount'] = $data_so->amount * ($management_fee_rate / 100);
            $tmp_so['description'] = $data_so->description;
            array_push($response, $tmp_so);
        }

        $sql_ca = DB::select(DB::raw("SELECT 
        				ca.reference as data,
                        ca.release_date,
                        CASE WHEN SUM(cad.act_amount) IS NULL THEN 0 ELSE SUM(cad.act_amount) END AS amount,
                        CONCAT(e.emp_id, '_', e.name, ' _', GROUP_CONCAT(cad.remark SEPARATOR '&_ ')) AS description
        			FROM 0_cashadvance ca 
        			INNER JOIN 0_cashadvance_details cad ON (ca.trans_no = cad.trans_no)
                    LEFT JOIN 0_hrm_employees e ON (e.id = ca.emp_no)
        			WHERE ca.ca_type_id IN (1,3,8)			
        			AND cad.status_id<2 AND cad.project_no=$project_no
        			AND cad.approval=7
                    GROUP BY cad.trans_no"));
        foreach ($sql_ca as $data_ca) {
            $tmp_ca = [];
            $tmp_ca['doc_type'] = "CA Project";
            $tmp_ca['data'] = $data_ca->data;
            $tmp_ca['date'] = $data_ca->release_date;
            $tmp_ca['amount'] = $data_ca->amount;
            $tmp_ca['description'] = $data_ca->description;
            array_push($response, $tmp_ca);
        }

        $sql_rmb = DB::select(DB::raw("SELECT 
        			ca.reference AS data,
                    ca.release_date,
                    CASE WHEN SUM(cad.act_amount) IS NULL THEN 0 ELSE SUM(cad.act_amount) END AS amount,
                    CONCAT(e.emp_id, '_', e.name, ' _', GROUP_CONCAT(cad.remark SEPARATOR '&_ ')) AS description
        			FROM 0_cashadvance ca 
        			INNER JOIN 0_cashadvance_details cad ON (ca.trans_no = cad.trans_no)
                    LEFT JOIN 0_hrm_employees e ON (e.id = ca.emp_no)
        			WHERE ca.ca_type_id IN (4)			
        			AND cad.status_id<2 AND cad.project_no=$project_no
        			AND cad.approval=7
                    GROUP BY cad.trans_no"));
        foreach ($sql_rmb as $data_rmb) {
            $tmp_rmb = [];
            $tmp_rmb['doc_type'] = "Reimburstment";
            $tmp_rmb['data'] = $data_rmb->data;
            $tmp_rmb['date'] = $data_rmb->release_date;
            $tmp_rmb['amount'] = $data_rmb->amount;
            $tmp_rmb['description'] = $data_rmb->description;
            array_push($response, $tmp_rmb);
        }

        $sql_bp2020 = DB::select(DB::raw("SELECT 
        			reference AS data,
                    tran_date,
                    CASE WHEN SUM(amount) IS NULL THEN 0 ELSE SUM(amount) END AS amount,
                    memo_ as description
        			FROM 0_cost_sheet
        			WHERE project_code= '$code'
        			AND amount > 0
        			AND TYPE=1 AND ACCOUNT NOT IN ('501012','501006', '601033')
                    GROUP BY reference"));
        foreach ($sql_bp2020 as $data_bp2020) {
            $tmp_bp2020 = [];
            $tmp_bp2020['doc_type'] = "Bank Payment 2020";
            $tmp_bp2020['data'] = $data_bp2020->data;
            $tmp_bp2020['date'] = $data_bp2020->tran_date;
            $tmp_bp2020['amount'] = $data_bp2020->amount;
            $tmp_bp2020['description'] = $data_bp2020->description;
            array_push($response, $tmp_bp2020);
        }

        $sql_bp = DB::select(DB::raw("SELECT 
        			r.reference AS data, 
                    gl.tran_date,
                    CASE WHEN SUM(gl.amount) IS NULL THEN 0 ELSE SUM(gl.amount) END AS amount,
                    gl.memo_ AS description
        			FROM 0_gl_trans gl		
                    LEFT OUTER JOIN 0_refs r ON (gl.type_no = r.id)
        					WHERE gl.project_code='$code'
        					AND gl.amount > 0
        					AND gl.type=1 AND gl.account NOT IN ('501012','501006', '601033')
                            GROUP BY gl.type_no"));
        foreach ($sql_bp as $data_bp) {
            $tmp_bp = [];
            $tmp_bp['doc_type'] = "Bank Payment";
            $tmp_bp['data'] = $data_bp->data;
            $tmp_bp['date'] = $data_bp->tran_date;
            $tmp_bp['amount'] = $data_bp->amount;
            $tmp_bp['description'] = $data_bp->description;
            array_push($response, $tmp_bp);
        }
        $sql_po = DB::select(DB::raw("SELECT 
        				po.reference AS data,
                        po.ord_date,
                        CASE WHEN SUM(pod.quantity_ordered * pod.unit_price * pod.rate) IS NULL THEN 0 ELSE 
        				SUM(pod.quantity_ordered * pod.unit_price * pod.rate) END AS amount,
                        CONCAT(s.supp_name, ' _', GROUP_CONCAT(pod.description SEPARATOR '&_ ')) AS description
        			FROM 0_purch_orders po 
        			INNER JOIN 0_purch_order_details pod ON (po.order_no = pod.order_no)
                    LEFT JOIN 0_suppliers s ON (s.supplier_id = po.supplier_id)
        			WHERE po.doc_type_id NOT IN (4008,4009) AND po.status_id=0 AND pod.quantity_ordered > 0
        			AND pod.project_code='$code'
                    GROUP BY pod.order_no"));
        foreach ($sql_po as $data_po) {
            $tmp_po = [];
            $tmp_po['doc_type'] = "Purch Orders";
            $tmp_po['data'] = $data_po->data;
            $tmp_po['date'] = $data_po->ord_date;
            $tmp_po['amount'] = $data_po->amount;
            $tmp_po['description'] = $data_po->description;
            array_push($response, $tmp_po);
        }
        $sql_atk = DB::select(DB::raw("SELECT 
                                            sm.reference AS data,
                                            sm.tran_date, 
                                            CASE WHEN SUM(-1 * sm.qty * sm.standard_cost) IS NULL THEN 0 ELSE SUM(-1 * sm.qty * sm.standard_cost) END AS amount,
                                            GROUP_CONCAT(sm.stock_id SEPARATOR '&_') AS description
                                       FROM 0_stock_moves sm
                                       WHERE sm.type=15
                                       AND sm.stock_id IN
                                       (
                                            SELECT stock_id FROM 0_stock_master
                                            WHERE category_id=10
                                       ) AND sm.project_code='$code'
                                       GROUP BY sm.trans_no"));
        foreach ($sql_atk as $data_atk) {
            $tmp_atk = [];
            $tmp_atk['doc_type'] = "ATK";
            $tmp_atk['data'] = $data_atk->data;
            $tmp_atk['date'] = $data_atk->tran_date;
            $tmp_atk['amount'] = $data_atk->amount;
            $tmp_atk['description'] = $data_atk->description;
            array_push($response, $tmp_atk);
        }

        $sql_salary = DB::select(DB::raw("SELECT 
                                                ps.date AS data,
                                                ps.date AS tran_date,
                                                CASE WHEN SUM(ps.salary) IS NULL THEN 0 ELSE SUM(ps.salary) END AS amount,
                                                GROUP_CONCAT(ps.project_no SEPARATOR ', ') AS description
                                            FROM 0_project_salary_budget ps
                                            WHERE ps.project_no=$project_no
                                            GROUP BY ps.date"));

        foreach ($sql_salary as $data_salary) {
            $tmp_salary = [];
            $tmp_salary['doc_type'] = "Salary";
            $tmp_salary['data'] = $data_salary->data;
            $tmp_salary['date'] = $data_salary->tran_date;
            $tmp_salary['amount'] = $data_salary->amount;
            $tmp_salary['description'] = $data_salary->description;
            array_push($response, $tmp_salary);
        }

        $sql_bpd2020 = DB::select(DB::raw("SELECT 
                                                reference AS data,
                                                tran_date, 
                                                CASE WHEN SUM(amount) IS NULL THEN 0 ELSE SUM(amount) END AS amount,
                                                memo_ as description
                                           FROM 0_cost_sheet 
                                           WHERE ACCOUNT='501012' 
                                           AND amount>0 
                                           AND TYPE IN (1)
                                           AND project_code='$code'
                                           GROUP BY reference"));

        foreach ($sql_bpd2020 as $data_bpd2020) {
            $tmp_bpd2020 = [];
            $tmp_bpd2020['doc_type'] = "BPD 2020";
            $tmp_bpd2020['data'] = $data_bpd2020->data;
            $tmp_bpd2020['date'] = $data_bpd2020->tran_date;
            $tmp_bpd2020['amount'] = $data_bpd2020->amount;
            $tmp_bpd2020['description'] = $data_bpd2020->description;
            array_push($response, $tmp_bpd2020);
        }

        $sql_bpd = DB::select(DB::raw(
            "SELECT 				
                                            r.reference AS data,
                                            gl.tran_date, 
                                            CASE WHEN SUM(gl.amount) IS NULL THEN 0 ELSE SUM(gl.amount) END AS amount,
                                            GROUP_CONCAT(gl.memo_ SEPARATOR ', ') as description
                                    FROM 0_gl_trans gl 
                                    LEFT OUTER JOIN 0_refs r ON (gl.type_no = r.id)
                                    WHERE gl.account='501012' 
                                    AND gl.amount>0 
                                    AND gl.type NOT IN (2003, 2004)
                                    AND gl.project_code='$code'
                                    GROUP BY gl.type_no"
        ));

        foreach ($sql_bpd as $data_bpd) {
            $tmp_bpd = [];
            $tmp_bpd['doc_type'] = "BPD";
            $tmp_bpd['data'] = $data_bpd->data;
            $tmp_bpd['date'] = $data_bpd->tran_date;
            $tmp_bpd['amount'] = $data_bpd->amount;
            $tmp_bpd['description'] = $data_bpd->description;
            array_push($response, $tmp_bpd);
        }

        $sql_vehicle = DB::select(
            DB::raw(
                "SELECT 
                                                av.reference AS data,
                                                av.ord_date, 
                                                CASE WHEN SUM(a.monthly_rate) IS NULL THEN 0 ELSE SUM(a.monthly_rate) END AS amount,
                                                GROUP_CONCAT(av.reference SEPARATOR ', ') AS description
                                            FROM 0_am_vehicles av
                                            LEFT JOIN 0_am_vehicle_details avd ON (av.order_no = avd.order_no)
                                            LEFT JOIN 0_am_assets a ON (avd.vehicle_no = a.asset_name)
                                            WHERE avd.project_no=$project_no			
                                            AND av.is_use_monthly_rate=1
                                            GROUP BY av.reference"
            )
        );

        foreach ($sql_vehicle as $data_vehicle) {
            $tmp_vehicle = [];
            $tmp_vehicle['doc_type'] = "Vehicle";
            $tmp_vehicle['data'] = $data_vehicle->data;
            $tmp_vehicle['date'] = $data_vehicle->ord_date;
            $tmp_vehicle['amount'] = $data_vehicle->amount;
            $tmp_vehicle['description'] = $data_vehicle->description;
            array_push($response, $tmp_vehicle);
        }

        $sql_laptop = DB::select(DB::raw("SELECT 
                                                i.doc_no AS data,
                                                i.creation_date, 
                                                CASE WHEN i.close_date IS NULL THEN (DATEDIFF(CURDATE(), i.trx_date) * rate) END AS amount,
                                                GROUP_CONCAT(g.name SEPARATOR ', ') AS description	 
                                        FROM 0_am_issues i
                                        LEFT JOIN 0_am_assets a ON (i.object_id = a.asset_id)
                                        LEFT JOIN 0_am_groups g ON (a.group_id = g.group_id)
                                        WHERE i.project_code='$code'
                                        GROUP BY i.doc_no"));
        foreach ($sql_laptop as $data_laptop) {
            $tmp_laptop = [];
            $tmp_laptop['doc_type'] = "TOOLS LAPTOP";
            $tmp_laptop['data'] = $data_laptop->data;
            $tmp_laptop['date'] = $data_laptop->creation_date;
            $tmp_laptop['amount'] = $data_laptop->amount;
            $tmp_laptop['desription'] = $data_laptop->description;

            array_push($response, $tmp_laptop);
        }
        $sql_deduction2020 = DB::select(DB::raw("SELECT 			
                                                    i.reference AS data, 
                                                    i.tran_date,
                                                    CASE WHEN SUM(i.ov_discount * i.rate) IS NULL THEN 0 ELSE SUM(i.ov_discount * i.rate) END AS amount,
                                                    GROUP_CONCAT(i.reference SEPARATOR '&_') AS description
                                                FROM 0_debtor_trans_2020 i			
                                                LEFT JOIN 0_sales_orders so ON (i.order_ = so.order_no)
                                                WHERE i.type=10 AND i.ov_discount > 0 AND so.project_no=$project_no
                                                GROUP BY i.reference"));
        foreach ($sql_deduction2020 as $data_deduction2020) {
            $tmp_deduction2020 = [];
            $tmp_deduction2020['doc_type'] = "Deduction 2020";
            $tmp_deduction2020['data'] = $data_deduction2020->data;
            $tmp_deduction2020['date'] = $data_deduction2020->tran_date;
            $tmp_deduction2020['amount'] = $data_deduction2020->amount;
            $tmp_deduction2020['description'] = $data_deduction2020->description;

            array_push($response, $tmp_deduction2020);
        }

        $sql_deduction = DB::select(DB::raw("SELECT 			
                                                i.reference AS data,
                                                i.tran_date, 
                                                CASE WHEN SUM(i.ov_discount * i.rate) IS NULL THEN 0 ELSE SUM(i.ov_discount * i.rate) END AS amount,
                                                GROUP_CONCAT(i.reference SEPARATOR '&_') AS description
                                            FROM 0_debtor_trans i			
                                            LEFT JOIN 0_sales_orders so ON (i.order_ = so.order_no)
                                            WHERE i.type=10 AND i.ov_discount > 0 AND so.project_no=$project_no
                                            GROUP BY i.reference"));
        foreach ($sql_deduction as $data_deduction) {
            $tmp_deduction = [];
            $tmp_deduction['doc_type'] = "Deduction";
            $tmp_deduction['data'] = $data_deduction->data;
            $tmp_deduction['date'] = $data_deduction->tran_date;
            $tmp_deduction['amount'] = $data_deduction->amount;
            $tmp_deduction['description'] = $data_deduction->description;
            array_push($response, $tmp_deduction);
        }

        return $response;
    }

    public static function define_projectcode_budget($budget_id)
    {
        $sql = "SELECT p.code FROM 0_project_budgets pb
                LEFT JOIN 0_projects p ON (pb.project_no = p.project_no)
                WHERE pb.project_budget_id=$budget_id";
        $exe = DB::select(DB::raw($sql));
        foreach ($exe as $data) {
            return $data->code;
        }
    }


    public static function rab_history($rab_id)
    {

        $sql = "SELECT rabd.id, p.code, rabd.amount,rabd.remark, rabd.description, u.name AS creator, rabd.created_at
                FROM 0_project_budget_rab rab
                LEFT OUTER JOIN 0_project_budget_rab_details rabd ON (rab.id = rabd.rab_id)
                LEFT OUTER JOIN 0_projects p ON (rab.project_no = p.project_no)
                LEFT OUTER JOIN users u ON (rabd.user_id = u.old_id)";

        if ($rab_id > 0) {
            $sql .= " WHERE rab.id = $rab_id";
        } else {
            $sql .= " WHERE rab.id != -1";
        }

        $sql .= " GROUP BY rabd.id ORDER BY rabd.id DESC";


        return $sql;
    }

    public static function check_budget_tmp($budget_id)
    {

        $sql = "SELECT b.*, ((b.budget_amount + if(b.reverse_budget IS NULL, 0, b.reverse_budget))- if(b.ca_amount IS NULL, 0, b.ca_amount) - if(b.po_amount IS NULL, 0, b.po_amount) - if(b.gl_amount IS NULL, 0, b.gl_amount) - if(b.use_atk IS NULL, 0, b.use_atk) - if(b.gl_bank_payment_not_approved_yet IS NULL, 0, b.gl_bank_payment_not_approved_yet) - if(b.use_spk IS NULL, 0, b.use_spk)) AS diff
			FROM
			(
				SELECT pb.amount AS budget_amount,
				(
					SELECT SUM(cd.act_amount)
					FROM 0_cashadvance c
					LEFT JOIN 0_cashadvance_details cd ON (c.trans_no = cd.trans_no)
					WHERE cd.project_budget_id = pb.project_budget_id 
					AND c.ca_type_id IN 
					(
					  SELECT ca_type_id FROM 0_cashadvance_types
					  WHERE type_group_id IN (1,2)
					) 
					and cd.status_id<2
				) AS ca_amount,
				(
					SELECT
						SUM(pod.quantity_ordered * pod.unit_price * pod.rate)
					FROM 0_purch_order_details pod
					INNER JOIN 0_purch_orders po ON (po.order_no = pod.order_no)
					WHERE po.status_id =0 AND pod.project_budget_id = pb.project_budget_id
				) AS po_amount,
				(
					SELECT SUM(gl.amount)
					FROM 0_gl_trans gl
					WHERE gl.project_budget_id = pb.project_budget_id AND gl.type='1'
				) AS gl_amount,
				(
					SELECT SUM(gl.amount)
					FROM 0_gl_trans_tmp gl
					WHERE gl.approval=0 AND gl.project_budget_id = pb.project_budget_id AND gl.type='1'
				) AS gl_bank_payment_not_approved_yet,
				(
					SELECT SUM(amount)
					FROM 0_project_budgets_reverse
					WHERE project_budget_id=pb.project_budget_id
				) AS reverse_budget,
				(
					SELECT SUM(standard_cost) 
					FROM 0_stock_moves sm
					WHERE sm.budget_id = pb.project_budget_id
				) AS use_atk,
				(
					SELECT SUM(spkd.subtotal_price_subcont) FROM 0_project_spk spk
					INNER JOIN 0_project_spk_details spkd ON (spkd.spk_id = spk.spk_id)
					WHERE spk.budget_id = pb.project_budget_id AND spkd.paid=0
				) AS use_spk,
				(
					SELECT SUM(gl.amount)
					FROM 0_gl_trans gl
					WHERE gl.project_budget_id = pb.project_budget_id AND gl.type='8'
				) AS project_deposit
				FROM 0_project_budgets pb
				WHERE pb.project_budget_id=$budget_id
			) b";

        $exe = DB::select(DB::raw($sql));
        foreach ($exe as $data) {
            return $data->diff;
        }
    }


    // public static function view_rab($project_no, $type_name)
    // {
    //     $sql = "SELECT rab.id,
    //             rab.budget_type_id,
    //             pbt.name AS budget_name,
    //             rab.project_no,
    //             p.code AS project_code,
    //             rab.amount,
    //             u.name AS creator,
    //             rab.created_at
    //             FROM 0_project_budget_rab rab
    //             LEFT OUTER JOIN 0_project_cost_type_group pbt ON (rab.budget_type_id = pbt.cost_type_group_id)
    //             LEFT OUTER JOIN 0_projects p ON (rab.project_no = p.project_no)
    //             LEFT OUTER JOIN users u ON (rab.user_id = u.id)
    //             WHERE rab.project_no = $project_no AND rab.amount > 0";

    //     if ($type_name != '') {
    //         $sql .= " AND pbt.name LIKE '%$type_name%'";
    //     }

    //     $sql .= " ORDER BY rab.id DESC";


    //     return $sql;
    // }

    // public static function rab_history($rab_id)
    // {
    //     $sql = "SELECT rabd.id,
    //             rabd.created_at,
    //             rabd.amount,
    //             rabd.remark,
    //             CASE
    //             WHEN rabd.status = 0 THEN 'Need Approval'
    //             WHEN rabd.status = 1 THEN 'Approved'
    //             WHEN rabd.status = 2 THEN 'Disapproved'
    //             END AS status,
    //             CASE
    //             WHEN rabd.type = 0 THEN 'NEW'
    //             WHEN rabd.type = 1 THEN 'Add'
    //             WHEN rabd.type = 2 THEN 'Edit'
    //             END AS type,
    //             u.name AS creator
    //             FROM 0_project_budget_rab_details rabd
    //             LEFT OUTER JOIN users u ON (rabd.created_by = u.id)
    //             WHERE rabd.rab_id = $rab_id";

    //     $sql .= " GROUP BY rabd.id ORDER BY rabd.id DESC";

    //     return $sql;
    // }

    // public static function view_rab_cost($project_no, $type_id)
    // {
    //     $sql = "SELECT SUM(amount) AS cost FROM 0_project_budgets
    //             WHERE project_no = $project_no AND budget_type_id = $type_id";

    //     return $sql;
    // }


    // public static function view_rab_approve($user_id)
    // {
    //     $sql = "SELECT rabd.id,
    //             rabd.rab_id,
    //             rabd.amount,
    //             pbt.name AS budget_name,
    //             p.code AS project_code,
    //             u.name AS creator,
    //             rabd.created_at
    //             FROM 0_project_budget_rab_details rabd
    //             RIGHT JOIN 0_project_budget_rab rab ON (rabd.rab_id = rab.id)
    //             LEFT OUTER JOIN 0_project_cost_type_group pbt ON (rab.budget_type_id = pbt.cost_type_group_id)
    //             LEFT OUTER JOIN 0_projects p ON (rab.project_no = p.project_no)
    //             LEFT OUTER JOIN users u ON (rabd.created_by = u.id)
    //             WHERE rabd.status = 0";

    //     if ($user_id != 1 && $user_id != 50 && $user_id != 848) {
    //         $sql .= " AND rabd.id = -1";
    //     } else {
    //         $sql .= " AND rabd.id > 1";
    //     }

    //     return $sql;
    // }
}
