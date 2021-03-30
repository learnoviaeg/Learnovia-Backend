<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ChainRepositoryInterface;
use App\Enroll;
use Illuminate\Support\Facades\Auth;
use Modules\QuestionBank\Entities\QuizOverride;
use Modules\QuestionBank\Entities\quiz;
use App\Lesson;
use App\Questions;
use App\Classes;
use App\Course;
use App\Level;
use App\Paginate;
use Modules\QuestionBank\Entities\QuizLesson;
use App\LastAction;
use Carbon\Carbon;

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
                return response()->json(['message' => __('messages.error.no_active_for_lesson'), 'body' => []], 400);
            
            $lessons  = [$request->lesson];
        }

        $sort_in = 'desc';
        if($request->has('sort_in'))
            $sort_in = $request->sort_in;

        $quiz_lessons = QuizLesson::whereIn('lesson_id',$lessons)->orderBy('start_date',$sort_in);

        if($request->user()->can('site/course/student'))
            $quiz_lessons->where('visible',1)->where('publish_date' ,'<=', Carbon::now());

        if($count == 'count')
            return response()->json(['message' => __('messages.quiz.count'), 'body' => $quiz_lessons->count() ], 200);
        
        $quiz_lessons = $quiz_lessons->get();

        $quizzes = collect([]);
        $quest= collect([]);

        foreach($quiz_lessons as $quiz_lesson){
            $quiz=quiz::with('course')->where('id',$quiz_lesson->quiz_id)->first();
            $quiz['quizlesson'] = $quiz_lesson;
            $quiz['lesson'] = Lesson::find($quiz_lesson->lesson_id);
            $quiz['class'] = Classes::find($quiz['lesson']->courseSegment->segmentClasses[0]->classLevel[0]->class_id);
            $quiz['level'] = Level::find($quiz['lesson']->courseSegment->segmentClasses[0]->classLevel[0]->yearLevels[0]->level_id);
            
            $quest=$quest->merge(Questions::where('type','T_F')->with('T_F_question')->get());
            $quest=$quest->merge(Questions::where('type','Essay')->with('Essay_question')->get());
            $quest=$quest->merge(Questions::where('type','Match')->with('Match_question')->get());
            $quest=$quest->merge(Questions::where('type','MCQ')->with('MCQ_question.MCQ_Choices')->get());
            $quest=$quest->merge(Questions::where('type','comprehension')->with('T_F_question','Essay_question','MCQ_question.MCQ_Choices')->get());
            $quiz['Question']=$quest;

            unset($quiz['lesson']->courseSegment);
            $quizzes[]=$quiz;
        }

        return response()->json(['message' => __('messages.quiz.list'), 'body' => $quizzes->paginate(Paginate::GetPaginate($request))], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:3',
            'course_id' => 'required|integer|exists:courses,id',
            'type' => 'required|in:0,1,2',
            'lesson_id' => 'required|exists:lessons,id',
            /**
             * type 0 => Old Question
             * type 1 => New Questions
             * type 2 => New & Old Questions
             */
            'is_graded' => 'required|boolean',
            'duration' => 'required|integer',
            'shuffle' => 'string|in:No Shuffle,Questions,Answers,Questions and Answers',
            'feedback' => 'integer| in:1,2,3',
            /**
             * feedback 1 => After submission
             * feedback 2 =>After due date,
             * feedback 3 => No feedback
            */
        ]);
        if($request->is_graded==1 && $request->feedback == 1)//should be 2 or 3
            return HelperController::api_response_format(200, null, __('messages.quiz.invaled_feedback'));

        $course=  Course::where('id',$request->course_id)->first();
        LastAction::lastActionInCourse($request->course_id);

        $newQuestionsIDs=array();
        $oldQuestionsIDs=array();
        if ($request->type == 1 || $request->type == 2) { // New
            $request->validate([
                'q_cat_id' => 'required|integer|exists:questions_categories,id',
                //for request of creation multi type questions
                'Question' => 'required|array',
                'Question.*.type' => 'required|in:MCQ,Essay,T_F,Match,Comprehension', 
                'Question.*.text' => 'required|string', //need in every type_question
                'Question.*.is_true' => 'required_if:Question.*.question_type,==,T_F|boolean', //for true-false
                'Question.*.and_why' => 'boolean', //if question t-f and have and_why question
                //MCQ validation
                'Question.*.MCQ_Choices' => 'array',
                'Question.*.MCQ_Choices.*.is_true' => 'boolean',
                'Question.*.MCQ_Choices.*.content' => 'string',
                //Comprehension 
                'Question.*.subQuestion' => 'array|required_if:Question.*.type,==,Comprehension',
                'Question.*.subQuestion.*.type' => 'required_if:Question.*.type,==,Comprehension|in:MCQ,Essay,T_F,Match',
                'Question.*.subQuestion.*.text' => 'required_if:Question.*.type,==,Comprehension|string',
                'Question.*.subQuestion.*.is_true' => 'required_if:Question.*.subQuestion.*.type,==,T_F|boolean', //for true-false
                'Question.*.subQuestion.*.and_why' => 'boolean', //if question t-f and have and_why question
                'Question.*.subQuestion.*.MCQ_Choices' => 'required_if:Question.*.subQuestion.*.type,==,MCQ|array',
                'Question.*.subQuestion.*.MCQ_Choices.*.is_true' => 'required_if:Question.*.subQuestion.*.type,==,MCQ|boolean',
                'Question.*.subQuestion.*.MCQ_Choices.*.content' => 'required_if:Question.*.subQuestion.*.type,==,MCQ|string',
                //Match
                // 'Question.*.matches' => 'required_if:Question.*.type,==,Match|array',
                'Question.*.match_a' => 'required_if:Question.*.type,==,Match|array',
                'Question.*.match_b' => 'required_if:Question.*.type,==,Match|array'
            ]);
            $newQuestionsIDs=app('App\Http\Controllers\QuestionsController')->store($request,1);
        }
        if ($request->type == 0 ||$request->type == 2) { // old
            $request->validate([
                'oldQuestion' => 'required|array',
                'oldQuestion.*' => 'required|integer|exists:questions,id',
            ]);
            $oldQuestionsIDs=($request->oldQuestion);
        }
        // return $newQuestionsIDs;
        $questionsIDs = array_merge($newQuestionsIDs,$oldQuestionsIDs);

        if ($questionsIDs != null) {
            $quiz = quiz::create([
                'name' => $request->name,
                'course_id' => $request->course_id,
                'is_graded' => $request->is_graded,
                'duration' => $request->duration,
                'created_by' => Auth::user()->id,
                'shuffle' => isset($request->shuffle)?$request->shuffle:'No Shuffle',
                'feedback' => isset($request->feedback) ? $request->feedback : 1,
            ]);
            $index = QuizLesson::where('lesson_id',$request->lesson_id)->get()->max('index');
            $Next_index = $index + 1;
            $quizLesson = QuizLesson::create([
                'quiz_id' => $quiz->id,
                'lesson_id' => $request->lesson_id,
                'start_date' => $request->opening_time,
                'due_date' => $request->closing_time,
                'max_attemp' => $request->max_attemp,
                'grading_method_id' => $request->grading_method_id,
                'grade' => $request->grade,
                'grade_category_id' => $request->filled('grade_category_id') ? $request->grade_category_id[$key] : null,
                'publish_date' => $request->opening_time,
                'index' => $Next_index,
                'visible' => isset($request->visible)?$request->visible:1
            ]);
            $quiz->Question()->attach($questionsIDs);
            $quiz->Question;
            foreach ($quiz->Question as $question)
                $question->with($question['type'].'_question');
            
            return HelperController::api_response_format(200, $quiz,__('messages.quiz.add'));
        }
        return HelperController::api_response_format(200, null, __('messages.error.not_found'));
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
            $quiz_lesson=QuizLesson::where('quiz_id',$quiz->id)->first();
            $quiz->with('course');
            $quiz->quizlesson = $quiz_lesson;
            $quiz->lesson= Lesson::find($quiz_lesson->lesson_id);
            $quiz->class = Classes::find($quiz->lesson->courseSegment->segmentClasses[0]->classLevel[0]->class_id);
            $quiz->level = Level::find($quiz->lesson->courseSegment->segmentClasses[0]->classLevel[0]->yearLevels[0]->level_id);
            unset($quiz->lesson->courseSegment);
            
            LastAction::lastActionInCourse($quiz->course_id);
            
            return response()->json(['message' => __('messages.quiz.quiz_object'), 'body' => $quiz ], 200);
        }

        return response()->json(['message' => __('messages.error.not_found'), 'body' => [] ], 400);
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
