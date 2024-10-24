<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\EmployeesController;
use App\Api\V1\Controllers\FinanceController;
use Illuminate\Support\Facades\DB;


class PoDummyNotif extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'podummy:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto Send Notif';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $command     =
            $year = date('Y');
        $response = [];
        $query = "SELECT so.reference, so.project_code, p.person_id, so.date_tolerance
                FROM 0_sales_orders so 
                LEFT OUTER JOIN 0_projects p ON (p.project_no = so.project_no)
                WHERE YEAR(so.ord_date) = '$year' 
                AND so.customer_ref LIKE '%Waiting%' AND so.date_tolerance != '0000-00-00'";
        $exe = DB::connection('mysql')->select(DB::raw($query));

        foreach ($exe as $data) {
            $selisih_hari = strtotime(date('Y-m-d')) - strtotime($data->date_tolerance);
            $selisih_hari = floor($selisih_hari / (60 * 60 * 24));

            if ($selisih_hari <= 14) {
                $item = DB::table('0_sales_order_details')->where('order_no', $data->order_no)->get();
                $tmp = [];
                $tmp['no_so'] = $data->reference;
                $tmp['project_code'] = $data->project_code;

                $user = DB::table('users')->select('email')->where('person_id', $data->person_id)->first();
                $tmp['pm_email'] = strtolower($user->email);
                $tmp['items'] = [];

                foreach ($item as $items => $val) {
                    $itm = [];
                    $itm['item_code'] = $val->stk_code;
                    $itm['description'] = $val->description;
                    $itm['site_id'] = $val->site_id;
                    $itm['site_name'] = $val->site_name;

                    array_push($tmp['items'], $itm);
                }

                array_push($response, $tmp);
            }
        }

        foreach ($response as $key => $val) {
            \Mail::to($val['pm_email'])->send(new \App\Mail\PoDummyNotify($response)); /* send to PM */
            \Mail::to('moehammad@adyawinsa.com')->send(new \App\Mail\PoDummyNotify($response)); /* send to BPC (moe) */
            // \Mail::to('rian.pambudi@adyawinsa.com')->send(new \App\Mail\PoDummyNotify($response)); /* test */
        }
        return response()->json([
            'success' => true,
            'message' => "Email sudah terkirim."
        ]);

        $return      = NULL;
        $output      = NULL;
        exec($command, $output, $return);
    }
}
