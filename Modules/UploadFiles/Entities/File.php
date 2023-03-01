<?php

namespace Modules\UploadFiles\Entities;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use App\Lesson as Lessonmodel;
use App\AuditLog;
use Modules\UploadFiles\Entities\FileLesson;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Course;
use App\Segment;

class file extends Model
{
    use Auditable, SoftDeletes;
    
    protected $fillable = ['type',
    'description',
    'name',
    'size' ,
    'attachment_name',
    'user_id' ,
    'url' ,
    'url2' ];

    protected $hidden = ['updated_at','created_at','user_id'];

    protected $softDelete = true;

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function FileCourseSegment()
    {
        return $this->belongsTo('Modules\UploadFiles\Entities\FileCourseSegment', 'id', 'file_id');
    }

    public function FileLesson()
    {
        return $this->hasMany('Modules\UploadFiles\Entities\FileLesson');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function getUrl2Attribute() {
        return url(Storage::url($this->attributes['url2']));
      }
      public function getUrl1Attribute() {
        return 'https://docs.google.com/viewer?url=' .url(Storage::url($this->attributes['url2']));
      }

    public function courseItem(){
        return $this->hasOne('App\CourseItem', 'item_id')->where('type', 'file');
    }

    public function lessons()
    {
        return $this->hasManyThrough('App\Lesson' ,'Modules\UploadFiles\Entities\FileLesson', 'file_id' , 'id' , 'id' , 'id' );
    }
    public function Lesson()
    {
        return $this->belongsToMany('App\Lesson', 'file_lessons', 'file_id', 'lesson_id');
    }

    // start function get name and value f attribute
    public static function get_year_name($old, $new)
    {
       /* $lessons_id   = FileLesson::withTrashed()->where('file_id', $new->id)->pluck('lesson_id');
        $course_id    = Lessonmodel::whereIn('id', $lessons_id)->first()->course_id;
        $segment_id   = Course::where('id', $course_id)->segment_id;
        $segment      = Segment::where('id', $segment_id)->first();
        $academic_year_id = $segment->academic_year_id;
        return $academic_year_id;*/
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_type_name($old, $new)
    {
        /*$lessons_id   = FileLesson::withTrashed()->where('file_id', $new->id)->pluck('lesson_id');
        $course_id    = Lessonmodel::whereIn('id', $lessons_id)->first()->course_id;
        $segment_id   = Course::where('id', $course_id)->segment_id;
        $segment      = Segment::where('id', $segment_id)->first();
        $academic_type_id = $segment->academic_type_id;
        return $academic_type_id;*/
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_level_name($old, $new)
    {
        /*$lessons_id   = FileLesson::withTrashed()->where('file_id', $new->id)->pluck('lesson_id');
        $course_id    = Lessonmodel::whereIn('id', $lessons_id)->first()->course_id;
        $level_id     = Course::where('id', $course_id)->level_id;
        return $level_id;*/
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_class_name($old, $new)
    {
        /*$lessons_id   = FileLesson::withTrashed()->where('file_id', $new->id)->pluck('lesson_id');
        $lesson       = Lessonmodel::whereIn('id', $lessons_id)->first();
        $classes      = $lesson['shared_classes']->pluck('id');
        return $classes;*/
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_segment_name($old, $new)
    {
        /*$lessons_id   = FileLesson::withTrashed()->where('file_id', $new->id)->pluck('lesson_id');
        $course_id    = Lessonmodel::whereIn('id', $lessons_id)->first()->course_id;
        $level_id     = Course::where('id', $course_id)->level_id;
        return $level_id;*/
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_course_name($old, $new)
    {
        /*$lessons_id   = FileLesson::withTrashed()->where('file_id', $new->id)->pluck('lesson_id');
        if (count($lessons_id) <= 0) {
            $course_id = null;
        }else{
            $course_id[]  = Lessonmodel::whereIn('id', $lessons_id)->first()->course_id;
            $audit_log_quiz_course_id = AuditLog::where(['subject_type' => 'file', 'subject_id' => $new->id])->first();
            $audit_log_quiz_course_id->update([
                'course_id' => $course_id
            ]);
        }
        return $course_id;*/
        return null;
    }
    // end function get name and value attribute
}
