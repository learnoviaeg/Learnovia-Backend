<?php

namespace Modules\Survey\Entities;

use Illuminate\Database\Eloquent\Model;

class UserSurveyAnswers extends Model
{
    protected $fillable = ['user_survey_id','question_id','answered','answer_id','user_grade'];
    
    public function Question()
    {
        return $this->belongsTo('Modules\QuestionBank\Entities\Questions', 'question_id', 'id');
    }

}
