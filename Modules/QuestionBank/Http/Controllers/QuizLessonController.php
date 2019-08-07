<?php

namespace Modules\QuestionBank\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Controllers\HelperController;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\quiz;
use App\Lesson;

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
            'lesson_id' => 'required|integer|exists:lessons,id',
            'opening_time' => 'required|date |date_format:Y-m-d H:i:s',
            'closing_time' => 'required|date |date_format:Y-m-d H:i:s|after:opening_time',
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

        $quizLesson = QuizLesson::create([
            'quiz_id' => $request->quiz_id,
            'lesson_id' => $request->lesson_id,

            'opening_time' => $request->opening_time,
            'closing_time' => $request->closing_time,
            'max_attemp' => $request->max_attemp,
            'grading_method_id' => $request->grading_method_id,
            'grade' => $request->grade,
            'grade_category_id' => $request->grade_category_id
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
            'quiz_lesson_id' => 'required|integer|exists:quiz_lessons,id',
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

        $quizLesson = QuizLesson::find($request->quiz_lesson_id);

        $quizLesson->update([
            'quiz_id' => $request->quiz_id,
            'lesson_id' => $request->lesson_id,

            'opening_time' => $request->opening_time,
            'closing_time' => $request->closing_time,
            'max_attemp' => $request->max_attemp,
            'grading_method_id' => $request->grading_method_id,
            'grade' => $request->grade,
            'grade_category_id' => $request->grade_category_id
        ]);

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
            'quiz_lesson_id' => 'required|integer|exists:quiz_lessons,id',
        ]);

        QuizLesson::destroy($request->quiz_lesson_id);
        return HelperController::api_response_format(200, [],'Quiz lesson deleted Successfully');
    }
}
