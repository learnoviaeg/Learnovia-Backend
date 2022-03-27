<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;

class quiz extends Model
{
    use Auditable, SoftDeletes;

    protected $fillable = ['name','course_id','is_graded','duration','created_by' , 'shuffle','grade_feedback', 'draft', 'correct_feedback','allow_edit'];
    protected $hidden = [
        'created_at', 'updated_at'
    ];

    public function Question()
    {
        return $this->belongsToMany('Modules\QuestionBank\Entities\Questions', 'quiz_questions', 'quiz_id', 'question_id')->whereNull('parent');
    }
    public function Lesson()
    {
        return $this->belongsToMany('App\Lesson', 'quiz_lessons', 'quiz_id', 'lesson_id');
    }

    public function course()
    {
        return $this->belongsTo('App\Course', 'course_id', 'id');
    }

    // public function course()
    // {
    //     return $this->belongsTo('App\CourseSegment', 'course_id', 'course_segment_id');
    // }

    public function quizLesson()
    {
        return $this->hasMany('Modules\QuestionBank\Entities\QuizLesson', 'quiz_id', 'id');
    }

    public static function checkSuffle($request){
        if(isset($request->shuffle)){
            return $request->shuffle;
        }
        return 0 ;
    }

    public function getAllowEditAttribute()
    {
        if($this->attributes['allow_edit'])
            $allow_edit= true;
        else
            $allow_edit= false;

        return $allow_edit;
   }

    public function user(){
        return $this->belongsTo('App\User','created_by');
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
