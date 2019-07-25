<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = ['name' , 'index' ];
    public function courseSegment(){
        return $this->belongsTo('App\CourseSegment');
    }
    public function assignment()
{
    return $this->belongsToMany('Modules\Assigments\Entities\assignment', 'assigment_lesson', 'lesson_id', 'assigment_id');
}
    protected $hidden = [
        'created_at','updated_at'
    ];
}
