<?php

namespace App\Query;

class QueryCashAdvance
{
    public static function ca_need_approval($user_id, $level, $person_id, $division_id)
    {
        $sql = "SELECT 
                    ca.trans_no,
                    ca.reference,
                    ca.tran_date,
                    ct.name as ca_type_name,
                    e.name as employee_name,
                    e.emp_id as emp_id,
                    d.name as division_name,
                    ca.amount,                                                             
                    SUM(cad.approval_amount) as approval_amount,
                    COUNT(cad.cash_advance_detail_id) as count_cad,
                    m.person_id,
                    ca.cashadvance_ref
                FROM 0_cashadvance ca 
                INNER JOIN 0_cashadvance_details cad ON (ca.trans_no = cad.trans_no)
                LEFT JOIN 0_hrm_employees e ON (e.id = ca.emp_no)
                LEFT JOIN 0_projects p ON (ca.project_no = p.project_no)
                LEFT OUTER JOIN 0_members m ON (p.person_id = m.person_id)
                LEFT JOIN 0_hrm_divisions d ON (d.division_id = p.division_id)
                LEFT JOIN 0_cashadvance_types ct ON (ct.ca_type_id = ca.ca_type_id)";

        if ($level == 1 && $user_id != 1241) {
            $sql .= " WHERE ca.approval=1 AND ca.ca_type_id NOT IN (2,9,6)
                AND YEAR(ca.tran_date) > 2020 AND p.person_id = $person_id

                GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id,ca.cashadvance_ref";
        } else if ($level == 222 && $user_id != 1241) {
            $sql .= " WHERE ca.approval=1 AND ca.ca_type_id NOT IN (2,9,6)
                AND YEAR(ca.tran_date) >2020 AND p.person_id = $person_id

                GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id,ca.cashadvance_ref";
        } else if ($level == 1 && $user_id == 1241) {
            $sql .= " WHERE ca.approval=1 AND ca.ca_type_id NOT IN (2,9) AND YEAR(ca.tran_date) >2020 AND p.person_id = $person_id OR ca.approval=1 AND ca.ca_type_id = 6 AND YEAR(ca.tran_date) >2020

                    GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id,ca.cashadvance_ref";
        } else if ($level == 2) {

            $sql .= " WHERE ca.approval=1 AND ca.ca_type_id NOT IN (2,9,6)
                    AND YEAR(ca.tran_date) >2020 AND p.person_id = $person_id
                    OR ca.approval=2 AND ca.ca_type_id NOT IN (2,9,6) AND YEAR(ca.tran_date) >2020 AND p.division_id IN (SELECT division_id FROM 0_user_dept WHERE user_id=$user_id)
                    
                    GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id, ca.cashadvance_ref";
        } else if ($level == 4) {
            $sql .= " WHERE ca.approval=1 AND cad.status_id < 2 AND ca.ca_type_id NOT IN (2,9)
                    AND YEAR(ca.tran_date) >2020 AND p.person_id = $person_id
                    OR ca.approval=4 AND cad.status_id < 2 AND ca.ca_type_id NOT IN (2,9)
                    AND YEAR(ca.tran_date) >2020 AND p.division_id IN 
                    (
                        SELECT division_id FROM 0_user_project_control 
                       WHERE user_id=$user_id
                    )
                    OR ca.approval=4 AND ca.ca_type_id NOT IN (2,9,6) AND YEAR(ca.tran_date) >2020 AND p.division_id IN (SELECT division_id FROM 0_user_dept WHERE user_id=$user_id)
                    
                    GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id, ca.cashadvance_ref";
        } else if ($level == 3) {
            $sql .= " WHERE cad.status_id < 2 AND YEAR(ca.tran_date) >2020 AND ca.approval = 3 AND p.division_id IN 
                    (
                        SELECT division_id FROM 0_user_divisions
                        WHERE user_id=$user_id
                    ) 
                    OR cad.status_id < 2 AND YEAR(ca.tran_date) >2020 AND ca.approval = 1 AND p.person_id = $person_id
                    
                    GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id, ca.cashadvance_ref";
        } else if ($level == 5) {
            $sql .= "WHERE ca.approval=5 AND cad.status_id < 2 AND ca.ca_type_id IN (2,9) AND YEAR(ca.tran_date) >2020
            GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id, ca.cashadvance_ref";
        } else if ($level == 41) {
            $sql .= " WHERE ca.approval = 41 AND cad.status_id < 2 AND ca.ca_type_id NOT IN (2,9) AND YEAR(ca.tran_date) >2020 
                    OR ca.approval = 1 AND cad.status_id < 2 AND ca.ca_type_id NOT IN (2,9) AND YEAR(ca.tran_date) >2020 AND p.person_id = $person_id
                    OR ca.approval = 3 AND cad.status_id < 2 AND ca.ca_type_id NOT IN (2,9) AND YEAR(ca.tran_date) >2020 
                    AND p.division_id IN (SELECT division_id FROM 0_user_divisions WHERE user_id=$user_id) 
                    
                    GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id, ca.cashadvance_ref
                    ORDER BY ca.tran_date DESC";
        } else if ($level == 42) {
            $sql .= " WHERE ca.approval = 1 AND p.person_id = $person_id OR ca.approval=42 AND cad.status_id < 2 AND ca.ca_type_id NOT IN (2,9) AND YEAR(ca.tran_date) >2020
                    OR ca.approval = 3 AND cad.status_id < 2 AND ca.ca_type_id NOT IN (2,9) AND YEAR(ca.tran_date) >2020 AND p.division_id IN (5,6,8,11,25)

                    GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id, ca.cashadvance_ref";
        } else if ($level == 43) {
            $sql .= " WHERE ca.approval=43 AND cad.status_id < 2 AND ca.ca_type_id NOT IN (2,9) AND YEAR(ca.tran_date) >2020
                    OR ca.approval=1 AND p.division_id = 3 AND cad.status_id < 2 AND ca.ca_type_id NOT IN (2,9) AND YEAR(ca.tran_date) >2020
                    OR ca.approval = 3 AND cad.status_id < 2 AND ca.ca_type_id NOT IN (2,9) AND YEAR(ca.tran_date) >2020 AND p.division_id IN (4,7,10)
                    GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id, ca.cashadvance_ref";
        } else if ($level == 999) {
            $sql .= " WHERE ca.approval < 6 AND cad.status_id < 2 AND ca.ca_type_id NOT IN (2,9) AND YEAR(ca.tran_date) >2020

                    GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id, ca.cashadvance_ref
                    ORDER BY ca.tran_date DESC";
        } else if ($level == 51) {
            $sql .= " WHERE ca.approval=51 AND ca.ca_type_id NOT IN (2,9,6) AND YEAR(ca.tran_date) >2020 AND d.division_id = $division_id
                   
                    GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id, ca.cashadvance_ref";
        } else if ($level == 52) {
            $sql .= " WHERE ca.approval=52 AND ca.ca_type_id NOT IN (2,9,6) AND YEAR(ca.tran_date) >2020 AND d.division_id = $division_id
                
                    GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id ,ca.cashadvance_ref";
        } else {
            $sql .= " WHERE ca.approval=9999

                    GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id ,ca.cashadvance_ref";
        }

        return $sql;
    }

    public static function ca_need_approval_detail($trans_no)
    {
        $sql = "SELECT 
                        cad.*,
                        CASE WHEN cad.approval = 0 THEN 'Open'
                        WHEN cad.approval = 1 THEN 'Approve'
                        WHEN cad.approval = 2 THEN 'Disapprove' ELSE cad.approval END AS approval_status,
                        p.project_no,
                        p.code as project_code,
                        m.name as project_manager,
                        CONCAT_WS(' ', 'COA', pcg.account_code) as cost_type_name,
                        s.name as site_name,
			            pcg.name as budget_type_name,
                        pb.budget_name as budget_name,
                        ca.tran_date as ca_date
                FROM 0_cashadvance_details cad  
                LEFT OUTER JOIN 0_cashadvance ca ON (ca.trans_no = cad.trans_no)
                LEFT JOIN 0_projects p ON (p.project_no = cad.project_no)
                LEFT JOIN 0_project_site s ON (s.site_no = cad.site_id)
                LEFT JOIN 0_members m On (m.person_id = p.person_id)
                LEFT JOIN 0_project_cost_type pct ON (pct.cost_type_id = cad.cost_code)
		        LEFT JOIN 0_project_budgets pb ON (cad.project_budget_id = pb.project_budget_id)
                LEFT JOIN 0_project_cost_type_group pcg ON (pb.budget_type_id = pcg.cost_type_group_id)
                WHERE cad.trans_no = $trans_no AND cad.status_id < 2";

        return $sql;
    }


    // ca revision approval
    public static function ca_revision_cost_allocation($user_id, $level, $person_id, $division_id)
    {
        $sql = "SELECT  rev.rev_id,
                        rev.ca_ref,
                        rev.ca_tran_date,
                        rev.emp_no,
                        rev.emp_id,
                        rev.emp_name,
                        rev.ca_amount,
                        rev.approval

                        FROM 0_cashadvance_rev_cost_alloc rev 
                        INNER JOIN 0_cashadvance_rev_cost_alloc_details revd ON (rev.rev_id = revd.rev_id)
                        LEFT JOIN 0_cashadvance_types ct ON (ct.ca_type_id = rev.ca_type_id)
                        LEFT JOIN 0_hrm_employees e ON (e.id = rev.emp_no)
                        LEFT JOIN 0_projects p ON (revd.project_no = p.project_no)
                        LEFT OUTER JOIN 0_members m ON (p.person_id = m.person_id)
                        LEFT JOIN 0_hrm_divisions d ON (d.division_id = p.division_id)";

        if ($level == 1 && $user_id != 1241) {
            $sql .= "WHERE rev.approval=1 AND rev.ca_type_id NOT IN (2,9,6) 
                            AND YEAR(rev.ca_tran_date) > 2020 AND p.person_id = $person_id

                            GROUP BY rev.rev_id,
                                    rev.ca_ref,
                                    rev.ca_tran_date,
                                    rev.emp_no,
                                    rev.emp_id,
                                    rev.emp_name,
                                    rev.ca_amount,
                                    rev.approval";
        } else if ($level == 222 && $user_id != 1241) {
            $sql .= "WHERE rev.approval=1 AND rev.ca_type_id NOT IN (2,9,6) 
                            AND YEAR(rev.ca_tran_date) > 2020 AND p.person_id = $person_id

                            GROUP BY rev.rev_id,
                                    rev.ca_ref,
                                    rev.ca_tran_date,
                                    rev.emp_no,
                                    rev.emp_id,
                                    rev.emp_name,
                                    rev.ca_amount,
                                    rev.approval";
        } else if ($level == 1 && $user_id == 1241) {
            $sql .= "WHERE rev.approval=1 AND rev.ca_type_id NOT IN (2,9) 
                            AND YEAR(rev.ca_tran_date) > 2020 AND p.person_id = $person_id 
                            OR rev.approval=1 AND rev.ca_type_id = 6 AND YEAR(rev.ca_tran_date) > 2020

                            GROUP BY rev.rev_id,
                                    rev.ca_ref,
                                    rev.ca_tran_date,
                                    rev.emp_no,
                                    rev.emp_id,
                                    rev.emp_name,
                                    rev.ca_amount,
                                    rev.approval";
        } else if ($level == 2) {
            $sql .= "WHERE rev.approval=1 AND rev.ca_type_id NOT IN (2,9,6)
                            AND YEAR(rev.ca_tran_date) > 2020 AND p.person_id = $person_id
                            OR rev.approval=2 AND rev.ca_type_id NOT IN (2,9,6) AND YEAR(rev.ca_tran_date) > 2020 
                            
                            AND p.division_id IN 
                            (
                                SELECT division_id FROM 0_user_dept WHERE user_id=$user_id
                            )
                            
                            GROUP BY rev.rev_id,
                                    rev.ca_ref,
                                    rev.ca_tran_date,
                                    rev.emp_no,
                                    rev.emp_id,
                                    rev.emp_name,
                                    rev.ca_amount,
                                    rev.approval";
        } else if ($level == 4) {
            $sql .= "WHERE rev.approval=1 AND rev.ca_type_id NOT IN (2,9) AND YEAR(rev.ca_tran_date) > 2020 AND p.person_id = $person_id 
                            OR rev.approval=4 AND rev.status_id < 2 AND rev.ca_type_id NOT IN (2,9) AND YEAR(rev.ca_tran_date) >2020 
                            AND p.division_id IN (SELECT division_id FROM 0_user_project_control WHERE user_id=$user_id) 

                            OR rev.approval=4 AND rev.status_id < 2 AND rev.ca_type_id NOT IN (2,9,6) AND YEAR(rev.ca_tran_date) >2020 
                            AND p.division_id IN (SELECT division_id FROM 0_user_dept WHERE user_id=$user_id)
                            
                            GROUP BY rev.rev_id,
                                    rev.ca_ref,
                                    rev.ca_tran_date,
                                    rev.emp_no,
                                    rev.emp_id,
                                    rev.emp_name,
                                    rev.ca_amount,
                                    rev.approval";
        } else if ($level == 3) {
            $sql .= "WHERE rev.status_id < 2 AND rev.approval = 3 AND YEAR(rev.ca_tran_date) > 2020  AND p.division_id IN 
                            (
                                SELECT division_id FROM 0_user_divisions
                                WHERE user_id=$user_id
                            ) 
                            OR rev.status_id < 2 AND YEAR(rev.ca_tran_date) > 2020 AND rev.approval = 1 AND p.person_id = $person_id
                            
                            GROUP BY rev.rev_id,
                                    rev.ca_ref,
                                    rev.ca_tran_date,
                                    rev.emp_no,
                                    rev.emp_id,
                                    rev.emp_name,
                                    rev.ca_amount,
                                    rev.approval";
        } else if ($level == 5) {
            $sql .= "WHERE rev.approval=5 AND rev.status_id < 2 AND rev.ca_type_id IN (2,9) AND YEAR(rev.ca_tran_date) > 2020
                            
                            GROUP BY rev.rev_id,
                                    rev.ca_ref,
                                    rev.ca_tran_date,
                                    rev.emp_no,
                                    rev.emp_id,
                                    rev.emp_name,
                                    rev.ca_amount,
                                    rev.approval";
        } else if ($level == 41) {
            $sql .= "WHERE rev.approval = 41 AND rev.status_id < 2 AND rev.ca_type_id NOT IN (2,9) AND YEAR(rev.ca_tran_date) > 2020 
                            OR rev.approval = 1 AND rev.status_id < 2 AND rev.ca_type_id NOT IN (2,9) AND YEAR(rev.ca_tran_date) > 2020 AND p.person_id = $person_id
                            OR rev.approval = 3 AND rev.status_id < 2 AND rev.ca_type_id NOT IN (2,9) AND YEAR(rev.ca_tran_date) > 2020 
                            AND p.division_id IN (SELECT division_id FROM 0_user_divisions WHERE user_id=$user_id) 
                            
                            GROUP BY rev.rev_id,
                                    rev.ca_ref,
                                    rev.ca_tran_date,
                                    rev.emp_no,
                                    rev.emp_id,
                                    rev.emp_name,
                                    rev.ca_amount,
                                    rev.approval

                            ORDER BY rev.ca_tran_date DESC";
        } else if ($level == 42) {
            $sql .= "WHERE rev.approval = 1 AND p.person_id = $person_id OR rev.approval=42 AND rev.status_id < 2 AND rev.ca_type_id NOT IN (2,9) AND YEAR(rev.ca_tran_date) >2020
                            OR rev.approval = 3 AND rev.status_id < 2 AND rev.ca_type_id NOT IN (2,9) AND YEAR(rev.ca_tran_date) > 2020 AND p.division_id IN (5,6,8,11,25)

                            GROUP BY rev.rev_id,
                                    rev.ca_ref,
                                    rev.ca_tran_date,
                                    rev.emp_no,
                                    rev.emp_id,
                                    rev.emp_name,
                                    rev.ca_amount,
                                    rev.approval";
        } else if ($level == 43) {
            $sql .= "WHERE rev.approval=43 AND rev.status_id < 2 AND rev.ca_type_id NOT IN (2,9) AND YEAR(rev.ca_tran_date) > 2020
                            OR rev.approval=1 AND p.division_id = 3 AND rev.status_id < 2 AND rev.ca_type_id NOT IN (2,9) AND YEAR(rev.ca_tran_date) >2020
                            OR rev.approval = 3 AND rev.status_id < 2 AND rev.ca_type_id NOT IN (2,9) AND YEAR(rev.ca_tran_date) >2020 AND p.division_id IN (4,7,10)
                            GROUP BY rev.rev_id,
                                    rev.ca_ref,
                                    rev.ca_tran_date,
                                    rev.emp_no,
                                    rev.emp_id,
                                    rev.emp_name,
                                    rev.ca_amount,
                                    rev.approval";
        } else if ($level == 999) {
            $sql .= "WHERE rev.approval < 6 AND rev.status_id < 2 AND rev.ca_type_id NOT IN (2,9) AND YEAR(rev.ca_tran_date) >2020

                            GROUP BY rev.rev_id,
                                    rev.ca_ref,
                                    rev.ca_tran_date,
                                    rev.emp_no,
                                    rev.emp_id,
                                    rev.emp_name,
                                    rev.ca_amount,
                                    rev.approval
                            ORDER BY rev.ca_tran_date DESC";
        } else if ($level == 51) {
            $sql .= "WHERE rev.approval=51 AND rev.ca_type_id NOT IN (2,9,6) AND YEAR(rev.ca_tran_date) >2020 AND d.division_id = $division_id
                        
                            GROUP BY rev.rev_id,
                                    rev.ca_ref,
                                    rev.ca_tran_date,
                                    rev.emp_no,
                                    rev.emp_id,
                                    rev.emp_name,
                                    rev.ca_amount,
                                    rev.approval";
        } else if ($level == 52) {
            $sql .= "WHERE rev.approval=52 AND rev.ca_type_id NOT IN (2,9,6) AND YEAR(rev.ca_tran_date) >2020 AND d.division_id = $division_id
                        
                            GROUP BY rev.rev_id,
                                    rev.ca_ref,
                                    rev.ca_tran_date,
                                    rev.emp_no,
                                    rev.emp_id,
                                    rev.emp_name,
                                    rev.ca_amount,
                                    rev.approval";
        } else {
            $sql .= "WHERE rev.approval=9999

                            GROUP BY rev.rev_id,
                                    rev.ca_ref,
                                    rev.ca_tran_date,
                                    rev.emp_no,
                                    rev.emp_id,
                                    rev.emp_name,
                                    rev.ca_amount,
                                    rev.approval";
        }

        return $sql;
    }


    public static function ca_revision_cost_allocation_details($rev_id)
    {
        $sql = "SELECT  id,
                        project_code,
                        project_name,
                        project_budget_id,
                        new_project_budget_id,
                        ca_detail_id,
                        site_id,
                        site_name,
                        cost_allocation_code,
                        cost_allocation_name,
                        new_cost_allocation_code,
                        new_cost_allocation_name,
                        remark,
                        amount
                FROM 0_cashadvance_rev_cost_alloc_details 
                WHERE rev_id =" . $rev_id;

        return $sql;
    }

    public static function get_history_approval_rev_ca($trans_no)
    {
        $sql = "SELECT ca_log.trans_rev_no, 
        CASE
            WHEN (ca_log.approval_id=0) THEN 'ADMIN'
            WHEN (ca_log.approval_id=1) THEN 'PM'
            WHEN (ca_log.approval_id=2 AND p.code NOT LIKE '%OFC%') THEN 'DGM'
            WHEN (ca_log.approval_id=2 AND p.code LIKE '%OFC%') THEN 'DEPT. HEAD'
            WHEN (ca_log.approval_id=3) THEN 'GM'
            WHEN (ca_log.approval_id=31) THEN 'GM(TI/MS)'
            WHEN (ca_log.approval_id=41) THEN 'DIRECTOR'
            WHEN (ca_log.approval_id) = 32 THEN 'Dir.Ops'
            WHEN (ca_log.approval_id) = 42 THEN 'Dir.Ops'
            WHEN (ca_log.approval_id) = 43 THEN 'Dir.FA'
            WHEN (ca_log.approval_id=4) THEN 'PC'
            WHEN (ca_log.approval_id=5) THEN 'FA'
            WHEN (ca_log.approval_id=6) THEN 'CASHIER'
            WHEN (ca_log.approval_id=7) THEN 'CLOSE'
        END AS routing_approval, 
            u.name AS person_name,
            ca_log.updated
        
        FROM 0_cashadvance_rev_cost_alloc_log ca_log
        INNER JOIN 0_cashadvance_rev_cost_alloc rev ON (ca_log.trans_rev_no = rev.rev_id)
        LEFT JOIN 0_cashadvance_rev_cost_alloc_details revd ON (revd.rev_id = rev.rev_id)
        LEFT JOIN users u ON (ca_log.person_id = u.id)
        LEFT OUTER JOIN 0_projects p ON (revd.project_no = p.project_no)
        
        WHERE ca_log.trans_rev_no= $trans_no
        GROUP BY ca_log.approval_id ORDER BY ca_log.updated ASC";

        return $sql;
    }

    public static function ca_list()
    {
        $sql = "SELECT 
                    ca.trans_no,
                    ca.reference,
                    ca.tran_date,
                    ct.name as ca_type_name,
                    e.name as employee_name,
                    e.emp_id as emp_id,
                    d.name as division_name,
                    ca.amount,                                                             
                    SUM(cad.approval_amount) as approval_amount,
                    COUNT(cad.cash_advance_detail_id) as count_cad,
		    CASE
                        WHEN (ca.approval=0) THEN 'ADMIN'
                        WHEN (ca.approval=1) THEN 'PM'
                        WHEN (ca.approval=2) THEN 'DGM'
                        WHEN (ca.approval=3) THEN 'GM'
                        WHEN (ca.approval=31) THEN 'GM(TI/MS)'
                        WHEN (ca.approval=41) THEN 'DIRECTOR'
                        WHEN (ca.approval) = 32 THEN 'Dir.Ops'
                        WHEN (ca.approval) = 42 THEN 'Dir.Ops'
                        WHEN (ca.approval) = 43 THEN 'Dir.FA'
                        WHEN (ca.approval=4) THEN 'PC'
                        WHEN (ca.approval=5) THEN 'FA'
                        WHEN (ca.approval=6) THEN 'CASHIER'
                        WHEN (ca.approval=7) THEN 'CLOSE'
                    END AS pic, 
                    m.person_id
                FROM 0_cashadvance ca 
                INNER JOIN 0_cashadvance_details cad ON (ca.trans_no = cad.trans_no)
                LEFT JOIN 0_hrm_employees e ON (e.id = ca.emp_no)
                LEFT JOIN 0_projects p ON (ca.project_no = p.project_no)
                LEFT OUTER JOIN 0_members m ON (p.person_id = m.person_id)
                LEFT JOIN 0_hrm_divisions d ON (d.division_id = p.division_id)
                LEFT JOIN 0_cashadvance_types ct ON (ct.ca_type_id = ca.ca_type_id)
                WHERE ca.approval != 7 AND ca.ca_type_id NOT IN (2,9)
                AND YEAR(ca.tran_date) >2020
                GROUP BY ca.trans_no, ca.reference, ca.tran_date, ct.name, e.name, e.emp_id, d.name, ca.amount, ca.approval_amount, m.person_id
                ORDER BY ca.tran_date DESC";

        return $sql;
    }

    public static function get_ca_history($trans_no)
    {
        $sql = "SELECT ca_log.trans_no, ca.reference, ca_log.approval_id,
                CASE
                    WHEN (ca_log.approval_id=0) THEN 'ADMIN'
                    WHEN (ca_log.approval_id=1) THEN 'PM'
                    WHEN (ca_log.approval_id=2 AND p.code NOT LIKE '%OFC%') THEN 'DGM'
                    WHEN (ca_log.approval_id=2 AND p.code LIKE '%OFC%') THEN 'DEPT. HEAD'
                    WHEN (ca_log.approval_id=3) THEN 'GM'
                    WHEN (ca_log.approval_id=31) THEN 'GM(TI/MS)'
                    WHEN (ca_log.approval_id=41) THEN 'DIRECTOR'
                    WHEN (ca_log.approval_id) = 32 THEN 'Dir.Ops'
                    WHEN (ca_log.approval_id) = 42 THEN 'Dir.Ops'
                    WHEN (ca_log.approval_id) = 43 THEN 'Dir.FA'
                    WHEN (ca_log.approval_id=4) THEN 'BPC'
                    WHEN (ca_log.approval_id=5) THEN 'FA'
                    WHEN (ca_log.approval_id=6) THEN 'CASHIER'
                    WHEN (ca_log.approval_id=7) THEN 'CLOSE'
                END AS routing_approval, 
                CASE
                    WHEN (ca_log.type=1) THEN u.real_name
                    WHEN (ca_log.type=3) THEN us.name
                END AS person_name, ca_log.updated, c.memo_
                FROM 0_cashadvance_log1 ca_log
                INNER JOIN 0_cashadvance ca ON (ca_log.trans_no = ca.trans_no)
                LEFT JOIN 0_cashadvance_comments c ON (ca_log.trans_no = c.trans_no AND ca_log.approval_id = c.approval_id AND c.type=1)
                LEFT JOIN 0_users u ON (ca_log.person_id = u.id)
                LEFT JOIN 0_projects p ON (ca.project_no = p.project_no)
                LEFT JOIN users us ON (ca_log.person_id = us.id)
                WHERE ca_log.type IN (1,3) AND ca_log.trans_no=$trans_no GROUP BY ca_log.approval_id ORDER BY ca_log.updated ASC";

        return $sql;
    }
}
