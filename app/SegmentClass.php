<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SegmentClass extends Model
{
    protected $fillable = ['class_level_id','segment_id'];
    
    public function GetClasseLevel($classLevID){
        $check = self::where('class_level_id',$classLevID)->pluck('id')->first();
        return $check;
    }

}