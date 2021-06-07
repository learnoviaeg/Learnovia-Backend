<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;

class Questions extends Model
{
    protected $fillable = ['text','mark','parent','content','category_id','survey','question_type_id','question_category_id','course_id'];
    protected $hidden = [
        'created_at', 'updated_at','course_segment_id','category_id'
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

    // public static function CheckAndWhy($squestion){
    //     if(isset($squestion->And_why))
    //         if($squestion->And_why == 1)
    //             return $squestion->And_why_mark;

    //     return null ;
    // }

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
            // dd($content);

            // $keys_a=array_keys($content['match_a']);
            // $keys_b=array_keys($content['match_b']);

            // // shuffle($keys_a);
            // // shuffle($keys_b);
            // foreach($keys_a as $key)
            //     $quest['match_a'][$key]=$content['match_a'][$key];

            // foreach($keys_b as $key)
            //     $quest['match_b'][$key]=$content['match_b'][$key];
            
            //     // $content=$quest;
            // $content = json_encode($quest, JSON_FORCE_OBJECT);
            // $content= json_decode($content,true);

        }
        
        if($this->attributes['question_type_id'] == 2){
            foreach($content as $con)
            {
                if($con->is_true == 1){
                    $con->is_true=True;
                    continue;
                }
                $con->is_true=False;
            }
        }
        return $content;
    }
}
