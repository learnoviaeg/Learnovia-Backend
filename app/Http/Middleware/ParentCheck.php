<?php

namespace App\Http\Middleware;

use App\Http\Controllers\HelperController;
use App\User;
use Closure;
use Illuminate\Support\Facades\Auth;

class ParentCheck
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
        $roles = Auth::user()->roles->pluck('name');
        if(!in_array("Parent" , $roles->toArray())){
            return $next($request);
        }
        if(Auth::user()->currentChild == null)
            return HelperController::api_response_format(400, __('messages.users.parent_choose_child'));
        $currentChild =User::find(Auth::user()->currentChild->child_id);
        Auth::setUser($currentChild);
        return $next($request);
    }
}
