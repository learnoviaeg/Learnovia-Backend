<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;

class QuestionsCategory extends Model
{
    protected $fillable = ['name','course_segment_id'];
    protected $hidden = [
        'created_at', 'updated_at',
    ];

    public function questions()
    {
        return $this->hasMany('Modules\QuestionBank\Entities\Questions', 'question_category_id', 'id');
    }

    public function CourseSegmnet()
    {
        return $this->belongsTo('App\CourseSegment', 'course_segment_id','id');
    }
}
