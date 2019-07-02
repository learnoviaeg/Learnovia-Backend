<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AcademicYearType extends Model
{
    protected $table = 'academic_year_types';
    public $primaryKey = 'id';

    public function academicyear()
    { 
        return $this->belongsToMany('App\AcademicYear');
    }
}
