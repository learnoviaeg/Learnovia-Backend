<?php

namespace Modules\Assigments\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Modules\Assigments\Entities\AssignmentLesson;
use App\Lesson as Lessonmodel;
use App\AuditLog;
use Illuminate\Database\Eloquent\SoftDeletes;

class assignment extends Model
{
    use Auditable, SoftDeletes;

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
        $first_created = AuditLog::where(['subject_type' => 'assignment', 'subject_id' => $new->id])
                                ->where('action', 'created')->first();
        $year_id = $first_created->year_id;
        return $year_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_type_name($old, $new)
    {
        $first_created = AuditLog::where(['subject_type' => 'assignment', 'subject_id' => $new->id])
                                ->where('action', 'created')->first();
        $type_id = $first_created->type_id;
        return $type_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_level_name($old, $new)
    {
        $first_created = AuditLog::where(['subject_type' => 'assignment', 'subject_id' => $new->id])
                                ->where('action', 'created')->first();
        $level_id = $first_created->level_id;
        return $level_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_class_name($old, $new)
    {
        $first_created = AuditLog::where(['subject_type' => 'assignment', 'subject_id' => $new->id])
                                ->where('action', 'created')->first();
        $class_id = $first_created->class_id;
        return $class_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_segment_name($old, $new)
    {
        $first_created = AuditLog::where(['subject_type' => 'assignment', 'subject_id' => $new->id])
                                ->where('action', 'created')->first();
        $segment_id = $first_created->segment_id;
        return $segment_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_course_name($old, $new)
    {
        $first_created = AuditLog::where(['subject_type' => 'assignment', 'subject_id' => $new->id])
                                ->where('action', 'created')->first();
        $course_id = $first_created->course_id;
        return $course_id;
    }
    // end function get name and value attribute
}
