<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SecondaryChain extends Model
{
    protected $fillable = ['user_id', 'role_id', 'group_id', 'lesson_id' ,'enroll_id','course_id'];

    public function Teacher()
    {
        return $this->hasOne('App\User','id','user_id');
    }

    public function Courses()
    {
        return $this->hasOne('App\Course','id','course_id');
    }
    public function Class()
    {
        return $this->hasOne('App\Classes','id','group_id');
    }
    public function Enroll()
    {
        return $this->hasOne('App\Enroll','id','enroll_id');
    }

    public function Lesson()
    {
        return $this->hasOne('App\Lesson','id','lesson_id');
    }

    public function materials()
    {
        return $this->hasMany('App\Material','lesson_id' , 'lesson_id');
    }

    public function QuizLesson()
    {
        return $this->hasMany('Modules\QuestionBank\Entities\QuizLesson', 'lesson_id', 'lesson_id');
    }

    public function AssignmentLesson()
    {
        return $this->hasMany('Modules\Assigments\Entities\AssignmentLesson', 'lesson_id', 'lesson_id');
    }
    
    public function H5PLesson()
    {
        return $this->hasMany('App\h5pLesson', 'lesson_id', 'lesson_id');
    }

    public function virtual()
    {
        return $this->hasMany('Modules\Bigbluebutton\Entities\BigbluebuttonModel', 'class_id', 'group_id');
    }
}
