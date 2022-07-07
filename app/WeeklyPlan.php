<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WeeklyPlan extends Model
{
    protected $guarded = [];
    protected $hidden = ['created_at','updated_at'];

    public function course(){
        return $this->belongsTo('App\Course','course_id','id');
    }

    public function user(){
        return $this->belongsTo('App\User','added_by','id');
    }

}
