<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class QuestionsAnswer extends Model
{
	use Auditable;
	
    protected $fillable = ['content','And_why_answer','true_false','match_a','match_b','is_true','question_id'];
    protected $hidden = [
        'created_at', 'updated_at','question_id'
    ];

    public function question()
    {
        return $this->belongsTo('Modules\QuestionBank\Entities\Questions', 'question_id', 'id');
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
