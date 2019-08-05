<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = ['name','course_segment_id','index' , 'image' , 'description'];
    public function courseSegment(){
        return $this->belongsTo('App\CourseSegment');
    }
    public static function Get_lessons_per_CourseSegment_from_lessonID($id){
        $lesson=self::where('id',$id)->first();
        $lessons=$lesson->courseSegment->lessons;
        return $lessons;
    }
    public function module($name,$model)
    {
        // dd($model.'_id');
        return $this->belongsToMany('Modules\\'.$name.'\Entities\\'.$model, $model.'_lessons', 'lesson_id', $model.'_id');
    }
    protected $hidden = [
        'created_at','updated_at'
    ];
}
