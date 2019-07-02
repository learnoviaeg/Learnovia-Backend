<?php

namespace Modules\UploadFiles\Entities;

use Illuminate\Database\Eloquent\Model;

class MediaCourseSegment extends Model
{
    protected $table = 'media_course_segments';
    protected $fillable = [];

    public function Media()
    {
        return $this->hasMany('Modules\UploadFiles\Entities\Media', 'id', 'media_id');
    }

}
