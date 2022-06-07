<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class CourseItem extends Model
{
    use Auditable;
    
    protected $fillable = ['item_id', 'type'];


    public function courseItemUsers(){
        return $this->hasMany('App\UserCourseItem', 'course_item_id', 'id');
    }

    public function page(){
        return $this->belongsTo('Modules\Page\Entities\Page','item_id')->where('type', 'page');
    }

    public function file(){
        return $this->belongsTo('Modules\UploadFiles\Entities\File','item_id')->where('type', 'file');
    }

    public function media(){
        return $this->belongsTo('Modules\UploadFiles\Entities\Media','item_id')->where('type', 'media');
    }

    public function assignment(){
        return $this->belongsTo('Modules\Assigments\Entities\Assignment','item_id')->where('type', 'assignment');
    }

    public function quiz(){
        return $this->belongsTo('Modules\QuestionBank\Entities\Quiz','item_id')->where('type', 'quiz');
    }

    public function h5pContent(){
        return $this->belongsTo('Djoudi\LaravelH5p\Eloquents\H5pContent','item_id')->where('type', 'h5p_content');
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
