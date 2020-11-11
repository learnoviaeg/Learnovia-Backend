<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ChainRepositoryInterface;
use App\Enroll;
use Illuminate\Support\Facades\Auth;
use Modules\QuestionBank\Entities\QuizOverride;
use Modules\QuestionBank\Entities\quiz;
use App\Lesson;
use Modules\QuestionBank\Entities\QuizLesson;


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
    public function index(Request $request)
    {
        $request->validate([
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
            'level' => 'exists:levels,id',
            'segment' => 'exists:segments,id',
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
            'class' => 'nullable|integer|exists:classes,id',
            'lesson' => 'nullable|integer|exists:lessons,id' 
        ]);
        
        $user_course_segments = $this->chain->getCourseSegmentByChain($request);
        if(!$request->user()->can('site/show-all-courses'))//student
            {
                $user_course_segments = $user_course_segments->where('user_id',Auth::id());
            }

        $user_course_segments = $user_course_segments->with('courseSegment.lessons')->get();
        $lessons =[];
        foreach ($user_course_segments as $user_course_segment){
            $lessons = array_merge($lessons,$user_course_segment->courseSegment->lessons->pluck('id')->toArray());
        }
        $lessons =  array_values (array_unique($lessons)) ;
        if($request->filled('lesson')){
            if (!in_array($request->lesson,$lessons)){
                return response()->json(['message' => 'No active course segment for this lesson ', 'body' => []], 400);
            }
            $lessons  = [$request->lesson];
        }
        $quiz_lessons = QuizLesson::whereIn('lesson_id',$lessons)->get()->sortByDesc('start_date');
        $quizzes = collect([]);
        foreach($quiz_lessons as $quiz_lesson){
            $quiz=quiz::with('course')->where('id',$quiz_lesson->quiz_id)->first();
            $quiz['quizlesson'] = $quiz_lesson;
            $quiz['lesson'] = Lesson::find($quiz_lesson->lesson_id);
            $quizzes[]=$quiz;
        }
        return response()->json(['message' => 'Quizzes List ....', 'body' => $quizzes], 200);
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

        if(isset($quiz))
            return response()->json(['message' => 'quiz objet ..', 'body' => $quiz ], 200);

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
