<?php

namespace Modules\Attendance\Entities;

use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    protected $fillable = ['ip_address','session_id','student_id','status_id','taker_id','taken_at'];

    public function User()
    {
        return $this->belongsTo('App\User', 'student_id' , 'id' );
    }
    public function session()
    {
        return $this->belongsTo('Modules\Attendance\Entities\AttendanceSession', 'session_id' , 'id' );
    }
    public function status()
    {
        return $this->hasOne('Modules\Attendance\Entities\AttendanceStatus', 'id' , 'status_id' );
    }
    
    public function getPrecentageStatus($count)
    {
        $status= $this->status;
        $status->precentage=100/$count;
        return $status;
    }
}
