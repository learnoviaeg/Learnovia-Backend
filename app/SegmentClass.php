<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SegmentClass extends Model
{
    protected $fillable = ['class_level_id','segment_id'];
    

    public function course_segment()
    {
        return $this->hasMany('App\CourseSegment','segment_class_id','id');
    } 

    public function classes(){
        return $this->belongsToMany('App\Classes', 'class_level','class_id','id');
    }
    public function classelevel(){
        return $this->hasMany('App\ClassLevel');
    }

}