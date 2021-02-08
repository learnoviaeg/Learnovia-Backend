<?php

namespace App\Http\Middleware;

use Closure;
use App\LastAction;

use Spatie\Permission\Models\Permission;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use App\Language;
use  Illuminate\Support\Facades\Config;
use App\Log;
use Illuminate\Support\Str;

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
        $defult_lang = Language::where('default', 1)->first();
        $lang = $request->user()->language ? $request->user()->language : ($defult_lang ? $defult_lang->id : null);
        
        if(isset($lang)){
            if($lang == 1)
                App::setLocale('en');

            if($lang == 2)
                App::setLocale('ar');
        }

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
        
        $route_views = Config::get('routes.view');

        if(in_array($request->route()->uri,$route_views) && $request->route()->methods[0] == 'GET'){
            
            $Model = explode('api/', $request->route()->uri)[1];
        
            if(str_contains($Model, '/'))
                $Model = substr($Model, 0, strpos($Model, "/"));
    
            Log::create([
                'user' => $request->user()->username,
                'action' => 'viewed',
                'model' => ucfirst(Str::singular($Model)),
                'data' => serialize($request->route()->uri),
            ]);
        }
        // \Artisan::call('cache:clear', ['--env' => 'local']);
        // \Artisan::call('config:clear', ['--env' => 'local']);
        return $next($request);
    }

}

