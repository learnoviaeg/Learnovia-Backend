<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;

class quiz_questions extends Model
{
    protected $fillable = ['question_id','quiz_id','grade_details'];
    protected $hidden = [
        'created_at','updated_at'
    ];

    public function Question()
    {
        return  $this->hasMany('Modules\QuestionBank\Entities\Questions', 'id', 'question_id');
    }

    public function getGradeDetailsAttribute()
    {
        $grade_details = json_decode($this->attributes['grade_details']);
        
        if(isset($grade_details->exclude_mark))
        {
            if($grade_details->exclude_mark)
                $grade_details->exclude_mark= true;
            else
                $grade_details->exclude_mark= false;

            if($grade_details->exclude_shuffle)
                $grade_details->exclude_shuffle= true;
            else
                $grade_details->exclude_shuffle= false;
        }
        return $grade_details;
    }

  
}
