<?php

namespace Modules\Attendance\Entities;

use Illuminate\Database\Eloquent\Model;

class AttendanceStatus extends Model
{
    protected $fillable = ['letter' , 'descrption' , 'grade' , 'visible' , 'attendance_id'];

    public static function defaultStatus(){
        return [
            [
                'letter' =>'L',
                'descrption' => 'This Status for late students',
                'grade' => 0,
            ],
            [
                'letter' =>'A',
                'descrption' => 'This Status for Absent students',
                'grade' => 0,
            ],
            [
                'letter' =>'E',
                'descrption' => 'This Status for Excuse students',
                'grade' => 1,
            ],
            [
                'letter' =>'P',
                'descrption' => 'This Status for Present students',
                'grade' => 2,
            ]
        ];
    }
    public function Attendence()
{
    return $this->belongsTo('Modules\Attendance\Entities\Attendance', 'attendance_id' , 'id' );
}
    public function user()
    {
        return $this->belongsTo('App\User', 'user_id' , 'id' );
    }

}
