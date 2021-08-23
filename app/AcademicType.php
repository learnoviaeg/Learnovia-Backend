<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicType extends Model
{
    use SoftDeletes;

    protected $fillable = ['name' , 'segment_no'];
    // public function AC_year()
    // {
    //     return $this->belongsToMany('App\AcademicYear', 'academic_year_types', 'academic_year_id', 'academic_type_id');
    // }

    // public function yearType() // this is wrong but it used so, i cann't delete it (same type in 2 years no way)
    // { 
    //     return $this->hasMany('App\AcademicYearType','academic_type_id','id');
    // }

    protected $hidden = [
        'created_at','updated_at','pivot'
    ];

    // public function Actypeyear() // this is right
    // {
    //     return $this->belongsTo('App\AcademicYearType', 'id', 'academic_type_id');
    // }
}
