<?php

namespace Modules\Page\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class page extends Model
{
    use Auditable;

    protected $fillable = ['title', 'content', 'visible'];
    
    public function Lesson()
    {
        return $this->belongsToMany('App\Lesson', 'page_lessons', 'page_id', 'lesson_id');
    }

    public function pageLesson()
    {
        return $this->hasMany('Modules\Page\Entities\pageLesson');
    }

    public function getContent($value)
    {
        return $value->getOriginal();
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
    
    public function courseItem(){
        return $this->hasOne('App\CourseItem', 'item_id')->where('type', 'page');
    }

    public function lessons()
    {
        return $this->hasManyThrough('App\Lesson' ,'Modules\Page\Entities\pageLesson', 'page_id' , 'id' , 'id' , 'id' );
    }
    
}
