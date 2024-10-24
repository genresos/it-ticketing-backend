<?php

namespace App\Http\Controllers;

class MessageController extends Controller
{
    //
    public static function UserInactive(){
        return response()->json([
            'status' => 'failed',
            'message' => 'Akun anda sudah dinonaktifkan!'
        ], 402);
    }

    public static function AccessDenied(){
        return response()->json([
            'status' => 'failed',
            'message' => 'Email atau Password salah!'
        ], 403);
    }
    public static function InvalidCredentials(){
        return response()->json([
            'status' => 'failed',
            'message' => 'Email tidak ditemukan!'
        ], 422);
    }
    public static function CodeNotMatchException(){
        return response()->json([
            'status' => 'failed',
            'message' => 'Kode Verifikasi Salah!'
        ], 403);
    }
    public static function CheckUserException(){
        return response()->json([
            'status' => 'failed',
            'message' => 'NIK Sudah Terdaftar!'
        ], 402);
    }
    public static function NikException(){
        return response()->json([
            'status' => 'failed',
            'message' => 'NIK Tidak Terdaftar'
        ], 402);
    }
}  
