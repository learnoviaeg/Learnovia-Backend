<?php

namespace App\Listeners;

use App\Events\UpdatedAttemptEvent;
use App\Events\GradeAttemptEvent;
use App\Grader\TypeGrader;
use Modules\QuestionBank\Entities\UserQuiz;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class FireAutoCorrectionEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct() //attempt
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  UpdatedAttemptEvent  $event
     * @return void
     */
    public function handle(UpdatedAttemptEvent $event)
    {
        if($event->attempt->isDirty('submit_time'))
        {
            event(new GradeAttemptEvent());
        }
    }
}
