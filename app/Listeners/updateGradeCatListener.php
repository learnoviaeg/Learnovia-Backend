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
        $gradeCat=GradeCategory::whereId($event->quizLesson->grade_category_id)->update([
            'hidden' => $event->quizLesson->visible,
            'lesson_id' => $event->quizLesson->lesson_id,
            'calculation_type' => json_encode($event->quizLesson->grading_method_id),
        ]);
    }
}
