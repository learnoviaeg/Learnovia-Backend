<?php

namespace Modules\UploadFiles\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use App\Lesson as Lessonmodel;
use App\AuditLog;
use Modules\UploadFiles\Entities\MediaLesson;
use Illuminate\Database\Eloquent\SoftDeletes;

class media extends Model
{
    use Auditable, SoftDeletes;

    protected $fillable = ['id','name','course_segment_id','media_id' , 'show'];
    protected $hidden = ['updated_at','created_at','user_id'];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function MediaCourseSegment()
    {
        return $this->belongsTo('Modules\UploadFiles\Entities\MediaCourseSegment', 'id', 'media_id');
    }

    public function MediaLesson()
    {
        return $this->hasMany('Modules\UploadFiles\Entities\MediaLesson');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }
    protected $appends = ['media_type'];

    public function getMediaTypeAttribute(){
        if($this->type != null)
            return 'Media';
        return 'Link';
    }

    public function courseItem(){
        return $this->hasOne('App\CourseItem', 'item_id')->where('type', 'media');
    }

    public function lessons()
    {
        return $this->hasManyThrough('App\Lesson' ,'Modules\UploadFiles\Entities\MediaLesson', 'media_id' , 'id' , 'id' , 'id' );
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
        $lessons_id   = MediaLesson::withTrashed()->where('media_id', $new->id)->pluck('lesson_id');
        if (count($lessons_id) <= 0) {
            $course_id = null;
        }else{
            $course_id[]  = Lessonmodel::whereIn('id', $lessons_id)->first()->course_id;
           $audit_log_quiz_course_id = AuditLog::where(['subject_type' => 'media', 'subject_id' => $new->id])->first();
            $audit_log_quiz_course_id->update([
                'course_id' => $course_id
            ]);
        }
        return $course_id;
    }
    // end function get name and value attribute
}
