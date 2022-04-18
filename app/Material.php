<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Modules\UploadFiles\Entities\file;
use  Modules\Page\Entities\page;
use Modules\UploadFiles\Entities\media;
use Auth;
use App\UserSeen;
use App\Traits\Auditable;

class Material extends Model
{
   use Auditable;
    
    protected $fillable = [
        'item_id', 'name','publish_date','course_id','lesson_id','type','link','visible','mime_type','seen_number','created_by','restricted'
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
            return file::find($this->item_id)->attachment_name;
        if($this->type == 'media' && $this->media_type!='Link' && $this->media_type!='media link')
            return media::find($this->item_id)->attachment_name;
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
        $old_count = count($old);
        if ($old_count == 0) {
            $course_id = [intval($new['course_id'])];
        }else{
            if ($old['course_id'] == $new['course_id']) {
                $course_id = [intval($new['course_id'])];
            }else{
                $course_id = [intval($old['course_id']), intval($new['course_id'])];
            }
        }
        return $course_id;
    }
    // end function get name and value attribute
}

