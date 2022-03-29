<?php

namespace Modules\UploadFiles\Entities;

use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class file extends Model
{
    use Auditable;
    
    protected $fillable = ['type',
    'description',
    'name',
    'size' ,
    'attachment_name',
    'user_id' ,
    'url' ,
    'url2' ];
    protected $hidden = ['updated_at','created_at','user_id'];

    public function FileCourseSegment()
    {
        return $this->belongsTo('Modules\UploadFiles\Entities\FileCourseSegment', 'id', 'file_id');
    }

    public function FileLesson()
    {
        return $this->hasMany('Modules\UploadFiles\Entities\FileLesson');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function getUrl2Attribute() {
        return url(Storage::url($this->attributes['url2']));
      }
      public function getUrl1Attribute() {
        return 'https://docs.google.com/viewer?url=' .url(Storage::url($this->attributes['url2']));
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
        return $this->hasOne('App\CourseItem', 'item_id')->where('type', 'file');
    }

    public function lessons()
    {
        return $this->hasManyThrough('App\Lesson' ,'Modules\UploadFiles\Entities\FileLesson', 'file_id' , 'id' , 'id' , 'id' );
    }
}
