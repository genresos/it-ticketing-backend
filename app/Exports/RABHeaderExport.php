<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithTitle;
use Carbon\Carbon;
use Auth;
use DateTime;

class RABHeaderExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithEvents
{

    protected $data;

    function __construct($data)
    {
        $this->data = $data;
    }
    /**
     * @return \Illuminate\Support\Collection
     */

    public function collection()
    {
        set_time_limit(0);
        $response = [];

        $separator1 = [];
        $separator1['1'] = "";
        array_push($response, $separator1);

        $head1 = [];
        $head1['1'] = "Print Out Date : ";
        $head1['2'] = date('H:i:s d-m-Y');
        $head1['3'] = "";
        $head1['4'] = "PT.Adyawinsa Telecommunication & Electrical";
        array_push($response, $head1);

        $head2 = [];
        $head2['1'] = "Printed By :";
        $head2['2'] = "System";
        $head2['3'] = "";
        $head2['4'] = "192.168.0.5:3223";
        array_push($response, $head2);


        $head3 = [];
        $head3['1'] = "Project Code : ";
        $head3['2'] = $this->data['project_code'];
        $head3['3'] = "RAB No :";
        $head3['4'] = $this->data['reference'];
        array_push($response, $head3);

        $separator2 = [];
        $separator2['1'] = "";
        array_push($response, $separator2);

        $body1 = [];
        $body1['1'] = "Division :";
        $body1['2'] = $this->data['division_name'];
        $body1['3'] = "Sales/Marketing :";
        $body1['4'] = $this->data['sales_person'];
        array_push($response, $body1);

        $body2 = [];
        $body2['1'] = "Project Name :";
        $body2['2'] = $this->data['project_name'];
        $body2['3'] = "DGM/GM :";
        $body2['4'] = $this->data['project_head_name'];
        array_push($response, $body2);

        $body3 = [];
        $body3['1'] = "Regional :";
        $body3['2'] = $this->data['area_name'];
        $body3['3'] = "Project Manager :";
        $body3['4'] = $this->data['project_manager'];
        array_push($response, $body3);

        $body4 = [];
        $body4['1'] = "Vendor / Operator :";
        $body4['2'] = '';
        $body4['3'] = "PMO - BPC :";
        $body4['4'] = $this->data['pc_user_name'];
        array_push($response, $body4);

        $separator3 = [];
        $separator3['1'] = "";
        array_push($response, $separator3);

        $body5 = [];
        $body5['1'] = "KICK OF MEETING";
        array_push($response, $body5);


        $separator4 = [];
        $separator4['1'] = "";
        array_push($response, $separator4);

        $body6 = [];
        $body6['1'] = "Project Value :";
        $body6['2'] = $this->data['project_value'];
        $body6['3'] = '';
        $body6['4'] = "Project Code :";
        $body6['5'] = $this->data['project_code'];
        array_push($response, $body6);

        $separator5 = [];
        $separator5['1'] = "";
        array_push($response, $separator5);


        $body7 = [];
        $body7['1'] = "Project Time Line";
        array_push($response, $body7);

        $work_start = new DateTime($this->data['work_start']);
        $work_end = new DateTime($this->data['work_end']);

        $interval = $work_start->diff($work_end);
        $months = $interval->format('%m');

        $body8 = [];
        $body8['1'] = "Duration :";
        $body8['2'] = $months;
        $body8['3'] = 'Months';
        $body8['4'] = "Start Date:";
        $body8['5'] = date('d-m-Y', strtotime($this->data['work_start']));
        $body8['6'] = "End Date:";
        $body8['7'] = date('d-m-Y', strtotime($this->data['work_end']));
        array_push($response, $body8);

        $separator6 = [];
        $separator6['1'] = "";
        array_push($response, $separator6);

        $body9 = [];
        $body9['1'] = "Project Risk Management";
        array_push($response, $body9);

        $body10 = [];
        $body10['1'] = "Penallty Cap / Allocation :";
        $body10['2'] = $this->data['risk_management_pct'] * 100 . ' In %';
        $body10['3'] = "In Value:";
        $body10['4'] = $this->data['risk_management'];
        array_push($response, $body10);

        $separator7 = [];
        $separator7['1'] = "";
        array_push($response, $separator7);

        $body11 = [];
        $body11['1'] = "Project Structure Organization";
        array_push($response, $body11);

        $separator8 = [];
        $separator8['1'] = "";
        array_push($response, $separator8);

        $body12 = [];
        $body12['1'] = "Management Cost :";
        $body12['2'] = ":";
        $body12['3'] = $this->data['management_cost_pct'] * 100 . '%';
        $body12['4'] = $this->data['management_cost'];
        $body12['5'] = '';
        $body12['6'] = "*" . $this->data['management_cost_pct'] * 100 . '%' . 'Project Value' . "*";
        array_push($response, $body12);

        $separator9 = [];
        $separator9['1'] = "";
        array_push($response, $separator9);

        $body13 = [];
        $body13['1'] = "Cost Of Money :";
        $body13['2'] = ":";
        $body13['3'] = '1.08%';
        $body13['4'] = $this->data['cost_of_money_permonth'];
        $body13['5'] = '';
        $body13['6'] = "*" . '1.08%' . '/ annum' . "*";
        array_push($response, $body13);

        $separator10 = [];
        $separator10['1'] = "";
        array_push($response, $separator10);

        $body14 = [];
        $body14['1'] = "Margin";
        array_push($response, $body14);

        $separator11 = [];
        $separator11['1'] = "";
        array_push($response, $separator11);

        $body15 = [];
        $body15['1'] = "Total Sales :";
        $body15['2'] = ':';
        $body15['3'] = $this->data['total_sales'];
        $body15['4'] = 0;
        $body15['5'] = 0;
        array_push($response, $body15);

        $body16 = [];
        $body16['1'] = "Total Expenses :";
        $body16['2'] = ':';
        $body16['3'] = $this->data['total_expenses'];
        $body16['4'] = 0;
        $body16['5'] = 0;
        array_push($response, $body16);

        $body17 = [];
        $body17['1'] = "Margin Value :";
        $body17['2'] = ':';
        $body17['3'] = $this->data['margin_value'];
        $body17['4'] = 0;
        $body17['5'] = 0;
        array_push($response, $body17);

        $body17 = [];
        $body17['1'] = "% Margin :";
        $body17['2'] = ':';
        $body17['3'] = $this->data['margin_pct'] . ' %';
        $body17['4'] = 0;
        $body17['5'] = 0;
        array_push($response, $body17);

        return collect($response);
    }
    public function headings(): array
    {
        $header = array(
            'PROJECT BUDGETARY FORM',
        );

        return $header;
    }
    public function title(): string
    {
        return 'Project RAB';
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $arr_count = count(self::collection());
                // $boundary = $arr_count + 1;
                // $border = "A7:D$boundary";
                $cellRange = 'A1:C1'; // All headers
                // $title = 'A2:D5';

                $event->sheet->mergeCells('A1:D2');
                // $event->sheet->getDelegate()->getStyle($title)->getFont('Arial')->setBold(true)->setSize(10);
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont('Arial')->setBold(true)->setSize(16);
                $event->sheet->getDelegate()->getStyle('A7:A33')->getFont('Arial')->setBold(true)->setSize(11);
                $event->sheet->getDelegate()->getStyle('C7:C10')->getFont('Arial')->setBold(true)->setSize(11);
                $event->sheet->getDelegate()->getStyle('D14:D17')->getFont('Arial')->setBold(true)->setSize(11);
                $event->sheet->getDelegate()->getStyle('F17')->getFont('Arial')->setBold(true)->setSize(11);

                // $event->sheet->getDelegate()->getStyle("A8:D$boundary")->getFont('Arial')->setSize(10);


                // $event->sheet->getStyle($border)->applyFromArray([
                //     'borders' => [
                //         'allBorders' => [
                //             'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                //             'color' => ['argb' => '000000'],
                //         ],
                //     ],
                // ]);
                // $event->sheet->getDelegate()->getStyle("C$boundary:D$boundary")->getFont('Arial')->setBold(true)->setSize(14);
            },
        ];
    }
}
