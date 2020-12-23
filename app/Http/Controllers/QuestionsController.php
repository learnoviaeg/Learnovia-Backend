<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\QuestionBank\Entities\quiz;
use Modules\QuestionBank\Entities\Questions;
use App\Repositories\ChainRepositoryInterface;
use App\Enroll;
use App\Paginate;
use Modules\QuestionBank\Entities\quiz_questions;
use App\CourseSegment;

use Illuminate\Support\Facades\Auth;


class QuestionsController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        $this->middleware(['permission:question/get' , 'ParentCheck'],   ['only' => ['index']]);
        
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request,$quiz_id=null,$question=null)
    {
        $request->validate([
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
            'class' => 'nullable|integer|exists:classes,id',
            'Question_Category_id' => 'array',
            'Question_Category_id.*' => 'integer|exists:questions_categories,id',
            'question_type' => 'array',
            'question_type.*' => 'integer|exists:questions_types,id',
            'search' => 'nullable|string',

        ]);
        //to get all questions in quiz id //quizzes/{quiz_id}/{questions}'
        if($question=='questions'){
            $questions = quiz_questions::where('quiz_id',$quiz_id)
                    ->with(['Question.question_answer','Question.question_category','Question.question_type'])->get()
                    ->pluck('Question.*')->collapse();
            return response()->json(['message' => __('messages.question.list'), 'body' => $questions->paginate(Paginate::GetPaginate($request))], 200);
        }

        $user_course_segments = $this->chain->getCourseSegmentByChain($request);
        if(!$request->user()->can('site/show-all-courses')){//student
                $user_course_segments = $user_course_segments->where('user_id',Auth::id());
            }

        $course_ides = $user_course_segments->with('courseSegment')->get()->pluck('courseSegment.course_id')->unique()->values();

        $questions = Questions::whereIn('course_id',$course_ides)->where('survey',0)->with(['course','question_answer','question_category','question_type']);

        if($request->filled('search'))
        {
           $questions->where('text', 'LIKE' , "%$request->search%");
        }
        if (isset($request->Question_Category_id)) {
            $questions->whereIn('question_category_id', $request->Question_Category_id);
        }
        if (isset($request->question_type)) {
            $questions->whereIn('question_type_id', $request->question_type);
        }

        return response()->json(['message' => __('messages.question.list'), 'body' => $questions->get()->paginate(Paginate::GetPaginate($request))], 200);
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
