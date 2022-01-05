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
use App\SecondaryChain;
use App\Classes;
use App\Course;
use App\Level;
use App\UserGrader;
use App\Paginate;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\UserQuiz;
use Modules\QuestionBank\Entities\UserQuizAnswer;
use Modules\QuestionBank\Entities\Questions;
use App\LastAction;
use Carbon\Carbon;
use App\Events\updateQuizAndQuizLessonEvent;
use App\Notification;
use App\Notifications\QuizNotification;
use App\Timeline;
use App\SystemSetting;
use App\Events\GraderSetupEvent;
use App\Jobs\RefreshUserGrades;
use App\Events\UpdatedQuizQuestionsEvent;

class QuizzesController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        $this->middleware(['permission:quiz/get'],   ['only' => ['index','show']]);
        $this->middleware('ParentCheck',   ['only' => ['show']]);
        $this->middleware(['permission:quiz/add'],   ['only' => ['store']]);
        $this->middleware(['permission:quiz/update'],   ['only' => ['update']]);
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

           $enrolls = $this->chain->getEnrollsByChain($request)->where('user_id',Auth::id())->get()->pluck('id');
           $lessons = SecondaryChain::whereIn('enroll_id', $enrolls)->where('user_id',Auth::id())->get()->pluck('lesson_id')->unique();

        if($request->filled('lesson')){
            if (!in_array($request->lesson,$lessons->toArray()))
                return response()->json(['message' => __('messages.error.no_active_for_lesson'), 'body' => []], 400);
            
            $lessons  = [$request->lesson];
        }

        $sort_in = 'desc';
        if($request->has('sort_in'))
            $sort_in = $request->sort_in;

        $quiz_lessons = QuizLesson::whereIn('lesson_id',$lessons)->orderBy('created_at','desc');;

        if($request->user()->can('site/course/student'))
            $quiz_lessons->where('visible',1)->where('publish_date' ,'<=', Carbon::now());

        if(!$request->user()->can('quiz/view-drafts')){
            $quiz_lessons->whereHas('quiz', function ($q){
                $q->where('draft', 0);
            });
        }

        if($count == 'count')
            return response()->json(['message' => __('messages.quiz.count'), 'body' => $quiz_lessons->count() ], 200);

        $page = Paginate::GetPage($request);
        $paginate = Paginate::GetPaginate($request);

        $result['last_page'] = Paginate::allPages($quiz_lessons->count(),$paginate);
        $result['total']= $quiz_lessons->count();

        $quiz_lessons = $quiz_lessons->skip(($page)*$paginate)->take($paginate);

        $quizzes = collect([]);

        foreach($quiz_lessons->cursor() as $quiz_lesson){
            $flag=false;
            $quiz=quiz::with('course','Question.children','quizLesson')->where('id',$quiz_lesson->quiz_id)->first();
            $userQuiz=UserQuiz::where('user_id',Auth::id())->where('quiz_lesson_id',$quiz_lesson->id)->first();
            if(isset($userQuiz->submit_time) && $userQuiz->submit_time !=null)
                $flag=true;

            $quiz['closed_attempt']=$flag;
            $quiz['lesson'] = Lesson::find($quiz_lesson->lesson_id);
            $quiz['class'] = Classes::whereIn('id',$quiz['lesson']->shared_classes->pluck('id'))->get();
            $quiz['level'] = Level::find(Course::find($quiz['lesson']->course_id)->level_id);
            $quizzes[]=$quiz;
        }
        $result['data'] =  $quizzes;
        $result['current_page']= $page + 1;
        $result['per_page']= count($result['data']);

        return response()->json(['message' => __('messages.quiz.list'), 'body' => $result], 200);
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
            'is_graded' => 'required|boolean',
            'grade_category_id' => 'required_if:is_graded,==,1',
            'duration' => 'required|integer|min:60', //by second
            'shuffle' => 'required|string|in:No Shuffle,Questions,Answers,Questions and Answers',
            'grade_feedback' => 'required|in:After submission,After due_date,Never',
            'correct_feedback' => 'required|in:After submission,After due_date,Never',
            'opening_time' => 'required|date',
            'closing_time' => 'required|date|after:opening_time',
            'max_attemp' => 'required|integer|min:1',
            'grading_method_id' => 'in:First,Last,Highest,Lowest,Average',
            'grade_category_id.*' => 'required_if:is_graded,==,1|exists:grade_categories,id',
            'grade_min' => 'integer',
            'grade_max' => 'integer',
            'visible'=>"in:1,0",
            'publish_date' => 'date|before_or_equal:opening_time'
        ]);
  
        $course=  Course::where('id',$request->course_id)->first();
        LastAction::lastActionInCourse($request->course_id);
 
        $newQuestionsIDs=[];
        $oldQuestionsIDs=array();

            $quiz = quiz::create([
                'name' => $request->name,
                'course_id' => $request->course_id,
                'is_graded' => $request->is_graded,
                'duration' => $request->duration,
                'created_by' => Auth::user()->id,
                'shuffle' => isset($request->shuffle)?$request->shuffle:'No Shuffle',
                'grade_feedback' => $request->grade_feedback,
                'correct_feedback' => $request->correct_feedback,
            ]);
            $lessons = Lesson::whereIn('id', $request->lesson_id) 
                        ->with([
                            'course.gradeCategory'=> function($query)use ($request){
                                $query->whereNull('parent');
                            },'QuizLesson'=>function($q){
                                $q->orderBy('index','desc')->limit(1);
                            }])->get();

            foreach($lessons as $key => $lesson)
            {   
                $grade_Cat = $lesson->course->gradeCategory[0];
                $index = isset($lesson->QuizLesson[0]) ? $lesson->QuizLesson[0]->index :1;      
                //add validations for all the feilds
                $newQuizLesson = QuizLesson::create([
                    'quiz_id' => $quiz->id,
                    'lesson_id' => $lesson->id,
                    'start_date' => $request->opening_time,
                    'due_date' => $request->closing_time,
                    'max_attemp' => $request->max_attemp,
                    'grading_method_id' => isset($request->grading_method_id)? json_encode((array)$request->grading_method_id) : json_encode(["Last"]),
                    'grade' => isset($request->grade) ? $request->grade : 0,
                    'grade_category_id' => $request->filled('grade_category_id') ? $request->grade_category_id : $grade_Cat->id,
                    'publish_date' => isset($request->publish_date) ? $request->publish_date : $request->opening_time,
                    'index' => ++$index,
                    'visible' => isset($request->visible)?$request->visible:1,
                    'grade_pass' => isset($request->grade_pass)?$request->grade_pass : null,
                    'grade_by_user' => isset($request->grade) ? carbon::now() : null,
                    'assign_user_gradepass' => isset($request->grade_pass) ? carbon::now() : null,
                ]);

                // //sending notifications
                // $notification = new QuizNotification($newQuizLesson,$quiz->name.' quiz is added.');
                // $notification->send();
            }
            
        return HelperController::api_response_format(200,Quiz::find($quiz->id),__('messages.quiz.add'));
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
            'lesson_id' => 'required|exists:lessons,id',
            'is_graded' => 'boolean',
            'duration' => 'integer|min:60',
            'shuffle' => 'string|in:No Shuffle,Questions,Answers,Questions and Answers',
            'grade_feedback' => 'in:After submission,After due_date,Never',
            'correct_feedback' => 'in:After submission,After due_date,Never',
            'updated_lesson_id' => 'exists:lessons,id',
            'opening_time' => 'date',
            'closing_time' => 'date|after:opening_time',
            'publish_date' => 'date|before_or_equal:opening_time'
        ]);

        $quiz=Quiz::find($id);
        $quiz_lesson=QuizLesson::where('quiz_id',$id)->where('lesson_id',$request->lesson_id)->first();
        LastAction::lastActionInCourse($quiz_lesson->lesson->course_id);

        if(!strtotime($quiz_lesson->start_date) < Carbon::now())
        {
            $quiz_lesson->update([
                'start_date' => isset($request->opening_time) ? $request->opening_time : $quiz_lesson->start_date,
                'publish_date' => isset($request->opening_time) ? $request->opening_time : $quiz_lesson->publish_date,
            ]);
        }
        
        $quiz->update([
            'name' => isset($request->name) ? $request->name : $quiz->name,
            'is_graded' => isset($request->is_graded) ? $request->is_graded : $quiz->is_graded,
            'shuffle' => isset($request->shuffle)?$request->shuffle:$quiz->shuffle,
        ]);

        if(isset($request->closing_time)){
            if(carbon::parse($request->closing_time) < Carbon::parse($request->opening_time)->addSeconds($request->duration))
                return HelperController::api_response_format(200,null,__('messages.quiz.wrong_date'));
        }

        $quiz_lesson->update([
            'due_date' => isset($request->closing_time) ? $request->closing_time : $quiz_lesson->due_date,
            'lesson_id' => isset($request->updated_lesson_id) ? $request->updated_lesson_id : $quiz_lesson->lesson_id,
            'grade' => isset($request->grade) ? $request->grade : $quiz_lesson->grade,
            'visible' => isset($request->visible)?$request->visible:$quiz_lesson->visible,
            'grade_pass' => isset($request->grade_pass) ? $request->grade_pass : $quiz_lesson->grade_pass,
            'grade_category_id' => $quiz_lesson->grade_category_id,
            'grade_by_user' => isset($request->grade) ? carbon::now() : $quiz_lesson->grade_by_user,
            'grading_method_id' => isset($request->grading_method_id) ?  json_encode((array)$request->grading_method_id) : json_encode($quiz_lesson->grading_method_id) ,
        ]);

        if($quiz->allow_edit)
        {
            $quiz->update([
                'duration' => isset($request->duration) ? $request->duration : $quiz->duration,
                'grade_feedback' => isset($request->grade_feedback) ? $request->grade_feedback : $quiz->grade_feedback,
                'correct_feedback' => isset($request->correct_feedback) ? $request->correct_feedback : $quiz->correct_feedback,
            ]);

            if(isset($request->updated_lesson_id))
            {
                $grade_cat=GradeCategory::where('instance_type','Quiz')->where('instance_id',$quiz_lesson->quiz_id)->where('lesson_id',$quiz_lesson->lesson_id)
                ->update(['lesson_id' => $request->updated_lesson_id]);
            }
    
            $quiz_lesson->update([
                'max_attemp' => isset($request->max_attemp) ? $request->max_attemp : $quiz_lesson->max_attemp,
                'start_date' => $quiz_lesson->start_date,
                'publish_date' => $quiz_lesson->publish_date,
            ]);
        }

        $quiz->save();
        $quiz_lesson->save();
        $quiz->quizLesson;

        $gg=GradeCategory::where('course_id', $quiz_lesson->lesson->course_id)
                            ->whereNull('parent')->where('type','category')->first();
        
        $gradeCat=GradeCategory::where('instance_type','Quiz')->where('instance_id',$quiz_lesson->quiz_id)->where('lesson_id', $request->lesson_id)->first();
        $gradeCat->update([
                    'hidden' => $quiz_lesson->visible,
                    'calculation_type' => json_encode($quiz_lesson->grading_method_id),
                    'parent' => isset($request->grade_category_id) ? $request->grade_category_id : $gg->id,
                    'lesson_id' => isset($request->updated_lesson_id) ? $request->updated_lesson_id : $gg->lesson_id
                ]);        
        
        // update timeline object and sending notifications
        event(new updateQuizAndQuizLessonEvent($quiz_lesson));
        event(new UpdatedQuizQuestionsEvent($quiz->id));      
        $userGradesJob = (new \App\Jobs\RefreshUserGrades($this->chain , GradeCategory::find($gradeCat->parent)));
        dispatch($userGradesJob);
                            

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
        Timeline::where('type', 'quiz')->where('item_id', $id)->where('lesson_id', $request->lesson_id)->delete();
        
        $grade_category = GradeCategory::where('instance_id',$id )->where('instance_type', 'Quiz')->where('lesson_id', $request->lesson_id)->first();
        $parent_Category = GradeCategory::find($grade_category->parent);
        $grade_category->delete();
        event(new GraderSetupEvent($parent_Category));
        $userGradesJob = (new \App\Jobs\RefreshUserGrades($this->chain , $parent_Category));
        dispatch($userGradesJob);
        QuizLesson::where('quiz_id',$id)->where('lesson_id',$request->lesson_id)->delete();
        $quizlesson=QuizLesson::where('quiz_id',$id)->get();
        if(!isset($quizlesson))
            $quiz=Quiz::where('id',$id)->delete();
        
        return HelperController::api_response_format(200, null,__('messages.quiz.delete'));
    }

    public function Grade_pass_settings(Request $request)
    {
        $request->validate([
            'percentage' => 'required|integer|min:0|max:100',
        ]);

        $grade_to_pass_setting = SystemSetting::updateOrCreate(
                                    ['key'=> 'Quiz grade to pass'],
                                    ['data' =>  $request->percentage]
                                );
        return HelperController::api_response_format(200, $grade_to_pass_setting,__('messages.quiz.grade_pass_settings'));
    }

    public function Get_grade_pass_settings(Request $request)
    {
        $grade_to_pass_setting = SystemSetting::where('key' , 'Quiz grade to pass')->first();
        return HelperController::api_response_format(200, $grade_to_pass_setting,__('messages.quiz.grade_pass_settings_list'));
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
            'user_id' => 'exists:users,id',
        ]);

        if(Auth::user()->can('site/course/student')){
            $users = SecondaryChain::where('lesson_id', $request->lesson_id)->where('course_id',Lesson::find($request->lesson_id)->course_id)->pluck('user_id')->unique();
            if(!in_array(Auth::id(),$users->toArray()))
                return HelperController::api_response_format(404, __('messages.error.data_invalid'));
        }

        $quiz = quiz::where('id',$id)->with('Question.children')->first();
        $quizLesson=QuizLesson::where('quiz_id',$id)->where('lesson_id',$request->lesson_id)->first();
        
        $grade_Cat=GradeCategory::where('instance_type','Quiz')->where('instance_id',$quiz->id)->where('lesson_id', $request->lesson_id)->first();
        if($grade_Cat->parent != null)
            $quizLesson->Parent_grade_category = $grade_Cat->Parents->id;

        if(!isset($quizLesson))
            return HelperController::api_response_format(404, null ,__('messages.error.item_deleted'));

        $user_quiz=UserQuiz::where('user_id',Auth::id())->where('quiz_lesson_id',$quizLesson->id);
        if($request->user_id)
            $user_quiz=UserQuiz::where('user_id',$request->user_id)->where('quiz_lesson_id',$quizLesson->id);

        $quiz_override = QuizOverride::where('user_id',Auth::id())->where('quiz_lesson_id',$quizLesson->id)->where('attemps','>','0')->first();
       
        if($request->user_id)
            $quiz_override = QuizOverride::where('user_id',$request->user_id)->where('quiz_lesson_id',$quizLesson->id)->where('attemps','>','0')->first();

        if(isset($quiz_override)){
            $quizLesson->due_date = $quiz_override->due_date;
            $quizLesson->max_attemp+=$quiz_override->attemps;
        }

        $query=clone $user_quiz;
        $last_attempt=$query->latest()->first();
        $quiz->remain_time = $quiz->duration;
        if(carbon::now()->diffInSeconds(carbon::parse($quizLesson->due_date)) < $quiz->duration)
            $quiz->remain_time= carbon::now()->diffInSeconds(carbon::parse($quizLesson->due_date));

        $quiz->token_attempts = 0;
        $quiz->last_attempt_status = 'newOne';
        $quiz->user_grade=null;

        if(isset($last_attempt)){
            $count=UserQuizAnswer::where('user_quiz_id',$last_attempt->id)->whereNull('force_submit')->count();
            if($count > 0 && Carbon::parse($last_attempt->open_time)->addSeconds($quizLesson->quiz->duration)->format('Y-m-d H:i:s') < Carbon::now()->format('Y-m-d H:i:s'))
            {
                UserQuizAnswer::where('user_quiz_id',$last_attempt->id)->update(['force_submit'=>1,'answered' => 1]);
                UserQuiz::find($last_attempt->id)->update(['submit_time'=>Carbon::parse($last_attempt->open_time)->addSeconds($quizLesson->quiz->duration)->format('Y-m-d H:i:s')]);
            }

            $usergrader = UserGrader::where('user_id',$last_attempt->user_id)->where('item_id', $quizLesson->grade_category_id)->where('item_type','category')->first();
            if(isset($usergrader))
                $quiz->user_grade=$usergrader->grade;
                
            $left_time=AttemptsController::leftTime($last_attempt);
            $quiz->remain_time = $left_time;
            $quiz->last_attempt_status = 'continue';

            //case-->user_answer in new attempt
            $answered=UserQuizAnswer::where('user_quiz_id',$last_attempt->id)->whereNull('force_submit')->get()->count();
            if($answered < 1)
            {
                $quiz->remain_time = $quiz->duration;
                $quiz->last_attempt_status = 'newOne';
            }
        }
        if(count($user_quiz->get())>0){
            $quiz->attempt_index=$user_quiz->pluck('id');
            $count_answered=UserQuizAnswer::whereIn('user_quiz_id',$user_quiz->pluck('id'))->where('force_submit','1')->pluck('user_quiz_id')->unique()->count();
            $quiz->token_attempts = $count_answered;
            $quiz->Question;
        }

        $quiz->quiz_lesson=[$quizLesson];
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
        if(!$request->user()->can('site/parent'))
            LastAction::lastActionInCourse($quiz->course_id);

        return response()->json(['message' => __('messages.quiz.quiz_object'), 'body' => $quiz ], 200);
    }

    public static function closeAttempts($quiz_lesson)
    {
        $user_quizzes=userQuiz::where('quiz_lesson_id',$quiz_lesson->id)->whereNull('submit_time')->get();
        foreach($user_quizzes as $userQuiz)
        {
            $quiz_time=Carbon::parse($userQuiz->open_time)->addSeconds($userQuiz->quiz_lesson->quiz->duration)->format('Y-m-d H:i:s');
            if( $quiz_time < Carbon::now()->format('Y-m-d H:i:s'))
            {
                if($quiz_time > Carbon::parse($userQuiz->quiz_lesson->due_date)->format('Y-m-d H:i:s'))
                    $quiz_time=$userQuiz->quiz_lesson->due_date;

                UserQuizAnswer::where('user_quiz_id',$userQuiz->id)->update(['force_submit'=> 1,'answered' => 1]);
                userQuiz::find($userQuiz->id)->update(['submit_time'=>$quiz_time]);
            }
        }
        // return 'Done';
    }
}
