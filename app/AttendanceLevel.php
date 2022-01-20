<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AttendanceLevel extends Model
{
    protected $fillable = ['attendance_id', 'level_d'];

    public function level()
    {
        return $this->belongsTo('App\AcademicType', 'level_id', 'id');
    }
}
