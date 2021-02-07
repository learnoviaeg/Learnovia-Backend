<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use App\User;

class getAuthMiddleware
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
        if ($request->has('api_token')){

            $user =User::where('api_token',$request->api_token)->first();
            Auth::setUser($user);
        }
        return $next($request);
    }
}
