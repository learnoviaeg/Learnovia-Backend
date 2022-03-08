<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WorkingDay extends Model
{
    public function getStatusAttribute()
    {
        if($this->attributes['status'])
            return True;
        return False;
    }
}
