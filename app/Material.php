<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $fillable = [
        'item_id', 'name','publish_date','course_id','lesson_id','type','link','visible','mime_type'
    ];
    protected $appends = ['media_type'];
    protected $hidden = ['mime_type'];
    public function getMediaTypeAttribute(){
        if($this->mime_type != null)
            return $this->mime_type ;
        return 'Link';
    }
    public function course(){
        return $this->belongsTo('App\Course');
    }
    public function lesson(){
        return $this->belongsTo('App\Lesson');
    }
}
