<?php

namespace App\Http\Middleware;

use Closure;
use App\LastAction;

use Spatie\Permission\Models\Permission;
use Carbon\Carbon;

class LastActionMiddleWare
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
        $permission_name = null;
        //for controller 
        // dd($request->route()->action) ;
        foreach($request->route()->action['middleware'] as $middleware){
            if( str_contains($middleware, 'permission:'))
                $permission_name =  explode(':',$middleware)[1];
        }

        $title = Permission::where('name',$permission_name)->first();
        $last_action = LastAction::updateOrCreate(['user_id'=> $request->user()->id ],[
                'user_id' => $request->user()->id 
                ,'name' => isset($title)?$title->title:explode('api/', $request->route()->uri)[1]
                ,'method'=>$request->route()->methods[0]
                ,'uri' =>  $request->route()->uri
                ,'resource' =>  $request->route()->action['controller']
                ,'date' => Carbon::now()
        ]);
        \Artisan::call('cache:clear', ['--env' => 'local']);
        \Artisan::call('config:clear', ['--env' => 'local']);
        return $next($request);
    }

}

