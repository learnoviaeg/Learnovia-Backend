<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AcademicYearType extends Model
{
    protected $fillable = ['academic_year_id' ,'academic_type_id'];

    public function academicyear()
    {
        return $this->hasMany('App\AcademicYear' , 'id' , 'academic_year_id');
    }

    public function academictype()
    {
        return $this->hasMany('App\AcademicType' , 'id' , 'academic_type_id');
    }
    public function yearLevel(){
        return $this->hasMany('App\YearLevel');
    }

    public static function checkRelation($year , $type){
        $yeartype = self::whereAcademic_year_id($year)->whereAcademic_type_id($type)->first();
        if($yeartype == null){
            $yeartype =  self::create([
                'academic_year_id' => $year,
                'academic_type_id' => $type,
            ]);
        }
        return $yeartype;
    }
}