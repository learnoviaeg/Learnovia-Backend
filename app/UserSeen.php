<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserSeen extends Model
{
    protected $fillable = [
        'user_id', 'item_id', 'type', 'lesson_id', 'count'
    ];
}
