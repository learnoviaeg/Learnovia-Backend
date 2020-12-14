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
use App\LastAction;

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
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'h5p/lesson/allow-edit', 'title' => 'Allow to edit interactive content']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'h5p/lesson/allow-delete', 'title' => 'Allow to delete interactive content']);

        $teacher_permissions=['h5p/lesson/create','h5p/lesson/toggle','h5p/lesson/get-all','h5p/lesson/delete','h5p/lesson/update','h5p/lesson/allow-edit',
        'h5p/lesson/allow-delete'];
        $tecaher = \Spatie\Permission\Models\Role::find(4);
        $tecaher->givePermissionTo(\Spatie\Permission\Models\Permission::whereIn('name', $teacher_permissions)->get());
        
        $student_permissions=['h5p/lesson/get-all'];
        $student = \Spatie\Permission\Models\Role::find(3);
        $student->givePermissionTo(\Spatie\Permission\Models\Permission::whereIn('name', $student_permissions)->get());


        $role = \Spatie\Permission\Models\Role::find(1);
        $role->givePermissionTo('h5p/lesson/create');
        $role->givePermissionTo('h5p/lesson/toggle');
        $role->givePermissionTo('h5p/lesson/get-all');
        $role->givePermissionTo('h5p/lesson/delete');
        $role->givePermissionTo('h5p/lesson/update');
        $role->givePermissionTo('h5p/lesson/allow-edit');
        $role->givePermissionTo('h5p/lesson/allow-delete');

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
                'start_date' => Carbon::now(),
                'user_id' => Auth::id()
            ]);
        }

        $url= substr($request->url(), 0, strpos($request->url(), "/api"));
        $content = DB::table('h5p_contents')->whereId($request->content_id)->first();
        $Lesson = Lesson::find($request->lesson_id);
        $courseID = CourseSegment::where('id', $Lesson->courseSegment->id)->pluck('course_id')->first();
        $class_id=$Lesson->courseSegment->segmentClasses[0]->classLevel[0]->class_id;
        $usersIDs = User::whereIn('id' , Enroll::where('course_segment', $Lesson->courseSegment->id)->where('user_id','!=',Auth::user()->id)->pluck('user_id')->toArray())->pluck('id');
        LastAction::lastActionInCourse($courseID);
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
        $lesson = Lesson::find($request->lesson_id);
        LastAction::lastActionInCourse($lesson->courseSegment->course_id);
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
        $lesson = Lesson::find($request->lesson_id);
        LastAction::lastActionInCourse($lesson->courseSegment->course_id);

        if(!$request->user()->can('h5p/lesson/allow-delete') && $h5pLesson->user_id != Auth::id() ){
            return HelperController::api_response_format(400, null, 'You dont have permission to delete this content.');
        }

        $h5pLesson->delete();
        DB::table('h5p_contents')->where('id', $request->content_id)->delete();

        return HelperController::api_response_format(200, null, 'Content deleted successfully');
    }

    public function edit (Request $request){

        $request->validate([
            'content_id' => 'required|exists:h5p_contents,id',
            'lesson_id' => 'required|integer|exists:h5p_lessons,lesson_id',
            'updated_lesson_id' => 'nullable|exists:lessons,id'
        ]);
        // $url= substr($request->url(), 0, strpos($request->url(), "/api"));
        $h5pLesson = h5pLesson::where('content_id', $request->content_id)->where('lesson_id', $request->lesson_id)->first();
        if(!isset($h5pLesson))
            return HelperController::api_response_format(500, null,'This lesson doesn\'t belongs to the course of this interactive');
        $lesson = Lesson::find($request->lesson_id);
        LastAction::lastActionInCourse($lesson->courseSegment->course_id);
        if ($request->filled('updated_lesson_id')) {
            $h5pLesson->update([
                'lesson_id' => $request->updated_lesson_id
            ]);
            }
        $lesson = Lesson::find($request->updated_lesson_id);
        LastAction::lastActionInCourse($lesson->courseSegment->course_id);
        // $content = response()->json(DB::table('h5p_contents')->whereId($h5pLesson->content_id)->first());
        // // $content->link =  $url.'/api/h5p/'.$h5pLesson->content_id.'/edit';
        // $content->pivot = [
        //     'lesson_id' =>  $h5pLesson->lesson_id,
        //     'content_id' =>  $h5pLesson->content_id,
        //     'publish_date' => $h5pLesson->publish_date,
        //     'created_at' =>  $h5pLesson->created_at,
        // ];

        return HelperController::api_response_format(200, [], 'Learnovia Interactive updated successfully');
    }
    
}
