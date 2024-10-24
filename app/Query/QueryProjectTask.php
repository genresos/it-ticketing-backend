<?php

namespace App\Query;

use App\Query\QueryProjectList;
use Illuminate\Support\Facades\DB;
use URL;

class QueryProjectTask
{
  public static function view_task_by_project($project_no)
  {
    $sql = "SELECT pt.id AS project_task_id,p.project_no AS project_no,
                pt.du_id,
                pt.site_no,
                p.code AS project_code,
                pt.title,
                p.division_id,
                pt.lat AS latitude,
                pt.long AS longitude,
                pt.remark,
                pa.name AS area, 
                dm.name AS customer, 
                pt.qty AS qty_task,
                pt.uom AS uom_task,
                s.name AS status,
                pdp.sow AS sow,
                CONCAT(pdp.plan_start,' - ',pdp.plan_end) AS plan_date,
                pdp.id AS surtug_id,
                pdp.reference AS surtug_doc_no,
                pt.created_at,
                u.name AS creator
            FROM 0_projects p
            LEFT JOIN 0_project_task pt ON (p.project_no = pt.project_no)
            LEFT OUTER JOIN 0_project_daily_plan pdp ON (pt.id = pdp.task_id)
            LEFT JOIN 0_project_area pa ON (p.area_id = pa.area_id)
            LEFT JOIN 0_debtors_master dm ON (p.debtor_no = dm.debtor_no)
            LEFT JOIN 0_project_status s ON (pt.status = s.status_id)
            LEFT OUTER JOIN users u ON (pt.created_by = u.id)
            WHERE pt.status != 3 AND p.project_no = $project_no AND pt.deleted < 1
            AND DATE(pt.created_at) > DATE_ADD(NOW(), INTERVAL -3 WEEK)
            GROUP BY pt.id ORDER BY pt.id DESC";
    return $sql;
  }

  public static function view_task_tree_by_project($project_no)
  {
    $sql = "SELECT pt.id, pt.title AS name, pt.parent AS project, pt.remark, pdp.plan_start, pdp.plan_end, pt.qty AS qty_plan, pt.qty_finished AS qty_actual FROM 0_project_task pt
             LEFT JOIN 0_project_daily_plan pdp ON (pdp.task_id = pt.id)
             WHERE project_no=$project_no
             GROUP BY pt.id
             ORDER BY pt.id";

    return $sql;
  }

  public static function project_task_head($user_emp_no)
  {
    $sql = "SELECT pte.id,p.project_no AS project_no,
                       pte.project_task_id,
                       pt.du_id,
                       pt.site_no,
                       p.code AS project_code,
                       p.division_id,
                       pt.title,
                       pt.lat AS latitude,
                       pt.long AS longitude,
                       pt.remark,
                       pa.name AS area, 
                       dm.name AS customer, 
                       pt.qty AS qty_task,
                       pt.uom AS uom_task,
                       s.name AS status,
                       pdp.sow AS sow,
                       CONCAT(pdp.plan_start,' - ',pdp.plan_end) AS plan_date,
                       pdp.id AS surtug_id,
                       pdp.reference AS surtug_doc_no,
                       pdp.qrcode
            FROM 0_project_task_employees pte
            LEFT JOIN 0_project_task pt ON (pte.project_task_id = pt.id)
            LEFT OUTER JOIN 0_project_daily_plan pdp ON (pt.id = pdp.task_id)
            LEFT JOIN 0_projects p ON (pt.project_no = p.project_no)
            LEFT JOIN 0_project_area pa ON (p.area_id = pa.area_id)
            LEFT JOIN 0_debtors_master dm ON (p.debtor_no = dm.debtor_no)
            LEFT JOIN 0_project_status s ON (pt.status = s.status_id)
            WHERE pt.status != 3 AND pte.emp_no = $user_emp_no AND DATE(pte.created) > DATE_ADD(NOW(), INTERVAL -1 MONTH)
            GROUP BY pt.id ORDER BY pt.id DESC";

    return $sql;
  }

  public static function project_task_body_1($project_task_id)
  {
    $sql = "SELECT e.name FROM 0_project_task_employees pte
            LEFT OUTER JOIN 0_hrm_employees e ON (pte.emp_no = e.id)
            WHERE pte.project_task_id = $project_task_id
            GROUP BY pte.emp_no";
    return $sql;
  }

  public static function project_task_body_2($project_task_id, $user_id)
  {
    $sql = "SELECT ptc.id,
                  ptc.date,
                  ptc.project_task_id,
                  ptc.check_in,
                  ptc.lat_in,
                  ptc.long_in,
                  ptc.start_time,
                  ptc.check_out,
                  ptc.lat_out,
                  ptc.long_out,
                  ptc.end_time,
                  u.name AS user,
                  ptc.user_id
              FROM 0_project_task_cico ptc
              LEFT JOIN users u ON (ptc.user_id = u.id)
              WHERE ptc.project_task_id = $project_task_id";

    if ($user_id > 0) {
      $sql .= " AND ptc.user_id = $user_id";
    }
    $sql .= " GROUP BY ptc.id";

    return $sql;
  }

  public static function project_task_detail_head($id)
  {
    $sql = "SELECT ptc.id,
                ptc.project_task_id,
                ptc.date,
                ptc.start_time,
                ptc.check_in AS status_in,
                ptc.image_in,
                ptc.end_time,
                ptc.check_out AS status_out,
                ptc.image_out
            FROM 0_project_task_cico ptc
            LEFT OUTER JOIN 0_project_task pt ON (ptc.project_task_id = pt.id)
            WHERE ptc.id = $id";
    return $sql;
  }

  public static function project_task_detail_body($id, $user_id)
  {
    $sql = "SELECT ptp.id,
                ptp.description,
                ptp.ext_description,
                ptp.qty,
                ptp.sow,
                ptp.du_id,
                pt.qty AS task_qty,
                ptp.site_no,
                ptp.created_at
            FROM 0_project_task_progress ptp
            LEFT OUTER JOIN 0_project_task_cico ptc ON (ptp.id_cico = ptc.id)
            LEFT OUTER JOIN 0_project_task pt ON (ptc.project_task_id = pt.id)
            LEFT OUTER JOIN 0_project_progress_photos pp ON (ptp.id = pp.progress_id)
            WHERE ptp.id_cico = $id
            GROUP BY ptp.id";
    return $sql;
  }

  public static function task_attendance($task_id, $emp_id, $date)
  {
    $sql = "SELECT ptc.*, u.name FROM 0_project_task_cico ptc
            LEFT OUTER JOIN users u ON (ptc.user_id = u.id)
            WHERE ptc.project_task_id = $task_id
            AND u.emp_id = '$emp_id'
            AND DATE(ptc.start_time) = '$date'";
    return $sql;
  }

  public static function show_du_id($du_id)
  {
    $sql = "SELECT * FROM 0_project_du_id
            WHERE du_id LIKE '%$du_id%'";
    return $sql;
  }

  public static function show_du_id_site($site_no)
  {
    $sql = "SELECT * FROM 0_project_site
            WHERE site_no IN ($site_no)";

    return $sql;
  }
  public static function get_latest_project_task_id()
  {
    $sql = DB::table('0_project_task')
      ->latest()
      ->first();

    return $sql->id;
  }

  public static function get_site_by_du_id($du_id)
  {

    $get_site = DB::table('0_project_du_id')
      ->where('du_id', $du_id)
      ->first();

    $site_no =  $get_site->site_no;

    $sql = "SELECT GROUP_CONCAT(site_id SEPARATOR'__') AS site_id FROM 0_project_site WHERE site_no IN ($site_no)";
    $exe = DB::select(DB::raw($sql));

    foreach ($exe as $data) {
      $site_id = $data->site_id;
      return $site_id;
    }
  }
  public static function get_qty_actual($task_id)
  {
    $progress_qty_ongoing = DB::table('0_project_task_progress')
      ->where('project_task_id', $task_id)
      ->sum('qty');


    return $progress_qty_ongoing;
  }

  public static function get_site_for_cico($site_no)
  {

    $myString = "$site_no";
    $myArray = explode(',', $myString);

    $data = DB::table('0_project_site')
      ->whereIn('site_no', $myArray)
      ->get();

    return $data;
  }

  public static function get_site_for_create_task($site_no)
  {

    $myString = "$site_no";
    $myArray = explode(',', $myString);

    $data = DB::table('0_project_site')
      ->whereIn('site_no', $myArray)
      ->selectRaw("GROUP_CONCAT(site_id SEPARATOR ',') as site")
      ->first();

    return $data->site;
  }

  public static function get_fat_info($progress_id)
  {
    $sql = DB::table('0_project_task_fat')
      ->join('users', '0_project_task_fat.created_by', '=', 'users.id')
      ->select('0_project_task_fat.*', 'users.name')
      ->where('progress_id', $progress_id)
      ->get();

    return $sql;
  }

  public static function get_fat_details($id, $type)
  {
    $type_file = ($type == 0) ? '%BEFORE%' : '%AFTER%';
    $url = URL::to('/storage/project_task/images');
    $sql = "SELECT id,fat_id,port,lamda1,lamda2,
            CONCAT('$url','/',photo1,'.jpg') AS photo1,
            CONCAT('$url','/',photo2,'.jpg') AS photo2,
            created_at
            FROM 0_project_task_fat_details WHERE fat_id = $id AND photo1 LIKE '$type_file' AND photo2 LIKE '$type_file'";

    return $sql;
  }

  public static function get_fat_by_site($site_no)
  {
    $sql = "SELECT ptf.fat_id,ptf.fat_no,ptf.id, pp.file_path AS fat_photo, ptg.lat, ptg.long, ps.name, ps.site_id FROM 0_project_task_fat ptf
            LEFT OUTER JOIN 0_project_task_progress ptg ON (ptg.id = ptf.progress_id)
            LEFT OUTER JOIN 0_project_task_fat_details ptfd ON (ptf.id = ptfd.fat_id)
            LEFT OUTER JOIN 0_project_site ps ON (ptg.site_no = ps.site_no)
            LEFT OUTER JOIN 0_project_progress_photos pp ON (ptg.id = pp.progress_id)
            WHERE ptg.site_no = $site_no
            GROUP BY ptf.id";

    return $sql;
  }

  public static function get_fat_by_site_details($fat_id)
  {
    $sql = DB::table('0_project_task_fat_details')
      ->where('fat_id', $fat_id)
      ->orderBy('port', 'ASC')
      ->get();

    return $sql;
  }
}
