<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use App\Lesson as Lessonmodel;
use App\AuditLog;
use Modules\QuestionBank\Entities\QuizLesson;

class quiz extends Model
{
    use Auditable;

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
        // get lessons sent with with create quiz request 
        // get course id that thses lessons belongs to 
        // these lessons is under one course 
        // save this course into audit_log
        /*$lessons_id   = QuizLesson::where('quiz_id', $new->id)->pluck('lesson_id');
        if (count($lessons_id) <= 0) {
            $course_id = null;
        }else{
            $course_id[]  = Lessonmodel::whereIn('id', $lessons_id)->first()->course_id;
            $audit_log_quiz_course_id = AuditLog::where(['subject_type' => 'quiz', 'subject_id' => $new->id])->first();
            $audit_log_quiz_course_id->update([
                'course_id' => $course_id
            ]);
        }
        return $course_id;*/
        return null;
    }
    // end function get name and value attribute
}
