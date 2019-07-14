<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CourseSegment extends Model
{
    protected $fillable = ['course_id' , 'segment_class_id'];
    public function courses()
    {
        return $this->hasMany('App\Course' , 'id' , 'course_id');
    }

    public function segmentClasses()
    {
        return $this->hasMany('App\SegmentClass' , 'id' , 'segment_class_id');
    }
}
