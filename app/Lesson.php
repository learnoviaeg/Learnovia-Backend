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
        return $this->belongsToMany('Modules\\'.$name.'\Entities\\'.$model, $model.'_lessons', 'lesson_id', $model.'_id');
    }
    protected $hidden = [
        'created_at','updated_at'
    ];

    public function FileLesson()
    {
        return $this->hasMany('Modules\UploadFiles\Entities\FileLesson', 'lesson_id', 'id');
    }

    public function MediaLesson()
    {
        return $this->hasMany('Modules\UploadFiles\Entities\MediaLesson', 'lesson_id', 'id');
    }

    public function QuizLesson()
    {
        return $this->hasMany('Modules\QuestionBank\Entities\QuizLesson', 'lesson_id', 'id');
    }

    public function AssignmentLesson()
    {
        return $this->hasMany('Modules\Assigments\Entities\AssignmentLesson', 'lesson_id', 'id');
    }
}
