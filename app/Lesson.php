<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = ['name','course_segment_id','index'];
    public function courseSegment(){
        return $this->belongsTo('App\CourseSegment');
    }
    public static function Get_lessons_per_CourseSegment_from_lessonID($id){
        $lesson=self::where('id',$id)->first();
        $lessons=$lesson->courseSegment->lessons;
        return $lessons;
    }
    protected $hidden = [
        'created_at','updated_at'
    ];
}
