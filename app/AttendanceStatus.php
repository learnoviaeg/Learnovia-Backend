<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AttendanceStatus extends Model
{
    protected $fillable = ['name', 'values'];

    public function getValuesAttribute()
    {
        $content= json_decode($this->attributes['values']);
        return $content;
    }
}
