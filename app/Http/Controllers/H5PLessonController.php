<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\h5pLesson;
use App\Lesson;
use App\User;
use App\Enroll;
use App\Component;
use App\CourseItem;
use Auth;
use Carbon\Carbon;
use DB;
use App\LastAction;
use App\Http\Controllers\Controller;
use App\Notification;
use App\Notifications\H5PNotification;
use App\UserCourseItem;
use App\Helpers\CoursesHelper;
use Illuminate\Database\Eloquent\Builder;

class H5PLessonController extends Controller
{

    public function install()
    {
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
        $parent = \Spatie\Permission\Models\Role::find(7);
        $student->givePermissionTo(\Spatie\Permission\Models\Permission::whereIn('name', $student_permissions)->get());
        $parent->givePermissionTo(\Spatie\Permission\Models\Permission::whereIn('name', $student_permissions)->get());


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
            'lesson_id' => 'required|exists:lessons,id|array',
            'visible'=>'in:0,1',
            'publish_date' => 'nullable|after:' . Carbon::now(),
            'users_ids' => 'array',
            'users_ids.*' => 'exists:users,id'
        ]);

        // $h5p_lesson = h5pLesson::where('content_id',$request->content_id)->whereIn('lesson_id',$request->lesson_id)->first();
        // if(!isset($h5p_lesson)){
        foreach($request->lesson_id as $lesson_id){
            $h5p_lesson = h5pLesson::firstOrCreate([
                'content_id' => $request->content_id,
                'lesson_id' => $lesson_id,
                'publish_date' => isset($request->publish_date)?$request->publish_date : Carbon::now(),
                'start_date' => Carbon::now(),
                'user_id' => Auth::id(),
                'visible'=>isset($request->visible)?$request->visible:1
            ]);
        // }
            /////////user restrictions 
            if(isset($request->users_ids)){
                CoursesHelper::giveUsersAccessToViewCourseItem($h5p_lesson->id, 'h5p_content', $request->users_ids);
                h5pLesson::where('id',$h5p_lesson->id)->update(['restricted' => 1]);
            }
            $content = DB::table('h5p_contents')->whereId($request->content_id)->first();
            $lesson = Lesson::find($lesson_id);
            LastAction::lastActionInCourse($lesson->course_id);
            // $class_id=$Lesson->shared_classes;

            //sending Notification
            $notification = new H5PNotification($h5p_lesson, $content->title.' interactive is added');
            $notification->send();
        }

        return HelperController::api_response_format(200,$h5p_lesson, __('messages.interactive.add'));
    }

    public function toggleVisibility(Request $request)
    {
        $request->validate([
            'content_id' => 'required|exists:h5p_contents,id',
            'lesson_id' => 'required|integer|exists:h5p_lessons,lesson_id',
        ]);

        $h5pLesson = h5pLesson::where('content_id', $request->content_id)->where('lesson_id', $request->lesson_id)->first();
        if (!isset($h5pLesson))
            return HelperController::api_response_format(400, null, __('messages.error.data_invalid'));

        $lesson = Lesson::find($request->lesson_id);
        LastAction::lastActionInCourse($lesson->course_id);
        $h5pLesson->visible = ($h5pLesson->visible == 1) ? 0 : 1;
        $h5pLesson->save();
        return HelperController::api_response_format(200, $h5pLesson, __('messages.success.toggle'));
    }

    public function get (Request $request){

        $rules = [
            'content_id' => 'exists:h5p_contents,id|required_with:lesson_id',
            'lesson_id' => 'integer|exists:h5p_lessons,lesson_id|required_with:content_id',
        ];

        $customMessages = [
            'content_id.exists' => 'This interactive is invalid.'
        ];

        $this->validate($request, $rules, $customMessages);

        if($request->filled('content_id') && $request->filled('lesson_id')){
            $h5p_lesson =  h5pLesson::where('lesson_id',$request->lesson_id)->where('content_id',$request->content_id)->first();
            if(!isset($h5p_lesson))
                return HelperController::api_response_format(404, null ,__('messages.error.item_deleted'));

            if($request->user()->can('site/course/student')){
                // $courseItem = CourseItem::where('item_id', $request->content_id)->where('type', 'h5p_content')->first();
                // if(isset($courseItem)){
                //         $users = UserCourseItem::where('course_item_id', $courseItem->id)->pluck('user_id')->toArray();
                //     if(!in_array(Auth::id(), $users))
                //         return response()->json(['message' => __('messages.error.no_permission'), 'body' => null], 403);
                // }

                if(($h5p_lesson->visible == 0 || $h5p_lesson->publish_date < Carbon::now()))
                    return HelperController::api_response_format(301,null, __('messages.interactive.hidden'));
            }

            return HelperController::api_response_format(200, $h5p_lesson, __('messages.interactive.list'));
        }


        $url= substr($request->url(), 0, strpos($request->url(), "/api"));
        $h5p_lesson =  h5pLesson::get();
        $h5p_content= collect();
        foreach($h5p_lesson as $h5p){
            $content = response()->json(DB::table('h5p_contents')->whereId($h5p->content_id)->first());
            $content->link =  $url.'/api/h5p/'.$h5p->content_id;
            $h5p_content->push($content);
        }
        return HelperController::api_response_format(200, $h5p_content, __('messages.interactive.list'));
    }

    public function delete(Request $request)
    {
        $request->validate([
            'content_id' => 'required|exists:h5p_contents,id',
            'lesson_id' => 'required|integer|exists:h5p_lessons,lesson_id',
        ]);

        $h5pLesson = h5pLesson::where('content_id', $request->content_id)->where('lesson_id', $request->lesson_id)->first();
        if (!isset($h5pLesson))
            return HelperController::api_response_format(400, null, __('messages.error.data_invalid'));

        $lesson = Lesson::find($request->lesson_id);
        LastAction::lastActionInCourse($lesson->course_id);

        if(!$request->user()->can('h5p/lesson/allow-delete') && $h5pLesson->user_id != Auth::id() )
            return HelperController::api_response_format(400, null, __('messages.permissions.user_doesnot_has_permission'));

        $h5pLesson->delete();
        DB::table('h5p_contents')->where('id', $request->content_id)->delete();

        return HelperController::api_response_format(200, null, __('messages.interactive.delete'));
    }

    public function edit (Request $request){

        $request->validate([
            'content_id' => 'required|exists:h5p_contents,id',
            'lesson_id' => 'required|integer|exists:h5p_lessons,lesson_id',
            'updated_lesson_id' => 'nullable|exists:lessons,id',
            'visible'=>'in:0,1'


        ]);
        // $url= substr($request->url(), 0, strpos($request->url(), "/api"));
        $h5pLesson = h5pLesson::where('content_id', $request->content_id)->where('lesson_id', $request->lesson_id)->first();
        if(!isset($h5pLesson))
            return HelperController::api_response_format(500, null,__('messages.interactive.interactive_not_belong'));
        $lesson = Lesson::find($request->lesson_id);
        LastAction::lastActionInCourse($lesson->course_id);
        if ($request->filled('updated_lesson_id')) {
            $h5pLesson->update([
                'lesson_id' => $request->updated_lesson_id
            ]);
            $lesson = Lesson::find($request->updated_lesson_id);
            LastAction::lastActionInCourse($lesson->course_id);
            }
        if ($request->filled('visible')) {
            $h5pLesson->update([
                'visible' => $request->visible
            ]);
        }



        // $content = response()->json(DB::table('h5p_contents')->whereId($h5pLesson->content_id)->first());
        // // $content->link =  $url.'/api/h5p/'.$h5pLesson->content_id.'/edit';
        // $content->pivot = [
        //     'lesson_id' =>  $h5pLesson->lesson_id,
        //     'content_id' =>  $h5pLesson->content_id,
        //     'publish_date' => $h5pLesson->publish_date,
        //     'created_at' =>  $h5pLesson->created_at,
        // ];

        return HelperController::api_response_format(200, [], __('messages.interactive.update'));
    }



    ////////////////////user restrictions


    public function editH5pAssignedUsers(Request $request){
        $request->validate([
            'content_id' => 'required|exists:h5p_lessons,content_id',
            'users_ids' => 'array',
            'users_ids.*' => 'exists:users,id'
        ]);

        $h5pLessons= h5pLesson::where('content_id',$request->content_id);
        $h5pLessons->update(['restricted' => 1]);

        if(!isset($request->users_ids))
            $h5pLessons->update(['restricted' => 0]);

        foreach($h5pLessons->cursor() as $h5pLesson){
            CoursesHelper::updateCourseItem($h5pLesson->id, 'h5p_content', $request->users_ids);
        }

        return response()->json(['message' => 'Updated successfully'], 200);
    }

    public function getH5pAssignedUsers(Request $request){

        $request->validate([
            'content_id' => 'required|exists:h5p_lessons,content_id',
        ]);

        $h5pLessons = h5pLesson::where('content_id', $request->content_id)->with(['Lesson', 'courseItem.courseItemUsers'])->get();
        foreach($h5pLessons as $h5pLesson){
            foreach($h5pLesson->Lesson->shared_classes->pluck('id') as $class_id)
                    $classes[] = $class_id;
        }
        $result['quiz_classes'][] = array_unique($classes);
        $result['restricted'] = $h5pLessons[0]->restricted;
        if(isset($h5pLessons['courseItem'])){
            $courseItemUsers = $h5pLessons['courseItem']->courseItemUsers;
            foreach($courseItemUsers as $user)
                $result['assigned_users'][] = $user->user_id;
        }

        return response()->json(['message' => null , 'body' => $result ], 200);
    }

}
