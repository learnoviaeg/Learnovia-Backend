<?php

namespace Modules\Assigments\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class UserAssigment extends Model
{
    use Auditable;
    
    protected $fillable = ['user_id', 'assignment_id', 'attachment_id','corrected_file', 'submit_date', 'content', 'override', 'status_id', 'feedback', 'grade', 'assignment_lesson_id'];

    public function assignment()
    {
        return $this->belongsTo('Modules\Assigments\Entities\assignment', 'attachment_id', 'id');
    }
    public function status()
    {
        return $this->belongsTo('Modules\Assigments\Entities\status', 'status_id', 'id');
    }
    public function attachment()
    {
        return $this->hasOne('App\attachment', 'attachment_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
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
