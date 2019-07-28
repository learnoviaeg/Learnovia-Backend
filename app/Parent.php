<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Parents extends Model
{
    protected $fillable = ['parent_id' , 'child_id'];

    protected $hidden = [
        'created_at', 'updated_at'
    ];

}
