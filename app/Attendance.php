<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = ['name','attendance_type','year_id', 'type_id', 'segment_id', 'level_id', 'course_id',
        'is_graded','grade_cat_id','start_date','end_date','min_grade','gradeToPass','max_grade', 'created_by'
    ];
    
    public function year()
    { 
        return $this->belongsTo('App\AcademicYear','year_id','id');
    }

    public function type()
    {
        return $this->belongsTo('App\AcademicType', 'type_id', 'id');
    }

    public function segment()
    {
        return $this->belongsTo('App\AcademicType', 'segment_id', 'id');
    }

    public function level()
    {
        return $this->belongsTo('App\AcademicType', 'level_id', 'id');
    }

    public function course()
    {
        return $this->belongsTo('App\AcademicType', 'course_id', 'id');
    }

    public function attendanceType()
    {
        return $this->belongsTo('App\AttendanceType', 'attendance_type_id', 'id');
    }

    public function gradeCategory()
    {
        return $this->belongsTo('App\GradeCategory', 'grade_cat_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'created_by', 'id');
    }
}
