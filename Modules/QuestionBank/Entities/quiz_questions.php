<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;

class quiz_questions extends Model
{
    use Auditable, SoftDeletes;
    
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

    // start function get name and value f attribute
    public static function get_year_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_type_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_level_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_class_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_segment_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_course_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute
}
