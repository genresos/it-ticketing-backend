<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class ListProjectExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    /**
     * @return \Illuminate\Support\Collection
     */

    public function collection()
    {
        set_time_limit(0);

        $sql = "SELECT 
				p.project_no, 
				p.code, 
				p.name, 
				d.name as division_name, 
				m.name as project_manager, 
				ps.name as status
			FROM 0_projects p
			LEFT JOIN 0_hrm_divisions d ON (d.division_id = p.division_id)
			LEFT JOIN 0_project_status ps ON (ps.status_id = p.project_status_id)
			LEFT JOIN 0_members m ON (m.person_id = p.person_id)";

        $project_cost_type_group = DB::select(DB::raw($sql));
        foreach ($project_cost_type_group as $data) {
            $output[] = [
                $data->project_no,
                $data->code,
                $data->name,
                $data->division_name,
                $data->project_manager,
                $data->status,
            ];
        }
        return collect($output);
    }
    public function headings(): array
    {
        return [
            '#',
            'Code',
            'Project Name',
            'Division',
            'Project Manager',
            'Status',

        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $cellRange = 'A1:G1'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(14);
            },
        ];
    }
}
