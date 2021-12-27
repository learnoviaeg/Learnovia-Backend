<?php

namespace App\Listeners;

use App\Events\updateQuizAndQuizLessonEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\GradeCategory;

class updateGradeCatListener
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
     * @param  updateQuizAndQuizLessonEvent  $event
     * @return void
     */
    public function handle(updateQuizAndQuizLessonEvent $event)
    {
        // $gradeCat=GradeCategory::where('instance_type','Quiz')->where('instance_id',$event->quizLesson->quiz_id)->where('lesson_id', $event->quizLesson->lesson_id)->update([
        //     'hidden' => $event->quizLesson->visible,
        //     'calculation_type' => json_encode($event->quizLesson->grading_method_id),
        // ]);
    }
}
