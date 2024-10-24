<?php

namespace App\Exports;

use JWTAuth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Auth;
use DateTime;


class ICTTicketsExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    /**
     * @return \Illuminate\Support\Collection
     */

     function __construct($from, $to)
     {
         $this->from = $from;
         $this->to = $to;
     }


    //     public function collection()
    //     {
    //         set_time_limit(0);
    //         $sql = "SELECT pctg.* FROM 0_project_cost_type_group pctg
    //                 WHERE pctg.inactive = 0";

    //         $aa = DB::select($sql)->all();

    //         return $aa;
    //     }

    public function collection()
    {
        $currentUser = JWTAuth::parseToken()->authenticate();
        $user_id = Auth::guard()->user()->id;
        $level = Auth::guard()->user()->approval_level;
        $from = $this->from;
        $to = $this->to;

        // $user_id = 1;
        // $level = 999;

        set_time_limit(0);
        $response = [];

        $sql = "SELECT t.*, u.name AS assigned_name, uc.name AS created_name, uc.emp_id AS created_nik, tc.name AS category_name, tp.name AS priority_name, ts.name AS status_name, p.code AS project_code 
                FROM 0_ict_tickets t 
                LEFT JOIN users u ON (t.assigned_to = u.id)
                LEFT JOIN users uc ON (t.user_id = uc.id)
                LEFT JOIN 0_ict_ticket_category tc ON (tc.id = t.category_id)
                LEFT JOIN 0_ict_ticket_priority tp ON (tp.id = t.priority_id)
                LEFT JOIN 0_ict_ticket_status ts ON (ts.id = t.status_id)
                LEFT JOIN 0_projects p ON (p.project_no = t.project_no)
                WHERE t.created_at BETWEEN '$from' AND '$to'";

        if ($level != 999) {
            $sql .= " AND t.user_id = $user_id";
        }

        $sql .= " ORDER BY t.created_at DESC";

        $issue_tickets = DB::select(DB::raw($sql));
        foreach ($issue_tickets as $data) {
            $created_date = date('d-m-Y H:i:s', strtotime($data->created_at));
            $end_date = date('d-m-Y H:i:s', strtotime($data->end_time));

            $timeStart = new DateTime($data->created_at);
            $timeFinish = new DateTime($data->end_time);

            $diff = date_diff($timeStart, $timeFinish);
            // $diff = $timeFinish->diff($timeStart);
            if ($data->status_id == 7) {
                $range_time = $diff->d . ' Day ' . $diff->h . ' Hour';
            } else {
                $range_time = null;
            }

            $output[] = [
                $data->title,
                $data->description,
                $data->category_name,
                $data->status_name,
                $data->asset_name,
                $data->project_code,
                $data->assigned_name,
                $data->created_name,
                $data->created_nik,
                $created_date,
                $end_date,
                $range_time,


            ];
        }
        return collect($output);


        // return Cashadvance::query()->where('trans_no', '=', 129819);
    }
    public function headings(): array
    {
        return [
            'Title',
            'Description',
            'Category',
            'Status',
            'Asset No',
            'Project Code',
            'Assigned User',
            'Created Name',
            'Created NIK',
            'Created Date',
            'End Date',
            'Finish In Hour',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $cellRange = 'A1:H1'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(14);
            },
        ];
    }
}
