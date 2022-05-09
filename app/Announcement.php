<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Topic;
use App\user;
use App\Traits\Auditable;

class Announcement extends Model
{
    use Auditable;
    
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
        $old_count = count($old);
        if ($old_count == 0) {
            $year_id = [intval($new['year_id'])];
        }else{
            if ($old['year_id'] == $new['year_id']) {
                $year_id = [intval($new['year_id'])];
            }else{
                $year_id = [intval($old['year_id']), intval($new['year_id'])];
            }
        }
        return $year_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_type_name($old, $new)
    {
        $old_count = count($old);
        if ($old_count == 0) {
            $type_id = [intval($new['type_id'])];
        }else{
            if ($old['type_id'] == $new['type_id']) {
                $type_id = [intval($new['type_id'])];
            }else{
                $type_id = [intval($old['type_id']), intval($new['type_id'])];
            }
        }
        return $type_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_level_name($old, $new)
    {
        $old_count = count($old);
        if ($old_count == 0) {
            $level_id = [intval($new['level_id'])];
        }else{
            if ($old['level_id'] == $new['level_id']) {
                $level_id = [intval($new['level_id'])];
            }else{
                $level_id = [intval($old['level_id']), intval($new['level_id'])];
            }
        }
        return $level_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_class_name($old, $new)
    {
        $old_count = count($old);
        if ($old_count == 0) {
            $class_id = [intval($new['class_id'])];
        }else{
            if ($old['class_id'] == $new['class_id']) {
                $class_id = [intval($new['class_id'])];
            }else{
                $class_id = [intval($old['class_id']), intval($new['class_id'])];
            }
        }
        return $class_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_segment_name($old, $new)
    {
        $old_count = count($old);
        if ($old_count == 0) {
            $segment_id = [intval($new['segment_id'])];
        }else{
            if ($old['segment_id'] == $new['segment_id']) {
                $segment_id = [intval($new['segment_id'])];
            }else{
                $segment_id = [intval($old['segment_id']), intval($new['segment_id'])];
            }
        }
        return $segment_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_course_name($old, $new)
    {
        $old_count = count($old);
        if ($old_count == 0) {
            $course_id = [intval($new['course_id'])];
        }else{
            if ($old['course_id'] == $new['course_id']) {
                $course_id = [intval($new['course_id'])];
            }else{
                $course_id = [intval($old['course_id']), intval($new['course_id'])];
            }
        }
        return $course_id;
    }
    // end function get name and value attribute

}
