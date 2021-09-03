<?php

namespace App\Helpers;

use Modules\Assigments\Entities\assignment;
use Modules\QuestionBank\Entities\quiz;
use App\SecondaryChain;
use App\Material;
use App\Lesson;

class ComponentsHelper
{
    public $course,$class,$teacher,$lessons,$from,$to;

    public function __construct($course = null)
    {
        $this->course = $course;
        $this->lessons = $this->getLessons();
    }

    public function setClass($class){
        $this->class = $class;
    }

    public function setTeacher($teacher){
        $this->teacher = $teacher;
    }

    public function setLessons($lessons){
        $this->lessons = $lessons;
    }

    public function setDate($from,$to){
        $this->from = $from;
        $this->to = $to;
    }

    private function getLessons(){
        $this->lessons = SecondaryChain::where('course_id',$this->course);

        if($this->class){
            $this->lessons->where('group_id',$this->class);
        }

        return $this->lessons->select('lesson_id')->distinct()->pluck('lesson_id');
    }

    public function materials()
    {
        $materials = Material::where('course_id',$this->course);
        
        if($this->teacher){
            $materials->where('created_by', $this->teacher);
        }

        if(count($this->lessons) > 0 ){
            $materials->whereIn('lesson_id', $this->lessons);
        }

        if($this->from && $this->to){
            $materials->whereBetween('created_at', [$this->from,$this->to]);
        }

        return $materials;
    }

    public function assignments()
    {
        $assignments = assignment::whereHas('assignmentLesson' , function($query){

            if(count($this->lessons) > 0 ){
                $query->whereIn('lesson_id', $this->lessons);
            }

        });

        if($this->teacher){
            $assignments->where('created_by', $this->teacher);
        }

        if($this->from && $this->to){
            $assignments->whereBetween('created_at', [$this->from,$this->to]);
        }

        return $assignments;
    }

    public function quizzes()
    {
        $quizzes = quiz::whereHas('quizLesson' , function($query){

            if(count($this->lessons) > 0 ){
                $query->whereIn('lesson_id', $this->lessons);
            }

        });

        if($this->teacher){
            $quizzes->where('created_by', $this->teacher);
        }

        if($this->from && $this->to){
            $quizzes->whereBetween('created_at', [$this->from,$this->to]);
        }

        return $quizzes;
    }

    public function lessons(){

        $lessons = Lesson::whereIn('id',$this->lessons);

        if($this->from && $this->to){
            $lessons->whereBetween('created_at', [$this->from,$this->to]);
        }

        return $lessons;
    }
}