<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;

class GradingMethod extends Model
{
    protected $fillable = ['name'];
    public function QuizLesson()
    {
        return $this->hasMany('Modules\QuestionBank\Entities\QuizLesson', 'grading_method_id', 'id');
    }
}
