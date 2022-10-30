<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Segment;
use App\Course;

class Questions extends Model
{
    use Auditable, SoftDeletes;

    protected $fillable = ['text','mark','parent','content','category_id','survey','question_type_id','question_category_id',
        'course_id' , 'mcq_type','complexity'];

    //count of all quizzes
    protected $appends = ['count_quizzes'];

    protected $casts = [
        'question_type_id'  => 'integer',
    ];

    public function getCountQuizzesAttribute()
    {
        $count_quest = 0;
        $count_quest = quiz_questions::where('question_id',$this->id)->count();
        return $count_quest;  
    }

    public function quizQuestion()
    {
        return $this->hasMany('Modules\QuestionBank\Entities\quiz_questions','question_id');
    }

    public function question_type()
    {
        return $this->belongsTo('Modules\QuestionBank\Entities\QuestionsType', 'question_type_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo('App\Category', 'category_id', 'id');
    }

    public function question_category()
    {
        return $this->belongsTo('Modules\QuestionBank\Entities\QuestionsCategory', 'question_category_id', 'id');
    }

    public function question_course()
    {
        return $this->belongsTo('App\Course', 'course_id', 'id');
    }

    public function bloom()
    {
        return $this->belongsTo('App\BloomCategory', 'complexity', 'id');
    }

    public function children()
    {
        return $this->hasMany('Modules\QuestionBank\Entities\Questions', 'parent', 'id');
    }

    public function course()
    {
        return $this->belongsTo('App\Course', 'course_id', 'id');
    }
    
    public function userAnswer($id)
    {
        return $this->hasOne('Modules\QuestionBank\Entities\userQuizAnswer', 'question_id', 'id')
            ->where('user_quiz_id',$id)->first();
    }

    public function Parent()
    {
        return $this->hasOne('Modules\QuestionBank\Entities\Questions','id', 'parent');
    }

    public function getContentAttribute()
    {
        $content= json_decode($this->attributes['content']);
        
        if($this->attributes['question_type_id'] == 3){
            $content= json_decode($this->attributes['content'],true);
        }
        
        if($this->attributes['question_type_id'] == 2){
            if(isset($content))
                foreach($content as $key => $con)
                {
                    if(isset($con->is_true)){
                        if($con->is_true == 1){
                            $con->is_true=True;
                            if(!isset($con->mark))
                                $con->mark = null;
                            continue;
                        }
                        $con->is_true=False;
                        if(!isset($con->mark))
                            $con->mark = null;
                    }
                }
        }
        return $content;
    }

        // start function get name and value f attribute
    public static function get_year_name($old, $new)
    {
        $course_id    = $new->course_id;
        $segment_id   = Course::where('id', $course_id)->first()->segment_id;
        $year_id      = Segment::where('id', $segment_id)->first()->academic_year_id;
        return $year_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_type_name($old, $new)
    {
        $course_id    = $new->course_id;
        $segment_id   = Course::where('id', $course_id)->first()->segment_id;
        $type_id      = Segment::where('id', $segment_id)->first()->academic_type_id;
        return $type_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_level_name($old, $new)
    {
        $course_id  = $new->course_id;
        $level_id   = Course::where('id', $course_id)->first()->level_id;
        return $level_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_class_name($old, $new)
    {
        $course_id = $new->course_id;
        $classes   = Course::where('id', $course_id)->first()->classes;
        return $classes;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_segment_name($old, $new)
    {
        $course_id = $new->course_id;
        $segment_id   = Course::where('id', $course_id)->first()->segment_id;
        return $segment_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_course_name($old, $new)
    {
        $course_id = intval($new->course_id);
        return $course_id;
    }
    // end function get name and value attribute
}

