<?php

namespace Modules\QuestionBank\Http\Controllers;

use App\GradeCategory;
use App\Http\Controllers\GradeCategoryController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Controllers\HelperController;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\quiz;
use App\Lesson;
use App\SegmentClass;
use App\ClassLevel;
use App\CourseSegment;
use App\Enroll;
use App\LessonComponent;
use App\User;
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
            'grade' => 'required|integer|min:1',
            'graded' => 'required|boolean',
            'grade_category_id' => 'array|required_if:graded,==,1',
            'grade_category_id.*' => 'exists:grade_categories,id',
            'grade_min' => 'integer|required_if:graded,==,1',
            'grade_max' => 'integer|required_if:graded,==,1',
            'grade_to_pass' => 'integer|required_if:graded,==,1',
        ]);

        $quiz = quiz::find($request->quiz_id);
        $users=Enroll::where('course_segment',$quiz->course_id)->where('role_id',3)->pluck('user_id')->toArray();
        foreach ($request->lesson_id as $key => $lessons)
        {
            $lesson = Lesson::find($lessons);

            //for notification
            $users = Enroll::where('course_segment',$lesson->courseSegment->id)->where('role_id',3)->pluck('user_id')->toArray();
            $course = $lesson->courseSegment->course_id;
            $class = $lesson->courseSegment->segmentClasses[0]->classLevel[0]->class_id;

            $gradeCats= $lesson->courseSegment->GradeCategory;
            $flag= false;
             foreach ($gradeCats as $grade){
                if($grade->id==$request->grade_category_id[$key]){
                    $flag =true;
                }
            }

            if($quiz->course_id != $lesson->courseSegment->course_id){
                return HelperController::api_response_format(500, null,'This lesson doesn\'t belongs to the course of this quiz');
            }

            $check = QuizLesson::where('quiz_id',$request->quiz_id)
                ->where('lesson_id',$request->lesson_id)->get();

            if(count($check) > 0){
                return HelperController::api_response_format(500, null,'This Quiz is aleardy assigned to this lesson');
            }
            if($flag==false){
                return HelperController::api_response_format(400, null,'this grade category invalid');
            }
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
                'grade_category_id' => $request->grade_category_id[$key],
                'publish_date' => $request->opening_time,
                'index' => $Next_index
            ]);

            $requ = ([
                'message' => 'the quiz is added',
                'id' => $quizLesson[0]->id,
                'users' => $users,
                'type' =>'quiz',
                'publish_date'=> $request->opening_time,
                'course_id' => $course,
                'class_id'=> $class,
                'from' => Auth::id(),
            ]);
            user::notify($requ);

             if($request->graded == true){
                 $grade_category=GradeCategory::find($request->grade_category_id[$key]);
                 $grade_category->GradeItems()->create([
                    'grademin' => $request->grade_min,
                    'grademax' => $request->grade_max,
                    'item_no' => 1,
                    'scale_id' => (isset($request->scale_id)) ? $request->scale_id : null,
                    'grade_to_pass' => (isset($request->grade_to_pass)) ? $request->grade_to_pass : null,
                    'aggregationcoef' => (isset($request->aggregationcoef)) ? $request->aggregationcoef : null,
                    'aggregationcoef2' => (isset($request->aggregationcoef2)) ? $request->aggregationcoef2 : null,
                    'item_type' => (isset($request->item_type)) ? $request->item_type : null,
                    'item_Entity' => $quizLesson[0]->id,
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
        return HelperController::api_response_format(200, $quizLesson,'Quiz added to lesson Successfully');
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
            'opening_time' => 'required|date|date_format:Y-m-d H:i:s',
            'closing_time' => 'required|date|date_format:Y-m-d H:i:s|after:opening_time',
            'max_attemp' => 'required|integer|min:1',
            'grading_method_id' => 'required',
            'grade' => 'required',
            'grade_category_id' => 'required|integer|exists:grade_categories,id'
        ]);

        $quiz = quiz::find($request->quiz_id);
        $lesson = Lesson::find($request->lesson_id);

        //for notification
        $users = Enroll::where('course_segment',$lesson->courseSegment->id)->where('role_id',3)->pluck('user_id')->toArray();
        $course = $lesson->courseSegment->course_id;
        $class = $lesson->courseSegment->segmentClasses[0]->classLevel[0]->class_id;

        if($quiz->course_id != $lesson->courseSegment->course_id){
            return HelperController::api_response_format(500, null,'This lesson doesn\'t belongs to the course of this quiz');
        }

        $gradeCats= $lesson->courseSegment->GradeCategory;
        $flag= false;
        foreach ($gradeCats as $grade){
            if($grade->id==$request->grade_category_id){
                $flag =true;
            }
        }

        $quizLesson = QuizLesson::where('quiz_id',$request->quiz_id)
                        ->where('lesson_id',$request->lesson_id)->first();

        if(!isset($quizLesson)){
            return HelperController::api_response_format(404, null,'This quiz doesn\'t belongs to the lesson');
        }
        if($flag==false){
            return HelperController::api_response_format(400, null,'this grade category invalid');
        }
        $quizLesson->update([
            'quiz_id' => $request->quiz_id,
            'lesson_id' => $request->lesson_id,
            'start_date' => $request->opening_time,
            'due_date' => $request->closing_time,
            'max_attemp' => $request->max_attemp,
            'grading_method_id' => $request->grading_method_id,
            'grade' => $request->grade,
            'grade_category_id' => $request->grade_category_id
        ]);
        $requ = ([
            'message' => 'the quiz is updated',
            'id' => $quizLesson->id,
            'users' => $users,
            'type' =>'quiz',
            'publish_date'=> $request->opening_time,
            'course_id' => $course,
            'class_id'=> $class,
            'from' => Auth::id(),
        ]);
        user::notify($requ);
        $all = Lesson::find($request->lesson_id)->module('QuestionBank', 'quiz')->get();
        return HelperController::api_response_format(200, $all,'Quiz updated atteched to lesson Successfully');
    }

    public function getGradeCategory(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'class_id' => 'required|integer|exists:classes,id',
        ]);
        $couse_segment_id= CourseSegment::GetWithClassAndCourse($request->class_id,$request->course_id)->id;
        $course_segment = CourseSegment::find($couse_segment_id);
        return $course_segment->GradeCategory;
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
            return HelperController::api_response_format(404, null,'This quiz doesn\'t belongs to the lesson');

        $quizLesson->delete();
        $all = Lesson::find($request->lesson_id)->module('QuestionBank', 'quiz')->get();
        return HelperController::api_response_format(200, $all,'Quiz lesson deleted Successfully');
    }

    public function getQuizInLesson(Request $request)
    {
        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
            'lesson_id' => 'required|integer|exists:lessons,id',
        ]);
        $quizLesson = QuizLesson::where('quiz_id',$request->quiz_id)
                ->where('lesson_id',$request->lesson_id)->first();
        return HelperController::api_response_format(200, $quizLesson);
    }
}
