<?php

namespace App\Helpers;

use Modules\Assigments\Entities\assignment;
use App\Material;
use Modules\QuestionBank\Entities\quiz;

class ComponentsCounterHelper
{
    public $course,$class,$teacher,$lessons,$from,$to;

    public function __construct($course,$class = null,$teacher = null,$lesson = null,$from = null,$to = null)
    {
        $this->course = $course;
        $this->class = $class;
        $this->teacher = $teacher;
        $this->lessons = $lesson ? [$lesson] : $this->getLessons();
        $this->from = $from;
        $this->to = $to;
    }

    private function getLessons(){
        //get the lessons in given class or course or both from secoundry cahin table
    }

    public function materialsCounter()
    {
        $materialsCount = Material::where('course_id',$this->course);

        if($this->teacher){
            $materialsCount->where('created_by', $this->teacher);
        }

        if(count($this->lesson) > 0 ){
            $materialsCount->whereIn('lesson_id', $this->lessons);
        }

        if($this->from && $this->to){
            $materialsCount->whereBetween('created_at', [$this->from,$this->to]);
        }

        return $materialsCount->count();
    }

    public function assignmentsCounter()
    {
        $assignmentCount = assignment::with(['assignmentLesson' => function($query){

            if(count($this->lesson) > 0 ){
                $query->whereIn('lesson_id', $this->lessons);
            }

        }]);

        if($this->teacher){
            $assignmentCount->where('created_by', $this->teacher);
        }

        if($this->from && $this->to){
            $assignmentCount->whereBetween('created_at', [$this->from,$this->to]);
        }

        return $assignmentCount->count();
    }

    public function quizzesCounter()
    {
        $quizCount = quiz::with(['quizLesson' => function($query){

            if(count($this->lesson) > 0 ){
                $query->whereIn('lesson_id', $this->lessons);
            }

        }]);

        if($this->teacher){
            $quizCount->where('created_by', $this->teacher);
        }

        if($this->from && $this->to){
            $quizCount->whereBetween('created_at', [$this->from,$this->to]);
        }

        return $quizCount->count();
    }

    public function lessonsCounter(){

        return count($this->lessons);
        
    }
}