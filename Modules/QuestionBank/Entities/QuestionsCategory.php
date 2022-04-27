<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class QuestionsCategory extends Model
{
    use Auditable;
    
    protected $fillable = ['name','course_segment_id','course_id'];
    
    protected $hidden = [ 'updated_at',];

    public function questions()
    {
        return $this->hasMany('Modules\QuestionBank\Entities\Questions', 'question_category_id', 'id');
    }

    // public function CourseSegment()
    // {
    //     return $this->belongsTo('App\CourseSegment', 'course_segment_id','id');
    // }

    public function course()
    {
        return $this->belongsTo('App\Course', 'course_id','id');
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
        $course   = Course::where('id', intval($new['course_id']))->first();
        $create_intvals = array();
        $v1      = $course['classes'];
        $first   = str_replace("\"", "", $v1);
        $r       = $first;
        $move1   = trim($r[0], "[");
        $move2   = trim($move1, "]");
        $v1_edit = explode(",", $move2); 
        $intvals = array();
        foreach ($v1_edit as $key => $value) {
            array_push($create_intvals, intval($value));
        }
        return $create_intvals;
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
