<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;

class QuestionsAnswer extends Model
{
    protected $fillable = ['content','And_why_answer','true_false','match_a','match_b','is_true','question_id'];
    protected $hidden = [
        'created_at', 'updated_at','question_id'
    ];

    public function question()
    {
        return $this->belongsTo('Modules\QuestionBank\Entities\Questions', 'question_id', 'id');
    }
}
