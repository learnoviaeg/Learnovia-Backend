<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use App\User;
use App\Enroll;
use App\Lesson;
use App\Component;
use App\h5pLesson;
use Carbon\Carbon;
use App\CourseItem;
use App\LastAction;
use App\Course;
use App\Notification;
use App\SecondaryChain;
use App\UserCourseItem;
use Illuminate\Http\Request;
use App\Helpers\CoursesHelper;
use App\Http\Controllers\Controller;
use App\Notifications\H5PNotification;
use Illuminate\Database\Eloquent\Builder;
use App\Repositories\NotificationRepoInterface;
use App\TempLog;
use App\AuditLog;

class H5PLessonController extends Controller
{
    public function __construct(NotificationRepoInterface $notification)
    {
        $this->notification = $notification;
    }

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

        foreach($request->lesson_id as $lesson_id){
            $h5p_lesson = h5pLesson::firstOrCreate([
                'content_id' => $request->content_id,
                'lesson_id' => $lesson_id,
                'publish_date' => isset($request->publish_date)?$request->publish_date : Carbon::now(),
                'start_date' =>  isset($request->publish_date)?$request->publish_date : Carbon::now(),
                'user_id' => Auth::id(),
                'visible'=>isset($request->visible)?$request->visible:1,
                'restricted' => 0
            ]);

            //user restrictions 
            if(isset($request->users_ids)){
                CoursesHelper::giveUsersAccessToViewCourseItem($h5p_lesson->id, 'h5p_content', $request->users_ids);
                h5pLesson::where('id',$h5p_lesson->id)->update(['restricted' => 1]);
            }
            $content = DB::table('h5p_contents')->whereId($request->content_id)->first();
            $lesson = Lesson::find($lesson_id);
            LastAction::lastActionInCourse($lesson->course_id);

            //sending Notification
            $updatedH5p=h5pLesson::find($h5p_lesson->id);
            if($updatedH5p->visible == 1){
                if(!$updatedH5p->restricted){
                    $reqNot=[
                        'message' => $content->title.' interactive is created',
                        'item_id' => $h5p_lesson->content_id,
                        'item_type' => 'h5p_content',
                        'type' => 'notification',
                        'publish_date' => Carbon::parse($updatedH5p->publish_date)->format('Y-m-d H:i:s'),
                        'lesson_id' => $lesson_id,
                        'course_name' => Course::find($lesson->course_id)->name
                    ];
                    $users=SecondaryChain::select('user_id')->where('role_id',3)->where('lesson_id',$lesson_id)->pluck('user_id');
                    $this->notification->sendNotify($users,$reqNot);
                }
            }
        }

        return HelperController::api_response_format(200,$updatedH5p, __('messages.interactive.add'));
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
            // 'content_id' => 'exists:h5p_contents,id|required_with:lesson_id',
            'content_id' => 'required_with:lesson_id',
            'lesson_id' => 'integer|exists:h5p_lessons,lesson_id|required_with:content_id',
        ];


        $customMessages = [
            // 'content_id.exists' => 'This item has been removed.'
        ];

        $h5p_lesson =  h5pLesson::where('lesson_id',$request->lesson_id)->where('content_id',$request->content_id);
        if($h5p_lesson->count() < 1)
            return HelperController::api_response_format(404, null ,__('messages.error.item_deleted'));

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

                // if(($h5p_lesson->visible == 0 || $h5p_lesson->publish_date < Carbon::now()))
                if($h5p_lesson->visible == 0 )
                    return HelperController::api_response_format(301,null, __('messages.interactive.hidden'));
            }

            $url= substr($request->url(), 0, strpos($request->url(), "/api"));
            $content->link =  $url.'/api/h5p/'.$request->content_id;
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

    public function edit(Request $request){
        $request->validate([
            'content_id' => 'required|exists:h5p_contents,id',
            'lesson_id' => 'required|integer|exists:h5p_lessons,lesson_id',
            'updated_lesson_id' => 'nullable|exists:lessons,id',
            'visible'=>'in:0,1'
        ]);
        // $url= substr($request->url(), 0, strpos($request->url(), "/api"));
        $content = DB::table('h5p_contents')->whereId($request->content_id)->first();
        $h5pLesson = h5pLesson::where('content_id', $request->content_id)->where('lesson_id', $request->lesson_id)->first();
        $h5pLessons=$h5pLesson;
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

        $users=SecondaryChain::select('user_id')->where('role_id',3)->where('lesson_id',$request->lesson_id)->pluck('user_id');
        // dd($h5pLessons->id);
        $courseItem = CourseItem::where('item_id', $h5pLessons->id)->where('type', 'h5p_content')->first();
        if(isset($courseItem))
            $users = UserCourseItem::where('course_item_id', $courseItem->id)->pluck('user_id');

            $reqNot=[
                'message' => $content->title.' interactive is updated',
                'item_id' => $request->content_id,
                'item_type' => 'h5p_content',
                'type' => 'notification',
                'publish_date' => Carbon::parse($h5pLesson->publish_date)->format('Y-m-d H:i:s'),
                'lesson_id' => $request->lesson_id,
                'course_name' => Course::find($lesson->course_id)->name
            ];
            $users=SecondaryChain::select('user_id')->where('role_id',3)->where('lesson_id',$request->lesson_id)->pluck('user_id');
            $this->notification->sendNotify($users,$reqNot); 

        // $this->notification->sendNotify($users->toArray(),$content->title.' interactive is updated',$h5pLessons->id,'notification','interactive');

        // $content = response()->json(DB::table('h5p_contents')->whereId($h5pLesson->content_id)->first());
        // // $content->link =  $url.'/api/h5p/'.$h5pLesson->content_id.'/edit';
        // $content->pivot = [
        //     'lesson_id'     =>  $h5pLesson->lesson_id,
        //     'content_id'    =>  $h5pLesson->content_id,
        //     'publish_date'  => $h5pLesson->publish_date,
        //     'created_at'    =>  $h5pLesson->created_at,
        // ];


        // comment 4o8l morsy because bydrap
        $temp_log = TempLog::where(['subject_type' => 'H5pContent', 'subject_id' => $request->content_id])->first();  
        // update some data
        $temp_log->user_id = $request->user('api')->id;
        $temp_log->role_id = auth()->user()->roles->pluck('id')->toArray();
        $temp_log->hole_description ='Item in module H5pContent has been updated by ( '. $request->user('api')->fullname. ' )';
        $temp_log->save();
        
        $arr = $temp_log->toArray();
        $result = Auditlog::firstOrCreate($arr);
        if ($result) {
            $temp_log->delete();
        }    
        
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
        // else
        //     $this->notification->sendNotify($request->users_ids,$content->title.' interactive is created',$request->content_id,'interactive','interactive');  

        foreach($h5pLessons->cursor() as $h5pLesson){
            CoursesHelper::updateCourseItem($h5pLesson->id, 'h5p_content', $request->users_ids);
        }

        return response()->json(['message' => 'Updated successfully'], 200);
    }

    public function getH5pAssignedUsers(Request $request){

        $request->validate([
            'id' => 'required|exists:h5p_lessons,content_id',
        ]);

        $h5pLessons = h5pLesson::where('content_id', $request->id)->with(['lesson', 'courseItem.courseItemUsers'])->get();

        foreach($h5pLessons as $lesson){
            if($lesson->lesson->shared_lesson ==1)
                $result['h5p_classes']= $lesson->lesson->shared_classes->pluck('id');
            else
                $result['h5p_classes'][]= $lesson->lesson->shared_classes->pluck('id')->first();
        }
        $result['restricted'] = $h5pLessons[0]->restricted;
        if(isset($h5pLessons[0]['courseItem'])){
            $courseItemUsers = $h5pLessons[0]['courseItem']->courseItemUsers;
            foreach($courseItemUsers as $user)
                $result['assigned_users'][] = $user->user_id;
        }

        return response()->json($result,200);
    }
}
