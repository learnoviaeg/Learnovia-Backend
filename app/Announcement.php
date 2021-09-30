<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = ['title','description','attached_file','start_date','due_date','assign','class_id','level_id','course_id',
        'year_id','type_id','segment_id','publish_date','created_by', 'topic',
    ];

    public function attachment()
    {
        return $this->hasOne('App\attachment', 'id', 'attached_file');
    }

    public function UserAnnouncement()
    {
        return $this->hasMany('App\userAnnouncement', 'announcement_id', 'id');
    }

    public  function chainAnnouncement(){
        return $this->hasMany('App\AnnouncementsChain','announcement_id', 'id');
    }

}
