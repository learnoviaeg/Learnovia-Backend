<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ChainRepositoryInterface;
use App\Enroll;
use Illuminate\Support\Facades\Auth;
use App\Lesson;
use App\Level;
use App\Course;
use App\Classes;
use Modules\Assigments\Entities\AssignmentLesson;
use Modules\Assigments\Entities\assignment;
use Modules\Assigments\Entities\UserAssigment;
use Modules\Assigments\Entities\assignmentOverride;
use App\Paginate;
use App\LastAction;
use Carbon\Carbon;

class AssignmentController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        $this->middleware(['permission:assignment/get' , 'ParentCheck'],   ['only' => ['index','show']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request,$count = null)
    {
        $request->validate([
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
            'level' => 'exists:levels,id',
            'segment' => 'exists:segments,id',
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
            'class' => 'nullable|integer|exists:classes,id',
            'lesson' => 'nullable|integer|exists:lessons,id',
            'sort_in' => 'in:asc,desc', 
        ]);

        $user_course_segments = $this->chain->getCourseSegmentByChain($request);
        if(!$request->user()->can('site/show-all-courses'))//student
            $user_course_segments = $user_course_segments->where('user_id',Auth::id());

        $lessons = $user_course_segments->select('course_segment')->distinct()->with('courseSegment.lessons')->get()->pluck('courseSegment.lessons.*.id')->collapse();
       
        if($request->filled('lesson')){
            if (!in_array($request->lesson,$lessons->toArray()))
                return response()->json(['message' => __('messages.error.no_active_for_lesson'), 'body' => []], 400);
            
            $lessons  = [$request->lesson];
        }

        $sort_in = 'desc';
        if($request->has('sort_in'))
            $sort_in = $request->sort_in;

        $assignment_lessons = AssignmentLesson::whereIn('lesson_id',$lessons)->orderBy('start_date',$sort_in);

        if($request->user()->can('site/course/student')){
            $assignment_lessons->where('visible',1)->where('publish_date' ,'<=', Carbon::now());
        }

        if($count == 'count'){
            
            return response()->json(['message' => __('messages.assignment.count'), 'body' => $assignment_lessons->count()], 200);        
        }

        $assignment_lessons = $assignment_lessons->get();

        $assignments = collect([]);

        foreach($assignment_lessons as $assignment_lesson){
            $assignment=assignment::where('id',$assignment_lesson->assignment_id)->first();
            $assignment['assignmentlesson'] = $assignment_lesson;
            $assignment['lesson'] = Lesson::find($assignment_lesson->lesson_id);
            $assignment['class'] = Classes::find($assignment['lesson']->courseSegment->segmentClasses[0]->classLevel[0]->class_id);
            $assignment['level'] = Level::find($assignment['lesson']->courseSegment->segmentClasses[0]->classLevel[0]->yearLevels[0]->level_id);
            $assignment['course'] = Course::find($assignment['lesson']->courseSegment->course_id);
            unset($assignment['lesson']->courseSegment);
            $assignments[]=$assignment;
        }

        return response()->json(['message' => __('messages.assignment.list'), 'body' => $assignments->paginate(Paginate::GetPaginate($request))], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($assignment_id,$lesson_id)
    {
        $user = Auth::user();

        $assigLessonID = AssignmentLesson::where('assignment_id', $assignment_id)->where('lesson_id', $lesson_id)->first();        
        if(!isset($assigLessonID))
            return response()->json(['message' => __('messages.assignment.assignment_not_belong'), 'body' => [] ], 400);

        $assignment = assignment::where('id',$assignment_id)->first();
        if(!isset($assignment))
            return response()->json(['message' => __('messages.error.not_found'), 'body' => [] ], 400);

        $lesson_drag = Lesson::find($lesson_id);
        LastAction::lastActionInCourse($lesson_drag->courseSegment->courses[0]->id);
        $userassigments = UserAssigment::where('assignment_lesson_id', $assigLessonID->id)->where('submit_date','!=',null)->get();
        if (count($userassigments) > 0) {
            $assignment['allow_edit'] = false;
        } else {
            $assignment['allow_edit'] = true;
        }
        $assignment['user_submit']=null;
        $assignment['visible'] = $assigLessonID->visible;
          /////////////student
        if ($user->can('site/assignment/getAssignment')) {
        $studentassigment = UserAssigment::where('assignment_lesson_id', $assigLessonID->id)->where('user_id', $user->id)->first();
        if(isset($studentassigment)){
            $assignment['user_submit'] =$studentassigment;}
        }

        $seen = $assigLessonID->seen_number + 1;
        $assigLessonID->seen_number = $seen;
        $assigLessonID->save();
       
        return response()->json(['message' => __('messages.assignment.assignment_object'), 'body' => $assignment], 200);        
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
