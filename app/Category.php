<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name'];

    protected $hidden = [
        'created_at', 'updated_at',
    ];


    public function courses(){
        return $this->hasMany('App\Course');
    }
}
