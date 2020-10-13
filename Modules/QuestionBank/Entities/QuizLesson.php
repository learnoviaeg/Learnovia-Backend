<?php

namespace Modules\QuestionBank\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Classes;
use App\Lesson;
use App\Course;
use App\Level;
use Carbon\Carbon;
class QuizLesson extends Model
{
    protected $fillable = [
        'quiz_id',
        'lesson_id',
        'start_date',
        'due_date',
        'max_attemp',
        'grading_method_id',
        'grade',
        'grade_category_id',
        'publish_date',
        'visible','index'
    ];
    protected $table = 'quiz_lessons';
    protected $appends = ['type','class','level','course','started'];
    public function getTypeAttribute(){
        return 'quiz';
    }
    public function getClassAttribute(){
        $class = Classes::find(Lesson::find($this->lesson_id)->courseSegment->segmentClasses[0]->classLevel[0]->class_id);
        return isset($class)?$class->name:null ;   
    }
    public function getCourseAttribute(){
        $course = Course::find(Lesson::find($this->lesson_id)->courseSegment->course_id);
        return  isset($course)?$course->name:null;
    }    
    public function getLevelAttribute(){
        $level = Level::find(Lesson::find($this->lesson_id)->courseSegment->segmentClasses[0]->classLevel[0]->yearLevels[0]->level_id);
        return isset($level)?$level->name:null;
    }  
    public function getStartedAttribute(){
        if($this->publish_date > Carbon::now() &&  Auth::user()->can('site/course/student'))
            return false;
        else
            return true;  
      } 
      
    public function quiz()
    {
        return $this->belongsTo('Modules\QuestionBank\Entities\quiz', 'quiz_id', 'id');
    }
    public function lesson()
    {
        return $this->belongsTo('App\Lesson', 'lesson_id', 'id');
    }
    public function grading_method()
    {
        return $this->belongsTo('App\GradingMethod', 'grading_method_id', 'id');
    }
  
}
