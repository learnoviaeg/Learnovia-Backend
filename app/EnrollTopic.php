<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EnrollTopic extends Model
{
    protected $table = "enroll_topic";
    
    protected $fillable = [
        'enroll_id',
        'topic_id',
    ];
    
}
