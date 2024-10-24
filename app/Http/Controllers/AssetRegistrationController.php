<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Validator,Redirect,Response,File;
use QrCode;
class AssetRegistrationController extends Controller
{
    public function index(Request $request)
    {
        $sql = "SELECT
                        ar.asset_type_name,
                        ar.asset_id,
                        CONCAT(ar.trx_date) AS registration_period,
                        e.emp_id,
                        e.name AS employee_name,
                        ar.project_code,
                        a.name AS area_name,
                        CASE WHEN ((ar.asset_type_name='Kendaraan Mobil' || ar.asset_type_name='Kendaraan Motor') AND ar.approval_position < 3) THEN 'Need Approval AM' ELSE
                            CASE WHEN (ar.approval_position = 1) THEN 'Need Approval PM'
                            WHEN (ar.approval_position = 2) THEN 'Need Approval AM'
                            WHEN (ar.approval_position=3) THEN 'Approved'
                            END
                            END approval_status_name,
                        COUNT(file_path) AS file_path,
                        ai.doc_no,
                        CASE WHEN ((ar.asset_type_name='Kendaraan Mobil' || ar.asset_type_name='Kendaraan Motor') AND ar.approval_position<3) THEN 2 ELSE ar.approval_position END approval_position
                FROM 0_asset_registration ar
                LEFT JOIN 0_users_api ua ON (ua.device_id = ar.device_id)
                LEFT JOIN 0_hrm_employees e ON (e.id = ua.emp_no)
                LEFT JOIN 0_am_issues ai ON (ai.issue_id = ar.issue_id)
                LEFT JOIN 0_projects p ON (ar.project_code = p.code)
                LEFT JOIN 0_project_area a ON (a.area_id = p.area_id)
                GROUP BY ar.asset_type_name, ar.device_id, MONTH(ar.trx_date), YEAR(ar.trx_date), ar.asset_id
                ORDER BY ar.trx_date DESC LIMIT 100";

        $asset_registration = DB::select( DB::raw($sql));
        

        dd($asset_registration);


    }

	public function get_qr_code(){
	$data = 121311112;
	$code = QrCode::size('255')
    			->generate('A simple example of QR code!');
	return $code;
	}


    
}
