<?php

namespace App\Repositories;
use Illuminate\Http\Request;
use App\Enroll;

class EnrollmentRepository implements EnrollmentRepositoryInterface
{
    public function calculate_course_progress($course_id){

        //enrolled people. (enrolls table)
        //get course lessons
        //all assignments,quizes,materials,h5p items (with user_seen_numbers)


        // enrolled = 2
        // quiz1  assign1 (items with count 2)
        // 2        1 

        //user_seen_number in quiz + user_seen_number in assign -> 2+1 = 3
        //(3/enrolled_studenrs * count_of_items (2x2) ) = 0.75 
        //0.75x100 = 75%
        // so the progress will be 75%


        //check h5p lesson and test the mutators and apply it to all components
    }
}