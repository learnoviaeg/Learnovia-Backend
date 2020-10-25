<?php

namespace App\Listerners;

use App\Events\AssignmentLessonEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class DeleteAssignmentLessonListener
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
     * @param  AssignmentLessonEvent  $event
     * @return void
     */
    public function handle(AssignmentLessonEvent $event)
    {
        Timeline::where('lesson_id',$event->assignment_lesson->lesson_id)->where('item_id',$event->assignment_lesson->assignment_id)->where('type','assignment')->delete();
    }
}
