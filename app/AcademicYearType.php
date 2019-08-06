<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AcademicYearType extends Model
{
    protected $fillable = ['academic_year_id' ,'academic_type_id'];

    public function academicyear()
    {
        return $this->belongsTo('App\AcademicYear' , 'academic_year_id' , 'id');
    }

    public function academictype()
    {
        return $this->belongsTo('App\AcademicType' , 'academic_type_id' , 'id');
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

    public static function get_yaer_type_by_year ($academic_year)
    {
        return self::where('academic_year_id', $academic_year)->pluck('id')->first();
    }

    public static function get_yaer_type_by_type ($academic_type)
    {
        return self::where('academic_type_id', $academic_type)->pluck('id')->first();
    }

    protected $hidden = [
        'created_at','updated_at','pivot',
    ];
}
