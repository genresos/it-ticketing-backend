<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithMapping;

class RABCashFlowExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithColumnFormatting, WithMapping
{
    protected $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function map($row): array
    {
        return [
            $row['trans_no'],
            $row['site_no'],
            $row['du_id'],
            $row['sow'],
            $row['po_line'],
            $row['qty'],
            $row['price'],
            $row['total_amount'],
            $row['remark']
        ];
    }

    public function headings(): array
    {
        return [
            'Trans No.',
            'Site No.',
            'DU ID',
            'SOW',
            'PO Line',
            'Qty',
            'Price',
            'Total',
            'Remark'
        ];
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'General';
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
}
