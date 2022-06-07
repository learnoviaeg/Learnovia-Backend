<?php

namespace Modules\Assigments\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Modules\Assigments\Entities\AssignmentLesson;
use App\Lesson as Lessonmodel;
use App\AuditLog;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Course;
use App\Segment;

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

        $old_count = count($old);
        if ($old_count == 0) {
            $year_id = null;
        }else{
            $lessons = AssignmentLesson::withTrashed()->where('assignment_id', $new->id)->groupBy('lesson_id')
                                       ->pluck('lesson_id');
            $course_id  = Lessonmodel::whereIn('id', $lessons)->first()->course_id;
            $segment_id = Course::where('id', $course_id)->first()->segment_id;
            $segment    = Segment::where('id', $segment_id)->first();
            $year_id    = $segment->academic_year_id;
        }
        return $year_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_type_name($old, $new)
    {
        $old_count = count($old);
        if ($old_count == 0) {
            $type_id = null;
        }else{
            $lessons = AssignmentLesson::withTrashed()->where('assignment_id', $new->id)->groupBy('lesson_id')
                                       ->pluck('lesson_id');
            $course_id  = Lessonmodel::whereIn('id', $lessons)->first()->course_id;
            $segment_id = Course::where('id', $course_id)->first()->segment_id;
            $segment    = Segment::where('id', $segment_id)->first();
            $type_id    = $segment->academic_type_id;
        }
        return $type_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_level_name($old, $new)
    {
        $old_count = count($old);
        if ($old_count == 0) {
            $level_id = null;
        }else{
            $lessons = AssignmentLesson::withTrashed()->where('assignment_id', $new->id)->groupBy('lesson_id')
                                       ->pluck('lesson_id');
            $course_id  = Lessonmodel::whereIn('id', $lessons)->first()->course_id;
            $level_id   = Course::where('id', $course_id)->first()->level_id;
        }
        return $level_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_class_name($old, $new)
    {
        $old_count = count($old);
        if ($old_count == 0) {
            $classes = null;
        }else{
            $lessons = AssignmentLesson::withTrashed()->where('assignment_id', $new->id)->groupBy('lesson_id')
                                       ->pluck('lesson_id');
            $course_id  = Lessonmodel::whereIn('id', $lessons)->first()->course_id;
            $classes    = Course::where('id', $course_id)->first()->classes;
        }
        return $classes;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_segment_name($old, $new)
    {
        $old_count = count($old);
        if ($old_count == 0) {
            $segment_id = null;
        }else{
            $lessons = AssignmentLesson::withTrashed()->where('assignment_id', $new->id)->groupBy('lesson_id')
                                       ->pluck('lesson_id');
            $course_id  = Lessonmodel::whereIn('id', $lessons)->first()->course_id;
            $segment_id = Course::where('id', $course_id)->first()->segment_id;
        }
        return $segment_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_course_name($old, $new)
    {
        $old_count = count($old);
        if ($old_count == 0) {
            $course_id = null;
        }else{
            $lessons = AssignmentLesson::withTrashed()->where('assignment_id', $new->id)->groupBy('lesson_id')
                                       ->pluck('lesson_id');
            $course_id  = Lessonmodel::whereIn('id', $lessons)->first()->course_id;
        }
        return $course_id;
    }
    // end function get name and value attribute
}
