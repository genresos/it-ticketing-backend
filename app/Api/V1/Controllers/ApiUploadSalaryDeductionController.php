<?php

namespace App\Api\V1\Controllers;

use JWTAuth;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\DB;
use App\Modules\PaginationArr;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Api\V1\Controllers\ApiProjectOverviewController;

class ApiUploadSalaryDeductionController extends Controller
{
    public function upload_ca_deduction(Request $request)
    {
        $logs = []; // Array to store logs

        $query = "SELECT 
                        pg.emp_no,
                        pg.pg_date,
                        pg.pg_amount,
                        pg.reference
                    FROM ca_pot_gj_2409 pg";
        $exe = DB::connection('mysql')->select(DB::raw($query));

        foreach ($exe as $data) {
            $date_ = strtotime('24/09/2024');
            $newDate = date('Y-m-d', $date_);
            
            try {
                DB::connection('pgsql')->table('hrd_loan')->insert(array(
                    'created' => $newDate,
                    'created_by' => 6320,
                    'modified_by' => 6320,
                    'id_employee' => $data->emp_no,
                    'amount' => $data->pg_amount,
                    'type' => 1,
                    'periode' => 1,
                    'purpose' => "OSKB FA Deduction",
                    'note' => "$data->reference",
                    'category' => 2,
                    'loan_date' => $data->pg_date,
                    'payment_from' => date('Y-m-d'),
                    'payment_thru' => date('Y-m-d')
                ));
                
                // Log success message for each record
                $logs[] = "Inserted deduction for employee no: {$data->emp_no}, amount: {$data->pg_amount}, reference: {$data->reference}";
            } catch (\Exception $e) {
                // Log error message if the insertion fails
                $logs[] = "Failed to insert deduction for employee no: {$data->emp_no}. Error: " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'logs' => $logs // Return the logs
        ]);
    }

}