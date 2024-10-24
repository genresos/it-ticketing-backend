<?php

namespace App\Exports;

use App\User; //Ap
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Contracts\View\View; //Harus diimport untuk men-convert blade menjadi file excel
use Maatwebsite\Excel\Concerns\FromView; //Harus diimport untuk men-convert blade menjadi file excel
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeExport;

class TestingExport implements FromView, ShouldAutoSize, WithEvents
{

    public function view(): View
    {

        $sql = DB::table('0_projects')
            ->where('inactive', 0)
            ->get();
        //export adalah file export.blade.php yang ada di folder views
        return view('exp', [
            //data adalah value yang akan kita gunakan pada blade nanti
            //User::all() mengambil seluruh data user dan disimpan pada variabel data
            'data' => $sql
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function (AfterSheet $event) {

                $event->sheet->getDelegate()->getRowDimension(1)->setRowHeight(30);
                $event->sheet->getDelegate()->getColumnDimension('A')->setWidth(50);
                $event->sheet->setFontFamily('A1:AC100', 'Arial');
                $event->sheet->setFontSize('A1', '16');
                $event->sheet->setFontSize('A2:D3', '10');
                $event->sheet->setFontSize('A5', '10');
                $event->sheet->setFontSize('A6:E8', '10');
                $event->sheet->setFontSize('A11', '10');
                $event->sheet->setFontSize('A12:B18', '10');
            },
        ];
    }
}
