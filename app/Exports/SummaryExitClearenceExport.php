<?php

namespace App\Exports;

use App\Cashadvance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\EmployeesController;


class SummaryExitClearenceExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    /**
     * @return \Illuminate\Support\Collection
     */


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
        set_time_limit(0);
        $response = [];
        $from_date =
            date("Y-m-d", strtotime(date(
                "Y-m-d",
                strtotime(date("Y-m-d"))
            ) . "-1 year"));
        $to_date =
            date("Y-m-d", strtotime(date(
                "Y-m-d",
                strtotime(date("Y-m-d"))
            ) . "+1 day"));

        $myArray = EmployeesController::summary_exit_clearences(
            0,
            0,
            $from_date,
            $to_date,
            '',
            ''
        );

        $no = 1;
        foreach ($myArray as $data) {
            $output[] = [

                $no++,
                $data['emp_id'],
                $data['emp_name'],
                $data['division_name'],
                $data['level_name'],
                $data['join_date'],
                $data['due_date'],
                $data['last_date'],
                $data['reason'],
                $data['pm_name'],
                $data['ec_status'],
                $data['ec_deduction'],
                $data['dept_terkait'],
                $data['dept_head_terkait'],
                $data['am_admin'],
                $data['am_dept'],
                $data['ga_admin'],
                $data['ga_dept'],
                $data['fa_admin'],
                $data['fa_dept'],
                $data['pc_admin'],
                $data['pc_dept'],
                $data['hr_admin'],
                $data['hr_recruitment'],
                $data['hr_payroll'],
                $data['hr_dept'],
                $data['fa_dir'],
            ];
        }
        return collect($output);
    }
    public function headings(): array
    {
        return [
            'No.',
            'Employee ID',
            'Employee Name',
            'Division Name',
            'Level',
            'Join Date',
            'Due Date',
            'Last Date',
            'Reason',
            'Project Manager',
            'Status',
            'Deduction',
            'PM Approval',
            'Dept. Approval',
            'Asset Management Approval',
            'Asset Management Head Approval',
            'General Affair Approval',
            'General Affair Head Approval',
            'Finance Approval',
            'Finance Head Approval',
            'Project Control Approval',
            'Project Control Head Approval',
            'Human Resource',
            'Human Resource Recruiter',
            'Human Resource Payroll',
            'Human Resource Head',
            'Finance Director',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $arr_count = count(self::collection());
                $boundary = $arr_count + 1;
                $border = "A2:AA$boundary";
                $event->sheet->getDelegate()->getStyle("A1:AA1")->getFont('Arial')->setBold(true)->setSize(10);
                $event->sheet->getDelegate()->getStyle("A2:AA$boundary")->getFont('Arial')->setSize(10);
                $event->sheet->getStyle($border)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                ]);
            },
        ];
    }
}
