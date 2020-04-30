<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OverrideAssignmentQuiz extends Model
{
    protected $fillable = [
        'user_id',
        'quiz_lesson_id',
        'assignment_lesson_id',
        'start_date',
        'due_date'
    ];

    public function assignmentLesson()
    {
        return $this->belongsTo('Modules\Assigments\Entities\AssignmentLesson', 'assignment_lesson_id', 'id');
    }

    public function users()
    {
        return $this->belongsTo('App\User','user_id','id');
    }

    public function quizLesson()
    {
        return $this->belongsTo('Modules\QuestionBank\Entities\QuizLesson', 'quiz_lesson_id', 'id');
    }


}
