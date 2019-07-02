<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
    protected $fillable = ['name'];
    public $primaryKey = 'id';

    public function classes()
    {
        return $this->belongsToMany('App\ClassLevel');
    }
}
