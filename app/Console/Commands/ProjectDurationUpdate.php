<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProjectDurationUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:duration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Project Start and End Duration';

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
            self::update_project();
        $return      = NULL;
        $output      = NULL;
        exec($command, $output, $return);
    }

    public static function update_project()
    {
        $all_project_2024 = DB::table('0_projects')->whereYear('created_date', '2024')->select('project_no', 'code')->get();

        DB::beginTransaction();
        try {

            foreach ($all_project_2024 as $data) {

                $cashAdvances = DB::table('0_cashadvance')->select('tran_date')
                    ->join('0_cashadvance_details', '0_cashadvance.trans_no', '=', '0_cashadvance_details.trans_no')
                    ->where('0_cashadvance_details.status_id', '<', 2)
                    ->where('0_cashadvance_details.project_no', $data->project_no)
                    ->where('0_cashadvance_details.approval', 7);

                $purchaseOrders = DB::table('0_purch_orders')->select('ord_date as tran_date')
                    ->join('0_purch_order_details', '0_purch_orders.order_no', '=', '0_purch_order_details.order_no')
                    ->whereNotIn('0_purch_orders.doc_type_id', [4008, 4009])
                    ->where('0_purch_orders.status_id', 0)
                    ->where('0_purch_order_details.quantity_ordered', '>', 0)
                    ->where('0_purch_order_details.project_no', $data->project_no);

                $glTransactions = DB::table('0_gl_trans')->select('tran_date')
                    ->where('type', 1)
                    ->where('amount', '>', 0)
                    ->whereNotIn('type', [2003, 2004])
                    ->where('project_code', "'$data->code'");

                $allDates = $cashAdvances->union($purchaseOrders)->union($glTransactions)->pluck('tran_date');

                $minDate = $allDates->min();
                $maxDate = $allDates->max();

                // Tambahkan satu bulan ke MaxDate
                $maxDate = Carbon::parse($maxDate)->addMonth();

                DB::table('0_projects')->where('project_no', $data->project_no)
                    ->update(array(
                        'start_date' => $minDate,
                        'end_date' => $maxDate
                    ));
            }

            // Commit Transaction
            DB::commit();

            return response()->json([
                'success' => true
            ]);
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }
}
