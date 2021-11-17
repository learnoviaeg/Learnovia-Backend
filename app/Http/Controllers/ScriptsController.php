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
use Modules\QuestionBank\Entities\quiz_questions;
use Modules\QuestionBank\Entities\Questions;

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

    public function gradeAttemptsInQuizlesson(Request $request) //auto correction
    {
        $request->validate([
            'quiz_id' => 'exists:quizzes,id',
            'lesson_id' => 'required_with:quiz_id|exists:quiz_lessons,lesson_id'
        ]);

        if(isset($request->quiz_id)){
            $Quiz_lesson = QuizLesson::where('quiz_id',$request->quiz_id)->where('lesson_id',$request->lesson_id)->first();
            $gradeCat=GradeCategory::whereId($Quiz_lesson->grade_category_id)->update(['calculation_type' => json_encode($Quiz_lesson->grading_method_id)] );
            $users_quiz=userQuiz::where('quiz_lesson_id',$Quiz_lesson->id)->get();
        }
        if(!isset($Quiz_lesson)){
            $users_quiz=userQuiz::cursor();
        }

        foreach($users_quiz as $user_quiz){
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
        $request->validate([
            'quiz_id' => 'exists:quizzes,id',
            'lesson_id' => 'required_with:quiz_id|exists:quiz_lessons,lesson_id'
        ]);

        if(isset($request->quiz_id)){
            $Quiz_lesson = QuizLesson::where('quiz_id',$request->quiz_id)->where('lesson_id',$request->lesson_id)->first();
            $quizLessons=[$Quiz_lesson];
        }
        if(!isset($Quiz_lesson)){
            $quizLessons=QuizLesson::cursor();
        }
        foreach($quizLessons as $quiz_lesson){
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
                        UserGrader::updateOrCreate([
                            'user_id'   => $user_id,
                            'item_type' => 'Item',
                            'item_id'   => $gradeItem->id
                        ],
                        [
                            'grade'     => null
                        ]);
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
            {
                if(count($userQuiz->quiz_lesson->override) > 0)
                {
                    foreach($userQuiz->quiz_lesson->override as $overwrite)
                    {
                        if(Carbon::parse($userQuiz->open_time) > $overwrite->due_date)
                        {
                            $userQuiz->delete();
                            continue;
                        }
                    }
                }
                else
                    $userQuiz->delete();
            }
        }
        return 'done';
    }

    public function reassign_shuffled_questions(){
        $quizzes =  Quiz::select('id')->whereIn('shuffle', ['Answers' , 'Questions and Answers']);
        $callback = function ($query)  {
                        $query->where('question_type_id' , 2);
                    };
        $quizzes_questions = $quizzes->whereHas('Question', $callback)->with(['Question'=> $callback])->get();
        foreach($quizzes_questions as $quiz_questions){
                foreach($quiz_questions->Question as $question){
                    $question_with_wrong_content = quiz_questions::where('question_id' , $question->id)->first();
                    $choices = [];
                    foreach($question_with_wrong_content->grade_details->details as $wrong_q){
                        $choices['type'] = $question_with_wrong_content->grade_details->type;
                        foreach($question->content as $right_quest){
                            if($right_quest->content == $wrong_q->content){
                                $wrong_q->key = $right_quest->key;
                                $choices['details'][] = $wrong_q;
                            }
                        }
                    }
                    $choices['total_mark'] = $question_with_wrong_content->grade_details->total_mark;
                    $choices['exclude_mark'] = $question_with_wrong_content->grade_details->exclude_mark;
                    $choices['exclude_shuffle'] = $question_with_wrong_content->grade_details->exclude_shuffle;
                    $question_with_wrong_content->update(['grade_details' => json_encode($choices)]);
            }
        }
        return 'done';
    }

    public function Full_Mark(Request $request)
    {
        $request->validate([
            'course' => 'required|exists:courses,short_name',
            'quiz_id' => 'exists:quizzes,id',
            'lesson_id' => 'required_with:quiz_id|exists:quiz_lessons,lesson_id'
        ]);

        if(isset($request->course))
        {
            $course=Course::where('short_name',$request->course)->first();
            $quizzesLessId=QuizLesson::whereIn('quiz_id',Quiz::where('course_id',$course->id)->pluck('id'))->pluck('id');
            if(isset($request->quiz_id)){
                $Quiz_lesson = QuizLesson::where('quiz_id',$request->quiz_id)->where('lesson_id',$request->lesson_id)->first();
                $users_quiz=userQuiz::where('quiz_lesson_id',$Quiz_lesson->id)->get();
            }
            if(!isset($Quiz_lesson)){
                $users_quiz=userQuiz::whereIn('quiz_lesson_id',$quizzesLessId)->get();
            }
            foreach($users_quiz as $user_quiz){
                $user_quiz->grade=$user_quiz->quiz_lesson->grade;
                $user_grader=UserGrader::where('item_type','category')->where('item_id',$user_quiz->quiz_lesson->grade_category_id)->
                                    where('user_id',$user_quiz->user_id)->first();
                $user_grader->update(['grade' => $user_quiz->quiz_lesson->grade]);
                $user_quiz->save();
                $user_grader->save();
            }
        }
        return 'done';
    }
}
