<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = ['name' , 'category_id'];
    protected $hidden = [
        'created_at', 'updated_at',
    ];
}
