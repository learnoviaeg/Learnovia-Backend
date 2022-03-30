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
        $old_count = count($old);
        if ($old_count == 0) {
            $course_id = [intval($new['course_id'])];
        }else{
            if ($old['course_id'] == $new['course_id']) {
                $course_id = [intval($new['course_id'])];
            }else{
                $course_id = [intval($old['course_id']), intval($new['course_id'])];
            }
        }
        return $course_id;
    }
    // end function get name and value attribute
}
