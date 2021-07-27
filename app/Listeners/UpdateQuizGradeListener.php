<?php

namespace App\Listeners;

use App\Events\UpdateQuizQuestionsEvent;
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
     * @param  UpdateQuizQuestionsEvent  $event
     * @return void
     */
    public function handle(UpdateQuizQuestionsEvent $event)
    {
        $marks_of_all_questions = 0;
        foreach(quiz_questions::where('quiz_id', $event->QuizQuestion['quiz_id'])->cursor() as $question){
            if(is_null($question['grade_details']))
                continue;
            if(isset($question['grade_details']->exclude_mark) && (bool) $question['grade_details']->exclude_mark == true)
                continue;  
            $marks_of_all_questions += (float)$question['grade_details']->total_mark;
        }
        foreach(QuizLesson::where('quiz_id', $event->QuizQuestion['quiz_id'])->cursor()  as $quiz_lesson){
            $quiz_lesson->questions_mark =  $marks_of_all_questions ;
            if($quiz_lesson->grade == 0){
                $quiz_lesson->grade = $marks_of_all_questions;
            }
            $quiz_lesson->save();
        }
    }
}
