<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class userAnnouncement extends Model
{
    protected $fillable = ['announcement_id' , 'user_id'];
}
