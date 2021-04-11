<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;

class QuizOverride extends Model
{
    protected $fillable = [ 
        'user_id',
        'quiz_lesson_id',
        'start_date',
        'due_date',
        'attemps'
    ];


    public function users()
    {
        return $this->belongsTo('App\User','user_id','id');
    }

    public function quizLesson()
    {
        return $this->belongsTo('Modules\QuestionBank\Entities\QuizLesson', 'quiz_lesson_id', 'id');
    }
}
