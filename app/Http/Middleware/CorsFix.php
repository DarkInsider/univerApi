<?php

namespace App\Http\Middleware;

use Closure;

class CorsFix
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
        $handle = $next($request);

        if(method_exists($handle, 'header')) {
            return $handle
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, X-Token-Auth, Authorization, token');
        }else{
            $handle->headers->set('Access-Control-Allow-Origin', '*') ;
            $handle->headers->set('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, X-Token-Auth, Authorization, token') ;
            $handle->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS') ;
            return $handle;
        }

    }
}
