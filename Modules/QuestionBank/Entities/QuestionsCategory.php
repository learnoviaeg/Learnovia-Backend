<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;

class QuestionsCategory extends Model
{
    protected $fillable = ['name'];

    public function questions()
    {
        return $this->hasMany('Modules\QuestionBank\Entities\Questions', 'question_category_id', 'id');
    }
}
