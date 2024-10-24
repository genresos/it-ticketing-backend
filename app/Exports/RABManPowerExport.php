<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class RABManPowerExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithColumnFormatting, WithMapping, WithEvents
{
    protected $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function map($row): array
    {
        return [
            $row['id'],
            $row['position'],
            $row['emp_id'] . '_' . $row['name'],
            $row['qty'],
            $row['duration'],
            ($row['duration_type'] == 1) ? "Days" : "Months",
            ($row['percentage'] * 100) . '%',
            $row['total_amount'],
            $row['remark']
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'Position',
            'Employee',
            'Qty',
            'Duration',
            'Duration Type',
            'Percentage',
            'Sub Total',
            'Remark'
        ];
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'Internal Man Power';
    }

    public function columnFormats(): array
    {
        return [
            'A' => '#,##0',
            'B' => '#,##0',
            'C' => '#,##0',
            'D' => '#,##0',
            'E' => '#,##0',
            'F' => '#,##0',
            'G' => '#,##0',
            'H' => '#,##0',
            'I' => '#,##0'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $arr_count = count($this->rows);
                $cellRange = 'A1:Z1'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont('Arial')->setBold(true)->setSize(12);
            },
        ];
    }
}
