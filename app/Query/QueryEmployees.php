<?php

namespace App\Query;

use App\Query\QueryProjectList;
use Illuminate\Support\Facades\DB;
use Auth;
use JWTAuth;

class QueryEmployees
{
  public static function employees(
    $emp_id,
    $emp_name,
    $client_id,
    $division_id,
    $position_id,
    $location_id,
    $type_id,
    $status_id
  ) {
    $sql = "SELECT
              c.name AS company_name,
              e.emp_id,
              e.name AS emp_name,
              e.join_date,
              d.name AS division_name,
              l.name AS position_name,
              a.name AS location_name,	
              t.name AS type_name,
              e.id,	
              e.inactive	
            FROM 0_hrm_employees e
            LEFT JOIN 0_clients c ON (e.client_id = c.client_id)
            LEFT JOIN 0_hrm_divisions d ON (e.division_id = d.division_id)
            LEFT JOIN 0_hrm_employee_levels l ON (l.level_id = e.level_id)			
            LEFT JOIN 0_project_area a ON (e.area_id = a.area_id)
            LEFT JOIN 0_hrm_employee_types t ON (t.employee_type_id = e.employee_type_id)
            WHERE e.id != -1";

    if ($emp_id != '') {
      $sql .= " AND e.emp_id = '$emp_id'";
    }

    if ($emp_name != '') {
      $sql .= " AND e.name LIKE '%$emp_name%'";
    }

    if ($client_id != 0) {
      $sql .= " AND e.client_id=$client_id";
    }

    if ($division_id != 0) {
      $sql .= " AND e.division_id=$division_id";
    }

    if ($position_id != 0) {
      $sql .= " AND e.level_id=$position_id";
    }

    if ($location_id != 0) {
      $sql .= " AND e.area_id=$location_id";
    }

    if ($type_id != 0) {
      $sql .= " AND e.employee_type_id=$type_id";
    }


    if ($status_id != -1) {
      $sql .= " AND e.inactive=$status_id";
    }
    $sql .= " ORDER BY e.id DESC";

    return $sql;
  }

  public static function employee_details(
    $emp_no
  ) {
    $sql = "SELECT
              c.name AS company_name,
              e.emp_id,
              e.name AS emp_name,
              e.join_date,
              d.name AS division_name,
              l.name AS position_name,
              a.name AS location_name,	
              t.name AS type_name,
              e.id,	
              e.inactive	
            FROM 0_hrm_employees e
            LEFT JOIN 0_clients c ON (e.client_id = c.client_id)
            LEFT JOIN 0_hrm_divisions d ON (e.division_id = d.division_id)
            LEFT JOIN 0_hrm_employee_levels l ON (l.level_id = e.level_id)			
            LEFT JOIN 0_project_area a ON (e.area_id = a.area_id)
            LEFT JOIN 0_hrm_employee_types t ON (t.employee_type_id = e.employee_type_id)
            WHERE e.id = $emp_no";
    return $sql;
  }

  public static function hardware_issue($emp_id)
  {
    $sql = "SELECT i.issue_id, i.doc_no, s.name AS status_name, i.trx_date, i.close_date, 
            a.asset_name, g.name as group_name, i.issue_description, i.accesories,  
            e.emp_id, e.name AS assignee,
            CASE WHEN (i.project_code='') THEN so.project_code ELSE i.project_code END AS project_code,
            u.real_name, i.creation_date, i.object_id, a.asset_name, i.approval_status, i.issue_flag, 
            i.debtor_no, i.issue_status
            FROM 0_am_issues i
            LEFT OUTER JOIN 0_am_issue_type it ON (i.issue_type = it.issue_type_id)
            LEFT OUTER JOIN 0_am_status s ON (s.asset_status_id = i.issue_status)
            LEFT OUTER JOIN 0_am_assets a ON (a.asset_id = i.object_id)
            LEFT OUTER JOIN 0_am_groups g ON (a.group_id = g.group_id)
            LEFT OUTER JOIN 0_hrm_employees e ON (i.issue_assignee = e.emp_id)
            LEFT OUTER JOIN 0_users u ON (i.creator = u.id)
            LEFT OUTER JOIN 0_sales_orders so ON (i.order_ref = so.order_no)
            LEFT OUTER JOIN 0_projects pj ON (i.project_code = pj.code)		
            WHERE i.inactive=0 AND a.type_id=3 AND i.issue_assignee = '$emp_id'";

    return $sql;
  }

  public static function tools_issue($emp_id)
  {
    $sql = "SELECT i.issue_id, i.doc_no, i.issue_name, s.name AS status_name,
            i.trx_date, i.due_date, i.close_date, a.asset_name, g.name as group_name, i.issue_description, i.accesories,
            dm.name AS customer, e.emp_id, e.name AS assignee,
            CASE WHEN (i.project_code='') THEN so.project_code ELSE i.project_code END AS project_code,
            u.real_name,i.creation_date, i.object_id, a.asset_name, i.approval_status, i.issue_flag, i.debtor_no
            FROM 0_am_issues i
            LEFT OUTER JOIN 0_am_issue_type it ON (i.issue_type = it.issue_type_id)
            LEFT OUTER JOIN 0_am_status s ON (s.asset_status_id = i.issue_status)
            LEFT OUTER JOIN 0_am_assets a ON (a.asset_id = i.object_id)
            LEFT OUTER JOIN 0_am_groups g ON (a.group_id = g.group_id)
            LEFT OUTER JOIN 0_hrm_employees e ON (i.issue_assignee = e.emp_id)
            LEFT OUTER JOIN 0_users u ON (i.creator = u.id)
            LEFT OUTER JOIN 0_sales_orders so ON (i.order_ref = so.order_no)
            LEFT OUTER JOIN 0_debtors_master dm ON (i.debtor_no = dm.debtor_no)
            LEFT OUTER JOIN 0_projects pj ON (i.project_code = pj.code)
            WHERE i.inactive=0 AND a.type_id != 3 AND i.issue_assignee = '$emp_id'";

    return $sql;
  }

  public static function cashadvance_issue($emp_id)
  {
    $sql = "SELECT
				ca.trans_no,
				CASE WHEN (av.cashadvance_ref >=1) THEN CONCAT_ws('','CA Carpool',av.order_no,av.reference) ELSE cat.short_name END AS doc_type_name,
				ca.reference,
				ca.tran_date,
				ca.emp_id,
                em.name AS EmployeeName,
				d.name as division_name,
				ca.amount as amount,
				ca.approval_amount as approval_amount,
				ca.release_amount as release_amount,
				ca.release_date as release_date,
                GROUP_CONCAT(stl.reference SEPARATOR ', ') AS stl_reference,
                stl.tran_date AS stl_date,
				SUM(stl.amount) as settlement_amount,
				SUM(stl.approval_amount) as settlement_approval_amount,
				if(stl.is_allocate_to_cash=1,(-1 * stl.diff_amount),0) as allocate_to_cash,
                stl.allocate_ear_amount,
                stl.allocate_ear_date,
				ca.approval,
				ca.status_id as openclosestatus,
                if(stl.approval < 7, concat(datediff(CURDATE(), stl.tran_date), ' Day(s)'),0),
                b.bank_account_name as cash_account,
				ca.approval_description,
                stl.approval as openclosestatus_stl,
                stl.approval as approval_stl,
				ca.bank_account_no,
				ca.ca_type_id,
				ca.emp_no,
				stl.trans_no as stl_trans_no,
				CASE WHEN (av.cashadvance_ref >=1) THEN 'CA Carpool' ELSE cat.short_name END AS doc_type_name_2,
                m.name AS project_manager, ca.vat_no as vat_no, ca.vat_amount as vat_amount_ca";

    $sql .= " FROM 0_cashadvance ca";

    $sql .= " LEFT OUTER JOIN 0_hrm_employees em ON (ca.emp_no = em.id)
			 LEFT OUTER JOIN 0_projects prj ON (prj.project_no = ca.project_no)
			 LEFT OUTER JOIN 0_hrm_divisions d ON (prj.division_id = d.division_id)
			 LEFT OUTER JOIN 0_cashadvance_types cat ON (ca.ca_type_id = cat.ca_type_id)
			 LEFT OUTER JOIN 0_attachments at ON (at.trans_no = ca.trans_no)
			 LEFT OUTER JOIN 0_am_vehicles av ON (av.cashadvance_ref = ca.reference)
			 LEFT OUTER JOIN 0_cashadvance_stl stl ON (ca.trans_no = stl.ca_trans_no)
			 LEFT OUTER JOIN 0_members m ON (m.person_id = prj.person_id)
			 LEFT OUTER JOIN 0_bank_accounts b ON (b.id = ca.bank_account_no)
       WHERE ca.active=1 AND ca.emp_id='$emp_id'";

    $sql .= " GROUP BY ca.trans_no ORDER BY ca.tran_date DESC, ca.reference";

    return $sql;
  }

  public static function show_ec($need_check, $status, $from_date, $to_date, $emp_id, $emp_name)
  {
    if ($need_check == 1) {
      $xx = 'LEFT OUTER JOIN 0_members m ON (ec.person_id = m.person_id)';
    } else {
      $xx = 'INNER JOIN 0_members m ON (ec.person_id = m.person_id)';
    }
    $sql = "SELECT
            ec.id,
            ec.emp_id,
            ec.emp_name,
            d.name AS division_name,
            eml.level_id AS level_id,
            eml.name AS level_name,
            ec.join_date,
            ec.due_date,
            ec.reason_id,
            ecr.name AS reason,
            ec.status AS status_id,
            m.name AS pm_name,
            CASE
            WHEN ec.status = 0 THEN 'New'
            WHEN ec.status = 1 THEN 'Open'
            WHEN ec.status = 2 THEN 'Close'
            WHEN ec.status = 3 THEN 'Pot. Gaji'
            END AS ec_status,
            ec.deduction AS deduction,
            ec.last_date,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, 4 ,1) END AS dept_terkait
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 7 AND ech.pic = 1
            ORDER BY ech.id DESC LIMIT 1
            )dept_terkait,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, 4 ,1) END AS dept_head_terkait
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 7 AND ech.pic = 2
            ORDER BY ech.id DESC LIMIT 1
            )dept_head_terkait,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, 4 ,1) END AS am_admin
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 1 AND ech.pic = 1
            ORDER BY ech.id DESC LIMIT 1
            )am_admin,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, 4 ,1) END AS am_admin
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 10 AND ech.pic = 1
            ORDER BY ech.id DESC LIMIT 1
            )ict_admin,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, 4 ,1) END AS am_admin
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 10 AND ech.pic = 2
            ORDER BY ech.id DESC LIMIT 1
            )ict_head,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, 4 ,1) END AS am_dept
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 1 AND ech.pic = 2
            ORDER BY ech.id DESC LIMIT 1
            )am_dept,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, 4 ,1) END AS ga_admin
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 2 AND ech.pic = 1
            ORDER BY ech.id DESC LIMIT 1
            )ga_admin,	
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, 4 ,1) END AS ga_dept
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 2 AND ech.pic = 2
            ORDER BY ech.id DESC LIMIT 1
            )ga_dept,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, 4 ,1) END AS fa_admin
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 3 AND ech.pic = 1
            ORDER BY ech.id DESC LIMIT 1
            )fa_admin,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, 4 ,1) END AS fa_dept
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 3 AND ech.pic = 2
            ORDER BY ech.id DESC LIMIT 1
            )fa_dept,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, 4 ,1) END AS pc_admin
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 4 AND ech.pic = 1
            ORDER BY ech.id DESC LIMIT 1
            )pc_admin,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, 4 ,1) END AS pc_dept
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 4 AND ech.pic = 2
            ORDER BY ech.id DESC LIMIT 1
            )pc_dept,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, 4 ,1) END AS hr_admin
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 9 AND ech.pic = 2
            ORDER BY ech.id DESC LIMIT 1
            )hr_rec,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, 4 ,1) END AS hr_admin
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 6 AND ech.pic = 1
            ORDER BY ech.id DESC LIMIT 1
            )hr_admin,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, 4 ,1) END AS payroll
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 8 AND ech.pic = 2
            ORDER BY ech.id DESC LIMIT 1
            )hr_payroll,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, 4 ,1) END AS hr_dept
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 6 AND ech.pic = 2
            ORDER BY ech.id DESC LIMIT 1
            )hr_dept,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, 4 ,1) END AS fa_dir
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 5 AND ech.pic = 2
            ORDER BY ech.id DESC LIMIT 1
            )fa_dir,
            ec.created_at,
            u.name AS created_by
            FROM 0_hrm_exit_clearences ec
            LEFT OUTER JOIN 0_hrm_ec_reasons ecr ON (ecr.id = ec.reason_id)
            INNER JOIN 0_hrm_employees em ON (ec.emp_id = em.emp_id)
            LEFT OUTER JOIN 0_hrm_employee_levels eml ON (em.level_id = eml.level_id)
            LEFT OUTER JOIN 0_hrm_divisions d ON (d.division_id = ec.division_id)
            INNER JOIN users u ON (ec.created_by = u.id)
            $xx
            WHERE ec.deleted_by = 0";

    if ($need_check == 0) {
      $sql .= " AND DATE(ec.created_at) BETWEEN '$from_date' AND '$to_date'";
      if ($status == 0) {
        $sql .= " AND ec.status > 0";
      } else if ($status > 0) {
        $sql .= " AND ec.status = $status";
      }
    } else if ($need_check == 1) {
      $sql .= " AND ec.person_id = -1";
    }
    if ($emp_id != '') {
      $sql .= " AND ec.emp_id = '$emp_id'";
    }

    if ($emp_name != '') {
      $sql .= " AND ec.emp_name LIKE '%$emp_name%'";
    }

    $sql .= " GROUP BY ec.id ORDER BY ec.id DESC LIMIT 120";

    return $sql;
  }

  public static function summary_exit_clearences($need_check, $status, $from_date, $to_date, $emp_id, $emp_name)
  {
    if ($need_check == 1) {
      $xx = 'LEFT OUTER JOIN 0_members m ON (ec.person_id = m.person_id)';
    } else {
      $xx = 'INNER JOIN 0_members m ON (ec.person_id = m.person_id)';
    }
    $sql = "SELECT
            ec.id,
            ec.emp_id,
            ec.emp_name,
            d.name AS division_name,
            eml.level_id AS level_id,
            eml.name AS level_name,
            ec.join_date,
            ec.due_date,
            ec.reason_id,
            ecr.name AS reason,
            ec.status AS status_id,
            m.name AS pm_name,
            CASE
            WHEN ec.status = 0 THEN 'New'
            WHEN ec.status = 1 THEN 'Open'
            WHEN ec.status = 2 THEN 'Close'
            WHEN ec.status = 3 THEN 'Pot. Gaji'
            END AS ec_status,
            ec.deduction AS deduction,
            ec.last_date,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, CONCAT_WS(' ', 'Pending', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y')) ,CONCAT_WS(' ', 'Approved', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y'))) END AS dept_terkait
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 7 AND ech.pic = 1
            ORDER BY ech.id DESC LIMIT 1
            )dept_terkait,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, CONCAT_WS(' ', 'Pending', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y')) ,CONCAT_WS(' ', 'Approved', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y'))) END AS dept_head_terkait
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 7 AND ech.pic = 2
            ORDER BY ech.id DESC LIMIT 1
            )dept_head_terkait,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, CONCAT_WS(' ', 'Pending', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y')) ,CONCAT_WS(' ', 'Approved', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y'))) END AS am_admin
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 1 AND ech.pic = 1
            ORDER BY ech.id DESC LIMIT 1
            )am_admin,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, CONCAT_WS(' ', 'Pending', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y')) ,CONCAT_WS(' ', 'Approved', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y'))) END AS am_dept
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 1 AND ech.pic = 2
            ORDER BY ech.id DESC LIMIT 1
            )am_dept,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, CONCAT_WS(' ', 'Pending', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y')) ,CONCAT_WS(' ', 'Approved', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y'))) END AS ga_admin
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 2 AND ech.pic = 1
            ORDER BY ech.id DESC LIMIT 1
            )ga_admin,	
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, CONCAT_WS(' ', 'Pending', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y')) ,CONCAT_WS(' ', 'Approved', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y'))) END AS ga_dept
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 2 AND ech.pic = 2
            ORDER BY ech.id DESC LIMIT 1
            )ga_dept,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, CONCAT_WS(' ', 'Pending', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y')) ,CONCAT_WS(' ', 'Approved', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y'))) END AS fa_admin
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 3 AND ech.pic = 1
            ORDER BY ech.id DESC LIMIT 1
            )fa_admin,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, CONCAT_WS(' ', 'Pending', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y')) ,CONCAT_WS(' ', 'Approved', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y'))) END AS fa_dept
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 3 AND ech.pic = 2
            ORDER BY ech.id DESC LIMIT 1
            )fa_dept,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, CONCAT_WS(' ', 'Pending', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y')) ,CONCAT_WS(' ', 'Approved', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y'))) END AS pc_admin
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 4 AND ech.pic = 1
            ORDER BY ech.id DESC LIMIT 1
            )pc_admin,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, CONCAT_WS(' ', 'Pending', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y')) ,CONCAT_WS(' ', 'Approved', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y'))) END AS pc_dept
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 4 AND ech.pic = 2
            ORDER BY ech.id DESC LIMIT 1
            )pc_dept,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, CONCAT_WS(' ', 'Pending', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y')) ,CONCAT_WS(' ', 'Approved', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y'))) END AS hr_admin
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 9 AND ech.pic = 2
            ORDER BY ech.id DESC LIMIT 1
            )hr_rec,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, CONCAT_WS(' ', 'Pending', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y')) ,CONCAT_WS(' ', 'Approved', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y'))) END AS hr_admin
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 6 AND ech.pic = 1
            ORDER BY ech.id DESC LIMIT 1
            )hr_admin,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, CONCAT_WS(' ', 'Pending', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y')) ,CONCAT_WS(' ', 'Approved', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y'))) END AS payroll
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 8 AND ech.pic = 2
            ORDER BY ech.id DESC LIMIT 1
            )hr_payroll,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, CONCAT_WS(' ', 'Pending', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y')) ,CONCAT_WS(' ', 'Approved', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y'))) END AS hr_dept
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 6 AND ech.pic = 2
            ORDER BY ech.id DESC LIMIT 1
            )hr_dept,
            (
            SELECT 
            CASE WHEN ech.id = NULL THEN 0 ELSE IF(ech.status = 4, CONCAT_WS(' ', 'Pending', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y')) ,CONCAT_WS(' ', 'Approved', DATE_FORMAT(ech.created_at, '%H:%i:%s %d-%m-%Y'))) END AS fa_dir
            FROM 0_hrm_ec_history ech
            WHERE ech.ec_id = ec.id AND ech.type = 5 AND ech.pic = 2
            ORDER BY ech.id DESC LIMIT 1
            )fa_dir,
            ec.created_at,
            u.name AS created_by
            FROM 0_hrm_exit_clearences ec
            LEFT OUTER JOIN 0_hrm_ec_reasons ecr ON (ecr.id = ec.reason_id)
            INNER JOIN 0_hrm_employees em ON (ec.emp_id = em.emp_id)
            LEFT OUTER JOIN 0_hrm_employee_levels eml ON (em.level_id = eml.level_id)
            LEFT OUTER JOIN 0_hrm_divisions d ON (d.division_id = ec.division_id)
            INNER JOIN users u ON (ec.created_by = u.id)
            $xx
            WHERE ec.deleted_by = 0";

    if ($need_check == 0) {
      $sql .= " AND DATE(ec.created_at) BETWEEN '$from_date' AND '$to_date'";
      if ($status == 0) {
        $sql .= " AND ec.status > 0";
      } else if ($status > 0) {
        $sql .= " AND ec.status = $status";
      }
    } else if ($need_check == 1) {
      $sql .= " AND ec.person_id = -1";
    }
    if ($emp_id != '') {
      $sql .= " AND ec.emp_id = '$emp_id'";
    }

    if ($emp_name != '') {
      $sql .= " AND ec.emp_name LIKE '%$emp_name%'";
    }

    $sql .= " GROUP BY ec.id ORDER BY ec.id DESC";

    return $sql;
  }

  public static function ec_need_approve($user_level, $user_person_id, $user_division)
  {
    $user_id = Auth::guard()->user()->id;
    $user_old_id = Auth::guard()->user()->old_id;

    /** OFFICE HEAD ARRAY */
    $office_head = array(
      2,
      171,
      50,
      904,
      569,
      696,
      2504,
      1772
    );


    $sql = "WITH HISTORY AS (
              SELECT
                  ech.ec_id,
                  ech.type,
                  ech.pic,
                  ech.status,
                  ROW_NUMBER() OVER (PARTITION BY ech.ec_id, ech.type, ech.pic ORDER BY ech.id DESC) AS rn
              FROM 0_hrm_ec_history ech
          )
          SELECT
              ec.id,
              ec.emp_id,
              ec.emp_name,
              d.name AS division_name,
              eml.level_id,
              eml.name AS level_name,
              ec.join_date,
              ec.due_date,
              ec.last_date,
              ec.reason_id,
              m.name AS pm_name,
              ecr.name AS reason,
              COALESCE(MAX(CASE WHEN h.type = 7 AND h.pic = 1 AND h.rn = 1 THEN IF(h.status = 4, 4, 1) ELSE 0 END), 0) AS dept_terkait,
              COALESCE(MAX(CASE WHEN h.type = 7 AND h.pic = 2 AND h.rn = 1 THEN IF(h.status = 4, 4, 1) ELSE 0 END), 0) AS dept_head_terkait,
              COALESCE(MAX(CASE WHEN h.type = 1 AND h.pic = 1 AND h.rn = 1 THEN IF(h.status = 4, 4, 1) ELSE 0 END), 0) AS am_admin,
              COALESCE(MAX(CASE WHEN h.type = 10 AND h.pic = 1 AND h.rn = 1 THEN IF(h.status = 4, 4, 1) ELSE 0 END), 0) AS ict_admin,
              COALESCE(MAX(CASE WHEN h.type = 10 AND h.pic = 2 AND h.rn = 1 THEN IF(h.status = 4, 4, 1) ELSE 0 END), 0) AS ict_head,
              COALESCE(MAX(CASE WHEN h.type = 1 AND h.pic = 2 AND h.rn = 1 THEN IF(h.status = 4, 4, 1) ELSE 0 END), 0) AS am_dept,
              COALESCE(MAX(CASE WHEN h.type = 1 AND h.pic = 1 AND h.rn = 1 THEN IF(h.status = 4, 4, 1) ELSE 0 END), 0) AS ict_admin,
              COALESCE(MAX(CASE WHEN h.type = 1 AND h.pic = 2 AND h.rn = 1 THEN IF(h.status = 4, 4, 1) ELSE 0 END), 0) AS ict_dept,
              COALESCE(MAX(CASE WHEN h.type = 2 AND h.pic = 1 AND h.rn = 1 THEN IF(h.status = 4, 4, 1) ELSE 0 END), 0) AS ga_admin,
              COALESCE(MAX(CASE WHEN h.type = 2 AND h.pic = 2 AND h.rn = 1 THEN IF(h.status = 4, 4, 1) ELSE 0 END), 0) AS ga_dept,
              COALESCE(MAX(CASE WHEN h.type = 3 AND h.pic = 1 AND h.rn = 1 THEN IF(h.status = 4, 4, 1) ELSE 0 END), 0) AS fa_admin,
              COALESCE(MAX(CASE WHEN h.type = 3 AND h.pic = 2 AND h.rn = 1 THEN IF(h.status = 4, 4, 1) ELSE 0 END), 0) AS fa_dept,
              COALESCE(MAX(CASE WHEN h.type = 4 AND h.pic = 1 AND h.rn = 1 THEN IF(h.status = 4, 4, 1) ELSE 0 END), 0) AS pc_admin,
              COALESCE(MAX(CASE WHEN h.type = 4 AND h.pic = 2 AND h.rn = 1 THEN IF(h.status = 4, 4, 1) ELSE 0 END), 0) AS pc_dept,
              COALESCE(MAX(CASE WHEN h.type = 9 AND h.pic = 2 AND h.rn = 1 THEN IF(h.status = 4, 4, 1) ELSE 0 END), 0) AS hr_rec,
              COALESCE(MAX(CASE WHEN h.type = 6 AND h.pic = 1 AND h.rn = 1 THEN IF(h.status = 4, 4, 1) ELSE 0 END), 0) AS hr_admin,
              COALESCE(MAX(CASE WHEN h.type = 8 AND h.pic = 2 AND h.rn = 1 THEN IF(h.status = 4, 4, 1) ELSE 0 END), 0) AS hr_payroll,
              COALESCE(MAX(CASE WHEN h.type = 6 AND h.pic = 2 AND h.rn = 1 THEN IF(h.status = 4, 4, 1) ELSE 0 END), 0) AS hr_dept,
              COALESCE(MAX(CASE WHEN h.type = 5 AND h.pic = 1 AND h.rn = 1 THEN IF(h.status = 4, 4, 1) ELSE 0 END), 0) AS fa_dir,
              ec.created_at
          FROM 0_hrm_exit_clearences ec
          LEFT JOIN 0_hrm_employees em ON ec.emp_id = em.emp_id
          LEFT JOIN 0_hrm_employee_levels eml ON em.level_id = eml.level_id
          LEFT JOIN 0_hrm_divisions d ON d.division_id = ec.division_id
          LEFT JOIN HISTORY h ON h.ec_id = ec.id
          LEFT JOIN 0_hrm_ec_reasons ecr ON ecr.id = ec.reason_id
          LEFT JOIN 0_members m ON ec.person_id = m.person_id";

    if ($user_level == 555 && $user_person_id == 0) {
      $sql .= " WHERE ec.status > 0 AND (ec.am_check < 1 OR ec.ict_check < 1)"; /* ASSET & ICT ADMIN */
    } elseif ($user_level == 555 && $user_person_id == 207) {
      $sql .= " WHERE ec.status > 0 AND ec.ga_check = 1"; /* HEAD GA */
    } elseif ($user_level == 555 && $user_person_id == 158) {
      $sql .= " WHERE ec.status > 0 AND ec.am_check = 1"; /* HEAD ASSET */
    } elseif ($user_level == 777 && $user_person_id == 98) {
      $sql .= " WHERE ec.status > 0 AND ec.ict_check = 1";  /* HEAD ICT */
      if (in_array($user_id, $office_head)) {
        $sql .= " OR ec.dept_check = 0 AND ec.ict_check = 2 AND ec.person_id = " . intval($user_person_id); /* KONDISI OFFICE */
      }
    } elseif ($user_level == 111 && $user_person_id == 0 && $user_division == 0) {
      $sql .= " WHERE ec.status > 0 AND ec.ga_check = 0"; /* GA ADMIN */
    } elseif ($user_level == 111 && $user_person_id == 0 && $user_division == 7) {
      $sql .= " WHERE ec.status > 0 AND ec.fa_check = 0"; /* FA ADMIN */
    } elseif ($user_level == 4 && $user_person_id > 0 && $user_division == 7) {
      $sql .= " WHERE ec.status > 0 AND ec.fa_check = 1"; /* HEAD FA */
      if (in_array($user_id, $office_head)) {
        $sql .= " OR ec.dept_check = 0 AND ec.fa_check = 2 AND ec.person_id = " . intval($user_person_id); /* KONDISI OFFICE */
      }
    } elseif ($user_level == 4 && $user_person_id == 0 && $user_division == 25) {
      $sql .= " WHERE ec.status > 0 AND ec.pc_check = 0"; /* BPC ADMIN */
    } elseif ($user_level == 4 && $user_person_id > 0 && $user_division == 25) {
      $sql .= " WHERE ec.status > 0 AND ec.pc_check = 1"; /* HEAD BPC */
      if (in_array($user_id, $office_head)) {
        $sql .= " OR ec.dept_check = 0 AND ec.pc_check = 2 AND ec.person_id = " . intval($user_person_id); /* KONDISI OFFICE */
      }
    } elseif ($user_level == 221 && $user_person_id == 0 && $user_division == 0) {
      $sql .= " WHERE ec.status > 0 AND ec.rec_check = 0";  /* RECRUITER ADMIN */
    } elseif ($user_level == 222 && $user_person_id == 0 && $user_division == 0) {
      $sql .= " WHERE ec.status > 0 AND ec.hr_check = 0"; /* HR ADMIN */
    } elseif ($user_level == 223 && $user_person_id == 0 && $user_division == 0) {
      $sql .= " WHERE ec.status > 0 AND ec.payroll_check = 0";  /* PAYROLL ADMIN */
    } elseif ($user_level == 222 && $user_person_id > 0 && $user_division == 0) {
      $sql .= " WHERE ec.status > 0 AND ec.hr_check = 1 AND ec.payroll_check = 2 AND ec.rec_check = 2"; /* HEAD HR */
      if (in_array($user_id, $office_head)) {
        $sql .= " OR ec.dept_check = 0 AND ec.hr_check = 2 AND ec.payroll_check = 2 AND ec.rec_check = 2 AND ec.person_id = " . intval($user_person_id); /* KONDISI OFFICE */
      }
    } elseif ($user_level == 42) {
      $sql .= " WHERE ec.status > 0 AND ec.dept_check = 0 AND ec.person_id = " . intval($user_person_id); /* DIR OPS */
    } elseif ($user_level == 1 && $user_person_id > 0) {
      $sql .= " WHERE ec.status > 0 AND ec.dept_check = 0 AND ec.person_id = " . intval($user_person_id); /* PROJECT MANAGER */
    } elseif ($user_level == 3 && $user_person_id > 0) {
      $sql .= " WHERE ec.status > 0 AND (ec.dept_check = 1 AND d.division_id IN (SELECT division_id FROM 0_user_dept WHERE user_id = " . intval($user_old_id) . ")
                OR (ec.dept_check = 0 AND ec.person_id = " . intval($user_person_id) . "))";  /* GENERAL MANAGER */
    } elseif ($user_level == 2 && $user_person_id > 0) {
      $sql .= " WHERE ec.status > 0 AND (ec.dept_check = 0 AND ec.person_id = " . intval($user_person_id) . "
                OR (ec.dept_check = 1 AND d.division_id IN (SELECT division_id FROM 0_user_dept WHERE user_id = " . intval($user_old_id) . ")))"; /* DEPUTY GENERAL MANAGER */
    } elseif ($user_level == 43) {
      $sql .= " WHERE ec.status > 0 AND ec.am_check = 2 AND ec.ga_check = 2 AND ec.fa_check = 2 AND ec.hr_check = 2 AND ec.payroll_check = 2 AND ec.fa_dir_check = 0";  /* DIR FA */
    } else {
      $sql .= " WHERE ec.id = -1";
    }
    $sql .= " AND ec.deleted_by = 0 GROUP BY ec.id ASC";

    return $sql;
  }

  // public static function history_ec($ec_id){
  //   $sql = ""
  // }

  public static function sql_ec_history($ec_id, $type, $pic)
  {
    return DB::table('0_hrm_ec_history AS ech')
      ->leftJoin('users AS u', 'u.id', '=', 'ech.user_id')
      ->where('ech.ec_id', $ec_id)->where('ech.type', $type)->where('ech.pic', $pic)
      ->select(
        'ech.user_id AS pic_id',
        'u.name',
        'ech.remark',
        DB::raw("IF(ech.deduction > 0, ech.deduction , 0) AS deduction"),
        DB::raw("CASE 
                            WHEN ech.status = 1 THEN 'Ok'
                            WHEN ech.status = 2 THEN 'Close'
                            WHEN ech.status = 3 THEN 'Pot. Gaji'
                            WHEN ech.status = 4 THEN 'Pending' END AS status"),
        'ech.created_at AS approval_date'
      )
      ->orderBy('ech.created_at', 'desc')
      ->first();
  }

  public static function get_attachment_ec($ec_id, $user_id)
  {
    $sql = "SELECT a.id, a.filename FROM 0_hrm_ec_attachments a WHERE a.ec_id = $ec_id AND uploaded_by = $user_id";
    return $sql;
  }
}
