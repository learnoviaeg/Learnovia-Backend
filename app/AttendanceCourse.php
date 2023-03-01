<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AttendanceCourse extends Model
{
    protected $fillable = ['attendance_id', 'course_id', 'grade_cat_id'];

    public function gradeCategory()
    {
        return $this->belongsTo('GradeCategory', 'grade_cat_id', 'id');
    }

    public function course()
    {
        return $this->belongsTo('App\Course', 'course_id', 'id');
    }
    
}
