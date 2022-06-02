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
        $user['name'] = User::find($value)->fullname;
        return $user;
    }

    // start function get name and value f attribute
    public static function get_year_name($old, $new)
    {
        // comment
        $old_count = count($old);
        if ($old_count == 0) {
            $year_id = $new->year_id;
        }else{
            $year_id = AnnouncementsChain::where('announcement_id', $new->id)->first()->year;
        }
        return $year_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_type_name($old, $new)
    {
        // comment
        $old_count = count($old);
        if ($old_count == 0) {
            $type_id = $new->type_id;
        }else{
            $type_id = AnnouncementsChain::where('announcement_id', $new->id)->first()->type;
        }
        return $type_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_level_name($old, $new)
    {
        // comment
        $old_count = count($old);
        if ($old_count == 0) {
            $level_id = $new->level_id;
        }else{
            $level_id = AnnouncementsChain::where('announcement_id', $new->id)->first()->level;
        }
        return $level_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_class_name($old, $new)
    {
        $old_count = count($old);
        if ($old_count == 0) {
            $class_id = $new->class_id;
        }else{
            $class_id = AnnouncementsChain::where('announcement_id', $new->id)->groupBy('class')
                                       ->pluck('class');
        }
        return $class_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_segment_name($old, $new)
    {
        // comment
        $old_count = count($old);
        if ($old_count == 0) {
            $segment_id = $new->segment_id;
        }else{
            $segment_id = AnnouncementsChain::where('announcement_id', $new->id)->first()->segment;
        }
        return $segment_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_course_name($old, $new)
    {
        // comment
        $old_count = count($old);
        if ($old_count == 0) {
            $course_id = $new->course_id;
        }else{
            $course_id = AnnouncementsChain::where('announcement_id', $new->id)->first()->course;
        }
        return $course_id;
    }
    // end function get name and value attribute

}
