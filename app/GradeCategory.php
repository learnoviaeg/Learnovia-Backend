<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GradeCategory extends Model
{
    protected $fillable = ['name','course_segment_id','parent','aggregation','aggregatedOnlyGraded','hidden'];
    Public Function Child(){
        return $this->hasMany('App\GradeCategory','parent','id');

    }
    Public Function Parents(){
        return $this->hasOne('App\GradeCategory','id','parent');

    }
    public function CourseSegment()
    {
        return $this->belongsTo('App\CourseSegment', 'course_segment_id', 'id');
    }
    public function GradeItems()
    {
        return $this->hasMany('App\GradeItems');
    }

}
