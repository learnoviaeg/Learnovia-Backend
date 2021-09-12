<?php

namespace App\Listeners;

use App\Events\UpdatedQuizQuestionsEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Lesson;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\quiz_questions;

class UpdateQuizGradeListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Handle the event.
     *
     * @param  UpdatedQuizQuestionsEvent  $event
     * @return void
     */
    public function handle(UpdatedQuizQuestionsEvent $event)
    {
        $marks_of_all_questions = 0;
        foreach(quiz_questions::where('quiz_id', $event->Quiz)->cursor() as $question){
            if(is_null($question['grade_details']))
                continue;
            if(isset($question['grade_details']->exclude_mark) && (bool) $question['grade_details']->exclude_mark == true)
                continue;  
            $marks_of_all_questions += (float)$question['grade_details']->total_mark;
        }
        foreach(QuizLesson::where('quiz_id', $event->Quiz)->cursor()  as $quiz_lesson){
            $quiz_lesson->questions_mark =  $marks_of_all_questions ;
            if(is_null($quiz_lesson->grade_by_user))
                $quiz_lesson->grade = $marks_of_all_questions;
                
            if(is_null($quiz_lesson->assign_user_gradepass))
                $quiz_lesson->grade_pass = $marks_of_all_questions/2;
            
            $quiz_lesson->save();
        }
    }
}
