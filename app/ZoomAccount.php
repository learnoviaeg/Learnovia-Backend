<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ZoomAccount extends Model
{
    protected $fillable = ['user_id','jwt_token','api_key','api_secret','user_zoom_id','email'];
}
