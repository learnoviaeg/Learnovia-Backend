<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;

class quiz_questions extends Model
{
    protected $fillable = ['question_id','quiz_id','grade_details'];
    protected $hidden = [
        'created_at','updated_at'
    ];

    public function Question()
    {
        return  $this->hasMany('Modules\QuestionBank\Entities\Questions', 'id', 'question_id');
    }

    public function getGradeDetailsAttribute()
    {
        return json_decode($this->attributes['grade_details']);
    }
}
