<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    protected $fillable = ['name'];

    public function AC_Type(){
        return $this->belongsToMany('App\AcademicType', 'academic_year_types','academic_year_id'
            ,'academic_type_id');

    }
}
