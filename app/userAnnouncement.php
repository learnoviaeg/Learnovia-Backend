<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class userAnnouncement extends Model
{
    protected $fillable = ['announcement_id' , 'user_id'];

    public function announcements()
    {
        return $this->belongsTo('App\Announcement', 'announcement_id', 'id');
    }
}
