<?php

namespace Modules\UploadFiles\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use App\Lesson as Lessonmodel;
use App\AuditLog;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Course;
use App\Segment;

class MediaLesson extends Model
{
    use Auditable, SoftDeletes;
    
    protected $table = 'media_lessons';
    protected $fillable = ['index' , 'visible' , 'publish_date' , 'media_id' , 'lesson_id'];
    protected $hidden = ['updated_at','created_at'];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];


    public function Media()
    {
        return $this->hasMany('Modules\UploadFiles\Entities\Media', 'id', 'media_id');
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
        $academic_year_id = $segment->academic_year_id;

        AuditLog::where(['subject_type' => 'media', 'subject_id' => $new->media_id])->update([
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
        $academic_type_id = $segment->academic_type_id;

        AuditLog::where(['subject_type' => 'media', 'subject_id' => $new->media_id])->update([
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
        $level_id   = Course::where('id', $course_id)->first()->level_id;

        AuditLog::where(['subject_type' => 'media', 'subject_id' => $new->media_id])->update([
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

        AuditLog::where(['subject_type' => 'media', 'subject_id' => $new->media_id])->update([
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
        $segment_id = Course::where('id', $course_id)->first()->segment_id;

        AuditLog::where(['subject_type' => 'media', 'subject_id' => $new->media_id])->update([
            'segment_id' => $segment_id
        ]);
        return $segment_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_course_name($old, $new)
    {
        //$lessons_id   = MediaLesson::where('media_id', $new->media_id)->pluck('lesson_id');
        $lesson_id   = $new->lesson_id;
        $course_id  = Lessonmodel::where('id', $lesson_id)->first()->course_id;

        AuditLog::where(['subject_type' => 'media', 'subject_id' => $new->media_id])->update([
            'course_id' => $course_id
        ]);
        return $course_id;
    }
    // end function get name and value attribute
}
