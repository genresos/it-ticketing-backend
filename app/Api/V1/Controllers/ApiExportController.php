<?php

namespace App\Api\V1\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\JsonExport;
use App\Imports\JsonImport;
use Illuminate\Support\Facades\Storage;

class ApiExportController extends Controller
{
    public function exportJsonToExcel(Request $request)
    {
        // Data JSON untuk diimpor
        $json = json_encode($request->data);
        // return $request->data;
        // Konversi JSON ke array
        $data = json_decode($json, true);

        // Ambil keys dari array pertama untuk dijadikan headings
        $headings = !empty($data) ? array_keys($data[0]) : [];

        // Export data ke Excel
        return Excel::download(new JsonExport($data, $headings), 'users.xlsx');
    }
}
