<?php

use Illuminate\Database\Seeder;
use App\Course;
use App\UserGrader;
use App\GradeCategory;

class fillCourseIdOnUserGrade extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user_graders=UserGrader::whereNull('course_id')->get();
        foreach($user_graders as $user_grade)
        {
            $gradeCat=GradeCategory::find($user_grade->item_id);
            if(isset($gradeCat)){
                $course=Course::find($gradeCat->course_id);
                if(isset($course)){
                    $user_grade->course_id=$gradeCat->course_id;
                    $user_grade->save();
                }
            }
        }
    }
}
