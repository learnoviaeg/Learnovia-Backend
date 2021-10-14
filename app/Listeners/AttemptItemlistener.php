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
use Auth;
use App\Enroll;
use App\UserGrader;
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
            
            // if((Auth::user()->can('site/quiz/unLimitedAttempts')))
            //     $max_attempt=1;

            for($key =1; $key<=$max_attempt; $key++){
                $gradeItem = GradeItems::firstOrcreate([
                    'type' => 'Attempts',
                    'index' => $key,
                    'name' => 'Attempt number ' .$key,
                    'grade_category_id' => $GradeCategory->id,
                ]);

                $enrolled_students = Enroll::where('role_id' , 3)->where('course',$GradeCategory->course_id)->pluck('user_id');
                foreach($enrolled_students as $student){
                    UserGrader::firstOrcreate([
                        'user_id'   => $student,
                        'item_type' => 'Item',
                        'item_id'   => $gradeItem->id,
                        'grade'     => null
                    ]);
                }
                event(new GradeItemEvent($gradeItem));
            }
        }
    }
}
