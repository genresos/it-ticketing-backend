<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
class TestController extends Controller
{
    public function index(){
    $data = Carbon::now();

    echo $data;
    }
}
