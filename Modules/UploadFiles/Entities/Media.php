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
        return $this->belongsTo('Modules\UploadFiles\Entities\MediaLesson', 'id', 'media_id');
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
}
