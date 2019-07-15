<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;

class Questions extends Model
{
    protected $fillable = ['text','mark','category_id','question_type_id','question_category_id','course_id'];
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



}
