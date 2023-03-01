<?php

namespace Modules\Survey\Entities;

use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    protected $fillable = ['name', 'template', 'year', 'types','levels', 'classes', 'courses', 'segments', 'start_date',
     'end_date','created_by'];

     public function getClassesAttribute($value)
     {
         if (is_null($value))
             return $value;
         $temp = [];
         $value = unserialize($value);
         foreach ($value as $classes) {
             if (gettype($classes) == 'array' || gettype($classes) == 'array') {
                 foreach ($classes as $class)
                     $temp[] = $class;
             } else {
                 $temp[] = $classes;
             }
         }
         return $temp;
     }
 
     public function getLevelsAttribute($value)
     {
         if (is_null($value))
             return $value;
         $temp = [];
         $value = unserialize($value);
         foreach ($value as $levels) {
             if (gettype($levels) == 'array' || gettype($levels) == 'array') {
                 foreach ($levels as $level)
                     $temp[] = $level;
             } else {
                 $temp[] = $levels;
             }
         }
         return $temp;
     }
 
     public function getCoursesAttribute($value)
     {
         if (is_null($value))
             return $value;
         $temp = [];
         $value = unserialize($value);
         foreach ($value as $courses) {
             if (gettype($courses) == 'array' || gettype($courses) == 'array') {
                 foreach ($courses as $course)
                     $temp[] = $course;
             } else {
                 $temp[] = $courses;
             }
         }
         return $temp;
     }

     public function getSegmentsAttribute($value)
     {
         if (is_null($value))
             return $value;
         $temp = [];
         $value = unserialize($value);
         foreach ($value as $courses) {
             if (gettype($courses) == 'array' || gettype($courses) == 'array') {
                 foreach ($courses as $course)
                     $temp[] = $course;
             } else {
                 $temp[] = $courses;
             }
         }
         return $temp;
     }

     public function getTypesAttribute($value)
     {
         if (is_null($value))
             return $value;
         $temp = [];
         $value = unserialize($value);
         foreach ($value as $courses) {
             if (gettype($courses) == 'array' || gettype($courses) == 'array') {
                 foreach ($courses as $course)
                     $temp[] = $course;
             } else {
                 $temp[] = $courses;
             }
         }
         return $temp;
     }

     public function Question()
     {
         return $this->belongsToMany('Modules\QuestionBank\Entities\Questions', 'survey_questions', 'survey_id', 'question_id');
     }
}
