<?php

namespace App;
use App\Level;
use App\Traits\Auditable;
use App\AuditLog;

use Illuminate\Database\Eloquent\Model;

class AnnouncementsChain extends Model
{
    use Auditable;

    protected $fillable = [
        'announcement_id',
        'year',
        'type',
        'level',
        'class',
        'segment',
        'course',
    ];

    public function level()
    {
        return $this->hasOne('App\Level', 'id', 'level');
    }

    public function course()
    {
        return $this->hasOne('App\Course', 'id', 'course');
    }

    // start function get name and value f attribute
    public static function get_year_name($old, $new)
    {
        $year_id = intval($new['year']);
        AuditLog::where(['subject_type' => 'Announcement', 'subject_id' => $new->announcement_id])->update([
            'year_id' => $year_id
        ]);
        return $year_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_type_name($old, $new)
    {
        $type_id = intval($new['type']);
        AuditLog::where(['subject_type' => 'Announcement', 'subject_id' => $new->announcement_id])->update([
            'type_id' => $type_id
        ]);
        return $type_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_level_name($old, $new)
    {
        $level_id = intval($new['level']);
        AuditLog::where(['subject_type' => 'Announcement', 'subject_id' => $new->announcement_id])->update([
            'level_id' => $level_id
        ]);
        return $level_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_class_name($old, $new)
    {
        $class_id = intval($new['class']);
        AuditLog::where(['subject_type' => 'Announcement', 'subject_id' => $new->announcement_id])->update([
            'class_id' => $class_id
        ]);
        return $class_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_segment_name($old, $new)
    {
        $segment_id = intval($new['segment']);
        AuditLog::where(['subject_type' => 'Announcement', 'subject_id' => $new->announcement_id])->update([
            'segment_id' => $segment_id
        ]);
        return $segment_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_course_name($old, $new)
    {
        $course_id = intval($new['course']);
        AuditLog::where(['subject_type' => 'Announcement', 'subject_id' => $new->announcement_id])->update([
            'course_id' => $course_id
        ]);
        return $course_id;
    }
    // end function get name and value attribute
}
