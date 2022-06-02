<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Traits\Auditable;

class AttendanceSession extends Model
{
    use Auditable;

    protected $fillable = ['name', 'attendance_id', 'class_id','course_id', 'from', 'to', 'created_by', 'start_date','session_id'];

    protected $dispatchesEvents = [
        'created' => \App\Events\SessionCreatedEvent::class,
    ];

    public function class()
    {
        return $this->belongsTo('App\Classes', 'class_id', 'id');
    }

    public function course()
    {
        return $this->belongsTo('App\Course', 'course_id', 'id');
    }

    public function attendance()
    {
        return $this->belongsTo('App\Attendance', 'attendance_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'created_by', 'id');
    }

    public function getStartDateAttribute()
    {
        return Carbon::parse($this->attributes['start_date'])->translatedFormat('l j F Y H:i:s');
    }

    public function getTakenAttribute()
    {
        if($this->attributes['taken'])
            return True;
        return False;
    }

    public function session_logs()
    {
        return $this->hasMany('App\SessionLog','session_id','id');
    }

    // start function get name and value f attribute
    public static function get_year_name($old, $new)
    {
        $course_id        = $new->course_id;
        $segment_id       = Course::where('id', $course_id)->first()->segment_id;
        $segment          = Segment::where('id', $segment_id)->first();
        $year_id          = $segment->academic_year_id;
        return $year_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_type_name($old, $new)
    {
        $course_id    = $new->course_id;
        $segment_id   = Course::where('id', $course_id)->first()->segment_id;
        $segment      = Segment::where('id', $segment_id)->first();
        $type_id      = $segment->academic_type_id;
        return $type_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_level_name($old, $new)
    {
        $course_id    = $new->course_id;
        $level_id   = Course::where('id', $course_id)->first()->level_id;
        return $level_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_class_name($old, $new)
    {
        $class_id   = $new->class_id;
        return $class_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_segment_name($old, $new)
    {
        $course_id    = $new->course_id;
        $segment_id   = Course::where('id', $course_id)->first()->segment_id;
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