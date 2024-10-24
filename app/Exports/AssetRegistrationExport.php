<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use App\Api\V1\Controllers\AssetsregistrationController;
use Carbon\Carbon;
use Auth;

class AssetRegistrationExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{

    protected $from_date, $to_date, $emp_id, $type_id, $area_id;

    function __construct($from_date, $to_date, $emp_id, $type_id, $area_id)
    {
        $this->from_date = $from_date;
        $this->to_date = $to_date;
        $this->emp_id = $emp_id;
        $this->type_id = $type_id;
        $this->area_id = $area_id;
    }
    /**
     * @return \Illuminate\Support\Collection
     */

    public function collection()
    {
        set_time_limit(0);
        $response = [];

        $from_date = $this->from_date;
        $to_date = $this->to_date;
        $emp_id = $this->emp_id;
        $type_id = $this->type_id;
        $area_id = $this->area_id;

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

        $separator2 = [];
        $separator2['1'] = "";
        array_push($response, $separator2);

        $separator3 = [];
        $separator3['1'] = "";
        array_push($response, $separator3);

        $tittle = [];
        $tittle['1'] = "Asset Type";
        $tittle['2'] = "Asset No";
        $tittle['3'] = "Asset Name";
        $tittle['4'] = "Reg. Period";
        $tittle['5'] = "Emp ID";
        $tittle['6'] = "Emp Name";
        $tittle['7'] = "Project Code";
        $tittle['8'] = "Area";
        $tittle['9'] = "Status";
        $tittle['10'] = "File Count";
        array_push($response, $tittle);

        $separator3 = [];
        $separator3['1'] = "";
        array_push($response, $separator3);

        $sql = "SELECT
						ar.id,
						ar.uniq_id,
						ar.asset_type_name,
						ar.asset_group_name,
						ar.asset_id,
						CONCAT(ar.trx_date) AS registration_period,
						e.emp_id,
						e.name AS employee_name,
						ar.project_code,
											a.name as area_name,
											CASE WHEN ((ar.asset_type_name='Kendaraan Mobil' || ar.asset_type_name='Kendaraan Motor') AND ar.approval_position < 3) THEN 'Need Approval AM' ELSE
										CASE WHEN (ar.approval_position = 1) THEN 'Need Approval PM'
						WHEN (ar.approval_position = 2) THEN 'Need Approval AM'
									WHEN (ar.approval_position=3) THEN 'Approved'
												END
													END approval_status_name,
						COUNT(file_path) as file_path,
											ai.doc_no,
											CASE WHEN ((ar.asset_type_name='Kendaraan Mobil' || ar.asset_type_name='Kendaraan Motor') AND ar.approval_position<3) THEN 2 ELSE ar.approval_position END approval_position,
											YEAR(ar.trx_date) AS year,
											MONTH(ar.trx_date) AS month
				FROM asset_registration ar
				LEFT OUTER JOIN 0_projects p ON (ar.project_code = p.code)
                LEFT JOIN 0_project_area a ON (a.area_id = p.area_id)
                LEFT OUTER JOIN users u ON (u.id = ar.user_id)
                LEFT OUTER JOIN 0_hrm_employees e ON (u.emp_no = e.id)
								LEFT JOIN 0_am_issues ai ON (ai.issue_id = ar.issue_id)
				where ar.id !=''";

        if ($emp_id != 0) {
            $sql .= " AND e.emp_id= '$emp_id'";
        }
        //
        if ($type_id != 0) {
            $sql .= " AND ar.asset_type_id= $type_id";
        }
        $sql .= " AND DATE(ar.trx_date) BETWEEN '$from_date' AND '$to_date'";

        if ($area_id != 0) {
            $sql .= " AND a.area_id = $area_id";
        }
        $sql .= " GROUP BY ar.uniq_id";

        $query = DB::select(DB::raw($sql));

        foreach ($query as $data) {
            $reg_period = date_create($data->registration_period);
            $tmp = [];
            $tmp['asset_type'] = $data->asset_type_name;
            $tmp['asset_id'] = $data->asset_id;
            $tmp['asset_name'] = $data->asset_group_name;
            $tmp['registration_period'] = date_format($reg_period, "d/m/Y");
            $tmp['emp_id'] = $data->emp_id;
            $tmp['employee_name'] = $data->employee_name;
            $tmp['project_code'] = $data->project_code;
            $tmp['area_name'] = $data->area_name;
            $tmp['approval_status_name'] = $data->approval_status_name;
            $tmp['file_path'] = $data->file_path;

            array_push($response, $tmp);
        }

        array_push($response);

        return collect($response);
    }
    public function headings(): array
    {
        $header = array(
            'ASSET REGISTRATION',
        );

        return $header;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $arr_count = count(self::collection());
                $boundary = $arr_count + 1;
                $border = "A7:J$boundary";
                $cellRange = 'A1:C1'; // All headers
                $title = 'A2:D5';

                $event->sheet->mergeCells('A1:D2');
                $event->sheet->getDelegate()->getStyle($title)->getFont('Arial')->setBold(true)->setSize(10);
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont('Arial')->setBold(true)->setSize(16);
                $event->sheet->getDelegate()->getStyle('A7:J7')->getFont('Arial')->setItalic(true)->setBold(true)->setSize(11);
                $event->sheet->getDelegate()->getStyle("A8:J$boundary")->getFont('Arial')->setSize(10);
                $event->sheet->getStyle($border)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                ]);
                $event->sheet->getDelegate()->getStyle("C$boundary:J$boundary")->getFont('Arial')->setBold(true)->setSize(14);
            },
        ];
    }
}
