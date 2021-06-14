<?php

namespace App\Listeners;

use App\Events\QuizAttemptEvent;
use App\Events\GradeItemEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\GradeCategory;
use App\GradeItems;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\UserQuiz;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AttemptItemlistener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  QuizAttemptEvent  $event
     * @return void
     */
    public function handle(QuizAttemptEvent $event)
    {
        // $event->item ---> Attempt
        $user_quiz=UserQuiz::where('quiz_lesson_id',$event->attempt->quiz_lesson_id)->get();
        if(count($user_quiz) == 1 ){
            $QuizLesson = QuizLesson::find($event->attempt->quiz_lesson_id);
            $QuizID=$QuizLesson->quiz->id;
            $max_attempt=$QuizLesson->max_attemp;
            $GradeCategory = GradeCategory::where('instance_id' , $QuizID)->first();
            for($key =1; $key<=$max_attempt; $key++){
                $gradeItem = GradeItems::create([
                    'type' => 'Attempts',
                    'item_id' => $key,
                    'name' => 'Attempt number ' .$key,
                    'grade_category_id' => $GradeCategory->id,
                ]);
                event(new GradeItemEvent($gradeItem));
            }
        }
    }
}
