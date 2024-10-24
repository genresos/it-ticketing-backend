<?php

namespace App\Api\V1\Controllers;
use App\Http\Controllers\Controller;
use JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Mail\MailNotify;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use Validator,Redirect,Response,File;
use Auth;
use URL;
use Symfony\Component\HttpKernel\Exception\CodeNotMatchHttpException;
use Symfony\Component\HttpKernel\Exception\CodeMatchHttpException;
use Symfony\Component\HttpKernel\Exception\UserNotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\NikHttpException;


class EmailVerificationController extends Controller
{
    
    public function index(Request $request){
	
		$rand = rand(1,999999);
		$encrypt_code = Crypt::encryptString($rand);
		$checkemp  =  DB::table('0_hrm_employees')->where("emp_id",$request->emp_id)->where("inactive","=",0)->first();


		if(!empty($checkemp)){
			DB::table('signup_verification')->insert(array('email' => $request->email,
							'code_verification' => $encrypt_code));   

			$details = [
			'title' => 'Registration Code',
			'code' => $rand
			];
		
			\Mail::to($request->email)->send(new \App\Mail\MailNotify($details));
		

			return response()->json([
				'success' => true,
				'message' => "Email sudah terkirim."
			]);

		}else if (empty($checkemp)){
			throw new NikHttpException();
		}
	
	}

	public function forgot_password(Request $request){
		
		$sql = "SELECT * FROM users WHERE email = '$request->email'";
		$response = [];
		
		$user = DB::select( DB::raw($sql));

			foreach($user as $data){
				$item = [];
				$item['nama'] = $data->name;
				$item['email'] = $data->email;

				array_push($response,$item);


				$nama = $item['nama'];
				$email = $item['email'];
				if(!empty($nama)){

					$rand = rand(1,999999);
					$encrypt_code = Crypt::encryptString($rand);

					DB::table('users')->where('email',$email)
									->update(array('remember_token' => $encrypt_code));   

					$details = [
					'title' => 'Recovery Password',
					'code' => $rand,
					'user' => $nama
					];
				
					\Mail::to($request->email)->send(new \App\Mail\ResetNotify($details));
				

					return response()->json([
						'success' => true,
						'message' => "Email sudah terkirim."
					]);
				}


			}
		
		
		throw new UserNotFoundHttpException();
	
	}

	public function validation_reset(Request $request) {

		$email = $request->email;
		$sql = "SELECT remember_token as code FROM users WHERE email = '$email' ORDER BY id LIMIT 1";
		
		
		$get_code = DB::select( DB::raw($sql));

			foreach($get_code as $data){
				$item = [];
				$item['code'] = $data->code;
				$item['decrypt'] = Crypt::decryptString($data->code);

				array_push($item);
			}
		
		$decrypt_result = $item['decrypt'];
		if($decrypt_result != $request->code){

			throw new CodeNotMatchHttpException();

		}else if($decrypt_result == $request->code){

			$new_password = Hash::make($request->password);
			DB::table('users')->where('email',$request->email)
                ->update(array('password' => $new_password));

			return response()->json([
				'success' => true,
				'message' => "Reset Kata Sandi Sukses"
			]);

		}
		
	}
	
}
