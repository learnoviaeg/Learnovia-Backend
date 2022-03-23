<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;

class quiz extends Model
{
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
}
