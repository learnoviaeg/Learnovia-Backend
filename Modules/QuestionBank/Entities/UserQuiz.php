<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;

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
}
