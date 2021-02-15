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


        $enroll_students = Enroll::where('course',$course_id)->where('role_id',3)->get();

        $lessons = Lesson::whereIn('course_segment_id',$enroll_students->pluck('course_segment'))->pluck('id');

        //Assignments
        $assignments = AssignmentLesson::whereIn('lesson_id',$lessons)->get()->pluck('user_seen_number');

        //quizzes
        $quizzes = QuizLesson::whereIn('lesson_id',$lessons)->get()->pluck('user_seen_number');

        //h5p
        $h5p = h5pLesson::whereIn('lesson_id',$lessons)->get()->pluck('user_seen_number');

        //materials
        $materials = Material::whereIn('lesson_id',$lessons)->get()->pluck('user_seen_number');

        //items count 
        $items_count = count($assignments) + count($quizzes) + count($h5p)  + count($materials);

        //sum all the seen number for all components
        $sum_views = array_sum($assignments->toArray()) + array_sum($quizzes->toArray()) + array_sum($h5p->toArray()) + array_sum($materials->toArray());

        $percentage = ($sum_views / (count($enroll_students) * $items_count)) * 100;

        Course::where('id',$course_id)->update([
            'progress' => round($percentage,2)
        ]);
    }
}