<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Segment extends Model
{
    protected $fillable = ['name','currents'];

    // public function Segment_class(){
    //     return $this->belongsToMany('App\ClassLevel', 'segment_classes','segment_id','class_level_id');
    // }

    public function Segment_class()
    {
        return $this->hasMany('App\SegmentClass', 'segment_id', 'id');
    }

    public  static function Get_current()
    {
        $current= self::where('current',1)->first();
        return $current;
    }
    protected $hidden = [
        'created_at','updated_at'
    ];
}
