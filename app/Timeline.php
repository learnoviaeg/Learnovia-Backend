<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Course;
use App\Classes;
use App\Level;

class Timeline extends Model
{
    protected $fillable = [
        'item_id', 'name','start_date','due_date','publish_date','course_id','class_id','level_id','lesson_id','type'
    ];

    protected $appends = ['class','course','level'];

    public function getClassAttribute(){
        $class = Classes::find($this->class_id);
        return isset($class)?$class->name:null ;   
    }
            
    public function getCourseAttribute(){
        $course = Course::find($this->course_id);
        return  isset($course)?$course->name:null;
    }

    public function getLevelAttribute(){
        $course = Level::find($this->course_id);
        return  isset($course)?$course->name:null;
    }
}
