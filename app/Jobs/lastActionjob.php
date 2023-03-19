<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Auth;

use App\Announcement;
use App\LastAction;
use Spatie\Permission\Models\Permission;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use App\Language;
use Illuminate\Support\Facades\Config;
use App\Log;
use Illuminate\Support\Str;
use App\Material;
use App\UserSeen;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\Assigments\Entities\AssignmentLesson;
use App\h5pLesson;

class lastActionjob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $req;
    public $data;
    public $user;

        /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data ,$req , $user)
    {
        $this->req=$req;
        $this->data=$data;
        $this->user=$user;
    }

        /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // $request=$this->req;
        // $data=$this->data;
        // $user=$this->user;
        // Auth::setUser($user);

        // $defult_lang = Language::where('default', 1)->first();
        // $lang = $user->language ? $user->language : ($defult_lang ? $defult_lang->id : null);

        // if(isset($lang)){
        //     if($lang == 1)
        //         App::setLocale('en');

        //     if($lang == 2)
        //         App::setLocale('ar');
        // }

        // $permission_name = null;
        // foreach($data['route_middleware'] as $middleware){
        //     if( str_contains($middleware, 'permission:'))
        //         $permission_name =  explode(':',$middleware)[1];
        // }

        // $title = Permission::where('name',$permission_name)->first();
        // if(!$user->can('site/parent'))
        //     $last_action = LastAction::updateOrCreate(['user_id'=> $user->id ],[
        //             'user_id' => $user->id
        //             ,'name' => isset($title)?$title->title:explode('api/', $data['uri'])[1]
        //             ,'method'=> $data['methods']
        //             ,'uri' =>  $data['uri']
        //             ,'resource' =>  $data['route_controller']
        //             ,'date' => Carbon::now()
        //     ]);

        // $route_views = Config::get('routes.view');

        // if(in_array($data['uri'],$route_views) && $data['methods'] == 'GET'){

        //     $Model = explode('api/',$data['uri'])[1];

        //     if(str_contains($Model, '/'))
        //         $Model = substr($Model, 0, strpos($Model, "/"));

        //     // Log::create([
        //     //     'user' => $request->user()->username,
        //     //     'action' => 'viewed',
        //     //     'model' => ucfirst(Str::singular($Model)),
        //     //     'data' => serialize($request->route()->uri),
        //     // ]);
        // }

        // //start seen report
        // $route_seen = Config::get('routes.seen_report');

        // if(in_array($data['uri'],$route_seen) && $user->can('site/course/student')){

        //     if(str_contains($data['uri'], 'material') || str_contains($data['uri'], 'page')){

        //         if(str_contains($data['uri'], 'material'))
        //             $materials = Material::whereId($data['id'])->first();

        //         if(str_contains($data['uri'], 'page'))
        //             $materials = Material::where('item_id',$request['id'])->where('lesson_id',$request['lesson_id'])->where('type','page')->first();

        //         if(isset($materials)){
        //             $materials->seen_number = $materials->seen_number + 1;
        //             $materials->save();
        //             $object = $materials;
        //         }
        //     }

        //     if(str_contains($data['uri'], 'announcement')){

        //         $announcement = Announcement::whereId( $data['announcement'])->first();

        //         if(isset($announcement)){
        //             $announcement->seen_number = $announcement->seen_number + 1;
        //             $announcement->save();
        //             $object = $announcement;
        //             $object['type'] = 'announcement';
        //             $object['item_id'] = $announcement->id;
        //             $object['lesson_id'] = null;

        //         }
        //     }

        //     if(str_contains($data['uri'], 'quizzes')){
        //         $quiz = QuizLesson::where('quiz_id',$data['quiz'])->where('lesson_id',$request['lesson_id'])->first();

        //         if(isset($quiz)){
        //             $quiz->seen_number = $quiz->seen_number + 1;
        //             $quiz->save();
        //             $object = $quiz;
        //             $object['type'] = 'quiz';
        //             $object['item_id'] = $quiz->quiz_id;
        //         }
        //     }

        //     if(str_contains($data['uri'], 'assignment')){

        //         $assignment = AssignmentLesson::where('assignment_id',$request['assignment_id'])->where('lesson_id',$request['lesson_id'])->first();

        //         if(isset($assignment)){
        //             $assignment->seen_number = $assignment->seen_number + 1;
        //             $assignment->save();
        //             $object = $assignment;
        //             $object['type'] = 'assignment';
        //             $object['item_id'] = $assignment->assignment_id;
        //         }
        //     }

        //     if(str_contains($data['uri'], 'interactive')){

        //         $interactive = h5pLesson::where('content_id',$data['id'])->first();

        //         if(isset($interactive)){
        //             $interactive->seen_number = $interactive->seen_number + 1;
        //             $interactive->save();
        //             $object = $interactive;
        //             $object['type'] = 'h5p';
        //             $object['item_id'] = $interactive->content_id;
        //         }
        //     }
        //     if(isset($object)){
        //         $user_views = UserSeen::updateOrCreate(['user_id' => $user->id,'item_id' => $object->item_id,'type' => $object->type,'lesson_id' => $object->lesson_id],[
        //             'user_id' => $user->id,
        //             'item_id' => $object->item_id,
        //             'lesson_id' => $object->lesson_id,
        //             'type' => $object->type,
        //         ])->increment('count');
        //     }

        // }
    }
}
