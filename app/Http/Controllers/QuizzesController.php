<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ChainRepositoryInterface;
use App\Enroll;
use Illuminate\Support\Facades\Auth;
use Modules\QuestionBank\Entities\QuizOverride;
use Modules\QuestionBank\Entities\quiz;
use Modules\QuestionBank\Entities\quiz_questions;
use App\Lesson;
use App\GradeCategory;
use App\Classes;
use App\Course;
use App\Level;
use App\Paginate;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\UserQuiz;
use Modules\QuestionBank\Entities\UserQuizAnswer;
use Modules\QuestionBank\Entities\Questions;
use App\LastAction;
use Carbon\Carbon;

class QuizzesController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        $this->middleware(['permission:quiz/get' , 'ParentCheck'],   ['only' => ['index','show']]);
        $this->middleware(['permission:quiz/add'],   ['only' => ['store']]);
        $this->middleware(['permission:quiz/view-drafts'],   ['only' => ['update']]);
        $this->middleware(['permission:quiz/delete'],   ['only' => ['destroy']]);
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

        if($request->user()->can('site/course/student'))
            $quiz_lessons->where('visible',1)->where('publish_date' ,'<=', Carbon::now());

        if(!$request->user()->can('quiz/view-drafts')){
            $quiz_lessons->whereHas('quiz', function ($q){
                $q->where('draft', 0);
            });
        }

        if($count == 'count')
            return response()->json(['message' => __('messages.quiz.count'), 'body' => $quiz_lessons->count() ], 200);
        
        $quiz_lessons = $quiz_lessons->get();

        $quizzes = collect([]);

        foreach($quiz_lessons as $quiz_lesson){
            $quiz=quiz::with('course','Question.children')->where('id',$quiz_lesson->quiz_id)->first();
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
            'lesson_id' => 'required|array|exists:lessons,id',
            // 'type' => 'required|in:0,1,2',
            /**
             * type 0 => Old Question
             * type 1 => New Questions
             * type 2 => New & Old Questions
             */
            'is_graded' => 'required|boolean',
            'grade_category_id' => 'required_if:is_graded,==,1',
            'duration' => 'required|integer|min:60', //by second
            'shuffle' => 'required|string|in:No Shuffle,Questions,Answers,Questions and Answers',
            'feedback' => 'required|integer| in:1,2,3',
            /**
             * feedback 1 => After submission
             * feedback 2 => After due date,
             * feedback 3 => No feedback
            */
            'opening_time' => 'required|date',
            'closing_time' => 'required|date|after:opening_time',
            'max_attemp' => 'required|integer|min:1',
            'grading_method_id' => 'required',
            'grade' => 'numeric',
            'grade_category_id.*' => 'required_if:is_graded,==,1|exists:grade_categories,id',
            'grade_min' => 'integer',
            'grade_max' => 'integer',
            'grade_pass' => 'numeric',
            'visible'=>"in:1,0",
            'publish_date' => 'date|before_or_equal:opening_time'
        ]);
        if($request->is_graded==1 && $request->feedback == 1)//should be 2 or 3
            return HelperController::api_response_format(200, null, __('messages.quiz.invaled_feedback'));

        $course=  Course::where('id',$request->course_id)->first();
        LastAction::lastActionInCourse($request->course_id);

        $newQuestionsIDs=[];
        $oldQuestionsIDs=array();

        /** if i return these comments to work again i must add type params in store of questions resource */

        // if ($request->type == 1 || $request->type == 2) { // New
        //     $request->validate([
        //     //for request of creation multi type questions
        //     'Question' => 'required|array',
        //     'Question.*.course_id' => 'required|integer|exists:courses,id', // because every question has course_id
        //     'Question.*.question_category_id' => 'required|integer|exists:questions_categories,id',
        //     'Question.*.question_type_id' => 'required|exists:questions_types,id', 
        //     'Question.*.text' => 'required|string', //need in every type_question
        //     ]);
        //     $newQuestionsIDs=app('App\Http\Controllers\QuestionsController')->store($request,1);
        // }
        // if ($request->type == 0 ||$request->type == 2) { // old
        //     $request->validate([
        //         'oldQuestion' => 'required|array',
        //         'oldQuestion.*' => 'required|integer|exists:questions,id',
        //     ]);
        //     $oldQuestionsIDs=($request->oldQuestion);
        // }
        // if(isset($newQuestionsIDs))
        //     $newQuestionsIDs=$newQuestionsIDs->toArray();
        // $questionsIDs = array_merge($newQuestionsIDs,$oldQuestionsIDs);

        // if ($questionsIDs != null) {
            $quiz = quiz::create([
                'name' => $request->name,
                'course_id' => $request->course_id,
                'is_graded' => $request->is_graded,
                'duration' => $request->duration,
                'created_by' => Auth::user()->id,
                'shuffle' => isset($request->shuffle)?$request->shuffle:'No Shuffle',
                'feedback' => isset($request->feedback) ? $request->feedback : 1,
            ]);
            foreach($request->lesson_id as $lesson)
            {
                $leson=Lesson::find($lesson);
                $grade_Cat=GradeCategory::where('course_segment_id',$leson->course_segment_id)->whereNull('parent')->first();
                if(!isset($grade_Cat))
                    return HelperController::api_response_format(200, null, __('messages.grade_category.not_found'));

                $index = QuizLesson::where('lesson_id',$lesson)->get()->max('index');
                $Next_index = $index + 1;
                //add validations for all the feilds
                $quizLesson = QuizLesson::create([
                    'quiz_id' => $quiz->id,
                    'lesson_id' => $lesson,
                    'start_date' => $request->opening_time,
                    'due_date' => $request->closing_time,
                    'max_attemp' => $request->max_attemp,
                    'grading_method_id' => $request->grading_method_id,
                    'grade' => isset($request->grade) ? $request->grade : 0,
                    'grade_category_id' => $request->filled('grade_category_id') ? $request->grade_category_id : $grade_Cat->id,
                    'publish_date' => $request->opening_time,
                    'index' => $Next_index,
                    'visible' => isset($request->visible)?$request->visible:1,
                    'grade_pass' => isset($request->grade_pass)?$request->grade_pass : null,
                ]);
            }

            // $quiz->Question()->attach($questionsIDs);
            // $quiz=Quiz::whereId($quiz->id)->with('Question.children')->get();
            
            return HelperController::api_response_format(200, $quiz,__('messages.quiz.add'));
        // }
        return HelperController::api_response_format(200, null, __('messages.error.not_found'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id,Request $request)
    {
        $request->validate([
            'lesson_id' => 'required|exists:lessons,id',
        ]);

        $quiz = quiz::where('id',$id)->with('Question.children')->first();
        $quiz->quizLesson=QuizLesson::where('quiz_id',$id)->where('lesson_id',$request->lesson_id)->first();
        $user_quiz=UserQuiz::where('user_id',Auth::id())->where('quiz_lesson_id',$quiz->quizLesson->id);

        $query=clone $user_quiz;
        $last_attempt=$query->latest()->first();
        $allow_edit=false;
        $remain_time = $quiz->duration;
        $quiz->token_attempts = 0;

        if(isset($last_attempt)){
            if(Carbon::parse($last_attempt->open_time)->addSeconds($quiz->quizLesson->quiz->duration)->format('Y-m-d H:i:s') < Carbon::now()->format('Y-m-d H:i:s'))
                UserQuizAnswer::where('user_quiz_id',$last_attempt->id)->update(['force_submit'=>'1']);

            $check_time = ($remain_time) - (strtotime(Carbon::now())- strtotime(Carbon::parse($last_attempt->open_time)));
            // dd($check_time);
            if($check_time < 0)
                $check_time= 0;

            $quiz->remain_time = $check_time;
            //case-->user_answer in new attempt
            $answered=UserQuizAnswer::where('user_quiz_id',$last_attempt->id)->whereNull('force_submit')->get()->count();
            if($answered < 1)
                $quiz->remain_time = $quiz->duration;
        }
        if(count($user_quiz->get())>0){
            $quiz->attempt_index=$user_quiz->pluck('id');
            $count_answered=UserQuizAnswer::whereIn('user_quiz_id',$user_quiz->pluck('id'))->where('force_submit','1')->pluck('user_quiz_id')->unique()->count();
            $quiz->token_attempts = $count_answered;
            $quiz->Question;
        }
        else
            $allow_edit=true;
        
        $quiz->allow_edit=$allow_edit;

        if(isset($quiz)){
            foreach($quiz->Question as $question){
                $children_mark = 0;
                QuestionsController::mark_details_of_question_in_quiz($question ,$quiz);
                if(isset($question->children)){
                    foreach($question->children as $child){
                        $childd = QuestionsController::mark_details_of_question_in_quiz($child ,$quiz);
                        $children_mark += $childd->mark;
                    }
                    $question->mark += $children_mark;
                }
            }
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
            'name' => 'string|min:3',
            'course_id' => 'integer|exists:courses,id',
            'question_id' => 'exists:questions,id',
            'lesson_id' => 'exists:lessons,id',
            'is_graded' => 'boolean',
            'duration' => 'integer',
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

        LastAction::lastActionInCourse($request->course_id);

        $quiz=Quiz::find($id);
        // $quiz_lesson=QuizLesson::where('id',$id)->where('lesson_id',$request->lesson_id);
        if(isset($request->course_id))
            if($quiz->course_id != $request->course_id)
                quiz_questions::where('quiz_id',$request->quiz_id)->delete(); //delete assigned questions
        
        $quiz->update([
            'name' => isset($request->name) ? $request->name : $quiz->name,
            'course_id' => isset($request->course_id) ? $request->course_id : $quiz->course_id,
            'is_graded' => isset($request->is_graded) ? $request->is_graded : $quiz->is_graded,
            'duration' => isset($request->duration) ? $request->duration : $quiz->duration,
            'created_by' => Auth::user()->id,
            'shuffle' => isset($request->shuffle)?$request->shuffle:'No Shuffle',
            'feedback' => isset($request->feedback) ? $request->feedback : 1,
        ]);
        // $quizLesson->update([
        //     'quiz_id' => $quiz->id,
        //     'lesson_id' => $lesson,
        //     'start_date' => $request->opening_time,
        //     'due_date' => $request->closing_time,
        //     'max_attemp' => $request->max_attemp,
        //     'grading_method_id' => $request->grading_method_id,
        //     'grade' => $request->grade,
        //     'grade_category_id' => $request->filled('grade_category_id') ? $request->grade_category_id[$key] : null,
        //     'publish_date' => $request->opening_time,
        //     'index' => $Next_index,
        //     'visible' => isset($request->visible)?$request->visible:1
        // ]);
        $quiz->save();
        $quiz->Question;
        $quiz->quizLesson;

        // if(isset($request->lesson_id))
        //     $quiz->quizLesson()->update(['lesson_id' => $request->lesson_id]);

        // if(isset($request->question_id)){
        //     $quest=Questions::find($request->question_id);
        //     $udatedQuestion=app('App\Http\Controllers\QuestionsController')->update($request,$request->question_id);
        // }
            
        return HelperController::api_response_format(200, $quiz,__('messages.quiz.update'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id,Request $request)
    {
        $request->validate([
            'lesson_id' => 'required|exists:lessons,id',
        ]);
        QuizLesson::where('quiz_id',$id)->where('lesson_id',$request->lesson_id)->delete();
        $quizlesson=QuizLesson::where('quiz_id',$id)->get();
        if(!isset($quizlesson))
            $quiz=Quiz::where('id',$id)->delete();
        
        return HelperController::api_response_format(200, null,__('messages.quiz.delete'));
    }
}
