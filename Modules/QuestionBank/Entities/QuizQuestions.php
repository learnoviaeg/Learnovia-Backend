<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;

class quiz_questions extends Model
{
    protected $fillable = ['question_id','quiz_id'];
    protected $hidden = [
        'created_at','updated_at'
    ];

    public function Question()
    {
        return  $this->hasMany('App\Questions', 'id', 'question_id');
    }
}
