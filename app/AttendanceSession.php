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

    public function getFromAttribute()
    {
        return Carbon::parse($this->attributes['from'])->translatedFormat('l j F Y H:i:s');
    }

    public function getToAttribute()
    {
        return Carbon::parse($this->attributes['to'])->translatedFormat('l j F Y H:i:s');
    }
}