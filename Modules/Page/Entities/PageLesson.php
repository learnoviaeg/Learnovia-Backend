<?php

namespace Modules\Page\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use App\Lesson as Lessonmodel;
use App\AuditLog;
use Illuminate\Database\Eloquent\SoftDeletes;

class pageLesson extends Model
{
	use Auditable, SoftDeletes;

    protected $fillable = ['page_id','lesson_id','visible' , 'publish_date'];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

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
        $lessons_id   = pageLesson::where('page_id', $new->page_id)->pluck('lesson_id');
        $course_id[]  = Lessonmodel::whereIn('id', $lessons_id)->first()->course_id;
        $audit_log_quiz_course_id = AuditLog::where(['subject_type' => 'page', 'subject_id' => $new->page_id])->first();
        $audit_log_quiz_course_id->update([
            'course_id' => $course_id
        ]);
        return $course_id;
    }
    // end function get name and value attribute
}
