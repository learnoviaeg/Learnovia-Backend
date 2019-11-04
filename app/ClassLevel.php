<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClassLevel extends Model
{
    protected $fillable = ['year_level_id', 'class_id'];

    public static function GetClass($row)
    {
        $check = self::where('class_id', $row)->pluck('id')->first();
        return $check;
    }

    public static function GetClassLevel($class_id)
    {
        $check = self::where('class_id', $class_id)->pluck('id');
        return $check;
    }

    public static function GetClassLevelid($yaer_level_id)
    {
        $check = self::where('year_level_id', $yaer_level_id)->pluck('id');
        return $check;
    }

    public function classes()
    {
        return $this->hasMany('App\Classes', 'id', 'class_id');
    }

    public function yearLevels()
    {
        return $this->hasMany('App\YearLevel', 'id', 'year_level_id');
    }

    public function segmentClass()
    {
        return $this->hasMany('App\SegmentClass' , 'class_level_id' , 'id');
    }

    public static function checkRelation($class, $yearlevel)
    {
        $classlevel = self::whereClass_id($class)->whereYear_level_id($yearlevel)->first();
        if ($classlevel == null) {
            $classlevel = self::create([
                'class_id' => $class,
                'year_level_id' => $yearlevel,
            ]);
        }
        return $classlevel;
    }

    protected $hidden = [
        'created_at', 'updated_at'
    ];
}
