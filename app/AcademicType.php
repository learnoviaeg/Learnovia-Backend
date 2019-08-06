<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AcademicType extends Model
{
    protected $fillable = ['name' , 'segment_no'];
    public function AC_year()
    {
        return $this->belongsToMany('App\AcademicYear', 'academic_year_types', 'academic_type_id', 'academic_year_id');
    }

    public function yearType(){
        return $this->hasMany('App\AcademicYearType');
    }
   
    protected $hidden = [
        'created_at','updated_at','pivot'
    ];
}
