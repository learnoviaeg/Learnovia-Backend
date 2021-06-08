<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\QuestionBank\Entities\Questions;

class userQuizAnswer extends Model
{
    protected $fillable = [
        'user_quiz_id','question_id','user_answers','content','user_grade','feedback','answered','force_submit','right'
    ];

    public function Question()
    {
        return $this->belongsTo('Modules\QuestionBank\Entities\Questions', 'question_id', 'id');
    }

    public function getUserAnswersAttribute()
    {
        $user_answers=json_decode($this->attributes['user_answers']);
        $question=Questions::find($this->attributes['question_id']);
        if(isset($user_answers)){
            // if($question->question_type_id == 2){
            //     foreach($user_answers as $con)
            //     {
            //         if($con->is_true == 1){
            //             $con->is_true=True;
            //             continue;
            //         }
            //         $con->is_true=False;
            //     }
            // }
            if($question->question_type_id == 1){
                if($user_answers->is_true == 1)
                    $user_answers->is_true=True;

                else if($user_answers->is_true == 0)
                    $user_answers->is_true=False;
                
                else
                    $user_answers->is_true=null;
            }
        }
        return $user_answers;
    }
}
