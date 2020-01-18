<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;

class userQuizAnswer extends Model
{
    protected $fillable = [
        'user_quiz_id','question_id','answer_id',
        'and_why','mcq_answers_array','choices_array','content','user_grade','feedback','answered'
    ];

    public function Question()
    {
        return $this->belongsTo('Modules\QuestionBank\Entities\Questions', 'question_id', 'id');
    }
}
