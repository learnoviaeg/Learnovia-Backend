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
use App\Material;
use App\UserSeen;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\Assigments\Entities\AssignmentLesson;
use App\h5pLesson;

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

        //start seen report
        $route_seen = Config::get('routes.seen_report');

        if(in_array($request->route()->uri,$route_seen) && $request->user()->can('site/show/as-participant')){

            if(str_contains($request->route()->uri, 'material') || str_contains($request->route()->uri, 'page')){

                if(str_contains($request->route()->uri, 'material'))
                    $materials = Material::find($request->route()->parameters()['id']);

                if(str_contains($request->route()->uri, 'page'))
                    $materials = Material::where('item_id',$request->id)->where('lesson_id',$request->lesson_id)->where('type','page')->first();

                if(isset($materials)){
                    $materials->seen_number = $materials->seen_number + 1;
                    $materials->save();
                    $object = $materials; 
                }
            }

            if(str_contains($request->route()->uri, 'quiz/get-single-quiz')){

                $quiz = QuizLesson::where('quiz_id',$request->quiz_id)->where('lesson_id',$request->lesson_id)->first();

                if(isset($quiz)){
                    $quiz->seen_number = $quiz->seen_number + 1;
                    $quiz->save();
                    $object = $quiz;
                    $object['type'] = 'quiz';
                    $object['item_id'] = $quiz->quiz_id;
                }
            }

            if(str_contains($request->route()->uri, 'assignment')){

                $assignment = AssignmentLesson::where('assignment_id',$request->assignment_id)->where('lesson_id',$request->lesson_id)->first();
                
                if(isset($assignment)){
                    $assignment->seen_number = $assignment->seen_number + 1;
                    $assignment->save();
                    $object = $assignment;
                    $object['type'] = 'assignment';
                    $object['item_id'] = $assignment->assignment_id;
                }
            }

            if(str_contains($request->route()->uri, 'interactive')){

                $interactive = h5pLesson::where('content_id',$request->route()->parameters()['id'])->first();
                
                if(isset($interactive)){
                    $interactive->seen_number = $interactive->seen_number + 1;
                    $interactive->save();
                    $object = $interactive;
                    $object['type'] = 'h5p';
                    $object['item_id'] = $interactive->content_id;
                }
            }

            if(isset($object)){
                $user_views = UserSeen::updateOrCreate(['user_id' => $request->user()->id,'item_id' => $object->item_id,'type' => $object->type,'lesson_id' => $object->lesson_id],[
                    'user_id' => $request->user()->id,
                    'item_id' => $object->item_id,
                    'lesson_id' => $object->lesson_id,
                    'type' => $object->type,
                ])->increment('count');
            }

        }

        return $next($request);
    }

}

