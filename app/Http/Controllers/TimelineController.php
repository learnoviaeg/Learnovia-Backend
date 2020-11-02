<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Timeline;
use Carbon\Carbon;
use App\Enroll;
use App\CourseSegment;
use App\Lesson;
use App\Segment;
use App\AcademicYear;
use Illuminate\Support\Facades\Auth;
use App\Repositories\ChainRepositoryInterface;
use Modules\Assigments\Entities\AssignmentLesson;
use Modules\Assigments\Entities\assignmentOverride;
use Modules\QuestionBank\Entities\QuizOverride;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\Quiz;
use Modules\Assigments\Entities\Assignment;

class TimelineController extends Controller
{
    protected $chain;

    /**
     * ChainController constructor.
     *
     * @param ChainRepositoryInterface $post
     */
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        $this->middleware('permission:timeline/store', ['only' => ['store']]);
        $this->middleware(['permission:timeline/get' , 'ParentCheck'],   ['only' => ['index']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //validate the request
        $request->validate([
            'level' => 'exists:levels,id',
            'class' => 'exists:classes,id',
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
            'item_type' => 'in:quiz,assignment',
            'sort_by' => 'in:course,name,due_date|required_with:sort_in',
            'sort_in' => 'in:asc,desc|required_with:sort_by',
            'start_date' => 'date',
            'due_date' => 'date',
        ]);

        $current_course_segments = $this->chain->getCourseSegmentByChain($request);
        if(count($current_course_segments) == 0)
            return response()->json(['message' => 'There is no active course segments', 'body' => []], 200);
            
        $user_course_segments = Enroll::where('user_id',Auth::id())->pluck('course_segment');
        $user_course_segments = array_intersect($current_course_segments->toArray(),$user_course_segments->toArray());
        if($request->user()->can('site/show-all-courses'))
            $user_course_segments = $current_course_segments;

        $lessons = Lesson::whereIn('course_segment_id', $user_course_segments)->pluck('id');
        $timeline = Timeline::with(['class','course','level'])->whereIn('lesson_id',$lessons)->where('visible',1)->where('start_date','<=',Carbon::now())->where('due_date','>=',Carbon::now());



        if($request->has('item_type'))
            $timeline->where('type',$request->item_type);

        if($request->has('start_date'))
            $timeline->whereDate('start_date', '=', $request->start_date);

        if($request->has('due_date'))
            $timeline->whereDate('due_date', '=', $request->due_date);

        if($request->has('sort_by') && $request->sort_by != 'course' && $request->has('sort_in'))
            $timeline->orderBy($request->sort_by, $request->sort_in);

        if($request->has('sort_by') && $request->sort_by == 'course' && $request->has('sort_in')){
            $object_sort = $timeline;
            $course_sort =  $object_sort->get()->sortBy('course.name')->values()->pluck('id');
            if($request->sort_in == 'desc')
                $course_sort =  $object_sort->get()->sortByDesc('course.name')->values()->pluck('id');

            $ids_ordered = implode(',', $course_sort->toArray());
            $timeline->orderByRaw("FIELD(id, $ids_ordered)");
        }

        $timelines = $timeline->get();
        if($request->user()->can('site/course/student')){
            foreach($timelines as $timeline){
                $override = null;
                if($timeline->item_type == 'assignment'){
                    $assignment_lesson = AssignmentLesson::where('assignment_id',$timeline->item_id)
                                                        ->where('lesson_id',$timeline->lesson_id)->first();
                    if(isset($assignment_lesson))
                        $override = assignmentOverride::where('user_id',Auth::id())->where('assignment_lesson_id',$assignment_lesson->id)->first();
                }

                if($timeline->item_type == 'quiz'){
                    $quiz_lesson = QuizLesson::where('quiz_id',$timeline->item_id)->where('lesson_id',$timeline->lesson_id)->first();
                    if(isset($quiz_lesson))
                        $override = QuizOverride::where('user_id',Auth::id())->where('quiz_lesson_id',$quiz_lesson->id)->first();
                }

                if(isset($override)){
                    $timeline->start_date = $override->start_date;
                    $timeline->due_date = $override->due_date;
                }
            }
        }

        return response()->json(['message' => 'Timeline List of items', 'body' => $timelines], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //validate the request
        $request->validate([
            'id' => 'required',
            'lesson_id' => 'required|exists:lessons,id',
            'type' => 'required|in:quiz,assignment'
        ]);

        if($request->type == 'assignment'){
            $request->validate(['id' => 'required|exists:assignments,id',]);
            $item_Lesson = AssignmentLesson::where('assignment_id',$request->id)->where('lesson_id',$request->lesson_id)->first();
            $assignment = Assignment::where('id',$item_Lesson->assignment_id)->first();  
            if(isset($assignment))  
                $item_name = $assignment->name;
        }
        
        if($request->type == 'quiz'){
            $request->validate(['id' => 'required|exists:quizzes,id',]);
            $item_Lesson = QuizLesson::where('quiz_id',$request->id)->where('lesson_id',$request->lesson_id)->first();
            $quiz = Quiz::where('id',$item_Lesson->quiz_id)->first();  
            if(isset($quiz))  
                $item_name = $quiz->name;
        }
        
        $lesson = Lesson::find($item_Lesson->lesson_id);
        $course_id = $lesson->courseSegment->course_id;
        $class_id = $lesson->courseSegment->segmentClasses[0]->classLevel[0]->class_id;
        $level_id = $lesson->courseSegment->segmentClasses[0]->classLevel[0]->yearLevels[0]->level_id;
        if(isset($item_name)){
           $new_timeline = Timeline::firstOrCreate([
                'item_id' => $request->id,
                'name' => $item_name,
                'start_date' => $item_Lesson->start_date,
                'due_date' => $item_Lesson->due_date,
                'publish_date' => isset($item_Lesson->publish_date)? $item_Lesson->publish_date : Carbon::now(),
                'course_id' => $course_id,
                'class_id' => $class_id,
                'lesson_id' => $item_Lesson->lesson_id,
                'level_id' => $level_id,
                'type' => $request->type,
                'visible' => $item_Lesson->visible
            ]);
        }
        return response()->json(['message' => 'timeline created.','body' => $new_timeline], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
