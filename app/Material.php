<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Modules\UploadFiles\Entities\file;


class Material extends Model
{
    protected $fillable = [
        'item_id', 'name','publish_date','course_id','lesson_id','type','link','visible','mime_type'
    ];
    protected $appends = ['media_type','attachment_name'];
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
    
    }
    public function course(){
        return $this->belongsTo('App\Course');
    }
    public function lesson(){
        return $this->belongsTo('App\Lesson');
    }
}
