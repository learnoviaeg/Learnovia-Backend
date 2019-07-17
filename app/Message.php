<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['text','From','To','about','seen','file','deleted'];
    public static $DELETE_FROM_ALL = 1;
    public static $DELETE_FOR_RECEIVER = 2;
    public static $DELETE_FOR_SENDER = 3;
    protected $hidden = [
        'created_at','updated_at'
    ];
}
