<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Message_Role extends Model
{
    protected $fillable = ['From_Role', 'To_Role'];
    protected $hidden = [
        'created_at','updated_at'
    ];
}
