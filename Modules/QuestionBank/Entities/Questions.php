<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;

class Questions extends Model
{
    protected $fillable = ['text','mark','parent','content','category_id','survey','question_type_id','question_category_id','course_id' , 'mcq_type'];
    protected $hidden = [
        'created_at', 'updated_at','course_segment_id','category_id'
    ];

    //count of all quizzes
    protected $appends = ['count_quizzes'];


    public function getCountQuizzesAttribute()
    {
        $count_quest = 0;
        $count_quest = quiz_questions::where('question_id',$this->id)->count();
        return $count_quest;  
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

    // public function question_answer()
    // {
    //     return $this->hasMany('Modules\QuestionBank\Entities\QuestionsAnswer', 'question_id', 'id');
    // }

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

    public function getContentAttribute()
    {
        $content= json_decode($this->attributes['content']);
        
        if($this->attributes['question_type_id'] == 3){
            $content= json_decode($this->attributes['content'],true);
        }
        
        if($this->attributes['question_type_id'] == 2){
            foreach($content as $key => $con)
            {
                if(isset($con->is_true)){
                    if($con->is_true == 1){
                        $con->is_true=True;
                        // $con->mark = null;
                        continue;
                    }
                    $con->is_true=False;
                    // $con->mark = null;
                }
            }
        }
        return $content;
    }
}
