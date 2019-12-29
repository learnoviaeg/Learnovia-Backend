<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class status extends Model
{
    protected $fillable = ['name'];
    public function UserAssigment()
    {
        return $this->hasMany('Modules\Assigments\Entities\UserAssigment', 'status_id', 'id');
    }

    public function Payment()
    {
        return $this->hasMany('App\Payment', 'status_id', 'id');
    }
}
