<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class YearLevel extends Model
{
    protected $fillable = ['level_id' , 'academic_year_type_id'];
    public function levels(){
        return $this->hasMany('App\Level' , 'id' , 'level_id');
    }
    public function classLevels(){
        return $this->hasMany('App\ClassLevel');
    }

    public function yearType(){
        return $this->hasMany('App\AcademicYearType' , 'id');
    }

    public static function checkRelation($yearType , $level){
        $yearlevel = self::whereLevel_id($level)->whereAcademic_year_type_id($yearType)->first();
        if($yearlevel == null){
            $yearlevel = self::create([
                'level_id' => $level,
                'academic_year_type_id' => $yearType,
            ]);
        }
        return $yearlevel;
    }
}
