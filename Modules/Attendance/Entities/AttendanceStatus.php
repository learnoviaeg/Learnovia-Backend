<?php

namespace Modules\Attendance\Entities;

use Illuminate\Database\Eloquent\Model;

class AttendanceStatus extends Model
{
    protected $fillable = ['attendance_id','letter'];
    public function Attendence()
    {
        return $this->belongsTo('Modules\Attendance\Entities\Attendance', 'attendance_id' , 'id' );
    }

}
