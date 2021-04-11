<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'name',
        'description',
        'attached_file',
        'from',
        'to',
        'cover',
        'id_number',
        'user_id',
    ];
}
