<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AcademicType extends Model
{
    protected $fillable = ['name','segment_no'];

    public function AC_year(){
        return $this->belongsToMany('App\AcademicYear' , 'academic_year_types','academic_year_id'
            ,'academic_type_id' );
}
}
