<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RABExport implements FromArray, WithMultipleSheets
{
    /**
     * @return \Illuminate\Support\Collection
     */

    private $data;
    private $man_power;

    //     public function collection()
    //     {
    //         set_time_limit(0);
    //         $sql = "SELECT pctg.* FROM 0_project_cost_type_group pctg
    //                 WHERE pctg.inactive = 0";

    //         $aa = DB::select($sql)->all();

    //         return $aa;
    //     }

    public function __construct(array $data)
    {

        $this->data = $data;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function sheets(): array
    {
        $header = json_decode(json_encode($this->data[0]), true);
        $project_value = json_decode(json_encode($this->data[0]['project_value_list']), true);
        $man_power = json_decode(json_encode($this->data[0]['man_power']), true);
        $vehicle = json_decode(json_encode($this->data[0]['vehicle']), true);
        $procurement = json_decode(json_encode($this->data[0]['procurement']), true);
        $tools = json_decode(json_encode($this->data[0]['tools']), true);
        $training = json_decode(json_encode($this->data[0]['training']), true);
        $other_expenses = json_decode(json_encode($this->data[0]['other_expenses']), true);
        $other_info = json_decode(json_encode($this->data[0]['other_info']), true);

        $sheets = [
            'Project RAB' => new RABHeaderExport($header),
            'Project Value' => new RABProjectValueExport($project_value),
            'Internal Man Power' => new RABManPowerExport($man_power),
            'Vehicle & Operation' => new RABVehicleAndOperationExport($vehicle),
            'Procurement' => new RABProcurementExport($procurement),
            'Internal Tools' => new RABInternalToolsExport($tools),
            'Training' => new RABTrainingInvestmentExport($training),
            'Other Expense' => new RABOtherExpenseExport($other_expenses),
            'Other Info' => new RABOtherInformationExport($other_info),
        ];

        return $sheets;
    }

    // public function registerEvents(): array
    // {
    //     return [
    //         AfterSheet::class => function (AfterSheet $event) {
    //             $arr_count = count(self::collection());
    //             $boundary = $arr_count + 1;
    //             $border = "A2:K$boundary";
    //             $event->sheet->getDelegate()->getStyle("A1:K1")->getFont('Arial')->setBold(true)->setSize(10);
    //             $event->sheet->getDelegate()->getStyle("A2:K$boundary")->getFont('Arial')->setSize(10);
    //             $event->sheet->getStyle($border)->applyFromArray([
    //                 'borders' => [
    //                     'allBorders' => [
    //                         'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
    //                         'color' => ['argb' => '000000'],
    //                     ],
    //                 ],
    //             ]);
    //         },
    //     ];
    // }
}
