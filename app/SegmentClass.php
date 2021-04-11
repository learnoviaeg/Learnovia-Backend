<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SegmentClass extends Model
{
    protected $fillable = ['class_level_id','segment_id'];

    protected $hidden = [
        'created_at','updated_at'
    ];
    public static function GetClasseLevel($classLevID){
        $check = self::where('class_level_id',$classLevID)->pluck('id');
        return $check;
    }

    public function courseSegment()
    {
        return $this->hasMany('App\CourseSegment' , 'segment_class_id' , 'id');
    }

    public function segments(){
        return $this->hasMany('App\Segment' , 'id' , 'segment_id');
    }

    public function classLevel(){
        return $this->hasMany('App\ClassLevel' , 'id' , 'class_level_id');
    }

    public static function checkRelation($classLevel , $segment){
        $segmentClass = self::whereSegment_id($segment)->whereClass_level_id($classLevel)->first();
        if ($segmentClass == null){
            $segmentClass = self::create([
                'segment_id' => $segment,
                'class_level_id' => $classLevel,
            ]);
        }
        return $segmentClass;
    }

}
