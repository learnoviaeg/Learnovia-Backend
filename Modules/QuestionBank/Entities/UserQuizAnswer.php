<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;

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
        return json_decode($this->attributes['user_answers']);
    }
}
