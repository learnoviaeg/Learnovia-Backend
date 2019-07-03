<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Segment extends Model
{
    protected $fillable = ['name'];

    public function Segment_class(){
        return $this->belongsToMany('App\ClassLevel', 'segment_classes','class_level_id','segment_id');
    }
}
