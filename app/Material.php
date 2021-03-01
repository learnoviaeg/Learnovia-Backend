<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Modules\UploadFiles\Entities\file;
use  Modules\Page\Entities\page;
use Modules\UploadFiles\Entities\media;
use Auth;
use App\UserSeen;

class Material extends Model
{
    protected $fillable = [
        'item_id', 'name','publish_date','course_id','lesson_id','type','link','visible','mime_type','seen_number'
    ];
    protected $appends = ['media_type','attachment_name','user_seen_number'];
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

        $url= config('app.url').'api/materials/'.$this->id.'/?api_token='.Auth::user()->api_token;

        return $url;
    }

    public function course(){
        return $this->belongsTo('App\Course');
    }
    public function lesson(){
        return $this->belongsTo('App\Lesson');
    }
}
