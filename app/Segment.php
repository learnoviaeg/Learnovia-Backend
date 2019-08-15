<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Segment extends Model
{
    protected $fillable = ['name','current','academic_type_id'];

    public function Segment_class(){
        return $this->belongsToMany('App\ClassLevel', 'segment_classes','segment_id','class_level_id');
    }

    public static function Get_current($type)
    {
        $year = self::where('academic_type_id', $type)->where('current',1)->first();
        
        return $year;
    }

    protected $hidden = [
        'created_at','updated_at'
    ];
}
