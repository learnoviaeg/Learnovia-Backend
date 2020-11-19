<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LastAction extends Model
{
    protected $fillable = [
    'user_id'
    ,'name'
    ,'method'
    ,'uri'
    ,'resource'
     ,'date'];
}
