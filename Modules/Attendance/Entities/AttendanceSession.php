<?php

namespace Modules\Attendance\Entities;

use Illuminate\Database\Eloquent\Model;

class AttendanceSession extends Model
{
    protected $fillable = ['name','type','graded','grade_tem_id','course_id','class_id','from','to','taker_id','start_date'];
}
