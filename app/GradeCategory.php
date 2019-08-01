<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GradeCategory extends Model
{
    protected $fillable = ['name','course_segment_id','parent','aggregation','aggregatedOnlyGraded','hidden'];
    Public Function ParentCategory(){
        return $this->hasMany('App\GradeCategory','parent','id');

    }

}
