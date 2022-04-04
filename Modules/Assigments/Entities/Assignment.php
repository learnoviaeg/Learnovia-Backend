<?php

namespace Modules\Assigments\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Modules\Assigments\Entities\AssignmentLesson;
use App\Lesson as Lessonmodel;
use App\AuditLog;

class assignment extends Model
{
    use Auditable;

    protected $fillable = ['name', 'content', 'attachment_id','created_by','restricted'];
    protected $appends = ['url' , 'url2'];
    public function attachment()
    {
        return $this->belongsTo('App\attachment', 'attachment_id', 'id');
    }
    public function UserAssigment()
    {
        return $this->hasMany('Modules\Assigments\Entities\UserAssigment', 'assignment_id', 'id');
    }
    public function Lesson()
    {
        return $this->belongsToMany('App\Lesson', 'assignment_lessons', 'assignment_id', 'lesson_id');
    }

    public function getUrlAttribute()
    {
        if (isset($this->attachment)) {
            return 'https://docs.google.com/viewer?url=' . $this->attachment->path;
        }
    }

    public function getUrl2Attribute()
    {
        if (isset($this->attachment)) {
            return $this->attachment->path;
        }
    }
    public function assignmentLesson()
    {
        return $this->hasMany('Modules\Assigments\Entities\AssignmentLesson', 'assignment_id', 'id');
    }

    public function user(){
        return $this->belongsTo('App\User','created_by');
    }

    public function courseItem(){
        return $this->hasOne('App\CourseItem', 'item_id')->where('type', 'assignment');
    }

    public function getRestrictedAttribute()
    {
        if($this->attributes['restricted'])
            return True;
        return False;
    }

      // start function get name and value f attribute
    public static function get_year_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_type_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_level_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_class_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_segment_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_course_name($old, $new)
    {
        $lessons_id   = AssignmentLesson::where('assignment_id', $new->id)->pluck('lesson_id');
        if (count($lessons_id) <= 0) {
            $course_id = null;
        }else{
            $course_id[]  = Lessonmodel::whereIn('id', $lessons_id)->first()->course_id;
            $audit_log_quiz_course_id = AuditLog::where(['subject_type' => 'assignment', 'subject_id' => $new->id])->first();
            $audit_log_quiz_course_id->update([
                'course_id' => $course_id
            ]);
        }
        return $course_id;
    }
    // end function get name and value attribute
}
