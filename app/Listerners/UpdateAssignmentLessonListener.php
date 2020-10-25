<?php

namespace App\Listerners;

use App\Events\AssignmentLessonEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateAssignmentLessonListener
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
        //
    }
}
