<?php

namespace Modules\Attendance\Entities;

use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    protected $fillable = ['ip_address','session_id','student_id','status','taker_id','taken_at','entered_date','left_date'
                        ,'type','attendnace_type'];

    public function User()
    {
        return $this->belongsTo('App\User', 'student_id' , 'id' );
    }

}
