<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClassLevel extends Model
{
    protected $fillable = ['year_level_id' , 'class_id'];
    public $primaryKey = 'id';

    public function Segment_class()
    {
        return $this->belongsTo('App\SegmentClass');
    }

    public function classes()
    {
        return $this->hasMany('App\Classes');
    }


}