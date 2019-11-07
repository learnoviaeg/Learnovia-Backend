<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Letter extends Model
{
    protected $fillable = ['name' , 'formate'];
    public function UserGrade()
    {
        return $this->hasMany('App\UserGrade');
    }

    public function course()
    {
        return $this->belongsToMany('App\Course', 'course_id', 'id');
    }

}
