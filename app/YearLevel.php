<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class YearLevel extends Model
{
    protected $fillable = ['level_id' , 'academic_year_type_id'];
}
