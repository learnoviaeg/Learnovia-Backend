<?php

namespace Modules\UploadFiles\Entities;

use Illuminate\Database\Eloquent\Model;

class FileCourseSegment extends Model
{
    protected $table = 'file_course_segments';
    protected $fillable = [];

    public function File()
    {
        return $this->hasMany('Modules\UploadFiles\Entities\File', 'id', 'file_id');
    }
}
