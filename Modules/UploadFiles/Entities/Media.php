<?php

namespace Modules\UploadFiles\Entities;

use Illuminate\Database\Eloquent\Model;

class media extends Model
{
    protected $fillable = ['id','name','course_segment_id','media_id' , 'show'];
    protected $hidden = ['updated_at','created_at','user_id'];

    public function MediaCourseSegment()
    {
        return $this->belongsTo('Modules\UploadFiles\Entities\MediaCourseSegment', 'id', 'media_id');
    }

    public function MediaLesson()
    {
        return $this->hasMany('Modules\UploadFiles\Entities\MediaLesson');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }
    protected $appends = ['media_type'];

    public function getMediaTypeAttribute(){
        if($this->type != null)
            return 'Media';
        return 'Link';
    }

    public function courseItem(){
        return $this->hasOne('App\CourseItem', 'item_id')->where('type', 'media');
    }

    public function lessons()
    {
        return $this->hasManyThrough('App\Lesson' ,'Modules\UploadFiles\Entities\MediaLesson', 'media_id' , 'id' , 'id' , 'id' );
    }
}
