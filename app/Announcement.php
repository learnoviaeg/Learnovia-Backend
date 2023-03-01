<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Topic;
use App\user;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\AnnouncementsChain;

class Announcement extends Model
{
    use Auditable, SoftDeletes;
    
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
    public function getTopicAttribute($value)
    {
        $topicObject =  Topic::find($value);

        $topic['id'] = $topicObject ? $topicObject->id : null;
        $topic['title'] = $topicObject ? $topicObject->title : null;
        return $topic;
    }
    public function getCreatedByAttribute($value)
    {
        $user['id'] = $value;
        $usr=User::find($value);
        if(isset($usr))
            $user['name'] = User::find($value)->fullname;
        return $user;
    }

    public static function get_year_name($old, $new)
    {
        $year_id=null;
        $check = AnnouncementsChain::where('announcement_id', $new->id)->first();
        if(isset($check))
            $year_id=$check->year;

        if (count($old) == 0) 
            $year_id = $new->year_id;

        return $year_id;
    }
    
    public static function get_type_name($old, $new)
    {
        $type_id=null;
        $check = AnnouncementsChain::where('announcement_id', $new->id)->first();
        if(isset($check))
            $type_id=$check->type;

        if (count($old) == 0) 
            $type_id = $new->type_id;

        return $type_id;
    }
    
    public static function get_level_name($old, $new)
    {
        $level_id=null;
        $check = AnnouncementsChain::where('announcement_id', $new->id)->first();
        if(isset($check))
            $level_id=$check->level;

        if (count($old) == 0) 
            $level_id = $new->level_id;

        return $level_id;
    }
    
    public static function get_class_name($old, $new)
    {
        $class_id=null;
        $check = AnnouncementsChain::where('announcement_id', $new->id)->groupBy('class')
                    ->pluck('class');;
        if(isset($check))
            $class_id=$check;

        if (count($old) == 0) 
            $class_id = $new->class_id;
        
        return $class_id;
    }
    
    public static function get_segment_name($old, $new)
    {
        $segment_id=null;
        $check = AnnouncementsChain::where('announcement_id', $new->id)->first();
        if(isset($check))
            $segment_id=$check->segment;

        if (count($old) == 0) 
            $segment_id = $new->segment_id;
        
        return $segment_id;
    }

    public static function get_course_name($old, $new)
    {
        $course_id=null;
        $check = AnnouncementsChain::where('announcement_id', $new->id)->first();
        if(isset($check))
            $course_id=$check->course;

        if (count($old) == 0) 
            $course_id = $new->course_id;
        
        return $course_id;
    }
}
