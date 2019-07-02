<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClassLevel extends Model
{

    public function Segment_class(){
        return $this->belongsToMany('App\Segment', 'segment_classes','class_level_id'
            ,'segment_id');

    }
    public function classlevel()
    {
        return $this->belongsToMany('App\Classes');
    }
}
