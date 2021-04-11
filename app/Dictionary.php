<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Dictionary extends Model
{
    protected $fillable = ['key','value','language'];
    protected $hidden = ['created_at', 'updated_at'];
}
