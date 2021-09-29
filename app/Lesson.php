<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = ['name','course_segment_id','index' , 'image' , 'description','shared_lesson','course_id' ,'shared_classes'];

    protected $dispatchesEvents = [
        'created' => \App\Events\LessonCreatedEvent::class,
    ];

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
        return $this->belongsToMany('Modules\\'.$name.'\Entities\\'.$model, $model.'_lessons', 'lesson_id', $model.'_id')->withPivot('publish_date','created_at');
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
    
    public function H5PLesson()
    {
        return $this->hasMany('App\h5pLesson', 'lesson_id', 'id');
    }

    public function Quiz()
    {
        return $this->hasMany('Modules\QuestionBank\Entities\Quiz','id');
    }

    public function SecondaryChain(){
        return $this->hasMany('App\SecondaryChain','lesson_id' , 'id');

    }

    public function getSharedClassesAttribute($value)
    {   if($value != null){
            $content= json_decode($value);
            return Classes::whereIn('id',$content)->get();
        }
        return $value;
    }

    public function course()
    {
        return $this->belongsTo('App\Course','course_id','id');
    }
    

}
