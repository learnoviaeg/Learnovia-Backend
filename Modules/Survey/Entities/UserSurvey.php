<?php

namespace Modules\Survey\Entities;

use Illuminate\Database\Eloquent\Model;

class UserSurvey extends Model
{
    protected $fillable = ['user_id', 'survey_id'];

    public function UserSurveyAnswer()
    {
        return $this->hasMany('Modules\Survey\Entities\UserSurveyAnswers', 'user_survey_id', 'id');
    }

    public function Survey()
    {
        return $this->belongsTo('Modules\Survey\Entities\Survey', 'survey_id', 'id');
    } 
}
