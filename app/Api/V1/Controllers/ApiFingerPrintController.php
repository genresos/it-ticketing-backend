<?php

namespace App\Api\V1\Controllers;

use JWTAuth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\EmployeesController;
use Illuminate\Http\Request;
use Auth;
use App\Modules\PaginationArr;
use App\Modules\InputList;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;


class ApiFingerPrintController extends Controller
{

    public function store_to_database()
    {
        $path = public_path() . "/FingerPrint.json";

        $json = json_decode(file_get_contents($path), true);

        DB::beginTransaction();
        try {
            foreach ($json['bandung'] as $dataBandung) {
                DB::table('machine_finger')
                    ->insert(array(
                        'pin' => $dataBandung['pin'],
                        'waktu' => $dataBandung['waktu'],
                        'status' => $dataBandung['status']
                    ));
            }

            foreach ($json['jakarta'] as $dataJakarta) {
                DB::table('machine_finger')
                    ->insert(array(
                        'pin' => $dataJakarta['pin'],
                        'waktu' => $dataJakarta['waktu'],
                        'status' => $dataJakarta['status']
                    ));
            }

            foreach ($json['semarang'] as $dataSemarang) {
                DB::table('machine_finger')
                    ->insert(array(
                        'pin' => $dataSemarang['pin'],
                        'waktu' => $dataSemarang['waktu'],
                        'status' => $dataSemarang['status']
                    ));
            }

            foreach ($json['surabaya'] as $dataSurabaya) {
                DB::table('machine_finger')
                    ->insert(array(
                        'pin' => $dataSurabaya['pin'],
                        'waktu' => $dataSurabaya['waktu'],
                        'status' => $dataSurabaya['status']
                    ));
            }

            DB::commit();

            return response()->json([
                'success' => true
            ]);
        } catch (Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }
    public function store_to_json()
    {
        $surabayaip =  '192.168.15.17';
        $semarangip =  '192.168.21.13';
        // $jakarta1ip =  '192.168.0.13';
        $jakarta2ip =  '192.168.0.17';
        $bandungip =  '192.168.12.3';

        $data = array();
        $bandung = $this->FingerPrintPull($bandungip);
        $jakarta = $this->FingerPrintPull($jakarta2ip);
        $semarang = $this->FingerPrintPull($semarangip);
        $surabaya = $this->FingerPrintPull($surabayaip);

        $data['bandung'] =  $bandung;
        $data['jakarta'] =  $jakarta;
        $data['semarang'] =  $semarang;
        $data['surabaya'] =  $surabaya;

        Storage::disk('public')->put('FingerPrint.json', json_encode($data));
        return response()->json([
            'success' => true
        ]);
    }
    private function FingerPrintPull($ip)
    {
        $IP = $ip;
        $Key = 0;
        if (empty($IP)) $IP = "192.168.0.13";
        if (empty($Key)) $Key = "0";


        $Connect = fsockopen($IP, "80", $errno, $errstr, 1);
        if ($Connect) {
            $soap_request = "<GetAttLog>
            <ArgComKey xsi:type=\"xsd:integer\">" . $Key . "</ArgComKey>
            <Arg><PIN xsi:type=\"xsd:integer\">All</PIN></Arg>
            </GetAttLog>";

            $newLine = "\r\n";
            fputs($Connect, "POST /iWsService HTTP/1.0" . $newLine);
            fputs($Connect, "Content-Type: text/xml" . $newLine);
            fputs($Connect, "Content-Length: " . strlen($soap_request) . $newLine . $newLine);
            fputs($Connect, $soap_request . $newLine);
            $buffer = "";
            while ($Response = fgets($Connect, 1024)) {
                $buffer = $buffer . $Response;
            }
        } else echo "Koneksi Gagal";

        $buffer = $this->Parse_Data($buffer, "<GetAttLogResponse>", "</GetAttLogResponse>");
        $buffer = explode("\r\n", $buffer);

        for ($a = 0; $a < count($buffer); $a++) {
            $data = $this->Parse_Data($buffer[$a], "<Row>", "</Row>");

            $export[$a]['pin'] = $this->Parse_Data($data, "<PIN>", "</PIN>");
            $export[$a]['waktu'] = $this->Parse_Data($data, "<DateTime>", "</DateTime>");
            $export[$a]['status'] = $this->Parse_Data($data, "<Status>", "</Status>");
        }

        return $export;
    }

    function Parse_Data($data, $p1, $p2)
    {
        $data = " " . $data;
        $hasil = "";
        $awal = strpos($data, $p1);
        if ($awal != "") {
            $akhir = strpos(strstr($data, $p1), $p2);
            if ($akhir != "") {
                $hasil = substr($data, $awal + strlen($p1), $akhir - strlen($p1));
            }
        }
        return $hasil;
    }
}
