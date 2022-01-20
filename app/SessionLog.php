<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SessionLog extends Model
{
    protected $fillable = ['session_id','taken_by','user_id','status'];
}
