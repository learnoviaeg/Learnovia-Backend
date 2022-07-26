<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use Auditable, SoftDeletes;

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
        return $this->belongsToMany('App\Level', 'attendance_levels','attendance_id','level_id');
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

    // start function get name and value f attribute
    public static function get_year_name($old, $new)
    {
        $year_id   = $new->year_id;
        return $year_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_type_name($old, $new)
    {
        $type_id   = $new->type_id;
        return $type_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_level_name($old, $new)
    {
        $level_id   = $new->level_id;
        return $level_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_class_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_segment_name($old, $new)
    {
        $segment_id   = $new->segment_id;
        return $segment_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_course_name($old, $new)
    {
        $course_id   = $new->course_id;
        return $course_id;
    }
    // end function get name and value attribute
}
