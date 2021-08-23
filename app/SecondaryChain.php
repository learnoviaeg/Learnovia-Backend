<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SecondaryChain extends Model
{
    protected $fillable = ['user_id', 'role_id', 'group_id', 'lesson_id' ,'enroll_id'];

    public function Teacher()
    {
        return $this->hasOne('App\User','id','user_id');
    }

}
