<?php

namespace Modules\Survey\Entities;

use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    protected $fillable = ['name', 'template', 'years', 'types','levels', 'classes', 'courses', 'segments', 'start_date',
     'end_date'];
}
