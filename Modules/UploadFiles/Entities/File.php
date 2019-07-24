<?php

namespace Modules\UploadFiles\Entities;

use Illuminate\Database\Eloquent\Model;

class file extends Model
{
    protected $fillable = [];
    protected $hidden = ['updated_at','created_at','user_id'];

    public function FileCourseSegment()
    {
        return $this->belongsTo('Modules\UploadFiles\Entities\FileCourseSegment', 'id', 'file_id');
    }

    public function FileLesson()
    {
        return $this->belongsTo('Modules\UploadFiles\Entities\FileLesson', 'id', 'file_id');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }
}
