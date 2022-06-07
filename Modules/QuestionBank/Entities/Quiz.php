<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use App\Lesson as Lessonmodel;
use App\AuditLog;
use Modules\QuestionBank\Entities\QuizLesson;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Course;
use App\Segment;

class quiz extends Model
{
    use Auditable, SoftDeletes;

    protected $fillable = ['name','course_id','is_graded','duration','created_by' , 'shuffle','grade_feedback', 'draft', 'correct_feedback','allow_edit','restricted'];
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

    public function courseItem(){
        return $this->hasOne('App\CourseItem', 'item_id')->where('type', 'quiz');
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
        $old_count = count($old);
        if ($old_count == 0) {
            $year_id = null;
        }else{
            $lessons = QuizLesson::withTrashed()->where('quiz_id', $new->id)->groupBy('lesson_id')
                                       ->pluck('lesson_id');
            $course_id  = Lessonmodel::whereIn('id', $lessons)->first()->course_id;
            $segment_id = Course::where('id', $course_id)->first()->segment_id;
            $segment    = Segment::where('id', $segment_id)->first();
            $year_id    = $segment->academic_year_id;
        }
        return $year_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_type_name($old, $new)
    {
        $old_count = count($old);
        if ($old_count == 0) {
            $type_id = null;
        }else{
            $lessons = QuizLesson::withTrashed()->where('quiz_id', $new->id)->groupBy('lesson_id')
                                       ->pluck('lesson_id');
            $course_id  = Lessonmodel::whereIn('id', $lessons)->first()->course_id;
            $segment_id = Course::where('id', $course_id)->first()->segment_id;
            $segment    = Segment::where('id', $segment_id)->first();
            $type_id    = $segment->academic_type_id;
        }
        return $type_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_level_name($old, $new)
    {
        $old_count = count($old);
        if ($old_count == 0) {
            $level_id = null;
        }else{
            $lessons = QuizLesson::withTrashed()->where('quiz_id', $new->id)->groupBy('lesson_id')
                                       ->pluck('lesson_id');
            $course_id  = Lessonmodel::whereIn('id', $lessons)->first()->course_id;
            $level_id   = Course::where('id', $course_id)->first()->level_id;
        }
        return $level_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_class_name($old, $new)
    {
        $old_count = count($old);
        if ($old_count == 0) {
            $classes = null;
        }else{
            $lessons = QuizLesson::withTrashed()->where('quiz_id', $new->id)->groupBy('lesson_id')
                                       ->pluck('lesson_id');
            $course_id  = Lessonmodel::whereIn('id', $lessons)->first()->course_id;
            $classes    = Course::where('id', $course_id)->first()->classes;
        }
        return $classes;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_segment_name($old, $new)
    {
        $old_count = count($old);
        if ($old_count == 0) {
            $segment_id = null;
        }else{
            $lessons = QuizLesson::withTrashed()->where('quiz_id', $new->id)->groupBy('lesson_id')
                                       ->pluck('lesson_id');
            $course_id  = Lessonmodel::whereIn('id', $lessons)->first()->course_id;
            $segment_id = Course::where('id', $course_id)->first()->segment_id;
        }
        return $segment_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_course_name($old, $new)
    {
        $old_count = count($old);
        if ($old_count == 0) {
            $course_id = null;
        }else{
            $lessons = QuizLesson::withTrashed()->where('quiz_id', $new->id)->groupBy('lesson_id')
                                       ->pluck('lesson_id');
            $course_id  = Lessonmodel::whereIn('id', $lessons)->first()->course_id;
        }
        return $course_id;
    }
    // end function get name and value attribute
}
