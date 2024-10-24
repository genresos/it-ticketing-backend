<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\EmployeesController;
use App\Api\V1\Controllers\FinanceController;
use Illuminate\Support\Facades\DB;


class PoOutstandingNotif extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pooutstanding:send';

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
            $response = [];
        $query = "SELECT so.order_no, sod.id, so.reference, so.project_code, p.person_id, sod.description, sod.qty_ordered, sod.site_id, sod.workend_date FROM 0_sales_order_details sod
                    INNER JOIN 0_sales_orders so ON (so.order_no = sod.order_no)
                    LEFT OUTER JOIN 0_projects p ON (p.project_no = so.project_no)
                    WHERE sod.qty_ordered > 0 and sod.unit_price > 0 AND sod.workstart_date IS NOT NULL";
        $exe = DB::connection('mysql')->select(DB::raw($query));

        $uniqueData = [];

        foreach ($exe as $data) {
            $workend = $data->workend_date;

            $qty_ord = $data->qty_ordered;
            $qty_inv = DB::table('0_debtor_trans_details')->where('sales_order_detail_id', $data->id)->sum('quantity');

            if ($workend <= date('Y-m-d') && $qty_inv < $qty_ord) {

                $item = DB::table('0_sales_order_details')->where('order_no', $data->order_no)->get();
                $tmp = [];
                $tmp['no_so'] = $data->reference;
                $tmp['project_code'] = $data->project_code;
                $tmp['qty_ord'] = $qty_ord;
                $tmp['qty_inv'] = $qty_inv;

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
        foreach ($response as $item) {
            $no_so = $item['no_so'];
            if (!array_key_exists($no_so, $uniqueData)) {
                $uniqueData[$no_so] = $item;
            }
        }

        $uniqueData = array_values($uniqueData);

        foreach ($uniqueData as $key => $val) {
            \Mail::to($val['pm_email'])->send(new \App\Mail\PoOutstandingNotify($uniqueData)); /* send to PM */
            \Mail::to('moehammad@adyawinsa.com')->send(new \App\Mail\PoOutstandingNotify($uniqueData)); /* send to BPC (moe) */
            // \Mail::to('rian.pambudi@adyawinsa.com')->send(new \App\Mail\PoOutstandingNotify($uniqueData)); /* test */
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
