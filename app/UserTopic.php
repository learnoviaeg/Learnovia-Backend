<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserTopic extends Model
{
    protected $fillable = ['topic_chain' , 'user_id'];
    
}
