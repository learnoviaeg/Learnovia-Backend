<?php

namespace Modules\QuestionBank\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Controllers\HelperController;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\quiz;
use App\Lesson;
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
            $users = Enroll::where('course_segment', $course_Segment)->where('role_id',3)->pluck('user_id')->toarray();
            user::notify([
                'message' => $msg,
                'from' => Auth::user()->id,
                'users' => $users,
                'course_id' => $quiz->course_id,
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
            'lesson_id' => 'required|integer|exists:lessons,id',
            'opening_time' => 'required|date|date_format:Y-m-d H:i:s',
            'closing_time' => 'required|date|date_format:Y-m-d H:i:s|after:opening_time',
            'max_attemp' => 'required|integer|min:1',
            'grading_method_id' => 'required',
            'grade' => 'required',
            'grade_category_id' => 'required'
        ]);

        $quiz = quiz::find($request->quiz_id);
        $lesson = Lesson::find($request->lesson_id);
        $coueseSegment = $lesson->courseSegment;
        if($quiz->course_id != $coueseSegment->course_id){
            return HelperController::api_response_format(500, null,'This lesson doesn\'t belongs to the course of this quiz');
        }

        $check = QuizLesson::where('quiz_id',$request->quiz_id)
            ->where('lesson_id',$request->quiz_id)->get();

        if(count($check) > 0){
            return HelperController::api_response_format(500, null,'This Quiz is aleardy assigned to this lesson');
        }

        $quizLesson = QuizLesson::create([
            'quiz_id' => $request->quiz_id,
            'lesson_id' => $request->lesson_id,
            'start_date' => $request->opening_time,
            'due_date' => $request->closing_time,
            'max_attemp' => $request->max_attemp,
            'grading_method_id' => $request->grading_method_id,
            'grade' => $request->grade,
            'grade_category_id' => $request->grade_category_id,
            'publish_date' => $request->opening_time
        ]);

        $this->NotifyQuiz($quiz,$request->opening_time,'add');
        LessonComponent::create([
            'lesson_id' => $request->lesson_id,
            'comp_id'   => $request->quiz_id,
            'module'    => 'QuestionBank',
            'model'     => 'quiz',
            'index'     => LessonComponent::getNextIndex($request->lesson_id)
        ]);
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
            'grade_category_id' => 'required'
        ]);

        $quiz = quiz::find($request->quiz_id);
        $lesson = Lesson::find($request->lesson_id);
        $coueseSegment = $lesson->courseSegment;
        if($quiz->course_id != $coueseSegment->course_id){
            return HelperController::api_response_format(404, null,'This lesson doesn\'t belongs to the course of this quiz');
        }

        $quizLesson = QuizLesson::where('quiz_id',$request->quiz_id)
                        ->where('lesson_id',$request->quiz_id)->first();

        if(!isset($quizLesson)){
            return HelperController::api_response_format(404, null,'This quiz doesn\'t belongs to the lesson');
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
