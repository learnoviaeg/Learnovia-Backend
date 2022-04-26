<?php

namespace Modules\UploadFiles\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use App\Lesson as Lessonmodel;
use App\AuditLog;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Course;
use App\Segment;

class FileLesson extends Model
{
    use Auditable, SoftDeletes;

    protected $table    = 'file_lessons';
    protected $fillable = ['index' , 'visible' , 'publish_date' , 'file_id' , 'lesson_id', 'deleted_at'];
//    protected $hidden   = ['updated_at','created_at', 'deleted_at'];
    protected $softDelete = true;

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];


    public function File()
    {
        return $this->hasMany('Modules\UploadFiles\Entities\File', 'id', 'file_id');
    }

    public static function getNextIndex($lesson_id){
        if(self::whereLesson_id($lesson_id)->max('index') == null)
            return 1;
        return self::whereLesson_id($lesson_id)->max('index') + 1;
    }

    // start function get name and value f attribute
    public static function get_year_name($old, $new)
    {
        $lesson_id    = $new->lesson_id;
        $course_id    = Lessonmodel::where('id', $lesson_id)->first()->course_id;
        $segment_id   = Course::where('id', $course_id)->first()->segment_id;
        $segment      = Segment::where('id', $segment_id)->first();
        $academic_year_id[] = $segment->academic_year_id;

        $audit_log_quiz_course_id = AuditLog::where(['subject_type' => 'file', 'subject_id' => $new->file_id])->first();
        $audit_log_quiz_course_id->update([
            'year_id' => $academic_year_id
        ]);

        return $academic_year_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_type_name($old, $new)
    {
        $lesson_id    = $new->lesson_id;
        $course_id    = Lessonmodel::where('id', $lesson_id)->first()->course_id;
        $segment_id   = Course::where('id', $course_id)->first()->segment_id;
        $segment      = Segment::where('id', $segment_id)->first();
        $academic_type_id[] = $segment->academic_type_id;

        $audit_log_quiz_course_id = AuditLog::where(['subject_type' => 'file', 'subject_id' => $new->file_id])->first();
        $audit_log_quiz_course_id->update([
            'type_id' => $academic_type_id
        ]);

        return $academic_type_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_level_name($old, $new)
    {
        $lesson_id    = $new->lesson_id;
        $course_id    = Lessonmodel::where('id', $lesson_id)->first()->course_id;
        $level_id[]     = Course::where('id', $course_id)->first()->level_id;

        $audit_log_quiz_course_id = AuditLog::where(['subject_type' => 'file', 'subject_id' => $new->file_id])->first();
        $audit_log_quiz_course_id->update([
            'level_id' => $level_id
        ]);

        return $level_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_class_name($old, $new)
    {
        $lesson_id    = $new->lesson_id;
        $lesson       = Lessonmodel::where('id', $lesson_id)->first();
        $classes      = $lesson['shared_classes']->pluck('id');

        $audit_log_quiz_course_id = AuditLog::where(['subject_type' => 'file', 'subject_id' => $new->file_id])->first();
        $audit_log_quiz_course_id->update([
            'class_id' => $classes
        ]);

        return $classes;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_segment_name($old, $new)
    {
        $lesson_id    = $new->lesson_id;
        $course_id    = Lessonmodel::where('id', $lesson_id)->first()->course_id;
        $segment_id[]   = Course::where('id', $course_id)->first()->segment_id;

        $audit_log_quiz_course_id = AuditLog::where(['subject_type' => 'file', 'subject_id' => $new->file_id])->first();
        $audit_log_quiz_course_id->update([
            'segment_id' => $segment_id
        ]);
        return $segment_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_course_name($old, $new)
    {
        $lesson_id   = $new->lesson_id;
        $course_id[]  = Lessonmodel::where('id', $lesson_id)->first()->course_id;

        $audit_log_quiz_course_id = AuditLog::where(['subject_type' => 'file', 'subject_id' => $new->file_id])->first();
        $audit_log_quiz_course_id->update([
            'course_id' => $course_id
        ]);
        return $course_id;
    }
    // end function get name and value attribute
}
