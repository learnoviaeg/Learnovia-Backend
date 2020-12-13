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
use App\Repositories\ChainRepositoryInterface;
use App\LastAction;

class QuestionCategoryController extends Controller
{
    protected $chain;

    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
    }
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

        $course_seg_id =CourseSegment::where('course_id',$request->course)->pluck('id');
        if(count($course_seg_id) < 1)
            return HelperController::api_response_format(200,null,'you doesn\'t have any courses'); 

        LastAction::lastActionInCourse($request->course);
        if($request->filled('class'))
        {
            $courses=[];
            foreach($request->class as $class)
            {
                $course_seg=CourseSegment::GetWithClassAndCourse($class,$request->course);
                if(isset($course_seg))
                    $courses[]=$course_seg->id;
            }
            $course_seg_id=$courses;
        }

        // return $course_seg_id;
        foreach($course_seg_id as $CourseSeg)
        {
            $duplicate=QuestionsCategory::where('name',$request->name)->where('course_segment_id',$CourseSeg)->get()->first();
            if(isset($duplicate->course_segment_id) && isset($duplicate->name))
                return HelperController::api_response_format(400, $duplicate, 'This category added before');

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
            'text' => 'string',
            'lastpage' => 'bool',
            'dropdown' => 'boolean',
            'class' => 'array|exists:classes,id'
        ]);
        $user_course_segments = $this->chain->getCourseSegmentByChain($request);
        if(!$request->user()->can('site/show-all-courses'))//  teacher 
            {
                $user_course_segments = $user_course_segments->where('user_id',Auth::id());
            }
        $user_course_segments = $user_course_segments->pluck('course_segment');

        $ques_cat=QuestionsCategory::where(function($q) use($request){
            if($request->filled('text'))
                $q->orWhere('name', 'LIKE' ,"%$request->text%" );
            })->whereIn('course_segment_id',$user_course_segments)->with('CourseSegment.courses')->get();

        if($request->filled('course_id'))
        {
        LastAction::lastActionInCourse($request->course_id);

            $all_courses=CourseSegment::where('course_id',$request->course_id)->pluck('id');
            if($request->filled('class'))
            {
                $courses=[];
                foreach($request->class as $class)
                {
                    $course_seg=CourseSegment::GetWithClassAndCourse($class,$request->course_id);
                    // return $course_seg;
                    if(isset($course_seg))
                        $courses[]=$course_seg->id;
                }
                $all_courses=$courses;
            }
            $ques_cat=QuestionsCategory::whereIn('course_segment_id',$all_courses)->where(function($q) use($request){
                if($request->filled('text'))
                    $q->orWhere('name', 'LIKE' ,"%$request->text%" );
            })->with('CourseSegment.courses')->get();
        }
        foreach($ques_cat as $cat)
        {
            $cat->course=isset($cat->CourseSegment) ? $cat->CourseSegment->courses[0] : null;
            $cat->class= isset($cat->CourseSegment)  && count($cat->CourseSegment->segmentClasses) > 0  && count($cat->CourseSegment->segmentClasses[0]->classLevel) > 0 && count($cat->CourseSegment->segmentClasses[0]->classLevel[0]->classes) > 0 ? $cat->CourseSegment->segmentClasses[0]->classLevel[0]->classes[0] : null;
        }
        if(isset($request->lastpage) && $request->lastpage == true){
            $request['page'] = $ques_cat->paginate(HelperController::GetPaginate($request))->lastPage();
        }
        if(isset($request->dropdown) && $request->dropdown == true)
            return HelperController::api_response_format(200, $ques_cat, 'Question Categories');    
        else
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
            LastAction::lastActionInCourse($request->course);        
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
        $course_segment = CourseSegment::find($questioncat->course_segment_id);
        if(isset($course_segment))
            LastAction::lastActionInCourse($course_segment->course_id);        
        if(count($questioncat->questions)>0)
            return HelperController::api_response_format(200, null,'you can\'t delete this question category');
        $questioncat->delete();
        return HelperController::api_response_format(200, $questioncat, 'Question Category deleted Successfully');


        // $myCourseSeg=Enroll::where('user_id',Auth::id())->pluck('course_segment');
        // if(in_array($questioncat->course_segment_id,$myCourseSeg->toArray()))
        //     $check=$questioncat->delete();
        // else
        //     return HelperController::api_response_format(200, 'you can\'t delete this question category');
        // if($check)
        //     return HelperController::api_response_format(200, $questioncat, 'Question Category deleted Successfully');
    }
}
