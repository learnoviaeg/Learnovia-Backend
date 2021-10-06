<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use app\Course;
use app\GradeCategory;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\Quiz;
use App\Events\UpdatedAttemptEvent;

class ScriptsController extends Controller
{
    public function CreateGradeCatForCourse(Request $request)
    {
        $allCourse = Course::all();
        foreach($allCourse as $course)
        {
            $gradeCat = GradeCategory::firstOrCreate([
                'name' => $course->name . ' Total',
                'course_id' => $course->id
            ]);
        }

        return 'done';
    }

    public function gradeAttemptsInQuizlesson(Request $request)
    {
        $request->validate([
            'lesson_id' => 'required|exists:lessons,id',
            'quiz_id' => 'required|exists:quizzes,id',
        ]);
        $user_quizzes = QuizLesson::where('lesson_id', $request->lesson_id)->where('quiz_id', $request->quiz_id)->with('user_quiz.UserQuizAnswer')->first();
        foreach($user_quizzes->user_quiz as $user_quiz){
            event(new UpdatedAttemptEvent($user_quiz));

        }
        return 'done';
    }


    public function quiz_total_mark(Request $request)
    {
          foreach(QuizLesson::cursor() as $quiz_lesson){
            $quiz_lesson->grade = $quiz_lesson->questions_mark;
            $quiz_lesson->save();
        }
        return 'done';
    }

}
