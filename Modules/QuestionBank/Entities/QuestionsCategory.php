<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;

class QuestionsCategory extends Model
{
    protected $fillable = ['name','course_id'];
    
    protected $hidden = [ 'updated_at',];

    public function questions()
    {
        return $this->hasMany('Modules\QuestionBank\Entities\Questions', 'question_category_id', 'id');
    }

    public function course()
    {
        return $this->belongsTo('App\Course', 'course_id','id');
    }
}
