<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GradeCategory extends Model
{
    protected $fillable = ['name','course_segment_id','parent','aggregation','aggregatedOnlyGraded','hidden' , 'id_number'];
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
        return $this->hasMany('App\GradeItems','grade_category','id');
    }

    public function depth(){
        if($this->Parents == null)
            return 1;
        return 1 + $this->Parents->depth();
    }

    public function path(){
        if($this->Parents == null)
            return $this;
        $result = collect();
        $category = $this;
        while(true){
            $result->push($category);
            if($category->Parents == null)
                break;
            $category = $category->Parents;
        }
        return $result;
    }
}
