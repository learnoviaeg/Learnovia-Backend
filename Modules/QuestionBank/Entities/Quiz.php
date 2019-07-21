<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;

class quiz extends Model
{
    protected $fillable = ['name','course_id'];
    protected $hidden = [
        'created_at', 'updated_at'
    ];

    public function Question()
    {
        return $this->belongsToMany('Modules\QuestionBank\Entities\Questions', 'quiz_questions', 'quiz_id', 'question_id');
    }


}
