<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    protected $fillable = ['email' , 'token'];
    protected $hidden = [
        'created_at','updated_at'
    ];
}
