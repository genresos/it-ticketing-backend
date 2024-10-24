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
        $employee  =  DB::table('0_hrm_employees')->where("emp_id",$request->emp_id)->first();
        $users_0   =  DB::table('0_users')->where("emp_id",$request->emp_id)->first();
        $checkemp  =  DB::table('0_hrm_employees')->where("emp_id",$request->emp_id)->where("inactive","=",0)->first();
        $checkuser =  DB::table('users')->where("emp_id",$request->emp_id)->first();
      
        $sql = DB::table('signup_verification')->where("email",$request->email)->latest("id")->first();
        $code = $sql->code_verification;
        $decrypt_result = Crypt::decryptString($code);
	if($decrypt_result != $request->code){

			throw new CodeNotMatchHttpException();

		}else if($decrypt_result == $request->code){
            if(!empty($checkemp)){
                if($checkuser){
                    throw new CheckUserHttpException();
                }else{
                    $user = new User($request->all());
                
                        if(!empty($users_0->id)){
                            $user->old_id = $users_0->id;
                        }
                        if(!empty($users_0->person_id)){
                            $user->person_id = $users_0->person_id;
                        }
                        if(!empty($users_0->division_id)){
                            $user->division_id = $users_0->division_id;
                        }
                        if(!empty($employee->id)){
                            $user->emp_no = $employee->id;
                        }
                        if(!empty($employee->emp_id)){
                            $user->emp_id = $employee->emp_id;
                        }
                        if(!empty($employee->level_id)){
                            $user->approval_level = $employee->level_id;
                        }
                        
                        if(!$user->save()) {
                            throw new HttpException(500);
                        }
    
                    if(!Config::get('boilerplate.sign_up.release_token')) {
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
            }else if(empty($checkemp)){
    
                throw new NikHttpException();
                
            }

	}        

    }
}
