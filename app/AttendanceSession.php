<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AttendanceSession extends Model
{
    protected $fillable = ['name', 'attendance_id', 'class_id', 'from', 'to', 'created_by'];

    public function class()
    {
        return $this->belongsTo('App\classes', 'class_id', 'id');
    }

    public function attendance()
    {
        return $this->belongsTo('App\Attendance', 'attendance_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'created_by', 'id');
    }
}
