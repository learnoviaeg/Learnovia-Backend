<?php

namespace App\Listeners;

use App\Events\UpdatedQuizQuestionsEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Lesson;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\quiz_questions;
use App\GradeCategory;
use App\Events\GraderSetupEvent;

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
                $quiz_lesson->grade = $marks_of_all_questions;
            $quiz_category = GradeCategory::where('instance_type','Quiz')->where('instance_id',$quiz_lesson->quiz_id)->where('lesson_id', $quiz_lesson->lesson_id);
            $quiz_category->update(['max' => $marks_of_all_questions]);
            ///launching event to recalculate grades in grader setup 
            event(new GraderSetupEvent($quiz_category->first()->Parents));

            if(is_null($quiz_lesson->assign_user_gradepass))
                $quiz_lesson->grade_pass = $marks_of_all_questions/2;
            $quiz_lesson->save();
        }
    }
}
