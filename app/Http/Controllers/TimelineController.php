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
use Modules\QuestionBank\Entities\UserQuiz;
use Modules\QuestionBank\Entities\Quiz;
use Modules\Assigments\Entities\Assignment;
use App\SecondaryChain;
use Redis;
use App\Paginate;

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
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
            'level' => 'exists:levels,id',
            'class' => 'exists:classes,id',
            'segment' => 'exists:segments,id',
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
            'item_type' => 'in:quiz,assignment',
            'sort_by' => 'in:course,name,due_date|required_with:sort_in',
            'sort_in' => 'in:asc,desc|required_with:sort_by',
            'start_date' => 'date',
            'due_date' => 'date',
            'date_filter' => 'in:week,month',
        ]);

        // if(!Auth::user()->can('site/course/student')){
        //     $request->validate([
        //         'level' => 'required|exists:levels,id',
        //     ]);
        // }
        $end = Carbon::now()->endOfWeek(Carbon::SATURDAY);

        if($request->filled('date_filter') && $request->date_filter == 'week'){
            $end = Carbon::now()->addDays(7);
        }
        if($request->filled('date_filter') && $request->date_filter == 'month'){
            $end = Carbon::now()->addDays(30);
        }

        $enrolls = $this->chain->getEnrollsByChain($request)->select('course','id')->where('user_id',Auth::id());
      //  $sec_chain = SecondaryChain::whereIn('enroll_id', $enrolls)->where('user_id',Auth::id())->select(['course']);
        $callQuery=function($q) use ($request){
            if(!$request->user()->can('course/show-hidden-courses'))
                $q->where('show',1);
        };
        
        $timeline = Timeline::whereHas('course',$callQuery)->with(['class','course'=>$callQuery,'level'])
                            //->whereIn('lesson_id',$sec_chain->pluck('lesson_id'))
                            ->whereIn('course_id',$enrolls->pluck('course'))
                            ->where('start_date','<=',Carbon::now())
                            ->where('due_date','>=',Carbon::now())
                            ->where('due_date','<=',$end )
                            ->where(function ($query) {
                                $query->whereNull('overwrite_user_id')->orWhere('overwrite_user_id', Auth::id());
                            });

        if(!$request->user()->can('site/show-all-courses')){
            $sec_chain = SecondaryChain::whereIn('enroll_id', $enrolls->pluck('id'))->where('user_id',Auth::id())->distinct('group_id')->select('group_id');
            $timeline->where('class_id',$sec_chain->pluck('group_id'));
        }
        if(!$request->filled('item_type'))
            $timeline->whereIn('type', ['quiz','assignment']);

        if($request->filled('item_type'))
            $timeline->where('type', $request->item_type);
                 
        if(Auth::user()->can('site/course/student'))
            $timeline->where('visible',1);

        if($request->has('start_date'))
            $timeline->whereDate('start_date', '=', $request->start_date);

        if($request->has('due_date'))
            $timeline->whereDate('due_date', '=', $request->due_date);

        if($request->has('sort_by') && $request->sort_by != 'course' && $request->has('sort_in'))
            $timeline->orderBy($request->sort_by, $request->sort_in);

        if($request->has('sort_by') && $request->sort_by == 'course' && $request->has('sort_in')){
            $object_sort = $timeline;
            if(count($object_sort->get()) > 0){
                $course_sort =  $object_sort->get()->sortBy('course.name')->values()->pluck('id');
                if($request->sort_in == 'desc')
                    $course_sort =  $object_sort->get()->sortByDesc('course.name')->values()->pluck('id');

                $ids_ordered = implode(',', $course_sort->toArray());
                $timeline->orderByRaw("FIELD(id, $ids_ordered)");
            }
        }

        $page = Paginate::GetPage($request);
        $paginate = Paginate::GetPaginate($request);
        $timeline->skip(($page)*$paginate)->take($paginate)->get()->map(function ($line){
            if($line->type == 'quiz'){
                $quizLesson=QuizLesson::where('quiz_id',$line->item_id)->where('lesson_id',$line->lesson_id)->first();
                $user_quiz = userQuiz::where('user_id', Auth::id())->where('quiz_lesson_id', $quizLesson->id)
                    ->whereNotNull('submit_time')->count();
                $line['max_attemp']=$quizLesson->max_attemp;
                $line['token_attempts']=$user_quiz;
                return $line;
            }
            return $line;
        });

        $result = $timeline->paginate(HelperController::GetPaginate($request));
        return response()->json(['message' => 'Timeline List of items', 'body' => $result  ], 200);
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
        $secondary_chains = SecondaryChain::where('lesson_id',$assignmentLesson->lesson_id)->get()->keyBy('group_id');
            foreach($secondary_chains as $secondary_chain){
                $course_id = $secondary_chain->course_id;
                $class_id = $secondary_chain->group_id;
                $level_id = Course::find($courseID)->level_id;
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
