<?php

namespace Modules\Attendance\Entities;

use Illuminate\Database\Eloquent\Model;

class AttendanceSession extends Model
{
    protected $fillable = ['attendance_id','taker_id','date','last_time_taken','duration','course_segment_id'];
}
