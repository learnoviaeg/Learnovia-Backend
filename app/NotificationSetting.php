<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $guarded=[];

    public function getRolesAttribute()
    {
        $content=$this->attributes['roles'];
        if(isset($content))
            return json_decode($content);
        return $content;
    }

    public function getUsersAttribute()
    {
        $content=$this->attributes['users'];
        if(isset($content))
            return json_decode($content);
        return $content;
    }
}
