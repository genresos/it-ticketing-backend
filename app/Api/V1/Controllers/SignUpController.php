<?php

namespace App\Api\V1\Controllers;

use Config;
use App\User;
use Illuminate\Support\Facades\Crypt;
use Tymon\JWTAuth\JWTAuth;
use App\Http\Controllers\Controller;
use App\Api\V1\Requests\SignUpRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NikHttpException;
use Symfony\Component\HttpKernel\Exception\CheckUserHttpException;
use Symfony\Component\HttpKernel\Exception\CodeNotMatchHttpException;
use Illuminate\Support\Facades\DB;

class SignUpController extends Controller
{
    public function signUp(SignUpRequest $request, JWTAuth $JWTAuth)
    {
        // $employee  =  DB::table('0_hrm_employees')->where("emp_id",$request->emp_id)->first();

        // $checkemp  =  DB::table('0_hrm_employees')->where("emp_id",$request->emp_id)->where("inactive","=",0)->first();
        $checkuser =  DB::table('users')->where("emp_id", $request->emp_id)->first();

        // $sql = DB::table('signup_verification')->where("email",$request->email)->latest("id")->first();
        // $code = $sql->code_verification;
        // $decrypt_result = Crypt::decryptString($code);



        if (2024 != $request->code) {

            throw new CodeNotMatchHttpException();
        } else if (2024 == $request->code) {
            if ($checkuser) {
                throw new CheckUserHttpException();
            } else {
                $user = new User($request->all());


                if (!$user->save()) {
                    throw new HttpException(500);
                }

                if (!Config::get('boilerplate.sign_up.release_token')) {
                    return response()->json([
                        'status' => 'ok'
                    ], 201);
                }

                $token = $JWTAuth->fromUser($user);
                return response()->json([
                    'status' => 'ok',
                    'token' => $token
                ], 201);
            }
        }
    }
}
