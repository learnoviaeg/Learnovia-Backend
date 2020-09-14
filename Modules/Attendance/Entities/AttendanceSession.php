<?php

namespace Modules\Attendance\Entities;
use Carbon\Carbon;
use App\Course;
use App\Classes;

use Illuminate\Database\Eloquent\Model;

class AttendanceSession extends Model
{
    protected $fillable = ['name','type','graded','grade_item_id','course_id','class_id','from','to','taker_id','start_date'];

    public function class(){
        return $this->belongsTo('App\Classes');
    }

    public function course(){
        return $this->belongsTo('App\Course');
    }
   
}

