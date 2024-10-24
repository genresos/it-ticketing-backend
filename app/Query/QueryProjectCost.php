<?php

namespace App\Query;

use App\Query\QueryProjectList;
use Illuminate\Support\Facades\DB;

class QueryProjectCost
{
  public static function get_project_order_info($project_no)
  {
    $sql = "SELECT SUM(xx.order_amount) AS order_amount, 
                SUM(xx.invoice_amount)AS invoice_amount,
                SUM(xx.invoice_paid) AS paid_amount,
                (
                SELECT SUM(amount)
                FROM 0_project_budgets
                WHERE project_no=$project_no
                ) AS budget_amount
                FROM
                (
                SELECT
                sod.id, 
                (sod.qty_ordered * sod.unit_price) AS order_amount,
                (
                SELECT SUM(invd.unit_price * invd.quantity) 
                FROM 0_debtor_trans_details invd
                INNER JOIN 0_debtor_trans inv ON (inv.type = invd.debtor_trans_type AND inv.trans_no = invd.debtor_trans_no)  
                WHERE invd.sales_order_detail_id=sod.id
                AND inv.type=10                                  
                ) invoice_amount,
                (
                SELECT 
                SUM(invd.unit_price * invd.quantity) AS paid_amount
                FROM 0_debtor_trans_details invd
                INNER JOIN 0_debtor_trans inv ON (inv.type = invd.debtor_trans_type AND inv.trans_no = invd.debtor_trans_no)  
                WHERE invd.sales_order_detail_id=sod.id                           
                AND inv.type=10 AND inv.trans_no IN
                (
                SELECT trans_no_to FROM 0_cust_allocations WHERE trans_type_to=10
                )
                ) AS invoice_paid
                FROM 0_sales_orders so
                INNER JOIN 0_sales_order_details sod ON (so.order_no =sod.order_no)
                LEFT JOIN 0_projects p ON (so.order_no = p.project_no)
                LEFT JOIN 0_hrm_divisions hd ON (p.division_id = hd.division_id)
                WHERE so.project_no=$project_no AND sod.qty_ordered > 0) AS xx";

    return $sql;
  }

  public static function get_total_invoice($project_no)
  {
    $sql = "SELECT 
                  SUM(xx_inv.amount) AS amount
                FROM 
                (   
                  SELECT
                    SUM(CASE WHEN (i.ppn_excluded=1) THEN ((i.ov_amount - i.ov_discount)   * i.rate) ELSE ((i.ov_line_amount - i.ov_discount) * i.rate) END) AS amount
                  FROM 0_debtor_trans i 
                  WHERE i.order_ IN
                  ( 
                    SELECT 
                        so.order_no 
                    FROM 0_sales_orders so
                    WHERE so.project_no = $project_no
                  ) AND i.type=10 AND i.is_proforma=0 AND i.ov_amount > 0       
                ) AS xx_inv";

    return $sql;
  }

  public static function get_project_invoice_less_2021($project_no)
  {

    $sql = "SELECT 
                      SUM(xx.invoice_amount) AS invoice_amount,
                      SUM(xx.invoice_paid) AS paid_amount
                FROM
                (
                    SELECT
                    sod.id,                   
                    (
                      SELECT SUM(invd.unit_price * invd.quantity * inv.rate) 
                      FROM 0_debtor_trans_details_2020 invd
                      INNER JOIN 0_debtor_trans_2020 inv ON (inv.type = invd.debtor_trans_type AND inv.trans_no = invd.debtor_trans_no)  
                      WHERE invd.sales_order_detail_id=sod.id
                      AND inv.type=10                                  
                    ) invoice_amount,
                    (
                      SELECT 
                          SUM(invd.unit_price * invd.quantity * inv.rate) AS paid_amount
                      FROM 0_debtor_trans_details_2020 invd
                      INNER JOIN 0_debtor_trans_2020 inv ON (inv.type = invd.debtor_trans_type AND inv.trans_no = invd.debtor_trans_no)  
                      WHERE invd.sales_order_detail_id=sod.id                           
                      AND inv.type=10 AND inv.trans_no IN
                      (
                        SELECT trans_no_to 
                        FROM 0_cust_allocations_2020 
                        WHERE trans_type_to=10
                      )
                    ) AS invoice_paid                  
                    FROM 0_sales_orders so
                    INNER JOIN 0_sales_order_details sod ON (so.order_no =sod.order_no)
                    WHERE so.project_no= $project_no AND sod.qty_ordered > 0
                ) AS xx";

    return $sql;
  }

  public static function get_project_cost_summary_default($project_no)
  {

    $sql = "SELECT 
                    SUM(budget_amount) as budget_amount,
                    SUM(ca_amount + po_amount + gl_amount) as amount
                FROM
                (
                            SELECT pb.project_budget_id, pb.budget_type_id, pb.amount AS budget_amount,
                            (
                                SELECT 
                                    CASE WHEN SUM(cd.act_amount) IS NULL THEN 0 ELSE SUM(cd.act_amount) END amount
                                FROM 0_cashadvance c
                                LEFT JOIN 0_cashadvance_details cd ON (c.trans_no = cd.trans_no)
                                WHERE cd.project_budget_id = pb.project_budget_id AND c.ca_type_id IN (1, 3, 4, 6, 10) AND cd.status_id<2
                            ) AS ca_amount,
                            (
                                SELECT
                                   CASE WHEN SUM(pod.quantity_ordered * pod.unit_price * pod.rate) IS NULL THEN 0 ELSE SUM(pod.quantity_ordered * pod.unit_price * pod.rate) END amount
                                FROM 0_purch_order_details pod
                                INNER JOIN 0_purch_orders po ON (po.order_no = pod.order_no)
                                WHERE po.status_id =0 AND pod.project_budget_id = pb.project_budget_id
                            ) AS po_amount,
                            (
                                SELECT
                                   CASE WHEN SUM(gl.amount) IS NULL THEN 0 ELSE SUM(gl.amount) END amount
                                FROM 0_gl_trans gl
                                WHERE gl.project_budget_id = pb.project_budget_id AND gl.type='1'
                                AND gl.account NOT IN ('501012','501006', '601033')
                            ) AS gl_amount
                            FROM 0_project_budgets pb
                            WHERE pb.project_no=$project_no
                ) xx WHERE xx.budget_type_id NOT IN(49)";

    return $sql;
  }

  public static function get_project_cost_salary_default($project_no)
  {

    $sql = "SELECT  
                    xx.name, 
                    SUM( xx.budget_amount) AS budget_amount, 
                    SUM(xx.cost_amount) AS cost_amount,
                    '' as account_code
                FROM 
                (
                  SELECT 
                    'Salary' AS name,
                    0 AS budget_amount,
                    SUM(ps.salary) AS cost_amount
                  FROM 0_project_salary_budget ps
                  WHERE ps.project_no=$project_no
                  UNION
                  SELECT 
                   'Salary' AS NAME,
                    pb.amount AS budget_amount,
                    0 AS cost_amount
                  FROM 0_project_budgets pb
                  WHERE pb.project_no=$project_no
                  AND pb.budget_type_id=49
                ) xx";

    return $sql;
  }

  public static function get_project_cost_default($project_no)
  {

    $sql = "SELECT xx.budget_type_id, 
                pg.name,
                pg.name,
                (
                  SELECT GROUP_CONCAT(account_code SEPARATOR ',') AS coa
                  FROM 0_project_cost_type
                  WHERE cost_type_group_id=xx.budget_type_id
                ) AS account_code,
                SUM(budget_amount) as budget_amount,
                SUM(ca_amount + po_amount + gl_amount) as cost_amount
                FROM
                (
                            SELECT pb.project_budget_id, pb.budget_type_id, pb.amount AS budget_amount,
                            (
                                SELECT 
                                    CASE WHEN SUM(cd.act_amount) IS NULL THEN 0 ELSE (SUM(cd.act_amount) - (
                                    SELECT SUM(allocate_ear_amount)
                                    FROM 0_cashadvance_stl
                                    WHERE ca_trans_no=c.trans_no
                                    ))
                                     END amount
                                FROM 0_cashadvance c
                                LEFT JOIN 0_cashadvance_details cd ON (c.trans_no = cd.trans_no)
                                WHERE cd.project_budget_id = pb.project_budget_id AND c.ca_type_id IN (1, 3, 4, 6, 10) AND cd.status_id<2
                            ) AS ca_amount,
                            (
                                SELECT
                                   CASE WHEN SUM(pod.quantity_ordered * pod.unit_price * pod.rate) IS NULL THEN 0 ELSE SUM(pod.quantity_ordered * pod.unit_price * pod.rate) END amount
                                FROM 0_purch_order_details pod
                                INNER JOIN 0_purch_orders po ON (po.order_no = pod.order_no)
                                WHERE po.status_id =0 AND pod.project_budget_id = pb.project_budget_id                            
                            ) AS po_amount,
                            (
                                SELECT
                                   CASE WHEN SUM(gl.amount) IS NULL THEN 0 ELSE SUM(gl.amount) END amount
                                FROM 0_gl_trans gl
                                WHERE gl.project_budget_id = pb.project_budget_id AND gl.type='1'                            
                                AND gl.account NOT IN ('501012','501006', '601033')
                            ) AS gl_amount
                            FROM 0_project_budgets pb
                            WHERE pb.project_no= $project_no
                ) xx 
                LEFT JOIN 0_project_cost_type_group pg ON (pg.cost_type_group_id = xx.budget_type_id)
                WHERE pg.cost_type_group_id NOT IN(49)
                GROUP BY xx.budget_type_id";
    return $sql;
  }
  public static function get_project_cost_atk_default($project_no)
  {
    $project_info = DB::select(DB::raw(QueryProjectList::get_project($project_no)));
    $project_code = $project_info[0]->code;
    $sql = "SELECT 
                  'Pemakaian Stock ATK' as name,
                  0 as budget_amount,
                  SUM(-1 * sm.qty * sm.standard_cost) as cost_amount,
                  '' as account_code
                FROM 0_stock_moves sm
                WHERE sm.type=15
                AND sm.stock_id IN
                (
                   SELECT stock_id FROM 0_stock_master
                   WHERE category_id=10
                ) AND sm.project_code= '$project_code'";
    return $sql;
  }

  public static function get_project_cost_rental_vehicle_mobil_default($project_no)
  {

    $sql = "SELECT 
                  'Sewa Kendaraan Internal (Mobil)' as name,
                  0 as budget_amount,
                  SUM((DATEDIFF(av.ord_date, av.to_date) + 1) * a.rate) as cost_amount,
                  '' as account_code
                FROM 0_am_vehicles av
                LEFT JOIN 0_am_vehicle_details avd ON (av.order_no = avd.order_no)
                LEFT JOIN 0_am_assets a ON (avd.vehicle_no = a.asset_name)
                WHERE av.vehicle_type_id=1 AND avd.project_no= $project_no";

    return $sql;
  }

  public static function get_project_cost_rental_vehicle_motor_default($project_no)
  {

    $sql = "SELECT 
                  'Sewa Kendaraan Internal (Motor)' as name,
                  0 as budget_amount,
                  SUM((DATEDIFF(av.ord_date, av.to_date) + 1) * a.rate) as cost_amount,
                  '' as account_code
                FROM 0_am_vehicles av
                LEFT JOIN 0_am_vehicle_details avd ON (av.order_no = avd.order_no)
                LEFT JOIN 0_am_assets a ON (avd.vehicle_no = a.asset_name)
                WHERE av.vehicle_type_id=2 AND avd.project_no= $project_no";


    return $sql;
  }

  public static function get_project_cost_rental_tools_default($project_no)
  {
    $project_info = DB::select(DB::raw(QueryProjectList::get_project($project_no)));
    $project_code = $project_info[0]->code;

    $sql = "SELECT 
                  'Rate Asset Warehouse' as name,
                  0 as budget_amount,
                  SUM(
                      CASE WHEN i.close_date IS NULL THEN (DATEDIFF(CURDATE(), i.trx_date) * rate) 
                      ELSE (DATEDIFF(i.close_date, i.trx_date) * rate) END
                  ) as cost_amount,
                  '' as account_code
                FROM 0_am_issues i
                LEFT JOIN 0_am_assets a ON (i.object_id = a.asset_id)
                WHERE a.type_id=1 AND i.project_code= '$project_code'";

    return $sql;
  }

  public static function get_project_cost_rental_tools_ict_default($project_no)
  {
    $project_info = DB::select(DB::raw(QueryProjectList::get_project($project_no)));
    $project_code = $project_info[0]->code;

    $sql = "SELECT 
                  'Rate Asset ICT' as name,
                  0 as budget_amount,
                  SUM(
                      CASE WHEN i.close_date IS NULL THEN (DATEDIFF(CURDATE(), i.trx_date) * rate) 
                      ELSE (DATEDIFF(i.close_date, i.trx_date) * rate) END
                  ) as cost_amount,
                  '' as account_code
                FROM 0_am_issues i
                LEFT JOIN 0_am_assets a ON (i.object_id = a.asset_id)
                WHERE a.type_id=3 AND i.project_code= '$project_code'";

    return $sql;
  }

  public static function get_project_cost_customer_deduction_default($project_no)
  {
    $sql = "SELECT 
                      xx_deduction.name, 
                      SUM(xx_deduction.budget_amount) as budget_amount, 
                      SUM(xx_deduction.cost_amount) as cost_amount,
                      '' as account_code
                FROM 
                (
                  SELECT      
                    'Customer Deduction' as name,
                    0 as budget_amount,
                    SUM(i.ov_discount * i.rate) AS cost_amount
                  FROM 0_debtor_trans_2020 i     
                  LEFT JOIN 0_sales_orders so ON (i.order_ = so.order_no)
                  WHERE i.type=10 AND i.ov_discount > 0 AND so.project_no= $project_no
                  UNION 
                  SELECT      
                    'Customer Deduction' as name,
                    0 as budget_amount,
                    SUM(i.ov_discount * i.rate) AS cost_amount
                  FROM 0_debtor_trans i     
                  LEFT JOIN 0_sales_orders so ON (i.order_ = so.order_no)
                  WHERE i.type=10 AND i.ov_discount > 0 AND so.project_no= $project_no
                ) xx_deduction";

    return $sql;
  }

  public static function get_start_year($project_no)
  {

    $project_info = DB::select(DB::raw(QueryProjectList::get_project($project_no)));
    $project_code = $project_info[0]->code;

    $sql = "SELECT 
                YEAR(cost.tran_date) AS _year                
                FROM
                (
                    SELECT 
                        1 AS id, 
                        po.ord_date AS tran_date
                    FROM 0_purch_orders po 
                    INNER JOIN 0_purch_order_details pod ON (pod.order_no =po.order_no)
                    WHERE po.doc_type_id NOT IN (4008,4009) AND po.status_id=0 
                    AND pod.project_code='$project_code'
                    UNION ALL
                    SELECT 
                        3 AS id, 
                        ca.tran_date AS tran_date
                    FROM 0_cashadvance ca 
                    INNER JOIN 0_cashadvance_details cad ON (cad.trans_no = ca.trans_no)
                    LEFT JOIN 0_projects p ON (p.project_no = cad.project_no)
                    WHERE ca.ca_type_id IN (1,3,4,8)            
                    AND cad.status_id<2 AND p.code='$project_code'
                    UNION ALL
                    SELECT 
                        4 AS id, 
                        gl.tran_date
                    FROM 0_gl_trans gl
                    WHERE gl.project_code='$project_code'
                    AND gl.amount > 0
                    AND gl.type=1            
                ) cost
                GROUP BY YEAR(cost.tran_date)
                ORDER BY _year ASC";

    return $sql;
  }

  public static function get_start_order_year($project_no)
  {
    $sql = "SELECT
                    YEAR(so.ord_date) AS _year               
                FROM 0_sales_orders so
                INNER JOIN 0_sales_order_details sod ON (so.order_no =sod.order_no)
                WHERE so.project_no= $project_no
                GROUP BY YEAR(so.ord_date)
                ORDER BY _year ASC LIMIT 1";

    return $sql;
    // return $row['_year'];            
  }
  public static function get_invoice_by_month($project_no)
  {

    $sql = "SELECT 
                  xx._year,
                  xx._month,
                  sum(xx.amount) as amount
                FROM
                (                  
                  SELECT 
                    year(inv.tran_date) as _year,
                    month(inv.tran_date) as _month,
                    SUM(invd.unit_price * invd.quantity * inv.rate)  as amount
                  FROM 0_debtor_trans_details invd
                  INNER JOIN 0_debtor_trans inv ON (inv.type = invd.debtor_trans_type AND inv.trans_no = invd.debtor_trans_no)  
                  LEFT JOIN 0_sales_order_details sod ON (sod.id = invd.sales_order_detail_id)
                  INNER JOIN 0_sales_orders so ON (so.order_no = sod.order_no)
                  WHERE inv.type=10 AND so.project_no= $project_no
                  GROUP BY year(inv.tran_date), month(inv.tran_date)
                  UNION  ALL                    
                  SELECT 
                    YEAR(inv.tran_date) AS _year,
                    MONTH(inv.tran_date) AS _month,
                    SUM(invd.unit_price * invd.quantity * inv.rate)  AS amount
                  FROM 0_debtor_trans_details_2020 invd
                  INNER JOIN 0_debtor_trans_2020 inv ON (inv.type = invd.debtor_trans_type AND inv.trans_no = invd.debtor_trans_no)  
                  LEFT JOIN 0_sales_order_details sod ON (sod.id = invd.sales_order_detail_id)
                  INNER JOIN 0_sales_orders so ON (so.order_no = sod.order_no)
                  WHERE inv.type=10 AND so.project_no= $project_no
                  GROUP BY YEAR(inv.tran_date), MONTH(inv.tran_date)
                ) xx GROUP BY _year, _month
                ORDER BY _year, _month";

    return $sql;
  }

  public static function get_cost_monthly($budget_type_id, $project_no)
  {

    $sql = "SELECT 
                      xx.budget_type_id, 
                      _year, 
                      _month, 
                      SUM(xx.amount) AS amount
                FROM 
                (
                    SELECT 
                        pb.project_budget_id, 
                        pb.budget_type_id, 
                        cost._year, 
                        cost._month, 
                        SUM(cost.amount) as amount
                    FROM 0_project_budgets pb
                    LEFT JOIN 
                    (
                             SELECT
                            cd.project_budget_id AS project_budget_id,
                            YEAR(c.tran_date) AS  _year,
                            MONTH(c.tran_date) AS _month,
                              CASE WHEN SUM(cd.act_amount) IS NULL THEN 0 ELSE SUM(cd.act_amount) END amount
                          FROM 0_cashadvance c
                          LEFT JOIN 0_cashadvance_details cd ON (c.trans_no = cd.trans_no)
                          WHERE  c.ca_type_id IN (1, 3, 4, 6, 10) AND cd.status_id<2
                          GROUP BY cd.project_budget_id, YEAR(c.tran_date), MONTH(c.tran_date)
                          UNION  
                             SELECT
                            pod.project_budget_id AS project_budget_id,
                            YEAR(po.ord_date) AS _year,
                            MONTH(po.ord_date) AS _month,
                                                   CASE WHEN SUM(pod.quantity_ordered * pod.unit_price * pod.rate) IS NULL THEN 0 ELSE SUM(pod.quantity_ordered * pod.unit_price * pod.rate) END amount
                                                FROM 0_purch_order_details pod
                                                INNER JOIN 0_purch_orders po ON (po.order_no = pod.order_no)
                                                WHERE po.status_id =0 
                                                GROUP BY pod.project_budget_id, YEAR(po.ord_date), MONTH(po.ord_date)
                      UNION
                            SELECT
                            gl.project_budget_id AS project_budget_id,
                                                    YEAR(gl.tran_date) AS _year,
                                                    MONTH(gl.tran_date) AS _month,
                                                   CASE WHEN SUM(gl.amount) IS NULL THEN 0 ELSE SUM(gl.amount) END amount
                                                FROM 0_gl_trans gl
                                                WHERE gl.type='1'  AND gl.account NOT IN ('501012','501006', '601033')
                                                GROUP BY gl.project_budget_id, YEAR(gl.tran_date), MONTH(gl.tran_date)
                    ) cost ON (  cost.project_budget_id=pb.project_budget_id)      
                    WHERE pb.project_no= $project_no
                    GROUP BY pb.project_budget_id, cost._year, cost._month
                )xx WHERE xx.budget_type_id= $budget_type_id AND xx._year IS NOT NULL
                GROUP BY xx.budget_type_id, xx._year, xx._month
                ORDER BY xx._year, xx._month";

    return $sql;
  }

  public static function check_compare_cost_to_order_over($project_no)
  {
    $sql = "SELECT
				p.project_no,
        (
					SELECT 
						CASE WHEN SUM(pb.rab_amount) IS NULL THEN 0 ELSE SUM(pb.rab_amount) END 
						FROM 0_project_budgets pb	
					WHERE pb.project_no=p.project_no   		
				) rab_amount,
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
					WHERE ca.ca_type_id IN (1,3,8)			
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
			WHERE p.inactive=0 AND p.management_fee_id <> 3
			AND p.debtor_no NOT IN (20) 
			AND p.code NOT IN ('20TRDE010010','20TRDE020010','20TRDE020012')
			AND project_no=$project_no";

    return $sql;
  }

  public static function current_date_project_transaction()
  {
    $sql = "SELECT project_no
            FROM 0_sales_orders
            WHERE ord_date=CURDATE()
            UNION
            SELECT DISTINCT cad.project_no 
            FROM 0_cashadvance_details cad
            INNER JOIN 0_cashadvance ca ON (ca.trans_no = cad.trans_no)
            WHERE ca.tran_date=CURDATE()			
            UNION 
            SELECT DISTINCT pod.project_no FROM 0_purch_order_details pod
            INNER JOIN 0_purch_orders po ON (po.order_no = pod.order_no)
            WHERE po.ord_date=CURDATE()		
            UNION
            SELECT DISTINCT p.project_no FROM 0_gl_trans gl
            LEFT JOIN 0_projects p ON (p.code = gl.project_code)
            WHERE gl.type =1 AND gl.tran_date=CURDATE()";

    return $sql;
  }
}
