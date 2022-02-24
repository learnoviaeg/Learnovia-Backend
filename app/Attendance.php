<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = ['name','attendance_type','year_id', 'type_id', 'segment_id', 'level_id', 'course_id',
        'is_graded','grade_cat_id','start_date','end_date','min_grade','gradeToPass','max_grade', 'created_by', 'attendance_status'
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

    public function levels()
    {
        return $this->belongsToMany('App\Level', 'attendance_levels','attendance_id','level_id')->withTimestamps();
    }

    public function courses()
    {
        return $this->belongsToMany('App\Course', 'attendance_courses', 'attendance_id', 'course_id');
    }

    public function gradeCategory()
    {
        return $this->belongsToMany('App\GradeCategory', 'attendance_courses', 'attendance_id', 'grade_cat_id');
    }

    public function attendanceType()
    {
        return $this->belongsTo('App\AttendanceType', 'attendance_type_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'created_by', 'id');
    }

    public function attendanceStatus()
    {
        return $this->belongsTo('App\AttendanceStatus', 'attendance_status', 'id');
    }
}
