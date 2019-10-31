<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\HelperController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Lesson;
use App\Enroll;
use App\Course;
use App\CourseSegment;
use App\SegmentClass;
use App\ClassLevel;
use App\User;
use Illuminate\Routing\Controller;
use Modules\Page\Entities\Page;
use Modules\Page\Entities\pageLesson;
use Illuminate\Support\Carbon;
use App\Component;
use App\LessonComponent;

class PageController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function install()
    {
        if (\Spatie\Permission\Models\Permission::whereName('page/add')->first() != null) {
            return \App\Http\Controllers\HelperController::api_response_format(400, null, 'This Component is installed before');
        }

        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'page/add','title' => 'add page']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'page/update','title' => 'update page']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'page/delete','title' => 'delete page']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'page/toggle','title' => 'toggle page']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'page/link-lesson','title' => 'link lesson page']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'page/get','title' => 'get page']);

        $role = \Spatie\Permission\Models\Role::find(1);
        $role->givePermissionTo('page/add');
        $role->givePermissionTo('page/update');
        $role->givePermissionTo('page/delete');
        $role->givePermissionTo('page/toggle');
        $role->givePermissionTo('page/link-lesson');
        $role->givePermissionTo('page/get');

        Component::create([
            'name' => 'Page',
            'module'=>'Page',
            'model' => 'page',
            'type' => 1,
            'active' => 1
        ]);

        return \App\Http\Controllers\HelperController::api_response_format(200, null, 'Component Installed Successfully');
    }
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function add(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'content' => 'required|string',
            'Lesson_id' => 'required|exists:lessons,id',
            'visible' => 'nullable|boolean',
            'publish_date'=>'nullable'
        ]);
        if($request->filled('publish_date'))
        {
            $publishdate=$request->publish_date;
            if(Carbon::parse($request->publish_date)->isPast()){
                $publishdate=Carbon::now();
            }
        }
        else
        {
            $publishdate=Carbon::now();
        }
        $courseSegID=Lesson::where('id',$request->Lesson_id)->pluck('course_segment_id')->first();
        $segmentClass=CourseSegment::where('id',$courseSegID)->pluck('segment_class_id')->first();
        $ClassLevel=SegmentClass::where('id',$segmentClass)->pluck('class_level_id')->first();
        $classId=ClassLevel::where('id',$ClassLevel)->pluck('class_id')->first();
        $courseID=CourseSegment::where('id',$courseSegID)->pluck('course_id')->first();
        $usersIDs=Enroll::where('course_segment',$courseSegID)->pluck('user_id')->toarray();

        $page= new Page();
        $page->title=$request->title;
        $page->content=$request->content;
        if(isset($request->visible))
        {
            $page->visible;
        }
        $page->save();

        User::notify([
            'message' => 'A new Page is added',
            'from' => Auth::user()->id,
            'users' => $usersIDs,
            'course_id' => $courseID,
            'class_id'=>$classId,
            'type' => 'Page',
            'link' => url(route('getPage')) . '?id=' . $page->id,
            'publish_date'=>$publishdate
        ]);

        pageLesson::firstOrCreate([
            'page_id'=>$page->id,
            'lesson_id' => $request->Lesson_id,
            'publish_date' => $publishdate
        ]);
        LessonComponent::create([
            'lesson_id' => $request->Lesson_id,
            'comp_id'   => $page->id,
            'module'    => 'Page',
            'model'     => 'page',
            'index'     => LessonComponent::getNextIndex($request->Lesson_id)
        ]);
        return HelperController::api_response_format(200, $page,'Page added Successfully');

    }
    public function linkpagelesson(Request $request)
    {
        $request->validate([
            'page_id' => 'required|exists:pages,id',
            'lesson_id' => 'required|exists:lessons,id',
        ]);
        $check=pageLesson::where('page_id',$request->page_id)->where('lesson_id',$request->lesson_id)->pluck('id')->first();
        if($check!=null)
        {
            return HelperController::api_response_format(422, [],'relation is already exist');

        }
        $page= new pageLesson();
        $page->page_id=$request->page_id;
        $page->lesson_id=$request->lesson_id;
        $page->save();
        return HelperController::api_response_format(200, $page,'Page linked with lesson Successfully');

    }
    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:pages,id'
        ]);

        $page = Page::find($request->id);

        $request->validate([
            'title' => 'required|string',
            'content' => 'required|string',
            'visible' => 'nullable|boolean',
            'publish_date'=>'nullable|'
        ]);
        if($request->filled('publish_date'))
        {
            $publishdate=$request->publish_date;
            if(Carbon::parse($request->publish_date)->isPast()){
                $publishdate=Carbon::now();
            }
        }
        else
        {
            $publishdate=Carbon::now();
        }

        $data=[
                'title' => $request->title,
                'content' => $request->content
            ];
            if(isset($request->visible)) {
                $data['visible']=$request->visible;
            }
            $lessonID=pageLesson::where('page_id',$request->id)->pluck('lesson_id')->first();

        $page->update($data);

        $courseSegID=Lesson::where('id',$lessonID)->pluck('course_segment_id');
        $segmentClass=CourseSegment::where('id',$courseSegID)->pluck('segment_class_id')->first();
        $ClassLevel=SegmentClass::where('id',$segmentClass)->pluck('class_level_id')->first();
        $classId=ClassLevel::where('id',$ClassLevel)->pluck('class_id')->first();
        $courseID=CourseSegment::where('id',$courseSegID)->pluck('course_id')->first();
        $usersIDs=Enroll::where('course_segment',$courseSegID)->pluck('user_id')->toarray();
        User::notify([
            'message' => 'Page is Edited',
            'from' => Auth::user()->id,
            'users' => $usersIDs,
            'course_id' => $courseID,
            'class_id'=>$classId,
            'type' => 'Page',
            'link' => url(route('getPage')) . '?id=' . $page->id,
            'publish_date' => $publishdate
        ]);
        return HelperController::api_response_format(200, $page,'Page Updated Successfully');
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'page_id' => 'required|exists:page_lessons,page_id',
            'lesson_id' => 'required|exists:page_lessons,lesson_id'
        ]);

        $page =PageLesson::where('page_id', $request->page_id)->where('lesson_id',$request->lesson_id)->first();
        if ($page->delete()) {
            return HelperController::api_response_format(200, $page,'Page Deleted Successfully');
        }
        return HelperController::api_response_format(404, [], 'Not Found');
    }



    public function get(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:pages,id'
        ]);
        $page = page::whereId($request->id)->first();
        if($page == null)
            return HelperController::api_response_format(200 , null , 'This Page is not Visible');
        return HelperController::api_response_format(200 , $page);
    }

    public function togglePageVisibity(Request $request)
    {
        try {
            $request->validate([
                'page_id' => 'required|exists:pages,id',
                'lesson_id' => 'required|exists:page_lessons,lesson_id'
            ]);

            $page_lesson = pageLesson::where('page_id',$request->page_id)
                ->where('lesson_id',$request->lesson_id)->first();
            if(!isset($page_lesson)){
                return HelperController::api_response_format(400, null, 'Try again , Data invalid');
            }

            $page_lesson->visible = ($page_lesson->visible == 1) ? 0 : 1;
            $page_lesson->save();

            return HelperController::api_response_format(200, $page_lesson, 'Toggle Successfully');
        } catch (Exception $ex) {
            return HelperController::api_response_format(400, null, 'Please Try again');
        }
    }
}
