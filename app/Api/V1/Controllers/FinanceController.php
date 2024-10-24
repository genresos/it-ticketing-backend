<?php

namespace App\Api\V1\Controllers;

use JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Carbon\Carbon;
use Auth;
use App\CashAdvance;
use Symfony\Component\HttpKernel\Exception\ValidationAmountCADHttpException;
use App\Exports\CashAdvanceExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class FinanceController extends Controller
{
    //
    use Helpers;

    /*
     *
     * 
     */
    //==================================================================== FUNCTION CA LIST User =============================================================\\  
    public function petty_cash_list()
    {
        $currentUser = JWTAuth::parseToken()->authenticate();
        $user_id = Auth::guard()->user()->old_id;

        $response = [];

        $sql = "SELECT id, bank_account_name, bank_curr_code, inactive
                    FROM 0_bank_accounts
                    WHERE id IN (SELECT ca_id FROM 0_user_cash_account WHERE user_id = $user_id)";


        $petty_cash = DB::select(DB::raw($sql));



        foreach ($petty_cash as $data) {

            $tmp = [];
            $tmp['id'] = $data->id;
            $tmp['bank_name'] = $data->bank_account_name;


            array_push($response, $tmp);
        }
        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    public static function generate_export_ca()
    {
        $date = date('d-m-Y');
        $name_excel = "CA_LIST_" . $date;

        Excel::store(new CashAdvanceExport, "/Finance/DATA CASHADVANCE/$name_excel.xlsx", 'sftp');

        return response()->json([
            'success' => true
        ]);
    }

    public function export_ca()
    {

        $date = date('d-m-Y');
        $name_excel = "CA_LIST_" . $date;

        if (Storage::disk('sftp')->exists("/IT/RIAN/data_ca_jadi/$name_excel.xlsx")) {
            $date = date('d-m-Y');
            return Storage::disk('sftp')->get("/IT/RIAN/data_ca_jadi/$name_excel.xlsx");
        } else {
            return response()->json([
                'success' => false,
                'error' => [
                    "message" => "Data Belum ada silahkan kontak ICT",
                    "status_code" => 403
                ]
            ], 403);
        }
    }

    public function upload_json(Request $request)
    {
        $file = $request->file('file');

        if ($file) {
            $date = date('d-m-Y');
            $filename = "CA_LIST_" . $date;
            $destination = public_path("/storage/finance/export");
            $file->move($destination, $filename . ".json");
            return response()->json([
                'success' => true,
                'data' => $filename . '.json' . ' was uploaded!'
            ]);
        }
    }

    public static function upload_ca_all_to_disk()
    {
        $response = array();

        $date = date('d-m-Y');
        $filename = "CA_LIST_" . $date . '.json';
        $sql = "SELECT 
                ca.trans_no AS ca_trans_no,
                cat.name AS doc_type_name,
                ca.reference AS ca_reference, 
                ca.tran_date  AS ca_tran_date, 
                ca.emp_id, 
                em.name AS emp_name, 
                d.name AS division_name, 
                SUM(cad.amount) AS ca_amount,
                SUM(cad.release_amount) AS cad_release_amount,
                ca.amount_pot_gaji as ca_deduction,
                MAX(cad.release_date) AS release_date,
                (
                SELECT GROUP_CONCAT(remark SEPARATOR '_') 
                FROM 0_cashadvance_details
                WHERE trans_no = ca.trans_no
                ) ca_remark,
                ca.due_date AS ca_due_date,
                CASE WHEN MAX(ca.approval) = 1 THEN 'PM'
                    WHEN MAX(ca.approval) = 2 THEN 'DGM'
                    WHEN MAX(ca.approval) = 3 THEN 'GM'
                    WHEN MAX(ca.approval) = 4 THEN 'BPC'
                    WHEN MAX(ca.approval) = 6 THEN 'CASHIER'
                    WHEN MAX(ca.approval) = 7 THEN 'RELEASE'
                    WHEN MAX(ca.approval) = 10 THEN 'CLOSE'
                    WHEN MAX(ca.approval) = 8 THEN 'CLOSE POT.GAJI'
                    WHEN MAX(ca.approval) = 31 THEN 'GM(TI/MS)'
                    WHEN MAX(ca.approval) = 32 THEN 'Dir.Ops'
                    WHEN MAX(ca.approval) = 42 THEN 'Dir.Ops'
                    WHEN MAX(ca.approval) = 43 THEN 'Dir.FA'
                    WHEN MAX(ca.approval) = 41 THEN 'Director' ELSE MAX(ca.approval) END AS ca_approval_status,
                b.bank_account_name,
                GROUP_CONCAT(prj.code SEPARATOR '_') AS project_code,
                GROUP_CONCAT(prj.name SEPARATOR '_') AS project_name,
                CASE WHEN prj.inactive = 1 THEN 'INACTIVE' ELSE 'ACTIVE' END AS project_status,
                GROUP_CONCAT(mb.name SEPARATOR '_') AS project_manager,
                (
                SELECT GROUP_CONCAT(trans_no SEPARATOR ' ') 
                FROM 0_cashadvance_stl
                WHERE ca_trans_no = ca.trans_no
                ) stl_trans_no,
                (
                SELECT GROUP_CONCAT(REFERENCE SEPARATOR ' ') 
                FROM 0_cashadvance_stl
                WHERE ca_trans_no = ca.trans_no
                ) stl_reference,
                (
                SELECT SUM(stld.amount)
                FROM 0_cashadvance_stl stl
                INNER JOIN 0_cashadvance_stl_details stld ON (stld.trans_no = stl.trans_no)
                WHERE stl.ca_trans_no = ca.trans_no AND stld.status_id < 2
                GROUP BY stl.ca_trans_no
                ) stl_amount,
                (
                SELECT SUM(stld.act_amount)
                FROM 0_cashadvance_stl stl
                INNER JOIN 0_cashadvance_stl_details stld ON (stld.trans_no = stl.trans_no)
                WHERE stl.ca_trans_no = ca.trans_no AND stld.status_id < 2
                GROUP BY stl.ca_trans_no
                ) stl_act_amount,
                (
                SELECT MAX(stld.approval_date)
                FROM 0_cashadvance_stl stl
                INNER JOIN 0_cashadvance_stl_details stld ON (stld.trans_no = stl.trans_no)
                WHERE stl.ca_trans_no = ca.trans_no AND stld.status_id < 2
                GROUP BY stl.ca_trans_no
                ) approval_date,
                (
                SELECT CASE WHEN MAX(stld.approval) = 1 THEN 'PM'
                    WHEN MAX(stld.approval) = 2 THEN 'DGM'
                    WHEN MAX(stld.approval) = 3 THEN 'GM'
                    WHEN MAX(stld.approval) = 4 THEN 'BPC'
                    WHEN MAX(stld.approval) = 6 THEN 'CASHIER'
                    WHEN MAX(stld.approval) = 7 THEN 'RELEASE'
                    WHEN MAX(stld.approval) = 10 THEN 'CLOSE'
                    WHEN MAX(stld.approval) = 8 THEN 'CLOSE POT.GAJI'
                    WHEN MAX(stld.approval) = 31 THEN 'GM(TI/MS)'
                    WHEN MAX(stld.approval) = 32 THEN 'Dir.Ops'
                    WHEN MAX(stld.approval) = 42 THEN 'Dir.Ops'
                    WHEN MAX(stld.approval) = 43 THEN 'Dir.FA'
                    WHEN MAX(stld.approval) = 41 THEN 'Director' ELSE MAX(stld.approval) END AS stl_approval
                FROM 0_cashadvance_stl stl
                INNER JOIN 0_cashadvance_stl_details stld ON (stld.trans_no = stl.trans_no)
                WHERE stl.ca_trans_no = ca.trans_no AND stld.status_id < 2
                GROUP BY stl.ca_trans_no
                ) stl_approval,
                (
                SELECT GROUP_CONCAT(stld.remark SEPARATOR ' _')
                FROM 0_cashadvance_stl stl
                INNER JOIN 0_cashadvance_stl_details stld ON (stld.trans_no = stl.trans_no)
                WHERE stl.ca_trans_no = ca.trans_no AND stld.status_id < 2
                GROUP BY stl.ca_trans_no
                ) stl_remark,
                (
                SELECT GROUP_CONCAT(pct.name SEPARATOR '_ ')
                FROM 0_cashadvance_stl stl
                INNER JOIN 0_cashadvance_stl_details stld ON (stld.trans_no = stl.trans_no)
                LEFT JOIN 0_project_cost_type pct ON (pct.cost_type_id = stld.cost_code)
                WHERE stl.ca_trans_no = ca.trans_no AND stld.status_id < 2
                GROUP BY stl.ca_trans_no
                ) stl_cost_type,
                (
                SELECT GROUP_CONCAT(stld.act_amount SEPARATOR  '_ ')
                FROM 0_cashadvance_stl stl
                INNER JOIN 0_cashadvance_stl_details stld ON (stld.trans_no = stl.trans_no)
                WHERE stl.ca_trans_no = ca.trans_no AND stld.status_id < 2
                GROUP BY stl.ca_trans_no
                ) stl_dtl_amount,
                (
                SELECT SUM(stl.allocate_ear_amount)
                FROM 0_cashadvance_stl stl
                INNER JOIN 0_cashadvance_stl_details stld ON (stld.trans_no = stl.trans_no)
                WHERE stl.ca_trans_no = ca.trans_no
                GROUP BY stl.ca_trans_no
                )stl_dtl_allocate_ear_amount,
                CASE
                WHEN DATEDIFF(ca.release_date,ca.tran_date) BETWEEN 0 AND 30 THEN '1-30 Days'
                WHEN DATEDIFF(ca.release_date,ca.tran_date) BETWEEN 31 AND 60 THEN '31-60 Days'
                WHEN DATEDIFF(ca.release_date,ca.tran_date) BETWEEN 61 AND 90 THEN '61-90 Days'
                WHEN DATEDIFF(ca.release_date,ca.tran_date) > 90 THEN '> 90 Days'
                END AS ca_aging,
                cat.name AS is_outer_area,
                ca.bank_account_no
                FROM 0_cashadvance ca 
                INNER JOIN 0_cashadvance_details cad ON (ca.trans_no = cad.trans_no)
                LEFT OUTER JOIN 0_hrm_employees em ON (ca.emp_no = em.id) 
                LEFT OUTER JOIN 0_projects prj ON (prj.project_no = ca.project_no) 
                LEFT OUTER JOIN 0_hrm_divisions d ON (prj.division_id = d.division_id) 
                LEFT OUTER JOIN 0_cashadvance_types cat ON (ca.ca_type_id = cat.ca_type_id) 
                LEFT OUTER JOIN 0_bank_accounts b ON (b.id = ca.bank_account_no) 
                LEFT OUTER  JOIN 0_members mb ON (mb.person_id = prj.person_id)
                WHERE ca.ca_type_id IN(SELECT ca_type_id FROM 0_cashadvance_types WHERE type_group_id IN (1,2)) AND cad.status_id < 2 AND YEAR(ca.tran_date) > 2017 AND ca.close_force = 0
                GROUP BY ca.trans_no 
                ORDER BY ca.trans_no";
        $exe = DB::select(DB::raw($sql));

        foreach ($exe as $data) {
            $tmp = array();
            $ca_tran_date = $data->ca_tran_date;
            $release_date = $data->release_date;
            $ca_due_date = $data->ca_due_date;

            $old_bank_name = DB::table('0_bank_accounts_old')->where('id', $data->bank_account_no)->first();
            $bank_name = ($data->bank_account_name == null || empty($data->bank_account_name)) ? $old_bank_name->bank_account_name : $data->bank_account_name;
            $tmp['ca_trans_no'] = $data->ca_trans_no;
            $tmp['doc_type_name'] = $data->doc_type_name;
            $tmp['ca_reference'] = $data->ca_reference;
            $tmp['ca_tran_date'] = $ca_tran_date;
            $tmp['emp_id'] = $data->emp_id;
            $tmp['emp_name'] = $data->emp_name;
            $tmp['division_name'] = $data->division_name;
            $tmp['ca_amount'] = $data->ca_amount;
            $tmp['cad_release_amount'] = $data->cad_release_amount;
            $tmp['ca_deduction'] = $data->ca_deduction;
            $tmp['release_date'] = $release_date;
            $tmp['ca_remark'] = $data->ca_remark;
            $tmp['ca_due_date'] = $ca_due_date;
            $tmp['ca_approval_status'] = $data->ca_approval_status;
            $tmp['bank_account_name'] = $bank_name;
            $tmp['project_code'] = $data->project_code;
            $tmp['project_name'] = $data->project_name;
            $tmp['project_status'] = $data->project_status;
            $tmp['project_manager'] = $data->project_manager;
            $tmp['stl_trans_no'] = $data->stl_trans_no;
            $tmp['stl_reference'] = $data->stl_reference;
            $tmp['stl_amount'] = $data->stl_amount;
            $tmp['stl_act_amount'] = $data->stl_act_amount;
            $tmp['approval_date'] = $data->approval_date;
            $tmp['stl_approval'] = $data->stl_approval;
            $tmp['stl_remark'] = $data->stl_remark;
            $tmp['stl_cost_type'] = $data->stl_cost_type;
            $tmp['stl_dtl_amount'] = $data->stl_dtl_amount;
            $tmp['stl_dtl_allocate_ear_amount'] = $data->stl_dtl_allocate_ear_amount;
            $tmp['ca_aging'] = $data->ca_aging;
            $tmp['is_outer_area'] = $data->is_outer_area;

            array_push($response, $tmp);
        }
        Storage::disk('sftp')->put("/IT/RIAN/data_ca_mentah/$filename", json_encode($response));

        self::generate_export_ca();
        return response()->json([
            'success' => true
        ]);
    }
}
