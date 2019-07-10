<?php

namespace Modules\UploadFiles\Entities;

use Illuminate\Database\Eloquent\Model;

class FileCourseSegment extends Model
{
    protected $table = 'file_course_segments';
    protected $fillable = [];
    protected $hidden = ['updated_at','created_at'];


    public function File()
    {
        return $this->hasMany('Modules\UploadFiles\Entities\File', 'id', 'file_id');
    }
}
