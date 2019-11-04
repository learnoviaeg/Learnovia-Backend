<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    protected $fillable = ['id','name','current'];
    public function AC_Type()
    {
        return $this->belongsToMany('App\AcademicType', 'academic_year_types', 'academic_year_id','academic_type_id');
    }
    public static function Get_current()
    {
        $current= self::where('current',1)->first();
        return $current;
    }

    protected $hidden = [
        'created_at','updated_at'
    ];

    public function Acyeartype()
    {
        return $this->belongsTo('App\AcademicYearType', 'id', 'academic_year_id');
    }
    
    public function YearType()
    {
        return $this->hasMany('App\AcademicYearType', 'academic_year_id' , 'id');
    }
}
