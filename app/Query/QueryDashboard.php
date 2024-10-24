<?php

namespace App\Query;

use Illuminate\Support\Facades\DB;

class QueryDashboard
{
  public static function pnl_summary_data(
    $year, $division_id
  ) {

    $sql_division = '';
    if ($division_id != 0)
    $sql_division .= " AND d.division_group_id = $division_id";

    $sql = "SELECT 
                imonth, 
                SUM(-1 * xx.income) AS income, 
                SUM(xx.cost) AS cost
            FROM
            (
                SELECT 
                  MONTH(tran_date) AS imonth,
                  CASE WHEN ctype=4 THEN SUM(0_gl_trans.amount) ELSE 0 END income,
                  CASE WHEN ctype>4 THEN SUM(0_gl_trans.amount) ELSE 0 END cost
                FROM 0_gl_trans
              LEFT OUTER JOIN 0_projects p ON (0_gl_trans.project_code = p.code)
                LEFT OUTER JOIN 0_hrm_divisions d ON (p.division_id = d.division_id),
              0_chart_master AS a, 
                0_chart_types AS t, 
                0_chart_class AS c 
                WHERE account = a.account_code $sql_division
                AND a.account_type = t.id AND t.class_id = c.cid
                AND YEAR(tran_date)=$year AND c.ctype > 3 
                GROUP BY MONTH(tran_date), c.ctype
            ) xx GROUP BY xx.imonth";

    return $sql;
  }

  public static function pnl_by_division_data($year){
    
    $sql = "SELECT 
              xx.division_name, 
              SUM(-1 * xx.income) AS income, 
              SUM(xx.cost) AS cost
            FROM
            (
                SELECT 
                  dg.name as division_name,
                  CASE WHEN ctype=4 THEN SUM(0_gl_trans.amount) ELSE 0 END income,
                  CASE WHEN ctype>4 THEN SUM(0_gl_trans.amount) ELSE 0 END cost
                FROM 0_gl_trans
                LEFT OUTER JOIN 0_projects p ON (0_gl_trans.project_code = p.code)
                LEFT OUTER JOIN 0_hrm_divisions d ON (p.division_id = d.division_id)
                LEFT OUTER JOIN 0_hrm_division_groups dg ON (dg.id = d.division_group_id),
                0_chart_master AS a, 
                0_chart_types AS t, 
                0_chart_class AS c 
                WHERE account = a.account_code
                AND a.account_type = t.id AND t.class_id = c.cid
                AND YEAR(tran_date)=$year AND c.ctype > 3 
                GROUP BY dg.name, c.ctype
            ) xx
            GROUP BY xx.division_name";

  return $sql;
    
  }

  public static function pnl_total_data($year, $division_id)
  {
    $sql_division = '';
    if ($division_id != 0)
    $sql_division .= " AND d.division_group_id = $division_id";

    $sql = "SELECT                   
                SUM(-1 * xx.income) AS income,
                SUM(xx.cost) AS cost    
            FROM
            (
                SELECT
                  CASE WHEN ctype=4 THEN SUM(0_gl_trans.amount) ELSE 0 END income,
                  CASE WHEN ctype>4 THEN SUM(0_gl_trans.amount) ELSE 0 END cost
                FROM 0_gl_trans
                LEFT OUTER JOIN 0_projects p ON (0_gl_trans.project_code = p.code)
                LEFT OUTER JOIN 0_hrm_divisions d ON (p.division_id = d.division_id),
                0_chart_master AS a,
                0_chart_types AS t,
                0_chart_class AS c
                WHERE account = a.account_code $sql_division
                AND a.account_type = t.id AND t.class_id = c.cid
                AND YEAR(tran_date)= $year AND c.ctype > 3   
                GROUP BY c.ctype                  
            ) xx";

    return $sql;
  }
  public static function current_asset_data($year)
	{
	  $sql = "SELECT
            t.name,
            SUM(amount) as amount
            FROM 0_gl_trans,
            0_chart_master AS a,
            0_chart_types AS t,
            0_chart_class AS c
            WHERE account = a.account_code
            AND a.account_type = t.id AND t.class_id = c.cid
            AND c.ctype=1 AND t.parent='1-100000'
            AND 0_gl_trans.tran_date <= '$year'";                
    $sql .= " GROUP BY t.id";

    return $sql;

  }
  
  public static function current_asset_total_data($year)
	{
	    $sql = "SELECT                    
		               SUM(amount) as amount
                    FROM 0_gl_trans,
                    0_chart_master AS a,
                    0_chart_types AS t,
                    0_chart_class AS c
                    WHERE account = a.account_code
                    AND a.account_type = t.id AND t.class_id = c.cid
                    AND c.ctype=1 AND t.parent='1-100000'
                    AND 0_gl_trans.tran_date <= '$year'";
	    
	    return $sql;
	}

  public static function non_current_asset_data($year)
	{
	    $sql = "SELECT
                       t.name,
		               CASE WHEN SUM(amount) < 0 THEN SUM(-1 * amount) ELSE SUM(amount) END AS amount
                    FROM 0_gl_trans,
                    0_chart_master AS a,
                    0_chart_types AS t,
                    0_chart_class AS c
                    WHERE account = a.account_code
                    AND a.account_type = t.id AND t.class_id = c.cid
                    AND c.ctype=1 AND t.parent='1-200000'
                    AND 0_gl_trans.tran_date <= '$year'
                    GROUP BY t.id";
	    return $sql;
	}

  public static function non_current_asset_total_data($year)
	{
	    $sql = "SELECT
		               SUM(amount) as amount
                    FROM 0_gl_trans,
                    0_chart_master AS a,
                    0_chart_types AS t,
                    0_chart_class AS c
                    WHERE account = a.account_code
                    AND a.account_type = t.id AND t.class_id = c.cid
                    AND c.ctype=1 AND t.parent='1-200000'
                    AND 0_gl_trans.tran_date <= '$year'";
	    return $sql;
	}
  
  public static function current_liabilities_data($year)
	{
	  $sql = "SELECT
                t.name,
            CASE WHEN SUM(amount) < 0 THEN SUM(-1 * amount) ELSE SUM(amount) END AS amount
            FROM 0_gl_trans,
            0_chart_master AS a,
            0_chart_types AS t,
            0_chart_class AS c
            WHERE account = a.account_code
            AND a.account_type = t.id AND t.class_id = c.cid
            AND c.ctype=2 AND t.parent='2-100000'
            AND 0_gl_trans.tran_date <= '$year'";           
    $sql .= " GROUP BY t.id";

    return $sql;

  }

  public static function current_liabilities_total_data($year)
	{
	    $sql = "SELECT
		                CASE WHEN SUM(amount) < 0 THEN SUM(-1 * amount) ELSE SUM(amount) END AS amount
                    FROM 0_gl_trans,
                    0_chart_master AS a,
                    0_chart_types AS t,
                    0_chart_class AS c
                    WHERE account = a.account_code
                    AND a.account_type = t.id AND t.class_id = c.cid
                    AND c.ctype=2 AND t.parent='2-100000'
                    AND 0_gl_trans.tran_date <= '$year'";
	    return $sql;
	}


  public static function non_current_liabilities_data($year)
	{
	  $sql = "SELECT
                t.name,
            CASE WHEN SUM(amount) < 0 THEN SUM(-1 * amount) ELSE SUM(amount) END AS amount
            FROM 0_gl_trans,
            0_chart_master AS a,
            0_chart_types AS t,
            0_chart_class AS c
            WHERE account = a.account_code
            AND a.account_type = t.id AND t.class_id = c.cid
            AND c.ctype=2 AND t.parent='2-200000'
            AND 0_gl_trans.tran_date <= '$year'";            
    $sql .= " GROUP BY t.id";

    return $sql;

  }


  public static function non_current_liabilities_total_data($year)
	{
	    $sql = "SELECT
		               CASE WHEN SUM(amount) < 0 THEN SUM(-1 * amount) ELSE SUM(amount) END AS amount
                    FROM 0_gl_trans,
                    0_chart_master AS a,
                    0_chart_types AS t,
                    0_chart_class AS c
                    WHERE account = a.account_code
                    AND a.account_type = t.id AND t.class_id = c.cid
                    AND c.ctype=2 AND t.parent='2-200000'
                    AND 0_gl_trans.tran_date <= '$year'";
	    
	    return $sql;
	}

  
}
