<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use App\Course;
use App\Segment;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use Auditable, SoftDeletes;

    protected $fillable = ['name','course_segment_id','index' , 'image' , 'description','shared_lesson','course_id' ,'shared_classes'];

    protected $dispatchesEvents = [
        'created' => \App\Events\LessonCreatedEvent::class,
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function module($name,$model)
    {
        return $this->belongsToMany('Modules\\'.$name.'\Entities\\'.$model, $model.'_lessons', 'lesson_id', $model.'_id')->withPivot('publish_date','created_at');
    }
    protected $hidden = [
        'created_at','updated_at'
    ];

    public function FileLesson()
    {
        return $this->hasMany('Modules\UploadFiles\Entities\FileLesson', 'lesson_id', 'id');
    }

    public function MediaLesson()
    {
        return $this->hasMany('Modules\UploadFiles\Entities\MediaLesson', 'lesson_id', 'id');
    }

    public function QuizLesson()
    {
        return $this->hasMany('Modules\QuestionBank\Entities\QuizLesson', 'lesson_id', 'id');
    }

    public function AssignmentLesson()
    {
        return $this->hasMany('Modules\Assigments\Entities\AssignmentLesson', 'lesson_id', 'id');
    }
    
    public function H5PLesson()
    {
        return $this->hasMany('App\h5pLesson', 'lesson_id', 'id');
    }

    public function Quiz()
    {
        return $this->hasMany('Modules\QuestionBank\Entities\Quiz','id');
    }

    public function SecondaryChain(){
        return $this->hasMany('App\SecondaryChain','lesson_id' , 'id');

    }

    public function getSharedClassesAttribute($value)
    {   if($value != null){
            $content= json_decode($value);
            return Classes::whereIn('id',$content)->get();
        }
        return $value;
    }
    
    public function course()
    {
        return $this->belongsTo('App\Course','course_id','id');
    }

    // start function get name and value f attribute
    public static function get_year_name($old, $new)
    {
        $course   = Course::where('id', intval($new['course_id']))->first();
        $segment  = Segment::where('id', $course->segment_id)->first();
        $academic_year_id[] = $segment->academic_year_id;
        return $academic_year_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_type_name($old, $new)
    {
        $course   = Course::where('id', intval($new['course_id']))->first();
        $segment  = Segment::where('id', $course->segment_id)->first();
        $academic_type_id[] = $segment->academic_type_id;
        return $academic_type_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_level_name($old, $new)
    {
        $level_id[] = Course::where('id', intval($new['course_id']))->first()->level_id;
        return $level_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_class_name($old, $new)
    {   
        $classes = $new['shared_classes']->pluck('id');
        return $classes;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_segment_name($old, $new)
    {
        $segment_id[] = Course::where('id', intval($new['course_id']))->first()->segment_id;
        return $segment_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_course_name($old, $new)
    {
        $course_id = [intval($new['course_id'])];
        return $course_id;
    }
    // end function get name and value attribute
}
