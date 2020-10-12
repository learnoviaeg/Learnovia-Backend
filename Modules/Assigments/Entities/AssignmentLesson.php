<?php

namespace Modules\Assigments\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Lesson;
use App\Course;
use App\Level;
use Carbon\Carbon;
use App\Classes;
class AssignmentLesson extends Model
{
    protected $fillable = ['assignment_id','lesson_id','publish_date','visible', 'start_date', 'due_date', 'is_graded', 'grade_category', 'mark', 'scale_id', 'allow_attachment'];
 
    protected $appends = ['type','class','level','course','started'];
    public function getTypeAttribute(){
        return 'assignment';
    }
    public function getClassAttribute(){
        return Classes::find(Lesson::find($this->lesson_id)->courseSegment->segmentClasses[0]->classLevel[0]->class_id)->name;   
    }
    public function getCourseAttribute(){
        return  Course::find(Lesson::find($this->lesson_id)->courseSegment->course_id)->name;
    }    
    public function getLevelAttribute(){
        return Level::find(Lesson::find($this->lesson_id)->courseSegment->segmentClasses[0]->classLevel[0]->yearLevels[0]->level_id)->name;
    } 
    public function getStartedAttribute(){
        if($this->publish_date > Carbon::now() &&  Auth::user()->can('site/course/student'))
            return false;
        else
            return true;  
    }
    public function Assignment()
    {
        return $this->hasMany('Modules\Assigments\Entities\assignment', 'id', 'assignment_id');
    }
    public function UserAssignment()
    {
        return $this->hasMany('Modules\Assigments\Entities\UserAssigment', 'id', 'assignment_lesson_id');
    }
}

