<?php

namespace App\Exports;

use App\Cashadvance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProjectPerformanceSummaryExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithStyles, WithColumnFormatting, WithCustomStartCell
{

    protected $project_no;
    function __construct($project_no)
    {
        $this->project_no = $project_no;
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function columnWidths(): array
    {
        return [
            'A' => 55,
            'B' => 45,
        ];
    }
    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_DATE_DATETIME,
        ];
    }
    public function collection()
    {
        set_time_limit(0);

        $sql = "SELECT * FROM 0_projects WHERE project_no = $this->project_no";


        $projects = DB::select(DB::raw($sql));
        foreach ($projects as $data) { }

        $output[] =
            [
                $data->code,
                Carbon::now(),
            ];
        return collect($output);
    }

    public function headings(): array
    {
        return [];
    }
}
