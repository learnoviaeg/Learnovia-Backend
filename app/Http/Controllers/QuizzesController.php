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

        if($request->user()->can('site/show-all-courses')){//admin
            $course_segments = collect($this->chain->getAllByChainRelation($request));
            $lessons = Lesson::whereIn('course_segment_id',$course_segments->pluck('id'))->pluck('id');
        }

        if(!$request->user()->can('site/show-all-courses')){// any one who is enrolled

            $user_course_segments = $this->chain->getCourseSegmentByChain($request);
            $user_course_segments = $user_course_segments->where('user_id',Auth::id());
            $user_course_segments = $user_course_segments->select('course_segment')->distinct()->with('courseSegment.lessons')->get();
            $lessons = $user_course_segments->pluck('courseSegment.lessons')->collapse()->pluck('id');    
        }

        if($request->filled('lesson')){
            if (!in_array($request->lesson,$lessons->toArray()))
                return response()->json(['message' => __('messages.error.no_active_for_lesson'), 'body' => []], 400);
            
            $lessons  = [$request->lesson];
        }

        $sort_in = 'desc';
        if($request->has('sort_in'))
            $sort_in = $request->sort_in;

        $quiz_lessons = QuizLesson::whereIn('lesson_id',$lessons)->orderBy('start_date',$sort_in);

        if($request->user()->can('site/course/student')){
            $quiz_lessons->where('visible',1)->where('publish_date' ,'<=', Carbon::now());
        }

        if($count == 'count'){
            return response()->json(['message' => __('messages.quiz.count'), 'body' => $quiz_lessons->count() ], 200);
        }
        
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
                //for interface model
                'course_id' => 'required|integer|exists:courses,id',
                'question_category_id' => 'required|integer|exists:questions_categories,id',
    
                //for request of creation multi type questions
                'Question' => 'required|array',
                'Question.*.is_comp' => 'required|in:0,1', 
                // 0 if this question not comprehension  
                // 1 if this question belong to comprehension_question
                'Question.*.question_type_id' => 'required_if:Question.*.question_type_id,==,2|exists:questions_types,id', 
                'Question.*.text' => 'required|string', //need in every type_question
                'Question.*.is_true' => 'required_if:Question.*.question_type_id,==,1|boolean', //for true-false
                'Question.*.and_why' => 'boolean', //if question t-f and have and_why question
    
                //MCQ validation
                'Question.*.MCQ_Choices' => 'required_if:Question.*.question_type_id,==,2|array',
                'Question.*.MCQ_Choices.*.is_true' => 'required_if:Question.*.question_type_id,==,2|boolean',
                'Question.*.MCQ_Choices.*.content' => 'required_if:Question.*.question_type_id,==,2|string',
    
                //Comprehension 
                'Question.*.parent_id' => 'required_if:Question.*.is_comp,==,1|exists:questions,id',
    
                //Match
                'Question.*.match_a' => 'required_if:Question.*.question_type_id,==,3|array',
                'Question.*.match_b' => 'required_if:Question.*.question_type_id,==,3|array'
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

        $quiz=Quiz::find($id);
        $quiz->update([
            'name' => isset($request->name) ? $request->name : $quiz->name,
            'course_id' => isset($request->course_id) ? $request->course_id : $quiz->course_id,
            'is_graded' => isset($request->is_graded) ? $request->is_graded : $quiz->is_graded,
            'is_graded' => isset($request->duration) ? $request->duration : $quiz->duration,
            'created_by' => Auth::user()->id,
            'shuffle' => isset($request->shuffle)?$request->shuffle:'No Shuffle',
            'feedback' => isset($request->feedback) ? $request->feedback : 1,
        ]);
         $quiz->save();
            
        return HelperController::api_response_format(200, $quiz,__('messages.quiz.add'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $quiz=Quiz::where('id',$id)->delete();
        return HelperController::api_response_format(200, $quiz,__('messages.quiz.delete'));
    }
}
