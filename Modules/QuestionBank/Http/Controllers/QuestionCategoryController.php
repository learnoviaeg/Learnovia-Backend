<?php

namespace Modules\QuestionBank\Http\Controllers;

use App\CourseSegment;
use App\Enroll;
use App\Http\Controllers\HelperController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\QuestionBank\Entities\QuestionsCategory;

class QuestionCategoryController extends Controller
{
    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create(Request $request)
    {
        $request->validate([
            'course' => 'required|integer|exists:courses,id',
            'class' => 'array|exists:classes,id',
            'name' => 'string|required'
        ]);
        $quest_cat=[];
        $course_seg_id = [];

        $myCourseSeg=Enroll::where('user_id',Auth::id())->pluck('course_segment');
        if(count($myCourseSeg) < 1)
            return HelperController::api_response_format(200,null,'you doesn\'t have any courses');        
        $course_seg_id =CourseSegment::whereIn('id',$myCourseSeg)->where('course_id',$request->course)->pluck('id');

        if($request->filled('class'))
        {
            foreach($request->class as $class)
            {
                $course_seg=CourseSegment::GetWithClassAndCourse($class,$request->course);
                if(isset($course_seg))
                    $course_seg_id[]=$course_seg->id;
            }
        }

        foreach($course_seg_id as $CourseSeg)
        {
            $quest_cat[]=QuestionsCategory::firstOrCreate([
                'name' => $request->name,
                'course_segment_id' => $CourseSeg
            ]);
        }
        return HelperController::api_response_format(200, array_values(array_unique($quest_cat)), 'Question categories added Successfully');
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show(Request $request)
    {
        $request->validate([
            'course_id' => 'integer|exists:courses,id',
            'text' => 'string'
        ]);

        $ques_cat=QuestionsCategory::where(function($q) use($request){
            if($request->filled('text'))
                $q->orWhere('name', 'LIKE' ,"%$request->text%" );
        })->with('CourseSegment.courses')->get();

        if($request->filled('course_id'))
        {
            $all_courses=CourseSegment::where('course_id',$request->course_id)->pluck('id');
            $ques_cat=QuestionsCategory::whereIn('course_segment_id',$all_courses)->where(function($q) use($request){
                if($request->filled('text'))
                    $q->orWhere('name', 'LIKE' ,"%$request->text%" );
            })->with('CourseSegment.courses')->get();
        }
        foreach($ques_cat as $cat)
        {
            $cat->course=isset($cat->CourseSegment) ? $cat->CourseSegment->courses[0] : null;
            $cat->class=isset($cat->CourseSegment) ? $cat->CourseSegment->segmentClasses[0]->classLevel[0]->classes[0] : null;
        }
        return HelperController::api_response_format(200, $ques_cat->paginate(HelperController::GetPaginate($request)), 'Question Categories');    
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
            'course' => 'integer|exists:courses,id',
            'class' => 'exists:classes,id',
            'name' => 'string',
            'id' => 'required|exists:questions_categories,id'
        ]);
        $questioncat=QuestionsCategory::find($request->id);
        $myCourseSeg=Enroll::where('user_id',Auth::id())->pluck('course_segment');
        $course_seg=CourseSegment::GetWithClassAndCourse($request->class,$request->course);
        if($request->user()->can('question/category/update'))
        {
            $questioncat->update([
                'name' => isset($request->name) ? $request->name : $questioncat->name,
                'course_segment_id' => isset($course_seg) ? $course_seg->id : $questioncat->course_segment_id
            ]);
        }
        if($request->filled('class') && $request->filled('course')){
            $course_seg=CourseSegment::GetWithClassAndCourse($request->class,$request->course);
            if(!isset($course_seg))
                return HelperController::api_response_format(200,'Can\'t update Question Category');
            if(in_array($course_seg->id,$myCourseSeg->toArray()))
                $course_seg_id=$course_seg->id;
            else
                return HelperController::api_response_format(200,'Can\'t update Question Category');
        }
        $questioncat->update([
            'name' => isset($request->name) ? $request->name : $questioncat->name,
            'course_segment_id' => isset($course_seg) ? $course_seg_id : $questioncat->course_segment_id
        ]);
        return HelperController::api_response_format(200, $questioncat, 'Question Category updated Successfully');
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:questions_categories,id'
        ]);

        $questioncat=QuestionsCategory::find($request->id);
        $myCourseSeg=Enroll::where('user_id',Auth::id())->pluck('course_segment');
        if(in_array($questioncat->course_segment_id,$myCourseSeg->toArray()))
            $check=$questioncat->delete();
        else
            return HelperController::api_response_format(200, 'you can\'t delete this question category');
        
        if($check)
            return HelperController::api_response_format(200, $questioncat, 'Question Category deleted Successfully');
    }
}
