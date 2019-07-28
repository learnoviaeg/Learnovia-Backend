<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title', 'description', 'attached_file','start_date','end_date','assign','class_id','level_id','course_id'
    ];


}
