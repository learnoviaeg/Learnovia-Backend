<?php

namespace App;

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
}
