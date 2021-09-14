<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\quiz_questions;

class userQuiz extends Model
{
    protected $fillable = [
        'quiz_lesson_id','user_id','status_id', 'status',
        'override','feedback','grade','attempt_index',
        'device_data','browser_data','ip',
        'open_time','submit_time'
    ];

    protected $dispatchesEvents = [
        'updated' => \App\Events\UpdatedAttemptEvent::class,
    ];

    public function quiz_lesson()
    {
        return $this->belongsTo('Modules\QuestionBank\Entities\QuizLesson', 'quiz_lesson_id', 'id');
    }

    public function UserQuizAnswer()
    {
        return $this->hasMany('Modules\QuestionBank\Entities\userQuizAnswer', 'user_quiz_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo('App\User','user_id','id');
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

    public function getUserGradeAttribute() {
        $quiz_lesson = QuizLesson::where('id', $this->quiz_lesson_id)->first();
        $quiz_questions = quiz_questions::where('quiz_id',$quiz_lesson->quiz_id)->pluck('question_id');
        $quiz_questions_total = Questions::whereIn('id',$quiz_questions)->pluck('mark');
        $quiz_questions_sum= array_sum($quiz_questions_total->toArray());
        if($this->grade == $quiz_questions_sum)
            $return = $quiz_lesson->grade;
        else
            $return = round(($this->grade*$quiz_lesson->grade)/$quiz_questions_sum,1);

        return $return;
    }

    public static function gradeMethod($quiz_lesson,$user)
    {
        // dd($user);
        $grading_method_id=  QuizLesson::where('id',$quiz_lesson->id)->pluck('grading_method_id');
        $attemps= userQuiz::where('user_id',$user->id)->where('quiz_lesson_id',$quiz_lesson->id);
        // return($attemps->get());
        $grade=0;
        $i=0;
        foreach($attemps->get() as $attemp)
        {
            $gradeAttemp[$i]=0;
            $user_quiz_answers=UserQuizAnswer::where('user_quiz_id',$attemp->id)->where('force_submit',1)->get();
            foreach($user_quiz_answers as $user_quiz_answer)
                $gradeAttemp[$i]+= $user_quiz_answer->user_grade;
            $i++;
        }
        switch ($grading_method_id[0]){
            case 1: //first
                $grade=$gradeAttemp[0];
                break;
            case 2 : //last
                $grade=last($gradeAttemp);
                break;
            case 3 : // average
                $grade=array_sum($gradeAttemp)/count($gradeAttemp);
                break;
            case 4 : // highest
                $grade= max($gradeAttemp);
                break;
            case 5 :  //lowest
                $grade= min($gradeAttemp);
                break;
        }
        return $grade;
    }
    
    public function getGradeAttribute($value)
    {
        if(!is_null($value))
            $content = round($value , 2);
        return $content;
    }
}
