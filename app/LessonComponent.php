<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LessonComponent extends Model
{
    protected $guarded = [];
    public static function getNextIndex($lesson_id){
        if(self::whereLesson_id($lesson_id)->max('index') == null)
            return 1;
        return self::whereLesson_id($lesson_id)->max('index') + 1;
    }

    public function item(){
        return $this->morphTo('item' , 'model', 'comp_id');
    }
}
