<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Segment extends Model
{
    protected $fillable = ['name'];

    public function Segment_class(){
        return $this->belongsToMany('App\ClassLevel', 'segment_classes','segment_id','class_level_id');
    }
    protected $hidden = [
        'created_at','updated_at'
    ];
}
