<?php

namespace Modules\UploadFiles\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class MediaLesson extends Model
{
    use Auditable;

    protected $table = 'media_lessons';
    protected $fillable = ['index' , 'visible' , 'publish_date' , 'media_id' , 'lesson_id'];
    protected $hidden = ['updated_at','created_at'];


    public function Media()
    {
        return $this->hasMany('Modules\UploadFiles\Entities\Media', 'id', 'media_id');
    }

    public static function getNextIndex($lesson_id){
        if(self::whereLesson_id($lesson_id)->max('index') == null)
            return 1;
        return self::whereLesson_id($lesson_id)->max('index') + 1;
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