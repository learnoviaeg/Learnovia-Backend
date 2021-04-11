<?php

namespace Modules\UploadFiles\Entities;

use Illuminate\Database\Eloquent\Model;

class MediaLesson extends Model
{
    protected $table = 'media_lessons';
    protected $fillable = ['index' , 'visible' , 'publish_date' , 'media_id' , 'lesson_id'];
    protected $hidden = ['updated_at','created_at'];


    public function Media()
    {
        return $this->hasMany('Modules\UploadFiles\Entities\Media', 'id', 'media_id');
    }

    public static function getNextIndex($lesson_id){
        if(self::whereLesson_id($lesson_id)->max('index') == null)
            return 1;
        return self::whereLesson_id($lesson_id)->max('index') + 1;
    }
}
