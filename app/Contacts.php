<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Contacts extends Model
{
    protected $fillable = ['Person_id','Friend_id'];
    protected $hidden = [
        'created_at','updated_at'
    ];

}
