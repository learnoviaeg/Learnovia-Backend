<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class userQuiz extends Model
{
    protected $fillable = [
        'quiz_lesson_id','user_id','status_id',
        'override','feedback','grade','attempt_index',
        'device_data','browser_data','ip',
        'open_time'
    ];

    public function quiz_lesson()
    {
        return $this->belongsTo('Modules\QuestionBank\Entities\QuizLesson', 'quiz_lesson_id', 'id');
    }

    public function UserQuizAnswer()
    {
        return $this->hasMany('Modules\QuestionBank\Entities\userQuizAnswer', 'user_quiz_id', 'id');
    }

    public static function calculate_grade_of_attempts_with_method($quiz_lesson){
        $grading_method_id=  QuizLesson::find($quiz_lesson)->pluck('grading_method_id');
        $user_id=Auth::User()->id;
        $attemps= userQuiz::where('user_id',$user_id)->where('quiz_lesson_id',$quiz_lesson);
        switch ($grading_method_id[0]){
            case 1: //first
                $Min_attemp= min($attemps->pluck('attempt_index')->toArray());
                $attemps=  $attemps->where('attempt_index',$Min_attemp)->first(['grade']);
                $grade=$attemps->grade;
                break;
            case 2 : //last
                $Max_attemp= max($attemps->pluck('attempt_index')->toArray());
                $attemps=  $attemps->where('attempt_index',$Max_attemp)->first(['grade']);
                $grade=$attemps->grade;
                break;
            case 3 : // average
               $grade=array_sum( $attemps->pluck('grade')->toArray())/count( $attemps->pluck('grade')->toArray());
                break;
            case 4 : // highest
                $grade= max($attemps->pluck('grade')->toArray());
                break;
            case 5 :  //lowest
                $grade= min($attemps->pluck('grade')->toArray());
                break;
        }
        return $grade;
    }
}
