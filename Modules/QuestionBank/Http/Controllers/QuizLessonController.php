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

    public function NotifyQuiz($quiz,$publishdate,$type)
    {
        $course_seg=CourseSegment::getidfromcourse($quiz->course_id);

        if($type=='add')
        {
            $msg='A New Quiz is Added!';
        }
        else
        {
            $msg='Quiz is Updated!';
        }

        foreach($course_seg as $course_Segment)
        {
            $roles_id =   Permission::where('name','site/quiz/notify_quiz')->roles->pluck('id');
            $users = Enroll::where('course_segment', $course_Segment)->whereIn('role_id',$roles_id)->pluck('user_id')->toarray();
            $segmentClass=CourseSegment::where('id',$course_Segment)->pluck('segment_class_id')->first();
            $ClassLevel=SegmentClass::where('id',$segmentClass)->pluck('class_level_id')->first();
            $classId=ClassLevel::where('id',$ClassLevel)->pluck('class_id')->first();
            user::notify([
                'message' => $msg,
                'from' => Auth::user()->id,
                'users' => $users,
                'course_id' => $quiz->course_id,
                'class_id'=> $classId,
                'type' =>'quiz',
                'link' => url(route('getquiz')) . '?quiz_id=' . $quiz->id,
                'publish_date'=> $publishdate
            ]);
        }
    }

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
            'opening_time' => 'required|date|date_format:Y-m-d H:i:s',
            'closing_time' => 'required|date|date_format:Y-m-d H:i:s|after:opening_time',
            'max_attemp' => 'required|integer|min:1',
            'grading_method_id' => 'required',
            'grade' => 'required',
            'grade_category_id' => 'required|integer|exists:grade_categories,id',
            'grade_min' => 'required|integer',
            'grade_max' => 'required|integer',
            'scale_id' => 'required|integer|exists:scales,id',
            'grade_to_pass' => 'required|integer',
        ]);

        $quiz = quiz::find($request->quiz_id);
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
                ->where('lesson_id',$request->quiz_id)->get();
    
            if(count($check) > 0){
                return HelperController::api_response_format(500, null,'This Quiz is aleardy assigned to this lesson');
            }
            if($flag==false){
                return HelperController::api_response_format(400, null,'this grade category invalid');
    
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
            $this->NotifyQuiz($quiz,$request->opening_time,'add');
            $grade_category=GradeCategory::find($request->grade_category_id);
            $grade_item = $grade_category->GradeItems()->create([
                'grademin'=>$request->grade_min,
                'grademax'=>$request->grade_max,
                'scale_id'=>$request->scale_id,
                'grade_pass'=>$request->grade_to_pass,
                'item_type'=>1,
                'item_Entity'=>$quizLesson[0]->id
            ]);
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
        $gradeCats= $lesson->courseSegment->GradeCategory;
        $flag= false;
        foreach ($gradeCats as $grade){
            if($grade->id==$request->grade_category_id){
                $flag =true;
            }
        }
        $coueseSegment = $lesson->courseSegment;
        if($quiz->course_id != $coueseSegment->course_id){
            return HelperController::api_response_format(404, null,'This lesson doesn\'t belongs to the course of this quiz');
        }

        $quizLesson = QuizLesson::where('quiz_id',$request->quiz_id)
                        ->where('lesson_id',$request->quiz_id)->first();

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
        $this->NotifyQuiz($quiz,$request->opening_time,'update');

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
                ->where('lesson_id',$request->quiz_id)->first();

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
                ->where('lesson_id',$request->quiz_id)->first();
        return HelperController::api_response_format(200, $quizLesson);
    }
}