<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Questions extends Model
{
    protected $fillable=['course_id','created_by','q_cat_id','question_id','question_type','text','type'];

    public function T_F_question()
    {
        return $this->hasMany('App\Q_T_F','question_id','id');
    }

    public function MCQ_question()
    {
        return $this->hasMany('App\Q_MCQ','question_id','id');
    }

    public function Essay_question()
    {
        return $this->hasMany('App\Q_Essay','question_id','id');
    }

    // public function Comprehension_question()
    // {
    //     return $this->hasMany('App\Questions', 'id');
    // }
}
