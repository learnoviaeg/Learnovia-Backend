<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ChainRepositoryInterface;
use App\Enroll;
use Illuminate\Support\Facades\Auth;
use Modules\QuestionBank\Entities\QuizOverride;
use Modules\QuestionBank\Entities\quiz;
use App\Lesson;
use App\Classes;
use App\Course;
use App\Level;
use App\Paginate;
use Modules\QuestionBank\Entities\QuizLesson;
use App\LastAction;

class QuizzesController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        $this->middleware(['permission:quiz/get' , 'ParentCheck'],   ['only' => ['index']]);
        $this->middleware(['permission:quiz/detailes' , 'ParentCheck'],   ['only' => ['show']]);

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

        $user_course_segments = $user_course_segments->select('course_segment')->distinct()->with('courseSegment.lessons')->get();

        $lessons = $user_course_segments->pluck('courseSegment.lessons')->collapse()->pluck('id');

        if($request->filled('lesson')){
            if (!in_array($request->lesson,$lessons->toArray()))
                return response()->json(['message' => 'No active course segment for this lesson ', 'body' => []], 400);
            
            $lessons  = [$request->lesson];
        }

        $sort_in = 'desc';
        if($request->has('sort_in'))
            $sort_in = $request->sort_in;

        $quiz_lessons = QuizLesson::whereIn('lesson_id',$lessons)->orderBy('start_date',$sort_in);

        if($count == 'count')
            return response()->json(['message' => 'Quizzes count', 'body' => $quiz_lessons->count() ], 200);

        $quiz_lessons = $quiz_lessons->get();

        $quizzes = collect([]);

        foreach($quiz_lessons as $quiz_lesson){
            $quiz=quiz::with('course')->where('id',$quiz_lesson->quiz_id)->first();
            $quiz['quizlesson'] = $quiz_lesson;
            $quiz['lesson'] = Lesson::find($quiz_lesson->lesson_id);
            $quiz['class'] = Classes::find($quiz['lesson']->courseSegment->segmentClasses[0]->classLevel[0]->class_id);
            $quiz['level'] = Level::find($quiz['lesson']->courseSegment->segmentClasses[0]->classLevel[0]->yearLevels[0]->level_id);
            unset($quiz['lesson']->courseSegment);
            $quizzes[]=$quiz;
        }

        return response()->json(['message' => 'Quizzes List ....', 'body' => $quizzes->paginate(Paginate::GetPaginate($request))], 200);
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
    public function show($id)
    {
        $quiz = quiz::find($id);

        if(isset($quiz)){
            LastAction::lastActionInCourse($quiz->course_id);
            return response()->json(['message' => 'quiz objet ..', 'body' => $quiz ], 200);
        }

        return response()->json(['message' => 'quiz not fount!', 'body' => [] ], 400);
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
