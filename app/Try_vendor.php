
<?php

namespace App;

use Djoudi\LaravelH5p\Eloquents\H5pContent;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Lesson as Lessonmodel;
use App\Course;
use App\Segment;
use App\h5pLesson;

class Try_vendor extends h5pLesson
{
    // log trait right here
    use Auditable, SoftDeletes;
    
    public $table = 'h5p_lessons';

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
    	$lesson_id    = $new->lesson_id;
        $course_id    = Lessonmodel::where('id', $lesson_id)->first()->course_id;
        return $course_id;
    }
    // end function get name and value attribute
}
