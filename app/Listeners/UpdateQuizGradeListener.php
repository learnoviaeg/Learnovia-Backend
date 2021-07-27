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
                // if(($question['question_id'] != 100))
                // continue;
            if(isset($question['grade_details']->exclude_mark) && $question['grade_details']->exclude_mark == false)
                continue;  
            $marks_of_all_questions += (float)$question['grade_details']->total_mark;
        }
        foreach(QuizLesson::where('quiz_id', $event->QuizQuestion['quiz_id'])->cursor()  as $quiz_lesson){
            $quiz_lesson->update(['questions_mark' => $marks_of_all_questions ,
                                    'grade'=> (($quiz_lesson->grade == 0)? $marks_of_all_questions : $marks_of_all_questions)
                                ]);
        }
    }
}
