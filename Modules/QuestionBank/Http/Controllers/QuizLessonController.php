<?php

namespace Modules\QuestionBank\Http\Controllers;

use App\GradeCategory;
use App\Http\Controllers\GradeCategoryController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Controllers\HelperController;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\UserQuiz;
use Modules\QuestionBank\Entities\UserQuizAnswer;
use Modules\QuestionBank\Entities\QuizOverride;
use Modules\QuestionBank\Entities\quiz;
use App\Lesson;
use App\SegmentClass;
use App\ClassLevel;
use App\CourseSegment;
use App\Enroll;
use App\LessonComponent;
use App\User;
use Carbon\Carbon;
use App\LastAction;
use Auth;

class QuizLessonController extends Controller
{
    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
            'lesson_id' => 'required|array|exists:lessons,id',
            'opening_time' => 'required|date',
            'closing_time' => 'required|date|after:opening_time',
            'max_attemp' => 'required|integer|min:1',
            'grading_method_id' => 'required',
            'grade' => 'required|numeric|min:1',
            'graded' => 'required|boolean',
            'grade_category_id' => 'array|required_if:graded,==,1',
            'grade_category_id.*' => 'exists:grade_categories,id',
            'grade_min' => 'integer',
            'grade_max' => 'integer',
            'grade_to_pass' => 'integer',
            'visible'=>"in:1,0",
            'publish_date' => 'date|before_or_equal:opening_time'
        ]);

        if(!$request->has('publish_date'))
            $request['publish_date'] = $request->opening_time;

        $quiz = quiz::find($request->quiz_id);
        // $users=Enroll::where('course_segment',$quiz->course_id)->where('role_id',3)->pluck('user_id')->toArray();
        foreach ($request->lesson_id as $key => $lessons)
        {
            $lesson = Lesson::find($lessons);

            //for notification
            $users = Enroll::where('course_id',$lesson->course_id)->where('user_id','!=',Auth::user()->id)->where('role_id','!=', 1 )->pluck('user_id')->toArray();
            $course = $lesson->course_id;
            LastAction::lastActionInCourse($course);

            if($request->filled('grade_category_id') && $request->grade_category_id[$key] != null)
            {
                $gradeCats= $lesson->courseSegment->GradeCategory;
                $flag= false;
                 foreach ($gradeCats as $grade){
                    if($grade->id==$request->grade_category_id[$key]){
                        $flag =true;
                    }
                }

                if($flag==false){
                    return HelperController::api_response_format(400, $request->grade_category_id[$key],__('messages.error.data_invalid'));
                }
            }

            if($quiz->course_id != $lesson->course_id){
                return HelperController::api_response_format(500, null,__('messages.quiz.quiz_not_belong'));
            }

            // $check = QuizLesson::where('quiz_id',$request->quiz_id)
            //     ->where('lesson_id',$lessons)->get();

            // if(count($check) > 0){
            //     return HelperController::api_response_format(500, null,'This Quiz is aleardy assigned to this lesson');
            // }
            $lesson_obj = Lesson::find($lessons);
            $course_segment =  CourseSegment::find($lesson_obj->course_segment_id);
            if( $course_segment->end_date < Carbon::parse($request->closing_time) )
                return HelperController::api_response_format(400, null , __('messages.date.end_before').$course_segment->end_date." ");
            $index = QuizLesson::where('lesson_id',$lessons)->get()->max('index');
            $Next_index = $index + 1;
            $quizLesson[] = QuizLesson::create([
                'quiz_id' => $request->quiz_id,
                'lesson_id' => $lessons,
                'start_date' => $request->opening_time,
                'due_date' => $request->closing_time,
                'max_attemp' => $request->max_attemp,
                'grading_method_id' => $request->grading_method_id,
                'grade' => $request->grade,
                'grade_category_id' => $request->filled('grade_category_id') ? $request->grade_category_id[$key] : null,
                'publish_date' => $request->publish_date,
                'index' => $Next_index,
                'visible' => isset($request->visible)?$request->visible:1
            ]);
            // $class = Second$lesson->courseSegment->segmentClasses[0]->classLevel[0]->class_id;
            $classes = SecondaryChain::whereIn('lesson_id', $lessons)->get()->pluck('group_id')->unique();
            foreach($classes as $class){
                $requ = ([
                    'message' => $quiz->name.' quiz is added',
                    'id' => $request->quiz_id,
                    'users' => $users,
                    'type' =>'quiz',
                    'publish_date'=> Carbon::parse($request->publish_date),
                    'course_id' => $course,
                    'class_id'=> $class,
                    'lesson_id'=> $lessons,
                    'from' => Auth::id(),
                ]);
                user::notify($requ);
            }

             if($request->graded == true){
                 $grade_category=GradeCategory::find($request->grade_category_id[$key]);
                 $grade_category->GradeItems()->create([
                    'grademin' => 0,
                    'grademax' => $request->grade,
                    'item_no' => 1,
                    'scale_id' => (isset($request->scale_id)) ? $request->scale_id : 1,
                    'grade_pass' => (isset($request->grade_to_pass)) ? $request->grade_to_pass : null,
                    'aggregationcoef' => (isset($request->aggregationcoef)) ? $request->aggregationcoef : null,
                    'aggregationcoef2' => (isset($request->aggregationcoef2)) ? $request->aggregationcoef2 : null,
                    'item_type' => 1, //Quiz
                    'name' => $quizLesson[0]->quiz->name,
                    'weight' => 0,
                 ]);
             }
            LessonComponent::create([
                'lesson_id' => $lessons,
                'comp_id'   => $request->quiz_id,
                'module'    => 'QuestionBank',
                'model'     => 'quiz',
                'index'     => LessonComponent::getNextIndex($lessons)
            ]);
        }
        return HelperController::api_response_format(200, $quizLesson,__('messages.quiz.add'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request)
    {
        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
            'lesson_id' => 'required|integer|exists:lessons,id',
            'opening_time' => 'date',
            'closing_time' => 'date|after:opening_time',
            'max_attemp' => 'integer|min:1',
            'grading_method_id' => 'integer',
            'grade' => 'numeric',
            // 'grade_category_id' => 'required|integer|exists:grade_categories,id'
            'updated_lesson_id' =>'nullable|exists:lessons,id',
            'visible'=>'in:0,1',
            'publish_date' => 'date|before_or_equal:opening_time'
        ]);

        if($request->filled('opening_time')){
            $start= $request->opening_time;
        }

        if($request->filled('publish_date')){
            $publish = $request->publish_date;
        }

        $quiz = quiz::find($request->quiz_id);
        $lesson_drag = Lesson::find($request->lesson_id);

        $course_drag = $lesson_drag->courseSegment->course_id;
    
        
        if($quiz->course_id != $lesson_drag->courseSegment->course_id){
            return HelperController::api_response_format(500, null, __('messages.quiz.quiz_not_belong'));
        }
        LastAction::lastActionInCourse($course_drag);

        if($request->grade_category_id != null)
        {
            $gradeCats= $lesson_drag->courseSegment->GradeCategory;
            $flag= false;
             foreach ($gradeCats as $grade){
                if($grade->id==$request->grade_category_id){
                    $flag =true;
                }
            }

            if($flag==false){
                return HelperController::api_response_format(400, null,__('messages.error.data_invalid'));
            }
        }
        $quiz=Quiz::find($request->quiz_id);
        $quizLesson = QuizLesson::where('quiz_id',$request->quiz_id)
                        ->where('lesson_id',$request->lesson_id)->first();

        if(!isset($quizLesson)){
            return HelperController::api_response_format(404, null, __('messages.quiz.quiz_not_belong'));
        }
        if($request->filled('grading_method_id'))
                $quizLesson->update(['grading_method_id' => $request->grading_method_id]);
        if($request->filled('closing_time'))
            $quizLesson->update(['due_date' => $request->closing_time]);
        if($request->filled('max_attemp'))
            $quizLesson->update(['max_attemp' => $request->max_attemp]);
        if($request->filled('grade'))
            $quizLesson->update([ 'grade' => $request->grade]);
        if($request->filled('grade_category_id'))
            $quizLesson->update(['grade_category_id' =>$request->grade_category_id]);
        if($request->filled('visible'))
            $quizLesson->update(['visible' =>$request->visible]);
        if (!$request->filled('updated_lesson_id')) {
            $request->updated_lesson_id= $request->lesson_id;
            }
        $lesson = Lesson::find($request->updated_lesson_id);
        $quiz->update([
            'course_id' => $lesson->courseSegment->course_id
        ]);
        $quizLesson->update([
            'quiz_id' => $request->quiz_id,
            'lesson_id' => $request->updated_lesson_id,
        ]);

        if(isset($start))
            $quizLesson->update(['start_date' => $start]);

        if(isset($publish))
            $quizLesson->update(['publish_date' => $publish]);

        //for notification
        $users = Enroll::where('course_segment',$lesson->courseSegment->id)->where('user_id','!=',Auth::user()->id)->where('role_id','!=', 1 )->pluck('user_id')->toArray();
        $course = $lesson->courseSegment->course_id;
        LastAction::lastActionInCourse($course);

        $class = $lesson->courseSegment->segmentClasses[0]->classLevel[0]->class_id;
        if(carbon::parse($quizLesson->publish_date)->isPast())
            $publish=Carbon::now();
        $requ = ([
            'message' => $quiz->name . ' quiz is updated',
            'id' => $request->quiz_id,
            'users' => $users,
            'type' =>'quiz',
            'publish_date'=> Carbon::parse($publish),
            'course_id' => $course,
            'class_id'=> $class,
            'lesson_id'=> $request->updated_lesson_id,
            'from' => Auth::id(),
        ]);
        user::notify($requ);
        $all = Lesson::find($request->updated_lesson_id)->module('QuestionBank', 'quiz')->get();
        return HelperController::api_response_format(200, $all,__('messages.quiz.update'));
    }

    public function getGradeCategory(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'class_id' => 'required|integer|exists:classes,id',
        ]);
        LastAction::lastActionInCourse($request->course_id);
    
        $couse_segment_id= CourseSegment::GetWithClassAndCourse($request->class_id,$request->course_id)->id;
        $course_segment = CourseSegment::find($couse_segment_id);
        return HelperController::api_response_format(200, $course_segment->GradeCategory,__('messages.grade.grade_category_list'));
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
            'lesson_id' => 'required|integer|exists:lessons,id',
        ]);

        $quizLesson = QuizLesson::where('quiz_id',$request->quiz_id)->where('lesson_id',$request->lesson_id)->first();

        if(!isset($quizLesson))
            return HelperController::api_response_format(404, null,__('messages.quiz.quiz_not_belong'));

        $quizLesson->delete();
        $all = Lesson::find($request->lesson_id)->module('QuestionBank', 'quiz')->get();
        return HelperController::api_response_format(200, $all,__('messages.quiz.delete'));
    }

    public function getQuizInLesson(Request $request)
    {
        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
            'lesson_id' => 'required|integer|exists:lessons,id',
        ]);
        $quizLesson = QuizLesson::where('quiz_id',$request->quiz_id)
                ->where('lesson_id',$request->lesson_id)->first();

        $userquizzes = UserQuiz::where('quiz_lesson_id', $quizLesson->id)->get();
        $quizLesson['allow_edit'] = true;
        foreach($userquizzes as $userQuiz)
        {
            $user_quiz_answer=UserQuizAnswer::where('user_quiz_id',$userQuiz->id)->pluck('answered')->first();
            if ($user_quiz_answer == 1)
                $quizLesson['allow_edit'] = false;
        }
        return HelperController::api_response_format(200, $quizLesson);
    }
    public function overrideQuiz(Request $request)
    {

        $request->validate([
        'users_id' => 'required|array',
        'users_id.*' => 'required|integer|exists:users,id',
        'quiz_id' => 'required|integer|exists:quizzes,id',
        'lesson_id' => 'required|integer|exists:lessons,id',
        'start_date' => 'required|before:due_date',
        'due_date' => 'required',//|after:' . Carbon::now(),
    ]);
    
    $quizLesson = QuizLesson::where('quiz_id', $request->quiz_id)->where('lesson_id', $request->lesson_id)->first();
    if(!isset($quizLesson)){
        return HelperController::api_response_format(400,null, __('messages.quiz.quiz_not_belong'));

    }
    $lesson= Lesson::find($quizLesson->lesson_id);
    $segment_due_date =  $lesson->SecondaryChain[0]->Enroll->Segment->end_date;
    if($segment_due_date < Carbon::parse($request->due_date))
            return HelperController::api_response_format(400, null , __('messages.date.end_before').$segment_due_date);

    $usersOverride =array();
    foreach ($request->users_id as $user_id) {
        $usersOverride [] =  QuizOverride::updateOrCreate(
        ['user_id'=> $user_id,
        'quiz_lesson_id'=> $quizLesson->id,],
        ['start_date' => $request->start_date,
        'due_date'=>$request->due_date ,
        'attemps' => $quizLesson->max_attemp]
        );
    }
    return HelperController::api_response_format(201, $usersOverride, __('messages.quiz.override'));

    }
}
