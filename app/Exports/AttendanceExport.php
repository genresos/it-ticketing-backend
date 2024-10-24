<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class AttendanceExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    /**
     * @return \Illuminate\Support\Collection
     */
    // protected $from;
    // protected $to;


    function __construct($from, $to, $emp_no, $type, $division)
    {
        $this->from = $from;
        $this->to = $to;
        $this->emp_no = $emp_no;
        $this->emp_no = $emp_no;
        $this->type = $type;
        $this->division = $division;
    }
    public function collection()
    {
        set_time_limit(0);

        $from = $this->from;
        $to = $this->to;
        $emp_no = $this->emp_no;
        $type = $this->type;
        $division = $this->division;

        $sql = "SELECT ptc.*, at.name AS attendance_type , u.name, u.division_name, u.emp_id,
                CASE WHEN ptc.already_sync = 1 THEN 'Synchronize' ELSE 'Not Synchronize' END AS sync_status
                FROM 0_project_task_cico ptc
                INNER JOIN 0_attendance_type at ON (ptc.attendance_id = at.id)
                INNER JOIN 0_projects p ON (ptc.person_id = p.person_id)
                INNER JOIN users u ON (ptc.user_id = u.id)";

        if ($type > 0) {
            $sql .= " AND ptc.attendance_id = $type";
        }

        if ($emp_no > 0) {
            $sql .= " WHERE at.id != -1 AND ptc.date BETWEEN '$from' AND '$to' AND u.emp_no = $emp_no AND ptc.status < 3";
        } else {
            $sql .= " WHERE ptc.check_out = 1 AND ptc.date BETWEEN '$from' AND '$to' AND ptc.status = 1";
            if ($division != '') {
                $sql .= " AND u.division_name = '$division'";
            }
        }
        $sql .= " GROUP BY ptc.id ORDER BY ptc.id DESC";

        $exec = DB::select(DB::raw($sql));
        foreach ($exec as $data) {
            $output[] = [
                $data->id,
                $data->emp_id,
                $data->name,
                $data->division_name,
                $data->attendance_type,
                $data->date,
                $data->start_time,
                $data->lat_in,
                $data->long_in,
                $data->end_time,
                $data->lat_out,
                $data->long_out,
                $data->remark,
                $data->sync_status,


            ];
        }
        return collect($output);
    }
    public function headings(): array
    {
        return [
            '#',
            'NIK',
            'Employee Name',
            'Division',
            'Type',
            'Date',
            'Clock In',
            'Latitude In',
            'Longitude In',
            'Clock Out',
            'Latitude Out',
            'Longitude Out',
            'Remark',
            'Sync Status',

        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $cellRange = 'A1:N1'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(14);
            },
        ];
    }
}
