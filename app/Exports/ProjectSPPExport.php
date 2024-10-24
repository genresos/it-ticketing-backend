<?php

namespace App\Exports;

use JWTAuth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Auth;
use DateTime;


class ProjectSPPExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    /**
     * @return \Illuminate\Support\Collection
     */

     function __construct($from, $to)
     {
         $this->from = $from;
         $this->to = $to;
     }


    //     public function collection()
    //     {
    //         set_time_limit(0);
    //         $sql = "SELECT pctg.* FROM 0_project_cost_type_group pctg
    //                 WHERE pctg.inactive = 0";

    //         $aa = DB::select($sql)->all();

    //         return $aa;
    //     }

    public function getLogSpp(int $spp_id, int $approval){
        $sql = "SELECT l.id, 
                        CASE
                            WHEN l.status = 1 THEN 'Approved'
                            WHEN l.status = 2 THEN 'Disapproved'
                            WHEN l.status = 3 THEN 'Pending'
                            WHEN l.status = 4 THEN 'Review'
                            WHEN l.status = 5 THEN 'Incorrect'
                            ELSE 'unknown'
                        END AS status_name,
                        l.remark,
                        l.last_update
                    FROM 0_project_spp_log AS l
                    WHERE l.spp_id = $spp_id";

        $sql .= " AND l.approval = $approval";

        $response = DB::select(DB::raw($sql));

        $data = [];

        foreach ($response as $val) {
            $tmp = $val->status_name . "_" . $val->remark . " ($val->last_update)";
            array_push($data, $tmp);
        }

        return implode("; ", $data);
    }

    public function collection()
    {
        $currentUser = JWTAuth::parseToken()->authenticate();
        $user_id = Auth::guard()->user()->id;
        $level = Auth::guard()->user()->approval_level;
        $from = $this->from;
        $to = $this->to;

        set_time_limit(0);
        $response = [];

        $sql = "SELECT sp.spp_id, 
                    sp.spk_no,
                    sp.po_ref,
                    sp.order_no,
                    sp.trans_date,
                    supl.supp_name AS supplier_name,
                    sp.termin,
                    sp.termin_pct,
                    sp.po_lines,
                    sp.approval,
                    CASE
                        WHEN sp.approval = 1 AND sp.status = 0 THEN 'On PM' -- admin create, lanjut approval PM
                        WHEN sp.approval = 2 AND sp.status = 3 THEN 'Pending PM'
                        WHEN sp.approval = 2 AND sp.status = 1 THEN 'On GM' -- PM approved, lanjut approval GM
                        WHEN sp.approval = 3 AND sp.status = 3 THEN 'Pending GM'
                        WHEN sp.approval = 3 AND sp.status = 1 THEN 'On BPC' -- GM approved, lanjut approval BPC
                        WHEN sp.approval = 7 AND sp.status = 3 THEN 'Pending BPC'
                        WHEN sp.approval = 7 AND sp.status = 1 THEN 'BPC Approved' -- change to On Finance
                        WHEN sp.approval = 8 AND sp.status = 3 THEN 'Pending Finance'
                        WHEN sp.approval = 8 AND sp.status = 4 THEN 'On Finance Review'
                        WHEN sp.approval = 8 AND sp.status = 5 THEN 'Incorrect Invoice'
                        WHEN sp.approval = 8 AND sp.status = 1 THEN 'Invoice Approved'
                        WHEN sp.status = 2 THEN 'DISAPPROVE'
                        ELSE 'unknown'
                    END AS pic,
                    sp.status,
                    sp.created_at,
                    u.name AS created_by,
                    spd.project_code
                FROM 0_project_spp AS sp
                JOIN 0_suppliers AS supl ON (supl.supplier_id = sp.supplier_id)
                JOIN users AS u ON (u.id = sp.created_by)
                JOIN 0_project_spp_detail AS spd ON (spd.spp_id = sp.spp_id)
                WHERE sp.spp_id != -1";

        if (!empty($from) && !empty($to)) {
            $sql .= " AND sp.created_at BETWEEN '$from' AND '$to'";
        }

        $sql .= " GROUP BY sp.spp_id ORDER BY sp.created_at DESC";

        $spp_list = DB::select(DB::raw($sql));
        foreach ($spp_list as $data) {
            $so_info = DB::table('0_sales_orders')->where('order_no', $data->order_no)->first();

            $log_pm = $this->getLogSpp($data->spp_id, 1);
            $log_gm = $this->getLogSpp($data->spp_id, 2);
            $log_bpc = $this->getLogSpp($data->spp_id, 3);
            $log_finance = $this->getLogSpp($data->spp_id, 7);

            $output[] = [
                $data->spp_id,
                $data->spk_no,
                $data->po_ref,
                $so_info->reference,
                $data->project_code,
                $data->trans_date,
                $data->supplier_name,
                $data->termin,
                $data->termin_pct,
                $data->po_lines,
                $data->pic,
                $data->created_by,
                $data->created_at,
                $log_pm,
                $log_gm,
                $log_bpc,
                $log_finance
            ];
        }

        return collect($output);
    }
    public function headings(): array
    {
        return [
            'Id',
            'No SPK',
            'PO Subcont',
            'PO Customer',
            'Project Code',
            'Trans Date',
            'Supplier Name',
            'Termin',
            'Termin Percentage',
            'PO Lines',
            'Approval Status',
            'Created By',
            'Created At',
            'Log PM',
            'Log GM',
            'Log BPC',
            'Log Finance'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $cellRange = 'A1:Q1'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(14);
            },
        ];
    }
}
