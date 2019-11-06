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
    public function percentage(){
        $grade_items= $this->GradeItems;
        $result=100;
        foreach ($grade_items as $Item){
            $result -=  $Item->override;
        }
        return $result;
    }
    public function naturalTotal(){
        $grade_items= $this->GradeItems->where('override','!=',0);
        $total=$this->total();
        foreach ($grade_items as $grades){
            $total-=$grades->grademax;
        }
        return $total;
    }
}
