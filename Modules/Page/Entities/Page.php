<?php

namespace Modules\Page\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use App\Lesson as Lessonmodel;
use App\AuditLog;
use Modules\Page\Entities\pageLesson;

class page extends Model
{
    use Auditable;
    
    protected $fillable = ['title', 'content', 'visible'];
    public function Lesson()
    {
        return $this->belongsToMany('App\Lesson', 'page_lessons', 'page_id', 'lesson_id');
    }

    public function pageLesson()
    {
        return $this->hasMany('Modules\Page\Entities\pageLesson');
    }

    public function getContent($value)
    {
        return $value->getOriginal();
    }

    public function courseItem(){
        return $this->hasOne('App\CourseItem', 'item_id')->where('type', 'page');
    }

    public function lessons()
    {
        return $this->hasManyThrough('App\Lesson' ,'Modules\Page\Entities\pageLesson', 'page_id' , 'id' , 'id' , 'id' );
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
        $lessons_id   = pageLesson::where('page_id', $new->id)->pluck('lesson_id');
        if (count($lessons_id) <= 0) {
            $course_id = null;
        }else{
            $course_id[]  = Lessonmodel::whereIn('id', $lessons_id)->first()->course_id;
            $audit_log_quiz_course_id = AuditLog::where(['subject_type' => 'page', 'subject_id' => $new->id])->first();
            $audit_log_quiz_course_id->update([
                'course_id' => $course_id
            ]);
        }
        return $course_id;
    }
    // end function get name and value attribute
}
