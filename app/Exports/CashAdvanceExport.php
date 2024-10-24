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


class CashAdvanceExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
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
        $date = date('d-m-Y');
        $filename = "CA_LIST_" . $date . '.json';
        $json = Storage::disk('sftp')->get("/IT/RIAN/data_ca_mentah/$filename");
        $file = json_decode($json);

        foreach ($file as $data) {
            $ca_tran_date = $data->ca_tran_date;
            $release_date = $data->release_date;
            $ca_due_date = $data->ca_due_date;

            $output[] = [
                $data->ca_trans_no,
                $data->doc_type_name,
                $data->ca_reference,
                $ca_tran_date,
                $data->emp_id,
                $data->emp_name,
                $data->division_name,
                $data->ca_amount,
                $data->cad_release_amount,
                $data->ca_deduction,
                $release_date,
                $data->ca_remark,
                $ca_due_date,
                $data->ca_approval_status,
                $data->bank_account_name,
                $data->project_code,
                $data->project_name,
                $data->project_status,
                $data->project_manager,
                $data->stl_trans_no,
                $data->stl_reference,
                $data->stl_amount,
                $data->stl_act_amount,
                $data->approval_date,
                $data->stl_approval,
                $data->stl_remark,
                $data->stl_cost_type,
                $data->stl_dtl_amount,
                $data->stl_dtl_allocate_ear_amount,
                $data->ca_aging,
                $data->is_outer_area,

            ];
        }
        return collect($output);
    }
    public function headings(): array
    {
        return [
            'ca_trans_no',
            'doc_type_name',
            'ca_reference',
            'ca_tran_date',
            'emp_id',
            'emp_name',
            'division_name',
            'ca_amount',
            'cad_release_amount',
            'ca_deduction',
            'release_date',
            'ca_remark',
            'ca_due_date',
            'ca_approval_status',
            'bank_account_name',
            'project_code',
            'project_name',
            'project_status',
            'project_manager',
            'stl_trans_no',
            'stl_reference',
            'stl_amount',
            'stl_act_amount',
            'approval_date',
            'stl_approval',
            'stl_remark',
            'stl_cost_type',
            'stl_dtl_amount',
            'stl_dtl_allocate_ear_amount',
            'ca_aging',
            'is_outer_area',
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
