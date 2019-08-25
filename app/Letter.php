<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Letter extends Model
{
    protected $fillable = ['letter','lowerBoundary','course_id'];
    public function UserGrade()
    {
        return $this->hasMany('App\UserGrade');
    }

    public function course()
    {
        return $this->belongsToMany('App\Course', 'course_id', 'id');
    }

}
