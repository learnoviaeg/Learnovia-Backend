<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\h5pLesson;
use App\Lesson;
use App\User;
use App\CourseSegment;
use App\Enroll;
use App\Component;
use Auth;
use Carbon\Carbon;
use DB;

class H5PLessonController extends Controller
{

    public function install()
    {
        if (\Spatie\Permission\Models\Permission::whereName('h5p/lesson/create')->first() != null) {
            return \App\Http\Controllers\HelperController::api_response_format(400, null, 'This Component is installed before');
        }

        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'h5p/lesson/create', 'title' => 'Create Learnovia interactive']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'h5p/lesson/toggle', 'title' => 'Toggle interactive visability']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'h5p/lesson/get-all', 'title' => 'Get all Learnovia interactive']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'h5p/lesson/delete', 'title' => 'Delete Learnovia interactive']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'h5p/lesson/update', 'title' => 'Update Learnovia interactive']);

        $role = \Spatie\Permission\Models\Role::find(1);
        $role->givePermissionTo('h5p/lesson/create');
        $role->givePermissionTo('h5p/lesson/toggle');
        $role->givePermissionTo('h5p/lesson/get-all');
        $role->givePermissionTo('h5p/lesson/delete');
        $role->givePermissionTo('h5p/lesson/update');

        Component::create([
            'name' => 'H5P',
            'module' => 'H5P',
            'model' => 'h5pLesson',
            'type' => 0,
            'active' => 1
        ]);

        return \App\Http\Controllers\HelperController::api_response_format(200, null, 'Component Installed Successfully');
    }


    public function create (Request $request)
    {
        $request->validate([
            'content_id' => 'required|exists:h5p_contents,id',
            'lesson_id' => 'required|exists:lessons,id'
        ]);
        
        $h5p_lesson = h5pLesson::where('content_id',$request->content_id)->where('lesson_id',$request->lesson_id)->first();
        if(!isset($h5p_lesson)){
            $h5p_lesson = h5pLesson::firstOrCreate([
                'content_id' => $request->content_id,
                'lesson_id' => $request->lesson_id,
                'publish_date' => Carbon::now(),
                'start_date' => Carbon::now()
            ]);
        }

        $url= substr($request->url(), 0, strpos($request->url(), "/api"));
        $content = DB::table('h5p_contents')->whereId($request->content_id)->first();
        $Lesson = Lesson::find($request->lesson_id);
        $courseID = CourseSegment::where('id', $Lesson->courseSegment->id)->pluck('course_id')->first();
        $class_id=$Lesson->courseSegment->segmentClasses[0]->classLevel[0]->class_id;
        $usersIDs = User::whereIn('id' , Enroll::where('course_segment', $Lesson->courseSegment->id)->where('user_id','!=',Auth::user()->id)->pluck('user_id')->toArray())->pluck('id');
        User::notify([
            'id' => $content->id,
            'message' => $content->title.' interactive is added',
            'from' => Auth::user()->id,
            'users' => isset($usersIDs) ? $usersIDs->toArray() : [null],
            'course_id' => $courseID,
            'class_id' => $class_id,
            'lesson_id' => $request->lesson_id,
            'type' => 'h5p',
            'link' => $url.'/api/h5p/'.$h5p_lesson->content_id,
            'publish_date' => Carbon::now(),
        ]);
        
        return HelperController::api_response_format(200,$h5p_lesson, 'Interactive content added successfully');
    }

    public function toggleVisibility(Request $request)
    {
        $request->validate([
            'content_id' => 'required|exists:h5p_contents,id',
            'lesson_id' => 'required|integer|exists:h5p_lessons,lesson_id',
        ]);

        $h5pLesson = h5pLesson::where('content_id', $request->content_id)->where('lesson_id', $request->lesson_id)->first();
        if (!isset($h5pLesson)) {
            return HelperController::api_response_format(400, null, 'Try again , Data invalid');
        }
        $h5pLesson->visible = ($h5pLesson->visible == 1) ? 0 : 1;
        $h5pLesson->save();
        return HelperController::api_response_format(200, $h5pLesson, 'Content toggled successfully');
    }

    public function get (Request $request){

        $url= substr($request->url(), 0, strpos($request->url(), "/api"));
        $h5p_lesson =  h5pLesson::get();
        $h5p_content= collect();
        foreach($h5p_lesson as $h5p){
            $content = response()->json(DB::table('h5p_contents')->whereId($h5p->content_id)->first());
            $content->link =  $url.'/api/h5p/'.$h5p->content_id;
            $h5p_content->push($content);
        }
        return HelperController::api_response_format(200, $h5p_content, 'List of Learnovia Interactive');
    }

    public function delete(Request $request)
    {
        $request->validate([
            'content_id' => 'required|exists:h5p_contents,id',
            'lesson_id' => 'required|integer|exists:h5p_lessons,lesson_id',
        ]);

        $h5pLesson = h5pLesson::where('content_id', $request->content_id)->where('lesson_id', $request->lesson_id)->first();
        if (!isset($h5pLesson)) {
            return HelperController::api_response_format(400, null, 'Try again , Data invalid');
        }
        $h5pLesson->delete();
        $content = DB::table('h5p_contents')->whereId($request->content_id)->delete();

        return HelperController::api_response_format(200, null, 'Content deleted successfully');
    }
}
