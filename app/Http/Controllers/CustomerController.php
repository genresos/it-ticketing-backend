<?php
// Our Controller 
namespace App\Http\Controllers;
  
use Illuminate\Http\Request;
// This is important to add here. 
use PDF;
use App\User;  
class CustomerController extends Controller
{
    public function printPDF()
    {
       $users = User::all();
 
    	$pdf = PDF::loadview('pdf_view',['users'=>$users]);
    	return $pdf->download('laporan-pegawai-pdf');
    }
}
