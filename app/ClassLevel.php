<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClassLevel extends Model
{

    protected $fillable = ['year_level_id' , 'class_id'];
    public $primaryKey = 'id';

    public function classlevel()
    { 
        return $this->belongsToMany('App\Classes');
    }
}
