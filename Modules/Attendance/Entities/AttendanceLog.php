<?php

namespace Modules\Attendance\Entities;

use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    protected $fillable = ['ip_address','session_id','student_id','status_id','taker_id','taken_at'];
}
