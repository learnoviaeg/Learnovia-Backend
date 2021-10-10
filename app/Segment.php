<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Segment extends Model
{
    use SoftDeletes;
    protected $fillable = ['name','current','academic_type_id'];

    public function Segment_class(){
        return $this->belongsToMany('App\ClassLevel', 'segment_classes','segment_id','class_level_id');
    }

    public function academicType()
    {
        return $this->belongsTo('App\AcademicType', 'academic_type_id', 'id');
    }

    public static function Get_current($type)
    {
        $segment = self::where('academic_type_id', $type)->where('current',1)->first();
        return $segment;
    }

    protected $hidden = [
        'created_at','updated_at'
    ];
}
