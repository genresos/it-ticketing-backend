<?php

namespace App\Query;

class QueryProjectList
{
    public static function list($inactive, $project_code)
    {
        $sql = "SELECT p.project_no,p.code,p.name,p.poreference,p.inactive, dm.name as customer_name, p.project_value,
                (
                    SELECT CONCAT_WS(' ', 'Cost Already', ai.reason, 'From RAB') AS cost_over FROM history_project_inactive ai
                    WHERE ai.project_no = p.project_no
                    ORDER BY ai.id DESC LIMIT 1
                ) AS cost_over
                FROM 0_projects p
                LEFT OUTER JOIN 0_debtors_master dm ON (p.debtor_no = dm.debtor_no)
                WHERE p.project_no != -1";


        if (empty($inactive) || $inactive == '' || $inactive == 0) {
            $sql .= " AND p.inactive = 0";
        } else if ($inactive == 1) {
            $sql .= " AND p.inactive IN (0,1)";
        }

        if ($project_code != '') {
            $sql .= " AND p.code LIKE '%$project_code%' OR dm.name LIKE '%$project_code%'";
            $sql .= " ORDER BY p.project_no DESC LIMIT 50";
        } else {
            $sql .= " ORDER BY p.project_no DESC LIMIT 10";
        }
        return $sql;
    }

    public static function get_project($project_no)
    {

        $sql = "SELECT p.*,
                p.name as project_name,
                dm.name as debtor_name,
                st.name AS site_name,
                a.name AS area_name,
                m.name AS person_name,
                pjs.name as project_status,
                mf.rate as mf_rate
                FROM 0_projects p
                LEFT JOIN 0_debtors_master dm ON (p.debtor_no = dm.debtor_no)
                LEFT JOIN 0_project_area a ON (p.area_id = a.area_id)
                LEFT JOIN ( SELECT status_id, name FROM 0_project_status WHERE type_id=1) pjs 
                ON (p.project_status_id = pjs.status_id)
                LEFT JOIN 0_members m ON (p.person_id = m.person_id)
                LEFT JOIN 0_project_site st ON (p.site_id = st.site_no)
                LEFT JOIN 0_project_management_fee mf ON (mf.id = p.management_fee_id)";
        if ($project_no != "") {
            $sql .= " WHERE p.project_no= $project_no";
        }

        return $sql;
    }

    public static function get_pm_sql($pm_name)
    {

        $sql = "SELECT 
                m.person_id, 
                m.name, 
                m.email, 
                m.division_id,
                d.name as division_name,
                CASE WHEN (m.inactive = 1) THEN 'Inactive' ELSE 'Active' END status_name, 
                m.inactive, 
                m.group_id,
			m.division_id 
			FROM  0_members m LEFT OUTER JOIN 0_hrm_divisions d ON (d.division_id = m.division_id)";
        if ($pm_name != "") {
            $sql .= " WHERE group_id=5 AND m.name LIKE '%" . $pm_name . "%'";
        }
        $sql .= " ORDER BY m.name";
        return $sql;
    }
}
