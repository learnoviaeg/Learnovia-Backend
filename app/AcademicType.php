<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicType extends Model
{
    use SoftDeletes;

    protected $fillable = ['name' , 'segment_no','academic_year_id'];

    public function year()
    { 
        return $this->hasone('App\AcademicYear','id','academic_year_id');
    }

    protected $hidden = [
        'created_at','updated_at','pivot'
    ];

    // public function Actypeyear() // this is right
    // {
    //     return $this->belongsTo('App\AcademicYearType', 'id', 'academic_type_id');
    // }
}
