<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Timeline extends Model
{
    protected $fillable = [
        'item_id', 'name','start_date','due_date','publish_date','course_id','class_id','level_id','lesson_id','type','visible','overwrite_user_id'
    ];

    protected $appends = ['started'];

    public function getStartedAttribute(){
        $started = true;
        if((Auth::user()->can('site/course/student') && $this->publish_date > Carbon::now()) || (Auth::user()->can('site/course/student') && $this->start_date > Carbon::now()))
            $started = false;

        return $started;  
    }

    public function class(){
        return $this->belongsTo('App\Classes');
    }

    public function course(){
        return $this->belongsTo('App\Course');
    }

    public function level(){
        return $this->belongsTo('App\Level');
    }
}
