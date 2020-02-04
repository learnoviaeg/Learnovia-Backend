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
            'grade_category_id' => 'integer|exists:grade_categories,id',
             //'grade_min' => 'integer',
            //'grade_max' => 'integer',
            //'grade_to_pass' => 'integer',
        ]);

        $quiz = quiz::find($request->quiz_id);
        $users=Enroll::where('course_segment',$quiz->course_segment_id)->where('role_id',3)->pluck('user_id')->toArray();
        $class=CourseSegment::find($quiz->course_segment_id)->segmentClasses[0]->classLevel[0]->classes[0]->id;
        $course=CourseSegment::find($quiz->course_segment_id)->courses[0]->id;
        foreach ($request->lesson_id as $lessons)
        {
            $lesson = Lesson::find($lessons);
            $gradeCats= $lesson->courseSegment->GradeCategory;
            $flag= false;
             foreach ($gradeCats as $grade){
                 if($grade->id==$request->grade_category_id){
                    $flag =true;
                 }
             }
            $course_Quiz=CourseSegment::where('id',$quiz->course_segment_id)->pluck('course_id')->first();
            $coueseSegment = $lesson->courseSegment;
            if($course_Quiz != $coueseSegment->course_id){
                return HelperController::api_response_format(500, null,'This lesson doesn\'t belongs to the course of this quiz');
            }

            $check = QuizLesson::where('quiz_id',$request->quiz_id)
                ->where('lesson_id',$request->lesson_id)->get();

            if(count($check) > 0){
                return HelperController::api_response_format(500, null,'This Quiz is aleardy assigned to this lesson');
            }

            $quizLesson[] = QuizLesson::create([
                'quiz_id' => $request->quiz_id,
                'lesson_id' => $lessons,
                'start_date' => $request->opening_time,
                'due_date' => $request->closing_time,
                'max_attemp' => $request->max_attemp,
                'grading_method_id' => $request->grading_method_id,
                'grade' => $request->grade,
                'grade_category_id' => $request->grade_category_id,
                'publish_date' => $request->opening_time
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
            // return $requ;
            $ss= user::notify($requ);
             if($request->graded == true){
                 if($flag==false){
                    return HelperController::api_response_format(400, null,'this grade category invalid');
                 }
                 $grade_category=GradeCategory::find($request->grade_category_id);
                 $grade_category->GradeItems()->create([
                     'grademin' => (isset($request->grade_min)) ? $request->grade_min : null,
                     'grademax' => (isset($request->grade_max)) ? $request->grade_max : null,
                     'item_no' => 1,
                     'scale_id' => (isset($request->scale_id)) ? $request->scale_id : null,
                     'grade_pass' => (isset($request->grade_pass)) ? $request->grade_pass : null,
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
            // 'grade_category_id' => 'required|integer|exists:grade_categories,id'
        ]);

        $quiz = quiz::find($request->quiz_id);
        $users=Enroll::where('course_segment',$quiz->course_segment_id)->where('role_id',3)->pluck('user_id')->toArray();
        $class=CourseSegment::find($quiz->course_segment_id)->segmentClasses[0]->classLevel[0]->classes[0]->id;
        $course=CourseSegment::find($quiz->course_segment_id)->courses[0]->id;
        $lesson = Lesson::find($request->lesson_id);
        $gradeCats= $lesson->courseSegment->GradeCategory;
        $flag= false;
        // foreach ($gradeCats as $grade){
        //     if($grade->id==$request->grade_category_id){
        //         $flag =true;
        //     }
        // }
        $coueseSegment = $lesson->courseSegment;

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
            // 'grade_category_id' => $request->grade_category_id
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
        $ss= user::notify($requ);

        return HelperController::api_response_format(200, $quizLesson,'Quiz updated atteched to lesson Successfully');
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

        $quizLesson = QuizLesson::where('quiz_id',$request->quiz_id)
                ->where('lesson_id',$request->lesson_id)->first();

        if(!isset($quizLesson)){
            return HelperController::api_response_format(404, null,'This quiz doesn\'t belongs to the lesson');
        }

        $quizLesson->delete();

        return HelperController::api_response_format(200, [],'Quiz lesson deleted Successfully');
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
