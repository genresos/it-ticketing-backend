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


class AuditMaterialExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
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
        $sql = "SELECT am.id,
                am.item_code,
                am.description,
                (
                SELECT location_name FROM 0_locations loc
                WHERE loc_code = am.current_location
                ) current_location,
                (
                SELECT location_name FROM 0_locations loc
                WHERE loc_code = am.origin_location
                ) origin_location,
                am.qty,
                am.uom,
                p.code AS project_code,
                am.owner,
                am.remark,
                u.name AS auditor
                FROM 0_audit_material am
                INNER JOIN 0_projects p ON (am.project_no = p.project_no)
                INNER JOIN users u ON (u.id = am.created_by)
                WHERE am.id != -1";

        $file = DB::connection('mysql')->select(DB::raw($sql));

        $no = 1;
        foreach ($file as $data) {
            $output[] = [
                $no++,
                $data->item_code,
                $data->description,
                $data->current_location,
                $data->origin_location,
                $data->qty,
                $data->uom,
                $data->project_code,
                $data->owner,
                $data->remark,
                $data->auditor,
            ];
        }
        return collect($output);
    }
    public function headings(): array
    {
        return [
            'no',
            'item_code',
            'description',
            'current_location',
            'origin_location',
            'qty',
            'uom',
            'project_code',
            'owner',
            'remark',
            'auditor',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $arr_count = count(self::collection());
                $boundary = $arr_count + 1;
                $border = "A2:K$boundary";
                $event->sheet->getDelegate()->getStyle("A1:K1")->getFont('Arial')->setBold(true)->setSize(10);
                $event->sheet->getDelegate()->getStyle("A2:K$boundary")->getFont('Arial')->setSize(10);
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
