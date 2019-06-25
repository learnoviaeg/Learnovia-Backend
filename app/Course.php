<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = [
        'name' , 'description' ,'hide' , 'start_date' , 'end_date'
    ];
}
