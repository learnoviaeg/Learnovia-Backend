<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $hidden = [
        'created_at','updated_at'
    ];
    protected $fillable = ['name' , 'index' , 'course_segment_id'];


    // public function courseSegment(){
    //     return $this->belongsTo('App\CourseSegment');
    // }


    public function quiz_lesson()
    {
        return $this->hasMany('App\Modules\QuestionBank\Entities\QuizLesson');
    }
// Wating Magdyyyy

    // public function assigment_lesson()
    // {
    //     return $this->hasMany('App\Modules');
    // }

}
