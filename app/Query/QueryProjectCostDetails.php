<?php

namespace App\Query;

class QueryProjectCostDetails
{
    public static function sql_Cost_Details($project_no, $start, $end, $type)
    {

        if ($type == 1) { //actual
            $extend_sql = "SELECT  
                pod.project_no,
                pb.budget_type_id,
                pg.name AS cost_name,
                pod.project_budget_id AS project_budget_id,
                YEAR(po.ord_date) AS  _year,
                MONTH(po.ord_date) AS _month,
                COALESCE(SUM(invd.qty_invd * pod.unit_price * pod.rate), 0) AS amount             
            FROM 0_purch_orders po 
            INNER JOIN 0_purch_order_details pod ON (po.order_no = pod.order_no)
            LEFT JOIN 0_project_budgets pb ON (pod.project_budget_id = pb.project_budget_id)
            LEFT OUTER JOIN 0_project_cost_type_group pg ON (pg.cost_type_group_id = pb.budget_type_id)
            LEFT JOIN
            (
                SELECT invd.po_detail_item_id, SUM(quantity) AS qty_invd, GROUP_CONCAT(inv.trans_no) AS group_of_trans_no
                FROM 0_supp_invoice_items invd
                INNER JOIN 0_supp_trans inv ON (inv.trans_no = invd.supp_trans_no) AND (inv.type = invd.supp_trans_type)            
                WHERE inv.type=20
                GROUP BY invd.po_detail_item_id 
            ) invd ON (invd.po_detail_item_id = pod.po_detail_item)   
            WHERE po.doc_type_id NOT IN (4008,4009) AND po.status_id=0 AND pod.quantity_ordered > 0 AND pod.project_no=$project_no AND pb.budget_type_id NOT IN (72,704,701,703,706,63,55,33,69,52,714)
            GROUP BY pod.project_budget_id, YEAR(po.ord_date), MONTH(po.ord_date)
            UNION";
        } else if ($type == 0) { //commited
            $extend_sql = "SELECT 
                pod.project_no,
                pb.budget_type_id,
                pg.name AS cost_name,
                pod.project_budget_id AS project_budget_id,
                YEAR(po.ord_date) AS  _year,
                MONTH(po.ord_date) AS _month,
                CASE WHEN SUM(pod.quantity_ordered * pod.unit_price * pod.rate) IS NULL THEN 0 ELSE 
                SUM(pod.quantity_ordered * pod.unit_price * pod.rate) END amount
            FROM 0_purch_orders po 
            INNER JOIN 0_purch_order_details pod ON (po.order_no = pod.order_no)
            LEFT JOIN 0_project_budgets pb ON (pod.project_budget_id = pb.project_budget_id)
            LEFT OUTER JOIN 0_project_cost_type_group pg ON (pg.cost_type_group_id = pb.budget_type_id)
            WHERE po.doc_type_id NOT IN (4008,4009) AND po.status_id=0 AND pod.quantity_ordered > 0
            AND pod.project_no=$project_no AND pb.budget_type_id NOT IN (72,704,701,703,706,63,55,33,69,52,714)
            GROUP BY pod.project_budget_id, YEAR(po.ord_date), MONTH(po.ord_date) 
            UNION";
        }

        // all query
        $sql = "SELECT 
        xx.project_no, 
        xx.budget_type_id, 
        REPLACE(xx.cost_name, 'Biaya ', '') AS cost_name,
        xx.project_budget_id,
        _year, 
        _month, 
        SUM(xx.amount) AS amount
        
    FROM 
    (
        $extend_sql
        
        SELECT
            cd.project_no,
            pb.budget_type_id,
            pg.name AS cost_name,
            cd.project_budget_id AS project_budget_id,
            YEAR(c.tran_date) AS  _year,
            MONTH(c.tran_date) AS _month,
            CASE WHEN SUM(cd.act_amount) IS NULL THEN 0 ELSE SUM(cd.act_amount) END amount
        FROM 0_cashadvance c
        LEFT JOIN 0_cashadvance_details cd ON (c.trans_no = cd.trans_no)
        LEFT JOIN 0_project_budgets pb ON (cd.project_budget_id = pb.project_budget_id)
        LEFT OUTER JOIN 0_project_cost_type_group pg ON (pg.cost_type_group_id = pb.budget_type_id)
        WHERE  c.ca_type_id IN 
        (
            SELECT ca_type_id FROM 0_cashadvance_types
            WHERE type_group_id IN (1,2)
        ) AND cd.status_id<2 AND cd.approval >= 7 AND cd.project_no=$project_no AND pb.budget_type_id NOT IN (72,704,701,703,706,63,55,33,69,52,714)
        GROUP BY cd.project_budget_id, YEAR(c.tran_date), MONTH(c.tran_date) 
        UNION
        SELECT 
            cad.project_no,
            pb.budget_type_id,
            pg.name AS cost_name,
            cad.project_budget_id AS project_budget_id,
            YEAR(ca.tran_date) AS  _year,
            MONTH(ca.tran_date) AS _month,
            CASE WHEN SUM(cad.act_amount) IS NULL THEN 0 ELSE SUM(cad.act_amount) END amount
        FROM 0_cashadvance ca 
        INNER JOIN 0_cashadvance_details cad ON (ca.trans_no = cad.trans_no)
        LEFT JOIN 0_project_budgets pb ON (cad.project_budget_id = pb.project_budget_id)
        LEFT OUTER JOIN 0_project_cost_type_group pg ON (pg.cost_type_group_id = pb.budget_type_id)
        WHERE ca.ca_type_id = 4	AND cad.status_id<2 AND cad.approval=7 AND cad.project_no=$project_no AND pb.budget_type_id NOT IN (72,704,701,703,706,63,55,33,69,52,714)
        GROUP BY cad.project_budget_id, YEAR(ca.tran_date), MONTH(ca.tran_date) 
        UNION
        SELECT 
            p.project_no,
            pb.budget_type_id,
            pg.name AS cost_name,
            gl.project_budget_id AS project_budget_id,
            YEAR(gl.tran_date) AS  _year,
            MONTH(gl.tran_date) AS _month,
            CASE WHEN SUM(gl.amount) IS NULL THEN 0 ELSE SUM(gl.amount) END amount
        FROM 0_gl_trans gl		
        LEFT JOIN 0_projects p ON (p.code = gl.project_code)
        LEFT JOIN 0_project_budgets pb ON (gl.project_budget_id = pb.project_budget_id)
        LEFT OUTER JOIN 0_project_cost_type_group pg ON (pg.cost_type_group_id = pb.budget_type_id)
        WHERE p.project_no =$project_no AND pb.budget_type_id NOT IN (72,704,701,703,706,63,55,33,69,52,714)
        AND gl.amount > 0 AND gl.type=1 
        GROUP BY gl.project_budget_id, YEAR(gl.tran_date), MONTH(gl.tran_date) 
        UNION
        SELECT 
            ps.project_no,
            pb.budget_type_id,
            pg.name AS cost_name,
            ps.budget_id AS project_budget_id,
            YEAR(ps.date) AS  _year,
            MONTH(ps.date) AS _month,
            CASE WHEN SUM(ps.salary) IS NULL THEN 0 ELSE SUM(ps.salary) END amount
        FROM 0_project_salary_budget ps
        LEFT JOIN 0_project_budgets pb ON (ps.budget_id = pb.project_budget_id)
        LEFT OUTER JOIN 0_project_cost_type_group pg ON (pg.cost_type_group_id = pb.budget_type_id)
        WHERE ps.project_no=$project_no AND pb.budget_type_id NOT IN (72,704,701,703,706,63,55,33,69,52,714)
        GROUP BY ps.budget_id, YEAR(ps.date), MONTH(ps.date)
        UNION
        SELECT 
            pj.project_no,
            pb.budget_type_id,
            pg.name AS cost_name,
            prv.budget_id AS project_budget_id,
            YEAR(prv.periode) AS  _year,
            MONTH(prv.periode) AS _month,
        CASE WHEN SUM(prv.amount) IS NULL THEN 0 ELSE SUM(prv.amount) END amount
        FROM 0_project_rent_vehicle prv
        LEFT JOIN 0_project_budgets pb ON (prv.budget_id = pb.project_budget_id)
        LEFT OUTER JOIN 0_project_cost_type_group pg ON (pg.cost_type_group_id = pb.budget_type_id)
        INNER JOIN 0_projects pj on (pj.code = prv.project_code)
        WHERE pj.project_no = $project_no AND pb.budget_type_id NOT IN (72, 704, 701, 703, 706, 63, 55, 33, 69,52,714) 
        GROUP BY prv.budget_id, YEAR(prv.periode), MONTH(prv.periode)
        UNION
        SELECT 
            pj.project_no,
            pb.budget_type_id,
            pg.name AS cost_name,
            prt.budget_id AS project_budget_id,
            YEAR(prt.tran_date) AS  _year,
            MONTH(prt.tran_date) AS _month,
        CASE WHEN SUM(prt.total) IS NULL THEN 0 ELSE SUM(prt.total) END amount
        FROM 0_project_rent_tools prt
        LEFT JOIN 0_project_budgets pb ON prt.budget_id = pb.project_budget_id
        LEFT OUTER JOIN 0_project_cost_type_group pg ON (pg.cost_type_group_id = pb.budget_type_id)
        INNER JOIN 0_projects pj ON (pj.project_no = pb.project_no)
        WHERE pj.project_no = $project_no AND pb.budget_type_id NOT IN (72, 704, 701, 703, 706, 63, 55, 33, 69,52,714) 
        GROUP BY prt.budget_id, YEAR(prt.tran_date), MONTH(prt.tran_date)
    )xx WHERE xx._year IS NOT NULL
    GROUP BY xx.budget_type_id, xx._year, xx._month
    ORDER BY xx.cost_name";

        return $sql;
    }
}
