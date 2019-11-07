<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class scale extends Model
{

    protected $fillable = ['name' , 'formate'];

    public function GradeItems()
    {
        return $this->hasMany('App\GradeItems');
    }
    public function UserGrade()
    {
        return $this->hasMany('App\UserGrade');
    }
}
