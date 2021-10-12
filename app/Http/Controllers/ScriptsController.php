<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use app\Course;
use app\GradeCategory;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\Quiz;
use App\Events\UpdatedAttemptEvent;
use Modules\QuestionBank\Entities\userQuiz;
use App\GradeItems;
use Auth;
use Carbon\Carbon;
use App\UserGrader;
use App\Enroll;
use App\Events\GradeItemEvent;

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
        foreach(userQuiz::cursor() as $user_quiz){
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

    public function grade_details_of_questions(Request $request)
    {
        foreach(QuizLesson::cursor() as $quiz_lesson){
            $grade_cat = GradeCategory::firstOrCreate(
                [
                    'instance_type'=>'Quiz',
                    'instance_id'=> $quiz_lesson->quiz_id,
                    'lesson_id'=> $quiz_lesson->lesson_id,
                    'course_id'=>$quiz_lesson->lesson->course_id,

                ],[
                    'parent' => $quiz_lesson->grade_category_id,
                    'calculation_type' => json_encode($quiz_lesson->grading_method_id),
                    'hidden' => 0 ,
                ]);
                $quiz_lesson->grade_category_id = $grade_cat->id;
                $quiz_lesson->save();
                $max_attempt=$quiz_lesson->max_attemp;                
                if((Auth::user()->can('site/quiz/unLimitedAttempts')))
                    $max_attempt=1;
    
                for($key =1; $key<=$max_attempt; $key++){
                    $gradeItem = GradeItems::updateOrCreate([
                        'index' => $key,
                        'grade_category_id' => $grade_cat->id,
                        'name' => 'Attempt number ' .$key,
                    ],
                    [
                        'type' => 'Attempts',
                    ]
                );    
                    $enrolled_students = Enroll::where('role_id' , 3)->where('course',$quiz_lesson->lesson->course_id)->pluck('user_id');
                    foreach($enrolled_students as $student){
                        $data = [
                            'user_id'   => $student,
                            'item_type' => 'Item',
                            'item_id'   => $gradeItem->id,
                            'grade'     => null
                        ];
                        UserGrader::firstOrcreate($data);
                    }
                    event(new GradeItemEvent($gradeItem));
                }
        }
        return 'done';
    }

    public function deleteWrongAttempts()
    {
        $user_quizzes=userQuiz::all();
        foreach($user_quizzes as $userQuiz)
        {
            if(Carbon::parse($userQuiz->open_time) > Carbon::parse($userQuiz->quiz_lesson->due_date))
                $userQuiz->delete();
        }
        return 'done';
    }

}
