<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use App\Http\Controllers\ProjectBudgetController;
use App\Query\QueryProjectBudget;
use Carbon\Carbon;
use Auth;

class ProjectBudgetUseExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{

    protected $project_budget_id;

    function __construct($project_budget_id)
    {
        $this->project_budget_id = $project_budget_id;
    }
    /**
     * @return \Illuminate\Support\Collection
     */

    public function collection()
    {
        set_time_limit(0);
        $response = [];

        $budget_id = $this->project_budget_id;
        $used_ca = QueryProjectBudget::ca_used_budget_amount($budget_id);
        $sql_project_code = "SELECT p.code FROM 0_projects p
                            LEFT OUTER JOIN 0_project_budgets pb ON (pb.project_no = p.project_no)
                            WHERE pb.project_budget_id =$budget_id";

        $get_project_code = DB::select(DB::raw($sql_project_code));
        foreach ($get_project_code as $data) {
            $project_code = $data->code;
        }
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
        $head2['2'] = Auth::guard()->user()->name;
        $head2['3'] = "";
        $head2['4'] = "192.168.0.5:3223";
        array_push($response, $head2);


        $head3 = [];
        $head3['1'] = "Project Code : ";
        $head3['2'] = $project_code;
        $head3['3'] = "Budget ID :";
        $head3['4'] = $budget_id;
        array_push($response, $head3);

        $separator2 = [];
        $separator2['1'] = "";
        array_push($response, $separator2);

        $tittle = [];
        $tittle['1'] = "Document Type";
        $tittle['2'] = "Document No.";
        $tittle['3'] = "Date";
        $tittle['4'] = "Amount";
        array_push($response, $tittle);

        $separator3 = [];
        $separator3['1'] = "";
        array_push($response, $separator3);

        $query_ca = DB::select(DB::raw($used_ca));
        foreach ($query_ca as $data_ca) {
            $ca_date = date_create($data_ca->tran_date);

            $tmp_ca = [];
            $tmp_ca['doc_type'] = "Cash Advance";
            $tmp_ca['doc_no'] = $data_ca->doc_no;
            $tmp_ca['tran_date'] = date_format($ca_date, "d/m/Y");
            $tmp_ca['used_amount'] = $data_ca->used_amount;

            // $response['used_ca'][] = $tmp_ca;
            array_push($response, $tmp_ca);
        }

        $used_po = QueryProjectBudget::po_used_budget_amount($budget_id);

        $query_po = DB::select(DB::raw($used_po));
        foreach ($query_po as $data_po) {
            $po_date = date_create($data_po->ord_date);

            $tmp_po = [];
            $tmp_po['doc_type'] = "Purchase Order";
            $tmp_po['doc_no'] = $data_po->doc_no;
            $tmp_po['tran_date'] = date_format($po_date, "d/m/Y");
            $tmp_po['used_amount'] = $data_po->used_amount;
            // $response['used_po'][] = $tmp_po;
            array_push($response, $tmp_po);
        }

        $used_gl = QueryProjectBudget::gl_used_budget_amount($budget_id);

        $query_gl = DB::select(DB::raw($used_gl));
        foreach ($query_gl as $data_gl) {
            $gl_date = date_create($data_gl->tran_date);

            $tmp_gl = [];
            $tmp_gl['doc_type'] = "Bank Payment";
            $tmp_gl['doc_no'] = $data_gl->doc_no;
            $tmp_gl['tran_date'] = date_format($gl_date, "d/m/Y");
            $tmp_gl['used_amount'] = $data_gl->used_amount;

            array_push($response, $tmp_gl);
        }

        $used_spk = QueryProjectBudget::spk_used_budget_amount($budget_id);

        $query_spk = DB::select(DB::raw($used_spk));
        foreach ($query_spk as $data_spk) {
            $spk_date = date_create($data_spk->tran_date);

            $tmp_spk = [];
            $tmp_spk['doc_type'] = "SPK";
            $tmp_spk['doc_no'] = $data_spk->doc_no;
            $tmp_spk['tran_date'] = date_format($spk_date, "d/m/Y");
            $tmp_spk['used_amount'] = $data_spk->used_amount;

            array_push($response, $tmp_spk);
        }

        array_push($response);


        $total_po = ProjectBudgetController::budget_use_po($budget_id);
        $total_ca = ProjectBudgetController::budget_use_ca($budget_id);
        $total_gl = ProjectBudgetController::budget_use_gl($budget_id);
        $total_spk = ProjectBudgetController::budget_use_spk($budget_id);

        $total_used = $total_po + $total_ca + $total_gl + $total_spk;

        $total = [];
        $total['1'] = "";
        $total['2'] = "";
        $total['3'] = "Total :";
        $total['4'] = "$total_used";
        array_push($response, $total);

        return collect($response);
    }
    public function headings(): array
    {
        $header = array(
            'PROJECT BUDGET USED',
        );

        return $header;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $arr_count = count(self::collection());
                $boundary = $arr_count + 1;
                $border = "A7:D$boundary";
                $cellRange = 'A1:C1'; // All headers
                $title = 'A2:D5';

                $event->sheet->mergeCells('A1:D2');
                $event->sheet->getDelegate()->getStyle($title)->getFont('Arial')->setBold(true)->setSize(10);
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont('Arial')->setBold(true)->setSize(16);
                $event->sheet->getDelegate()->getStyle('A7:D7')->getFont('Arial')->setItalic(true)->setBold(true)->setSize(11);
                $event->sheet->getDelegate()->getStyle("A8:D$boundary")->getFont('Arial')->setSize(10);


                $event->sheet->getStyle($border)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                ]);
                $event->sheet->getDelegate()->getStyle("C$boundary:D$boundary")->getFont('Arial')->setBold(true)->setSize(14);
            },
        ];
    }
}
