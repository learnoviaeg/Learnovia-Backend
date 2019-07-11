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
 
}