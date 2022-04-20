<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\QuestionBank\Entities\Questions;
use App\Traits\Auditable;
use App\Lesson as Lessonmodel;
use Modules\QuestionBank\Entities\userQuiz;

class userQuizAnswer extends Model
{
    use Auditable;
    
    protected $fillable = [
        'user_quiz_id','question_id','user_answers','correction','answered','force_submit'
    ];

    public function Question()
    {
        return $this->belongsTo('Modules\QuestionBank\Entities\Questions', 'question_id', 'id');
    }

    public function getUserAnswersAttribute()
    {
        $user_answers=json_decode($this->attributes['user_answers']);
        $question=Questions::find($this->attributes['question_id']);
        if(isset($user_answers)){
            if($question->question_type_id == 1){
                if($user_answers->is_true)
                    $user_answers->is_true=True;

                else if(!is_null($user_answers->is_true))
                    $user_answers->is_true=False;
                
                else
                    $user_answers->is_true=null;
            }
        }
        return $user_answers;
    }
    public function getCorrectionAttribute()
    {
        return json_decode($this->attributes['correction']);
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
        $userQuiZZ    = userQuiz::where('id', $new->user_quiz_id)->first();
        $QuizLesson   = QuizLesson::find($userQuiZZ->quiz_lesson_id);
        $course_id[]  = Lessonmodel::where('id', $QuizLesson->lesson_id)->first()->course_id;
        return $course_id;
    }
    // end function get name and value attribute
}
