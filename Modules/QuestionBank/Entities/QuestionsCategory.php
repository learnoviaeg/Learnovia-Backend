<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;

class QuestionsCategory extends Model
{
    protected $fillable = ['name','course_segment_id','course_id'];
    
    protected $hidden = [ 'updated_at',];

    public function questions()
    {
        return $this->hasMany('Modules\QuestionBank\Entities\Questions', 'question_category_id', 'id');
    }

    // public function CourseSegment()
    // {
    //     return $this->belongsTo('App\CourseSegment', 'course_segment_id','id');
    // }

    public function course()
    {
        return $this->belongsTo('App\Course', 'course_id','id');
    }
}
