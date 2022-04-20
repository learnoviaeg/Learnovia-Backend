<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\UserSeen;
use Illuminate\Support\Facades\DB;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Lesson as Lessonmodel;

class h5pLesson extends Model
{
      use Auditable, SoftDeletes;

      protected $fillable = ['content_id',
        'lesson_id',
        'visible',
        'publish_date' ,
        'start_date' ,
        'due_date',
        'user_id',
        'seen_number',
        'restricted'
    ];
    protected $appends = ['user_seen_number'];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];


    public function getUserSeenNumberAttribute(){

        $user_seen = 0;
        if($this->seen_number != 0)
            $user_seen = UserSeen::where('type','h5p')->where('item_id',$this->content_id)->where('lesson_id',$this->lesson_id)->count();

        return $user_seen;
    }

    public function user(){
        return $this->belongsTo('App\User');
    }

    public function getNameAttribute(){
        return DB::table('h5p_contents')->whereId($this->content_id)->first()->title;
    }

    public function lesson()
    {
        return $this->belongsTo('App\Lesson', 'lesson_id', 'id');
    }

    public function h5pContent(){
        return $this->belongsTo('Djoudi\LaravelH5p\Eloquents\H5pContent','content_id');
    }

    public function courseItem(){
        return $this->hasOne('App\CourseItem', 'item_id')->where('type', 'h5p_content');
    }

    public function getRestrictedAttribute()
    {
        if($this->attributes['restricted'])
            return True;
        return False;
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
        //$lessons_id   = pageLesson::where('page_id', $new->page_id)->pluck('lesson_id');
        $lesson_id   = $new->lesson_id;
        $course_id[]  = Lessonmodel::where('id', $lesson_id)->first()->course_id;
        return $course_id;
    }
    // end function get name and value attribute
}
