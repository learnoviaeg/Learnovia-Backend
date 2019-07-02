<?php

namespace Modules\UploadFiles\Entities;

use Illuminate\Database\Eloquent\Model;

class media extends Model
{
    protected $fillable = ['id','name','course_segment_id','media_id'];
}
