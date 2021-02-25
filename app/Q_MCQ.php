<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Q_MCQ extends Model
{
    protected $fillable=['text','question_id'];

    public function MCQ_Choices()
    {
        return $this->hasMany('App\Q_MCQ_Choices','q_mcq_id','id');
    }
}
