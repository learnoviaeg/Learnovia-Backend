<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AttendanceSession extends Model
{
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
}