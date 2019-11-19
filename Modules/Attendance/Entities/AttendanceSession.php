<?php

namespace Modules\Attendance\Entities;

use Illuminate\Database\Eloquent\Model;

class AttendanceSession extends Model
{
protected $fillable = ['attendance_id','taker_id','date','last_time_taken','duration','course_segment_id'];

    public function Attendence()
    {
        return $this->belongsTo('Modules\Attendance\Entities\Attendance', 'attendance_id' , 'id' );
    }
    public function Course_Segment()
    {
        return $this->belongsTo('App\CourseSegment', 'course_segment_id' , 'id' );
    }

    public function logs()
    {
        return $this->hasMany('Modules\Attendance\Entities\AttendanceLog','session_id' ,'id');
    }
}
