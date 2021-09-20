<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
//use App\Events\TopicCreatedEvent;

class Topic extends Model
{
    protected $table = "topics";

    protected $fillable = [
        'title',
        'filter',
    ];

    public function getFilterAttribute($value)
    {
        return json_decode($value);
    }


    public function enrolls()
    {
        return $this->belongsToMany('App\Enroll' , 'enroll_id' , 'id');
    }
    
}
