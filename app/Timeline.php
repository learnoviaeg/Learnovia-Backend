<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\Assigments\Entities\AssignmentLesson;
use Modules\Assigments\Entities\UserAssigment;
use Modules\QuestionBank\Entities\userQuizAnswer;
use Modules\QuestionBank\Entities\userQuiz;
use Modules\QuestionBank\Entities\QuizLesson;
use Carbon\Carbon;

class Timeline extends Model
{
    protected $fillable = [
        'item_id', 'name','start_date','due_date','publish_date','course_id','class_id','level_id','lesson_id','type','visible','overwrite_user_id'
    ];

    protected $appends = ['started','answered'];

    public function getStartedAttribute(){
        $started = true;
        if((Auth::user()->can('site/course/student') && $this->publish_date > Carbon::now()) || (Auth::user()->can('site/course/student') && $this->start_date > Carbon::now()))
            $started = false;

        return $started;  
    }

    public function getAnsweredAttribute(){
        $answered = false;
        if(Auth::user()->can('site/course/student')){
            if($this->type == 'assignment'){
                $assigLessonID = AssignmentLesson::where('assignment_id', $this->item_id)->where('lesson_id', $this->lesson_id)->first();
                $user_assigment = UserAssigment::where('assignment_lesson_id', $assigLessonID->id)->where('user_id',Auth::id())->whereNotNull('submit_date')->first();
                if(isset($user_assigment))
                    $answered = true;
            }

            if($this->type == 'quiz'){
                $quiz_lesson = QuizLesson::where('quiz_id', $this->item_id)->where('lesson_id', $this->lesson_id)->with('quiz')->first();
                $user_quiz = userQuiz::where('user_id', Auth::id())->where('quiz_lesson_id', $quiz_lesson->id)->latest('attempt_index')->first();
                if(isset($user_quiz) && $quiz_lesson->max_attemp == $user_quiz->attempt_index && Carbon::parse($user_quiz->open_time)->addSeconds($quiz_lesson->quiz->duration) <= Carbon::now())
                    $answered = true;
            }
        }
        return $answered;
    }

    public function class(){
        return $this->belongsTo('App\Classes');
    }

    public function course(){
        return $this->belongsTo('App\Course');
    }

    public function level(){
        return $this->belongsTo('App\Level');
    }
}
