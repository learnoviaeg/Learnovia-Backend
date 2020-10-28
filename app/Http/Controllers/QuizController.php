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


class QuizController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        $this->middleware(['permission:quiz/get' , 'ParentCheck'],   ['only' => ['index']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',

        ]);
        $enrolls = $this->chain->getCourseSegmentByChain($request);
        $user_course_segments = $enrolls;
        if(!$request->user()->can('site/show-all-courses'))//student
            $user_course_segments = $enrolls->where('user_id',Auth::id());
        $user_course_segments = $user_course_segments->pluck('course_segment');
        $lessons = Lesson::whereIn('course_segment_id', $user_course_segments)->pluck('id');
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
