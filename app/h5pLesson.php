<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\UserSeen;
use Illuminate\Support\Facades\DB;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Lesson as Lessonmodel;
use App\Course;
use App\Segment;

class h5pLesson extends Model
{
    // log trait right here
      use Auditable, SoftDeletes;

      public $table = 'h5p_lessons';

      protected $fillable = ['content_id',
        'lesson_id',
        'visible',
        'publish_date' ,
        'start_date' ,
        'due_date',
        'user_id',
        'seen_number',
        'restricted'
    ];
    protected $appends = ['user_seen_number'];   

    public function getUserSeenNumberAttribute(){

        $user_seen = 0;
        if($this->seen_number != 0)
            $user_seen = UserSeen::where('type','h5p')->where('item_id',$this->content_id)->where('lesson_id',$this->lesson_id)->count();

        return $user_seen;
    }

    public function user(){
        return $this->belongsTo('App\User');
    }

    public function getNameAttribute(){
        return DB::table('h5p_contents')->whereId($this->content_id)->first()->title;
    }

    public function lesson()
    {
        return $this->belongsTo('App\Lesson', 'lesson_id', 'id');
    }

    public function h5pContent(){
        return $this->belongsTo('Djoudi\LaravelH5p\Eloquents\H5pContent','content_id');
    }

    public function courseItem(){
        return $this->hasOne('App\CourseItem', 'item_id')->where('type', 'h5p_content');
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
        $course_id    = Self::get_course_name($old, $new);
        $segment_id   = Course::where('id', $course_id)->first()->segment_id;
        $segment      = Segment::where('id', $segment_id)->first();
        $year_id      = $segment->academic_year_id;
        return $year_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_type_name($old, $new)
    {
        $course_id    = Self::get_course_name($old, $new);
        $segment_id   = Course::where('id', $course_id)->first()->segment_id;
        $segment      = Segment::where('id', $segment_id)->first();
        $type_id      = $segment->academic_type_id;
        return $type_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_level_name($old, $new)
    {
        $course_id    = Self::get_course_name($old, $new);
        $level_id     = Course::where('id', $course_id)->first()->level_id;
        return $level_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_class_name($old, $new)
    {
        $course_id    = Self::get_course_name($old, $new);
        $classes      = Course::where('id', $course_id)->first()->classes;
        return $classes;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_segment_name($old, $new)
    {
        $course_id    = Self::get_course_name($old, $new);
        $segment_id   = Course::where('id', $course_id)->first()->segment_id;
        return $segment_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_course_name($old, $new)
    {
        //$lessons_id   = pageLesson::where('page_id', $new->page_id)->pluck('lesson_id');
        $lesson_id    = $new->lesson_id;
        $course_id    = Lessonmodel::where('id', $lesson_id)->first()->course_id;
        return $course_id;
    }
    // end function get name and value attribute



}
