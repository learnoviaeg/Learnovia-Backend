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
use Modules\QuestionBank\Entities\Questions;

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
            'name' => 'string|required'
        ]);
    
        $duplicate=QuestionsCategory::where('name',$request->name)->where('course_id',$request->course)->first();
        if(isset($duplicate))
            return HelperController::api_response_format(400, $duplicate, 'This category added before');

        LastAction::lastActionInCourse($request->course);

        //course segment doesn't have any need better to be removed
        $course_segment = CourseSegment::where('course_id',$request->course)->first();
        if(!isset($course_segment))
            return HelperController::api_response_format(400, null, 'This course is not assigned to chain');
            
        $quest_cat = QuestionsCategory::firstOrCreate([
            'name' => $request->name,
            'course_id' => $request->course,
            'course_segment_id' => $course_segment->id
        ]);

        $quest_cat = [$quest_cat];
        
        return HelperController::api_response_format(200, $quest_cat, 'Question category added Successfully');
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

        $enrolls = $this->chain->getCourseSegmentByChain($request);
        
        if(!$request->user()->can('site/show-all-courses'))//teacher 
        {
            $enrolls = $enrolls->where('user_id',Auth::id());
        }

        if($request->filled('course_id'))
            $enrolls->where('course',$request->course_id);

        $enrolls = $enrolls->select('course')->distinct()->pluck('course');

        $ques_cat=QuestionsCategory::where(function($q) use($request){
            if($request->filled('text'))
                $q->orWhere('name', 'LIKE' ,"%$request->text%" );
        })->whereIn('course_id',$enrolls)->with(['course','CourseSegment.courses'])->get();

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
            'name' => 'string',
            'id' => 'required|exists:questions_categories,id'
        ]);

        $questioncat=QuestionsCategory::find($request->id);
        
        if($request->filled('name'))
            $questioncat->name = $request->name;

        LastAction::lastActionInCourse($questioncat->course_id);        

        $questioncat->save();
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

    }

    public function MigrationScript(Request $request)
    {
        $categories = QuestionsCategory::get();

        foreach($categories as $category){
            $category->course_id = CourseSegment::where('id',$category->course_segment_id)->pluck('course_id')->first();
            $category->save();
        }

        $i = 0;
        $count = count($categories);
        while($count != $i){

            $categories = QuestionsCategory::get()->toArray();
            $category = $categories[$i];

            $id = $category['id'];

            $will_update = QuestionsCategory::where('id','!=',$id)->where('name',$category['name'])
                                                                  ->where('created_at',$category['created_at'])
                                                                  ->where('course_id',$category['course_id'])
                                                                  ->pluck('id');

            Questions::whereIn('question_category_id', $will_update)->update([
                'question_category_id' => $id
            ]);

            QuestionsCategory::whereIn('id',$will_update)->delete();

            $count = QuestionsCategory::count();
            $i++;
        }

        return 'done';
       
    }
}
