<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Modules\UploadFiles\Entities\file;
use  Modules\Page\Entities\page;
use Modules\UploadFiles\Entities\media;
use Auth;
use App\UserSeen;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Course;
use App\Segment;
use App\Lesson as Lessonmodel;

class Material extends Model
{
   use Auditable, SoftDeletes;
    
    protected $fillable = [
        'item_id', 'name','publish_date','course_id','lesson_id','type','link','visible','mime_type','seen_number','created_by','restricted'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $appends = ['media_type','attachment_name','user_seen_number','main_link'];

    protected $hidden = ['mime_type'];

    public function getMediaTypeAttribute(){
        if($this->mime_type != null)
            return $this->mime_type ;
        if($this->type=='file')
            return null;
        return 'Link';
    }

    public function getAttachmentNameAttribute(){
        if($this->type == 'file')
            return file::whereNull('deleted_at')->find($this->item_id)->attachment_name;
        if($this->type == 'media' && $this->media_type!='Link' && $this->media_type!='media link')
            return media::whereNull('deleted_at')->find($this->item_id)->attachment_name;
    }

    public function getUserSeenNumberAttribute(){
        $user_seen = 0;
        if($this->seen_number != 0)
            $user_seen = UserSeen::where('type',$this->type)->where('item_id',$this->item_id)->where('lesson_id',$this->lesson_id)->count();

        return $user_seen;
    }

    public function getLinkAttribute(){
        $url= config('app.url').'api/materials/'.$this->id.'?api_token='.Auth::user()->api_token;
        return $url;
    }

   public function getMainLinkAttribute()
   {
        if ($this->getOriginal() != null) 
        {
            return $this->getOriginal()['link'];
        }
    }

    public function course(){
        return $this->belongsTo('App\Course');
    }

    public function lesson(){
        return $this->belongsTo('App\Lesson');
    }

    public function user(){
        return $this->belongsTo('App\User','created_by');
    }

    public function item(){
        return $this->morphTo('item' , 'type', 'item_id');
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
        $course   = Course::where('id', intval($new['course_id']))->first();
        $segment  = Segment::where('id', $course->segment_id)->first();
        $academic_year_id[] = $segment->academic_year_id;
        return $academic_year_id;
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_type_name($old, $new)
    {
        $course   = Course::where('id', intval($new['course_id']))->first();
        $segment  = Segment::where('id', $course->segment_id)->first();
        $academic_type_id[] = $segment->academic_type_id;
        return $academic_type_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_level_name($old, $new)
    {
        $level_id[] = Course::where('id', intval($new['course_id']))->first()->level_id;
        return $level_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_class_name($old, $new)
    {
        $lesson_id    = $new->lesson_id;
        $lesson       = Lessonmodel::where('id', $lesson_id)->first();
        $classes      = $lesson['shared_classes']->pluck('id');
        return $classes;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_segment_name($old, $new)
    {
        $segment_id[] = Course::where('id', intval($new['course_id']))->first()->segment_id;
        return $segment_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_course_name($old, $new)
    {
        $course_id = [intval($new['course_id'])];
        return $course_id;
    }
    // end function get name and value attribute
}

