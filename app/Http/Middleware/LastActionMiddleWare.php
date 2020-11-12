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
        foreach($request->route()->action['middleware'] as $middleware){
            if( str_contains($middleware, 'permission:'))
                $permission_name =  explode(':',$middleware)[1];
        }
        // dd(collect($request->route()->controller->getMiddleware())->pluck('middleware'));
        // // resource 
        // if($permission_name==null &&count($request->route()->controller['middleware'])>0)
        //     dd($request->route()->controller['middleware']);

        // dd($request->route()->action['middleware'] );
        $title = Permission::where('name',$permission_name)->first();
        $last_action = LastAction::updateOrCreate(['user_id'=> $request->user()->id ],[
                'user_id' => $request->user()->id 
                ,'name' => isset($title)?$title->title:explode('api/', $request->route()->uri)[1]
                ,'method'=>$request->route()->methods[0]
                ,'uri' =>  $request->route()->uri
                ,'resource' =>  $request->route()->action['controller']
                ,'date' => Carbon::now()->format('Y-m-d H:i:s a')
        ]);
       
        return $next($request);
    }

}

