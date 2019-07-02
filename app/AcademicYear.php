<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    protected $fillable = [
        'name'
    ];
    public $primaryKey = 'id';
    public function academicyeartype()
    {
        return $this->belongsToMany('App\AcademicYearType');
    }
}
