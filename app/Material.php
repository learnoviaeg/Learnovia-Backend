<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $fillable = [
        'item_id', 'name','publish_date','course_id','lesson_id','type','link','visible'
    ];
}
