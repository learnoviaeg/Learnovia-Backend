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

        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'page/add', 'title' => 'add page']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'page/update', 'title' => 'update page']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'page/delete', 'title' => 'delete page']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'page/toggle', 'title' => 'toggle page']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'page/link-lesson', 'title' => 'link page to lesson']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'page/get', 'title' => 'get page']);

        $teacher_permissions=['page/add','page/update','page/delete','page/toggle','page/link-lesson','page/get'];
        $tecaher = \Spatie\Permission\Models\Role::find(4);
        $tecaher->givePermissionTo(\Spatie\Permission\Models\Permission::whereIn('name', $teacher_permissions)->get());

        $student_permissions=['page/get'];
        $student = \Spatie\Permission\Models\Role::find(3);
        $student->givePermissionTo(\Spatie\Permission\Models\Permission::whereIn('name', $student_permissions)->get());
        $parent = \Spatie\Permission\Models\Role::find(7);
        $parent->givePermissionTo(\Spatie\Permission\Models\Permission::whereIn('name', $student_permissions)->get());


        $role = \Spatie\Permission\Models\Role::find(1);
        $role->givePermissionTo('page/add');
        $role->givePermissionTo('page/update');
        $role->givePermissionTo('page/delete');
        $role->givePermissionTo('page/toggle');
        $role->givePermissionTo('page/link-lesson');
        $role->givePermissionTo('page/get');

        Component::create([
            'name' => 'Page',
            'module' => 'Page',
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
            'lesson_id' => 'required|array',
            'lesson_id.*' => 'required|exists:lessons,id',
            'publish_date' => 'nullable|date'
        ]);
        if ($request->filled('publish_date')) {
            $publishdate = Carbon::parse($request->publish_date);
            if (Carbon::parse($request->publish_date)->isPast()) {
                $publishdate = Carbon::now();
            }
        } else {
            $publishdate = Carbon::now();
        }
        $page = new Page();
        $page->title = $request->title;
        $page->content = $request->content;
        $page->save();
        foreach($request->lesson_id as $lesson){
            pageLesson::firstOrCreate([
                'page_id' => $page->id,
                'lesson_id' => $lesson,
                'publish_date' => $publishdate
            ]);
            LessonComponent::create([
                'lesson_id' => $lesson,
                'comp_id' => $page->id,
                'module' => 'Page',
                'model' => 'page',
                'index' => LessonComponent::getNextIndex($request->Lesson_id)
            ]);
            $TempLesson = Lesson::find($lesson);
            $usersIDs = Enroll::where('course_segment', $TempLesson->courseSegment->id)->where('user_id','!=',Auth::user()->id)->pluck('user_id')->toarray();
            User::notify([
                'id' => $page->id,
                'message' => 'A new Page is added',
                'from' => Auth::user()->id,
                'users' => $usersIDs,
                'course_id' => $TempLesson->courseSegment->courses[0]->id,
                'class_id' => $TempLesson->courseSegment->segmentClasses[0]->classLevel[0]->classes[0]->id,
                'lesson_id' => $lesson,
                'type' => 'Page',
                'link' => url(route('getPage')) . '?id=' . $page->id,
                'publish_date' => $publishdate,
            ]);
        }
        $tempReturn = Lesson::find($request->lesson_id[0])->module('Page', 'page')->get();;
        return HelperController::api_response_format(200, $tempReturn, 'Page added successfully');

    }

    public function linkpagelesson(Request $request)
    {
        $request->validate([
            'page_id' => 'required|exists:pages,id',
            'lesson_id' => 'required|exists:lessons,id',
        ]);
        $check = pageLesson::where('page_id', $request->page_id)->where('lesson_id', $request->lesson_id)->pluck('id')->first();
        if ($check != null) {
            return HelperController::api_response_format(422, [], 'relation is already exist');

        }
        $page = new pageLesson();
        $page->page_id = $request->page_id;
        $page->lesson_id = $request->lesson_id;
        $page->save();
        return HelperController::api_response_format(200, $page, 'Page linked with lesson successfully');

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
            'title' => 'required|string',
            'content' => 'required|string',
            'id' => 'required|exists:pages,id',
            'lesson_id' => 'required|array',
            'lesson_id.*' => 'required|exists:lessons,id',
            'updated_lesson_id' =>'nullable|exists:lessons,id'
            ]);

        $page = Page::find($request->id);
        $data = [
            'title' => $request->title,
            'content' => $request->content
        ];
        $page->update($data);
        $page_lesson = pageLesson::where('page_id', $request->id)
                ->where('lesson_id', $request->lesson_id[0])->first();
        if (!$request->filled('updated_lesson_id')) {
            $request->updated_lesson_id= $request->lesson_id[0];
            }
            $page_lesson->update([
                'lesson_id' => $request->updated_lesson_id
            ]);
        $page_lesson->updated_at = Carbon::now();
        $page_lesson->save();
        $pagename = $page->title;
        if($request->filled('lesson_id')){
            $page = Lesson::find($request->lesson_id[0])->module('Page', 'page')->get();
        }
        $lesson = Lesson::find($request->lesson_id[0]);
        $usersIDs = Enroll::where('course_segment', $lesson->course_segment_id)->where('user_id','!=',Auth::user()->id)->pluck('user_id')->toarray();
        User::notify([
            'id' => $request->id,
            'message' => $pagename.' is updated',
            'from' => Auth::user()->id,
            'users' => $usersIDs,
            'course_id' => $lesson->courseSegment->courses[0]->id,
            'class_id' => $lesson->courseSegment->segmentClasses[0]->classLevel[0]->classes[0]->id,
            'lesson_id' => $request->lesson_id[0],
            'type' => 'Page',
            'link' => url(route('getPage')) . '?id=' . $request->id,
            'publish_date' => Carbon::now()
        ]);
        return HelperController::api_response_format(200, $page, 'Page edited successfully');
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
            'lesson_id' => 'required|exists:lessons,id'
        ]);
        $page = PageLesson::where('page_id', $request->page_id)->where('lesson_id', $request->lesson_id)->first();
        if($page)
        {
            $page->delete();
            $pagelesson = PageLesson::where('page_id', $request->page_id)->get();

            if(count($pagelesson) == 0 ){
                Page::whereId($request->page_id)->delete();
            }

            $tempReturn = Lesson::find($request->lesson_id)->module('Page', 'page')->get();
            return HelperController::api_response_format(200, $tempReturn, 'Page deleted successfully');
        }
        return HelperController::api_response_format(404, [], 'Not Found');
    }


    public function get(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:pages,id',
            'lesson_id' => 'required|exists:lessons,id'
        ]);
        $page = page::whereId($request->id)->first();
        if ($page == null)
            return HelperController::api_response_format(200, null, 'This page is not visible');
        $lesson = $page->lesson->where('id', $request->lesson_id)->first();
        if(isset($lesson))
            $course_id= $lesson->courseSegment->course_id;
        else
            return HelperController::api_response_format(200, null , 'This page is not assigned to the given lesson');
        $page->course_id=$course_id;
        $page->page_lesson = PageLesson::where('page_id',$request->id)->where('lesson_id',$request->lesson_id)->first();
        unset($page->lesson);
        
        return HelperController::api_response_format(200, $page);
    }

    public function togglePageVisibity(Request $request)
    {
        try {
            $request->validate([
                'page_id' => 'required|exists:pages,id',
                'lesson_id' => 'required|exists:page_lessons,lesson_id'
            ]);

            $page_lesson = pageLesson::where('page_id', $request->page_id)
                ->where('lesson_id', $request->lesson_id)->first();
            if (!isset($page_lesson)) {
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
