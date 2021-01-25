<?php

namespace Modules\Bigbluebutton\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Classes;
use App\Course;

class BigbluebuttonModel extends Model
{
    protected $fillable = ['name','class_id','course_id','attendee_password','moderator_password','duration','meeting_id','started','is_recorded'
    ,'actutal_start_date','status','actual_duration','join_url','type','host_id'];

    protected $appends = ['class','course'];

    public function getClassAttribute(){
        $class = Classes::find($this->class_id);
        return isset($class)?$class->name:null ;   
    }
            
    public function getCourseAttribute(){
        $course = Course::find($this->course_id);
        return  isset($course)?$course->name:null;
    }
}
