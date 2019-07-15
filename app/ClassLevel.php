<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClassLevel extends Model
{
    protected $fillable = ['year_level_id' , 'class_id'];

    public static function GetClass($row)
    {
        $check = self::where('class_id',$row['class_id'])->pluck('id')->first();
        return $check;
    }


}