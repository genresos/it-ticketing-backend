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


class AuditAssetExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
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
        $sql = "SELECT aa.id,
                amt.name AS type_name,
                ag.name AS group_name,
                aa.asset_no,
                aa.serial_no,
                aa.qty,
                aa.condition AS asset_condition,
                loc.location_name,
                aa.last_user_id,
                aa.last_user_name,
                p.code AS project_code,
                u.name AS auditor,
                aa.created_at
                FROM 0_audit_asset aa
                INNER JOIN 0_am_types amt ON (aa.type_id = amt.type_id)
                INNER JOIN 0_am_groups ag ON (aa.group_id = ag.group_id)
                INNER JOIN 0_projects p ON (aa.project_no = p.project_no)
                INNER JOIN 0_locations loc ON (aa.location = loc.loc_code)
                INNER JOIN users u ON (aa.created_by = u.id)
                WHERE aa.id != -1";

        $file = DB::connection('mysql')->select(DB::raw($sql));

        $no = 1;
        foreach ($file as $data) {
            $output[] = [
                $no++,
                $data->type_name,
                $data->group_name,
                $data->asset_no,
                $data->serial_no,
                $data->qty,
                $data->asset_condition,
                $data->location_name,
                $data->last_user_id,
                $data->last_user_name,
                $data->project_code,
                $data->auditor,
                $data->created_at,

            ];
        }
        return collect($output);
    }
    public function headings(): array
    {
        return [
            'no',
            'type_name',
            'group_name',
            'asset_no',
            'serial_no',
            'qty',
            'asset_condition',
            'location_name',
            'last_user_id',
            'last_user_name',
            'project_code',
            'auditor',
            'created_at',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $arr_count = count(self::collection());
                $boundary = $arr_count + 1;
                $border = "A2:M$boundary";
                $event->sheet->getDelegate()->getStyle("A1:M1")->getFont('Arial')->setBold(true)->setSize(10);
                $event->sheet->getDelegate()->getStyle("A2:M$boundary")->getFont('Arial')->setSize(10);
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
