<?php

namespace App\Api\V1\Controllers;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Tymon\JWTAuth\JWTAuth;
use App\Http\Controllers\Controller;
use App\Api\V1\Requests\LoginRequest;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UserInactiveHttpException;
use Symfony\Component\HttpKernel\Exception\InvalidEmailHttpException;
use Illuminate\Support\Facades\DB;
use Auth;
use SiteHelper;

class LoginController extends Controller
{
    /**
     * Log the user in
     *
     * @param LoginRequest $request
     * @param JWTAuth $JWTAuth
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     tags={"login"},
     *     summary="Returns a Sample API response",
     *     description="A sample login to test out the API",
     *     operationId="greet",
     *     @OA\Parameter(
     *          name="email",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Parameter(
     *          name="password",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *     ),
     *     @OA\Response(
     *         response="default",
     *         description="successful operation"
     *     )
     * )
     */
    public function login(LoginRequest $request, JWTAuth $JWTAuth)
    {
        $credentials = $request->only(['email', 'password']);
        $device_id = $request->device_id;
        $firebase_token = $request->firebase_token;
        $user_inactive = DB::table('users')->where('email', $request->email)->first();
        if (empty($user_inactive)) { //cek user email
            // user email tidak ditemukan
            throw new InvalidEmailHttpException();
        } else if (!empty($user_inactive)) { //user email ditemukan
            if ($user_inactive->inactive == 0) { // user masih activ
                try { 
                    $token = Auth::guard()->attempt($credentials);

                    if (!$token) { // cek pw 
                        return response()->json([
                            'error' => array(
                                'message' => "Email atau kata sandi salah!",
                                'status' => 403
                            )
                        ], 403);
                    }
                } catch (JWTException $e) {
                    throw new HttpException(500);
                }

                //cek token firebase
                $user_firebase_token = $user_inactive->firebase_token;
                if (!empty($firebase_token)) {
                    if (empty($user_firebase_token) || $user_firebase_token == null || $user_firebase_token != $firebase_token) {
                        DB::table('users')->where('email', $request->email)
                            ->update(array(
                                'firebase_token' => $firebase_token
                            ));
                    }
                }

                //cek device ID
                if (isset($device_id)) { //$device_id != null

                    $db_device_id = $user_inactive->device_id;
                    if (!empty($db_device_id)) 
                    {  
                        if ($db_device_id != $device_id) { // compare device id 

                            
                            $check_tbl_device_temporary = DB::table('0_device_temporary')->where('email', $request->email)->first();
                            if (!empty($check_tbl_device_temporary->email)) {
                                DB::table('0_device_temporary')->where('email', $request->email)
                                ->update(array(
                                    'device_id' => $device_id,
                                    'inactive' => 0
                                ));
                            }else {
                                DB::table('0_device_temporary')
                                ->insert(array(
                                    'email' =>  $user_inactive->email,
                                    'device_id' =>  $device_id,
                                    'user_id' => $user_inactive->id,
                                    'inactive' => 0
                                ));
                            }

                            return response()->json([
                                'error' => array(
                                    'message' => "Anda menggunakan device yang berbeda, hubungi ICT.",
                                    'status' => 403
                                )
                            ], 403);
                        }

                    }else { 

                        DB::table('users')->where('email', $request->email)
                            ->update(array(
                                'device_id' => $device_id
                            ));
                        
                    }
                }
     
                SiteHelper::update_last_login(Auth::guard()->user()->id);
                return response()
                    ->json([
                        'status' => 'ok',
                        'token' => $token,
                        'expires_in' => Auth::guard()->factory()->getTTL() * 60,
                        'user' => Auth::guard()->user()
                    ]);
            } else if ($user_inactive->inactive == 1) { // user tidak activ
                throw new UserInactiveHttpException();
            }
        }
    }
}
