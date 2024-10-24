<?php

namespace App\Api\V1\Controllers;

use Config;
use App\User;
use Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
// use Tymon\JWTAuth\JWTAuth;
use JWTAuth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Password;
use App\Api\V1\Requests\ResetPasswordRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ResetPasswordController extends Controller
{
    public function resetPassword(Request $request, JWTAuth $JWTAuth)
    {
        $currentUser = JWTAuth::parseToken()->authenticate();
        $user = Auth::guard()->user();
        // $response = $this->broker()->reset(
        //     $this->credentials($request), function ($user, $password) {
        //         $this->reset($user, $password);
        //     }
        // );

        // if($response !== Password::PASSWORD_RESET) {
        //     throw new HttpException(500);
        // }

        // if(!Config::get('boilerplate.reset_password.release_token')) {
        //     return response()->json([
        //         'status' => 'ok',
        //     ]);
        // }
        // $this->validate($request, [
        //     'old_password' => 'required'. $request->old_password,
        //     'new_password' => 'confirmed|max:8|different:old_password' . $request->new_password,
        // ]);
        // $user_id = $request->email;
        // $password = $request->password;
        // $user = User::where('email', '=', $user_id)->first();
        // $user->password = $request->password;
        // $user->save();

        if (Hash::check($request->old_password, $user->password)) { 
            $user->fill([
             'password' => $request->new_password
             ])->save();
         
             return response()->json([
                'status' => 'success'
            ]);
         
         } else {
            return response()->json([
                'status' => 'failed'
            ]);
         }

        // return response()->json([
        //     'status' => 'ok'
        // ]);
    }

    public function update_password(Request $request)
    {
        $currentUser = JWTAuth::parseToken()->authenticate();
        $tmp = [];

        $sql = "SELECT id,emp_id,email FROM users WHERE email='$request->email'";
        $get_id = DB::select( DB::raw($sql));
        $new_password = Hash::make($request->password);

        foreach ($get_id as $key) {
            $items = [];
            $items['id'] = $key->id;  
            $items['emp_id'] = $key->emp_id;  
            $items['email'] = $key->email;  
            $items['new_password'] = $request->password;

	    if($admin = 1){
                DB::table('users')->where('id',$items['id'])
                ->update(array('password' => $new_password));
                $success_update = "UPDATE PASSWORD SUCCESS";
                $items['status'] = $success_update;
            }else if($admin != 1){
                $success_update = "UPDATE PASSWORD FAILED (You not have access)";
                $items['status'] = $success_update;
            }
            $tmp['cad_list'][] = $items;

            array_push($tmp, $items);
        
        }
        
        return response()->json([
            'success' => true,
            'data' => $items
        ]);

    }


    public function change_level(Request $request)
    {
        $currentUser = JWTAuth::parseToken()->authenticate();
        $admin = Auth::guard()->user()->id;
        $tmp = [];

        $sql = "SELECT id,emp_id,email FROM users WHERE emp_id='$request->emp_id'";
        $get_id = DB::select( DB::raw($sql));
       

        foreach ($get_id as $key) {
            $items = [];
            $items['id'] = $key->id;  
            $items['emp_id'] = $key->emp_id;  
            $items['email'] = $key->email;  
            $items['new_level'] = $request->level;


            if($admin = 1){
                DB::table('users')->where('id',$items['id'])
                ->update(array('approval_level' => $request->level));
                $success_update = "UPDATE PASSWORD SUCCESS";
                $items['status'] = $success_update;
            }else if($admin != 1){
                $success_update = "UPDATE PASSWORD FAILED (You not have access)";
                $items['status'] = $success_update;
            }
            
            $tmp['cad_list'][] = $items;

            array_push($tmp, $items);
	}
		  return response()->json([
            'success' => true,
            'data' => $items
        ]);        
    }
    /**
     * Get the broker to be used during password reset.
     *
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     */
    public function broker()
    {
        return Password::broker();
    }

    /**
     * Get the password reset credentials from the request.
     *
     * @param  ResetPasswordRequest  $request
     * @return array
     */
    protected function credentials(ResetPasswordRequest $request)
    {
        return $request->only(
            'email', 'password'
        );
    }

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @param  string  $password
     * @return void
     */
    protected function reset($user, $password)
    {
        $user->password = $password;
        $user->save();
    }
}
