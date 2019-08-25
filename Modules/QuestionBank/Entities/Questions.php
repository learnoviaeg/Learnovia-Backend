<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;

class Questions extends Model
{
    protected $fillable = ['text','mark','parent','And_why','And_why_mark','category_id','question_type_id','question_category_id','course_id'];
    protected $hidden = [
        'created_at', 'updated_at','course_id','category_id','question_type_id','question_category_id'
    ];

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

    public function question_answer()
    {
        return $this->hasMany('Modules\QuestionBank\Entities\QuestionsAnswer', 'question_id', 'id');
    }

    public function childeren()
    {
        return $this->hasMany('Modules\QuestionBank\Entities\Questions', 'parent', 'id');
    }

    public static function CheckAndWhy($squestion){
        if(isset($squestion->And_why))
        {
            if($squestion->And_why == 1){
                return $squestion->And_why_mark;
            }
        }
        return null ;
    }

    public function userAnswer($id)
    {
        return $this->hasOne('Modules\QuestionBank\Entities\userQuizAnswer', 'question_id', 'id')
            ->where('user_quiz_id',$id)->first();
    }

    public function GradeQuestion($question , $answer_id){
        $type=$question->question_type_id;

        switch ($type){
            case 1 : // true or false
                $answer = QuestionsAnswer::find($answer_id);
                $mark = 0;
                if($answer->is_true){
                    if ($question->And_why){
                        $mark = $question->mark + $question->And_why_mark;
                    }else {
                        $mark = $question->mark ;
                    }
                }
                return $mark;
                break;
            case 2 :
                $answer = QuestionsAnswer::find($answer_id);
                if($answer->is_true){
                    return $question->mark;
                }else {
                    return 0;
                }
                break;
            case 3 :
                $count=0;
                foreach ($answer_id as $answerId ){
                    $answer = QuestionsAnswer::find($answerId);
                    if($answer->is_true){
                        $count+=1;
                    }
                }
                $mark = $count * ($question->mark /count($answer_id ) );
                    return $mark;

                break;
        }

    }
}
