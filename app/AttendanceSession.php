<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AttendanceSession extends Model
{
    protected $fillable = ['name', 'attendance_id', 'class_id', 'from', 'to', 'created_by', 'start_date'];

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

    public function getStartDateAttribute()
    {
        return Carbon::parse($this->attributes['start_date'])->translatedFormat('l j F Y H:i:s');
    }
}