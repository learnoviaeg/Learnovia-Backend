<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ChainRepositoryInterface;
use App\Repositories\NotificationRepoInterface;
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
use App\CourseItem;
use App\Level;
use App\UserGrader;
use App\Paginate;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\UserQuiz;
use Modules\QuestionBank\Entities\UserQuizAnswer;
use Modules\QuestionBank\Entities\Questions;
use App\LastAction;
use Carbon\Carbon;
use App\Notification;
use App\Notifications\QuizNotification;
use App\Timeline;
use App\SystemSetting;
use App\Events\GraderSetupEvent;
use App\Jobs\RefreshUserGrades;
use App\Events\UpdatedQuizQuestionsEvent;
use App\Helpers\CoursesHelper;
use App\UserCourseItem;
use Illuminate\Database\Eloquent\Builder;
use App\LessonComponent;

class QuizzesController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain,NotificationRepoInterface $notification)
    {
        $this->chain = $chain;
        $this->notification = $notification;
        $this->middleware('auth');
        $this->middleware(['permission:quiz/get','ParentCheck'],   ['only' => ['index','show']]);
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

        $enrolls = $this->chain->getEnrollsByChain($request)->where('user_id',Auth::id())->pluck('id');
        $lessons = SecondaryChain::select('lesson_id')->whereIn('enroll_id', $enrolls)->pluck('lesson_id');

        if($request->filled('lesson')){
            if (!in_array($request->lesson,$lessons->toArray()))
                return response()->json(['message' => __('messages.error.no_active_for_lesson'), 'body' => []], 400);

            $lessons  = [$request->lesson];
        }

        $sort_in = 'desc';
        if($request->has('sort_in'))
            $sort_in = $request->sort_in;

        $quiz_lessons = QuizLesson::whereIn('lesson_id',$lessons)->with(['quiz'])->orderBy('created_at','desc');

        if($request->user()->can('site/course/student')){
            $quiz_lessons
            ->where('visible',1)
            ->where('publish_date' ,'<=', Carbon::now())
            ->whereHas('quiz',function($q){
                $q->where(function($query) {                //Where accessible
                        $query->doesntHave('courseItem')
                                ->orWhereHas('courseItem.courseItemUsers', function (Builder $query){
                                    $query->where('user_id', Auth::id());
                                });
                    });
            });
        }

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

        $callQuery=function($q) use ($request){
            if(!$request->user()->can('course/show-hidden-courses'))
                $q->where('show',1);
        };

        foreach($quiz_lessons->cursor() as $quiz_lesson){
            $flag=false;
            $quiz=quiz::whereId($quiz_lesson->quiz_id)->whereHas('course',$callQuery)->with(['course' => $callQuery,'Question.children','quizLesson'])->first();
            if(!isset($quiz))
                continue;
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
            'lesson_id' => 'required|array',
            'lesson_id.*' => 'exists:lessons,id',
            'is_graded' => 'required|boolean',
            'grade_category_id' => 'required_if:is_graded,==,1',
            'duration' => 'required|integer|min:60', //by second
            'shuffle' => 'required|string|in:No Shuffle,Questions,Answers,Questions and Answers',
            'grade_feedback' => 'required|in:After submission,After due_date,Never',
            'correct_feedback' => 'required|in:After submission,After due_date,Never',
            'opening_time' => 'required|date',
            'closing_time' => 'required|date|after:opening_time',
            'max_attemp' => 'required|integer|min:1',
            'collect_marks' => 'integer|in:0,1',
            'grading_method_id' => 'in:First,Last,Highest,Lowest,Average',
            'grade_category_id.*' => 'exists:grade_categories,id',
            'grade_min' => 'integer',
            'grade_max' => 'integer',
            'visible'=>"in:1,0",
            'publish_date' => 'date|before_or_equal:opening_time',
            'users_ids' => 'array',
            'users_ids.*' => 'exists:users,id'
        ]);

        $publish_date=Carbon::now();
        if(isset($request->publish_date))
            $publish_date=$request->publish_date;

        if(Carbon::parse($request->opening_time) < Carbon::parse($publish_date))
            $publish_date=$request->opening_time;

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
                'publish_date' => $publish_date,
                'index' => ++$index,
                'visible' => isset($request->visible)?$request->visible:1,
                'grade_pass' => isset($request->grade_pass)?$request->grade_pass : null,
                // 'grade_by_user' => isset($request->grade) ? carbon::now() : null,
                'collect_marks' => isset($request->collect_marks) ? $request->collect_marks : 1,
                'assign_user_gradepass' => isset($request->grade_pass) ? carbon::now() : null,
            ]);
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
            'lesson_id' => 'required|exists:lessons,id', //but we update on all lessons
            'is_graded' => 'boolean',
            'collect_marks' => 'integer|in:0,1',
            'duration' => 'integer|min:60',
            'shuffle' => 'string|in:No Shuffle,Questions,Answers,Questions and Answers',
            'grade_feedback' => 'in:After submission,After due_date,Never',
            'correct_feedback' => 'in:After submission,After due_date,Never',
            // 'updated_lesson_id' => 'exists:lessons,id',
            'opening_time' => 'date',
            'closing_time' => 'date|after:opening_time',
            'publish_date' => 'date|before_or_equal:opening_time',
        ]);

        $quiz=Quiz::find($id);
        $quiz_lessons=QuizLesson::where('quiz_id',$id)->get();
        foreach($quiz_lessons as $quiz_lesson)
        {
            LastAction::lastActionInCourse($quiz_lesson->lesson->course_id);

            if(!strtotime($quiz_lesson->start_date) < Carbon::now() && !strtotime($quiz_lesson->publish_date) < Carbon::now())
            {
                $publish_date=$quiz_lesson->publish_date;
                if(isset($request->publish_date))
                    $publish_date=$request->publish_date;

                $opening_time=$quiz_lesson->start_date;
                if(isset($request->opening_time))
                    $opening_time = $request->opening_time;
                    
                if(Carbon::parse($opening_time) < Carbon::parse($publish_date))
                    return HelperController::api_response_format(400,null,__('messages.quiz.error_date'));

                $quiz_lesson->update([
                    'start_date' =>$opening_time,
                    'publish_date' => $publish_date,
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
                // 'lesson_id' => isset($request->updated_lesson_id) ? $request->updated_lesson_id : $quiz_lesson->lesson_id,
                'grade' => isset($request->grade) ? $request->grade : $quiz_lesson->grade,
                'visible' => isset($request->visible)?$request->visible:$quiz_lesson->visible,
                'grade_pass' => isset($request->grade_pass) ? $request->grade_pass : $quiz_lesson->grade_pass,
                'grade_category_id' => $quiz_lesson->grade_category_id,
                // 'grade_by_user' => isset($request->grade) ? carbon::now() : $quiz_lesson->grade_by_user,
                'collect_marks' => isset($request->collect_marks) ? $request->collect_marks : $quiz_lesson->collect_marks,
                'grading_method_id' => isset($request->grading_method_id) ?  json_encode((array)$request->grading_method_id) : json_encode($quiz_lesson->grading_method_id) ,
                'assign_user_gradepass' => isset($request->grade_pass) ? carbon::now() : null,
            ]);
    
            if($quiz->allow_edit)
            {
                $quiz->update([
                    'duration' => isset($request->duration) ? $request->duration : $quiz->duration,
                    'grade_feedback' => isset($request->grade_feedback) ? $request->grade_feedback : $quiz->grade_feedback,
                    'correct_feedback' => isset($request->correct_feedback) ? $request->correct_feedback : $quiz->correct_feedback,
                ]);
    
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
                        // 'lesson_id' => isset($request->updated_lesson_id) ? $request->updated_lesson_id : $gradeCat->lesson_id
                    ]);
    
            // update timeline object and sending notifications
            event(new UpdatedQuizQuestionsEvent($quiz->id));
            $userGradesJob = (new \App\Jobs\RefreshUserGrades($this->chain , GradeCategory::find($gradeCat->parent)));
            dispatch($userGradesJob);    

            //send notification
            if(!$quiz->draft && $quiz_lesson->visible)
            {
                $users=SecondaryChain::select('user_id')->where('role_id',3)->where('lesson_id',$request->lesson_id)->pluck('user_id');
                $courseItem = CourseItem::where('item_id', $quiz->id)->where('type', 'quiz')->first();
                if(isset($courseItem))
                    $users = UserCourseItem::where('course_item_id', $courseItem->id)->pluck('user_id');
            
                $reqNot=[
                    'message' => $quiz->name.' quiz is updated',
                    'item_id' => $quiz->id,
                    'item_type' => 'quiz',
                    'type' => 'notification',
                    'publish_date' => $quiz_lesson->publish_date,
                    'lesson_id' => $request->lesson_id,
                    'course_name' => $quiz->course->name,
                ];
                $this->notification->sendNotify($users,$reqNot);   
            }
        }
        return HelperController::api_response_format(200, $quiz,__('messages.quiz.update'));
    }

    public function Drag(Request $request)
    {
        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
            'lesson_id' => 'required|integer|exists:lessons,id',
            'updated_lesson_id' => 'required|exists:lessons,id'
        ]);

        $quiz_lesson = QuizLesson::where('lesson_id',$request->lesson_id)->where('quiz_id',$request->quiz_id)->first();
        $quiz=Quiz::whereId($request->quiz_id)->with('courseItem')->first();
        if(isset($quiz['courseItem']))
            return HelperController::api_response_format(400,[], $message = __('messages.error.not_allowed_to_edit'));

        $check = QuizLesson::where('lesson_id',$request->updated_lesson_id)->where('quiz_id',$request->quiz_id)->first();
            
        if(isset($check))
            return HelperController::api_response_format(400,[], $message = __('messages.error.assigned_before'));

        $grade_cat=GradeCategory::where('instance_type','Quiz')->where('instance_id',$quiz_lesson->quiz_id)->where('lesson_id',$quiz_lesson->lesson_id)
                ->update(['lesson_id' => $request->updated_lesson_id]);

        $quiz_lesson->update([
            'lesson_id' => isset($request->updated_lesson_id) ? $request->updated_lesson_id : $quiz_lesson->lesson_id,
        ]);
        
        $gradeCat=GradeCategory::where('instance_type','Quiz')->where('instance_id',$quiz_lesson->quiz_id)->where('lesson_id', $request->updated_lesson_id)->first();
        $userGradesJob = (new \App\Jobs\RefreshUserGrades($this->chain , GradeCategory::find($gradeCat->parent)));
        dispatch($userGradesJob);  

        return HelperController::api_response_format(200, null, $message = __('messages.quiz.update'));
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

        $grade_category = GradeCategory::where('instance_id',$id )->where('instance_type', 'Quiz')->first();
        if(isset($grade_category)){
            $parent_Category = GradeCategory::find($grade_category->parent);
            $grade_category->delete();
        }
        event(new GraderSetupEvent($parent_Category));
        $userGradesJob = (new \App\Jobs\RefreshUserGrades($this->chain , $parent_Category));
        dispatch($userGradesJob);
        $quizLesson = QuizLesson::where('quiz_id',$id)->where('lesson_id',$request->lesson_id);
        
        $LessonComponent =  LessonComponent::where('comp_id',$quizLesson->first()->quiz_id)
        ->where('lesson_id',$quizLesson->first()->lesson_id)->where('model' , 'quiz')->first();
        
        if($LessonComponent !=  null){
            $current_lesson_component = LessonComponent::select('index')->where('lesson_id',$quizLesson->first()->lesson_id)->where('comp_id',$quizLesson->first()->quiz_id)
            ->where('model' , 'quiz')->first();
            LessonComponent::where('lesson_id',$quizLesson->first()->lesson_id)
            ->where('index' ,'>=',$current_lesson_component->index )->decrement('index');
            $LessonComponent->delete();
        }

        $quizLesson->delete();
        $quizlesson=QuizLesson::where('quiz_id',$id)->get();
        // if(!isset($quizlesson))
        if( count($quizlesson) <= 0)
        {
            $targetQuiz = Quiz::where('id',$id)->first();
            $targetQuiz->delete();
        }

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
            // $courseItem = CourseItem::where('item_id', $id)->where('type', 'quiz')->first();
            // if(isset($courseItem)){
            //     $users = UserCourseItem::where('course_item_id', $courseItem->id)->pluck('user_id')->toArray();
            //     if(!in_array(Auth::id(), $users))
            //         return response()->json(['message' => __('messages.error.no_permission'), 'body' => null], 403);
            // }
            $users = SecondaryChain::where('lesson_id', $request->lesson_id)->where('course_id',Lesson::find($request->lesson_id)->course_id)->pluck('user_id')->unique();
            if(!in_array(Auth::id(),$users->toArray()))
                return HelperController::api_response_format(404, __('messages.error.data_invalid'));
        }

        $quiz = quiz::where('id',$id)->with(['Question.children','courseItem.courseItemUsers'])->first();
        $quizLesson=QuizLesson::where('quiz_id',$id)->where('lesson_id',$request->lesson_id)->first();

        if($request->user()->can('site/course/student') && !$quizLesson->visible)
            return HelperController::api_response_format(404, null ,__('messages.error.not_available_now'));

        if(!isset($quiz))
            return HelperController::api_response_format(404, null ,__('messages.error.item_deleted'));

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

    public function editQuizAssignedUsers(Request $request){
        $request->validate([
            'id' => 'required|exists:quizzes,id',
            'users_ids' => 'array',
            'users_ids.*' => 'exists:users,id',
        ]);

        $quiz= Quiz::find($request->id);
        $quiz->restricted=1;

        if(!isset($request->users_ids))
            $quiz->restricted=0;
        
        $quiz->save();
        CoursesHelper::updateCourseItem($request->id, 'quiz', $request->users_ids);
        return response()->json(['message' => 'Updated successfully'], 200);
    }

    public function getQuizAssignedUsers(Request $request){

        $request->validate([
            'id' => 'required|exists:quizzes,id',
        ]);

        $quiz = Quiz::with(['Lesson', 'courseItem.courseItemUsers'])->find($request->id);

        foreach($quiz->Lesson as $lesson){
            if($lesson->shared_lesson ==1)
                $result['quiz_classes']= $lesson->shared_classes->pluck('id');
            else
                $result['quiz_classes'][]= $lesson->shared_classes->pluck('id')->first();
        }
        
        $result['restricted'] = $quiz->restricted;
        if(isset($quiz['courseItem'])){

            $courseItemUsers = $quiz['courseItem']->courseItemUsers;
            foreach($courseItemUsers as $user)
                $result['assigned_users'][] = $user->user_id;
        }

        return response()->json($result, 200);
    }
}
