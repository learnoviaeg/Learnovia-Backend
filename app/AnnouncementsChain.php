<?php

namespace App;
use App\Level;


use Illuminate\Database\Eloquent\Model;

class AnnouncementsChain extends Model
{
    protected $fillable = [
        'announcement_id',
        'year',
        'type',
        'level',
        'class',
        'segment',
        'course',
    ];

    public function level()
    {
        return $this->hasOne('App\Level', 'id', 'level');
    }

    public function course()
    {
        return $this->hasOne('App\Course', 'id', 'course');
    }


}
