<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $fillable = ['user','action','model','data', 'effected_users'];

    public function user()
    {
        return $this->belongsTo('App\User','user','username');
    }
}
