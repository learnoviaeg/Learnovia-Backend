<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $fillable = ['name','default'];
    protected $hidden = ['created_at', 'updated_at'];
}
