<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\UserController;
use Carbon\Carbon;

class SyncAbsenceLeave extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cuti:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync abence leave to Devosa';

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
            $sql = DB::table('0_project_task_cico')->whereIn('attendance_id', array(2, 3, 4, 5, 6, 7, 8, 13, 19, 20, 21, 22))->where('status', 1)->where('already_sync', 0)->get();
        if (count($sql) > 0) {
            DB::beginTransaction();
            try {
                foreach ($sql as $data) {
                    $request_date = date('Y-m-d');
                    $date_from = $data->date;
                    $date_thru = $data->date;
                    $user_info = UserController::get_user_info($data->user_id);
                    $emp_id_atasan = UserController::get_emp_id_by_person_id($data->person_id);
                    $devosa_atasan = UserController::get_info_user_devosa($emp_id_atasan);

                    if (empty($devosa_atasan->id_adm_user)) {
                        return response()->json([
                            'error' => array(
                                'message' => "PM belum mempunyai akun Devosa!",
                                'status' => 403
                            )
                        ], 403);
                    }

                    $id_employee = $user_info->emp_no;

                    $get_attendance_type_code = DB::table('0_attendance_type')
                        ->where('id', $data->attendance_id)
                        ->first();

                    $absence_type_code = $get_attendance_type_code->code;

                    $note = $data->remark;

                    // KONDISI KALAU DATA SUDAH ADA / GENERATE ALPHA BY SYSTEM DEVOSA
                    $existing_data = DB::connection('pgsql')->table('hrd_absence')->where('id_employee', $id_employee)->where('date_from', $date_from)->first();
                    $absence_devosa_existing = (!empty($existing_data)) ? 1 : 0;
                    $absence_leave_duration = DB::table('0_absence_leave')->where('absence_id', $data->id)->orderBy('id', 'desc')->first();

                    $duration = abs((strtotime($absence_leave_duration->end_date) - strtotime($absence_leave_duration->start_date)) / (60 * 60 * 24));
                    switch ($absence_devosa_existing) {
                        case 0:
                            DB::table('0_project_task_cico')->where('id', $data->id)
                                ->update(array(
                                    'already_sync' => 1
                                ));
                            $header = DB::connection('pgsql')->table('hrd_absence')
                                ->insertGetId(array(
                                    'id_employee' => $id_employee,
                                    'request_date' => $request_date,
                                    'date_from' => $absence_leave_duration->start_date,
                                    'date_thru' => $absence_leave_duration->end_date,
                                    'absence_type_code' => $absence_type_code,
                                    'duration' => $duration,
                                    'leave_duration' => $duration,
                                    'note' => $note,
                                    'status' => 2,
                                    'approved_by' => $devosa_atasan->id_adm_user,
                                    'approved_time' => Carbon::now(),
                                    'created_by' => 1034,
                                    'modified_by' => 1034
                                ));

                            DB::connection('pgsql')->table('hrd_absence_detail')
                                ->insert(array(
                                    'id_absence' => $header,
                                    'id_employee' => $id_employee,
                                    'absence_date' => $date_from,
                                    'absence_type' => $absence_type_code,
                                    'created_by' => 1034,
                                    'modified_by' => 1034
                                ));

                            break;
                        case 1:


                            DB::table('0_project_task_cico')->where('id', $data->id)
                                ->update(array(
                                    'already_sync' => 1
                                ));
                            DB::connection('pgsql')->table('hrd_absence')->where('id', $existing_data->id)
                                ->update(array(
                                    'id_employee' => $id_employee,
                                    'request_date' => $request_date,
                                    'date_from' => $absence_leave_duration->start_date,
                                    'date_thru' => $absence_leave_duration->end_date,
                                    'absence_type_code' => $absence_type_code,
                                    'duration' => $duration,
                                    'leave_duration' => $duration,
                                    'note' => $note,
                                    'status' => 2,
                                    'approved_by' => $devosa_atasan->id_adm_user,
                                    'approved_time' => Carbon::now(),
                                    'created_by' => 1034,
                                    'modified_by' => 1034
                                ));

                            DB::connection('pgsql')->table('hrd_absence_detail')->where('id_absence', $existing_data->id)
                                ->update(array(
                                    'id_employee' => $id_employee,
                                    'absence_date' => $date_from,
                                    'absence_type' => $absence_type_code,
                                    'created_by' => 1034,
                                    'modified_by' => 1034
                                ));

                            break;
                    }
                }

                // Commit Transaction
                DB::commit();

                // Semua proses benar

                echo 'Success';
            } catch (Exception $e) {
                // Rollback Transaction
                DB::rollback();
            }
        } else {
            echo 'Empty Data';
        }
        $return      = NULL;
        $output      = NULL;
        exec($command, $output, $return);
    }
}
