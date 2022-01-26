<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SessionLog extends Model
{
    protected $fillable = ['session_id','taken_by','user_id','status'];

    public function user()
    {
        return $this->belongsTo('App\User' , 'user_id' , 'id');
    }

    public function takenBy()
    {
        return $this->belongsTo('App\User' , 'taken_by' , 'id');
    }
}
