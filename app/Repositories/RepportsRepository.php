<?php

namespace App\Repositories;
use Illuminate\Http\Request;
use App\Enroll;
use App\Lesson;
use Modules\Assigments\Entities\AssignmentLesson;
use Modules\QuestionBank\Entities\QuizLesson;
use App\h5pLesson;
use App\Material;
use App\Course;
use App\UserSeen;

class RepportsRepository implements RepportsRepositoryInterface
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

        $enroll_students = Enroll::where('course',$course_id)->where('role_id',3)->get()->groupBy('class');
        $all_percentages = [];
        $i=0;

        foreach($enroll_students as $enroll){
            
            $lessons = Lesson::whereIn('course_segment_id',$enroll->pluck('course_segment'))->pluck('id');
            
            //Assignments
            $assignments = AssignmentLesson::whereIn('lesson_id',$lessons)->count();
            
            //quizzes
            $quizzes = QuizLesson::whereIn('lesson_id',$lessons)->count();

            //h5p
            $h5p = h5pLesson::whereIn('lesson_id',$lessons)->count();
    
            //materials
            $materials = Material::whereIn('lesson_id',$lessons)->count();

            //items count 
            $items_count = $assignments + $quizzes + $h5p  + $materials;
            
            //sum all the seen number for all components
            $sum_views = UserSeen::whereIn('user_id',$enroll->pluck('user_id'))->whereIn('lesson_id',$lessons)->count();
            $divided_by = count($enroll) * $items_count;

            $percentage = 0;
            if($divided_by > 0)
                $percentage = ($sum_views / $divided_by) * 100;

            if($divided_by > 0){
                $all_percentages[$i]= $percentage;
                $i++;    
            }
        }

        $final=0;
        if(count($all_percentages) > 0)
            $final = (array_sum($all_percentages) / count($all_percentages));

        Course::where('id',$course_id)->update([
            'progress' => round($final,2)
        ]);
       
    }
}