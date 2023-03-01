<?php

namespace Modules\Survey\Entities;

use Illuminate\Database\Eloquent\Model;

class SurveyQuestion extends Model
{
    protected $fillable = ['question_id','survey_id'];
}
