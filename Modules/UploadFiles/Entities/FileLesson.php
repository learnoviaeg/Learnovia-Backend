<?php

namespace Modules\UploadFiles\Entities;

use Illuminate\Database\Eloquent\Model;

class FileLesson extends Model
{
    protected $table = 'file_lessons';
    protected $fillable = ['index' , 'visible' , 'publish_date' , 'file_id' , 'lesson_id'];
    protected $hidden = ['updated_at','created_at'];


    public function File()
    {
        return $this->hasMany('Modules\UploadFiles\Entities\File', 'id', 'file_id');
    }
}
