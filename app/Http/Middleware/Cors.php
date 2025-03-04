<?php

namespace App\Http\Middleware;

use Closure;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        //$response= $next($request);  
        $allow_origin = [
            'http://localhost',
            'http://localhost:3000',
            'http://localhost:3001',
            'http://localhost:3223',
            'http://localhost:5173',
            'http://192.168.0.5:3000',
            'http://192.168.0.105:3000',
            'http://192.168.0.5:3001',
            'http://career.adyawinsa.com:3001',
            'http://103.145.151.187/:3001',
            'http://192.168.0.5',
            'http://103.105.66.214:3000',
            'http://103.165.130.101:3000',
            'http://adyawinsa.com:3000',
            'http://localhost:8000',
            'http://localhost:8080',
            'http://192.168.0.5:3223',
            'http://192.168.0.4:3223',
            'http://192.168.0.7:3223',
            'https://a6a3-103-145-151-187.ap.ngrok.io',
            'http://192.168.0.8:3200',
            'http://192.168.0.202:3223',
            'https://noc.adyawinsa.com',
            'https://app.adyawinsa.com',
            'https://app-public.adyawinsa.com'

        ];

        if (
            isset($_SERVER['HTTP_ORIGIN']) &&
            in_array($_SERVER['HTTP_ORIGIN'], $allow_origin)
        ) {

            //配置信任的跨域来源
            header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
            //配置允许发送认证信息 比如cookies（会话机制的前提）
            header('Access-Control-Allow-Credentials: true');
            //允许的自定义请求头
            header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Secret');
            //信任跨域有效期，秒为单位
            header('Access-Control-Max-Age: 120');
        }

        //return $response;
        return $next($request);
    }
}
