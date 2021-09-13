<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TopicChain extends Model
{
    protected $fillable = [
        'years', 
        'types',
        'levels',
        'classes',
        'sgments',
        'courses',
        'topic_title'
    ];

    public function users()
    {
        return $this->belongsToMany('App\User', 'user_id', 'id');
    }
    public function announcements()
    {
        return $this->hasMany('App\Announcement');
    }
    
}
