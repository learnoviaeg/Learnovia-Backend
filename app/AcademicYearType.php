<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AcademicYearType extends Model
{
    protected $fillable = ['academic_year_id' ,'academic_type_id'];
    public function academicyear()
    {
        return $this->belongsToMany('App\AcademicYear');
    }
}