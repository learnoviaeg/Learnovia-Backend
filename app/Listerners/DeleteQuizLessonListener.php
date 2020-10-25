<?php

namespace App\Listerners;

use App\Events\QuizLessonEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class DeleteQuizLessonListener
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
     * @param  QuizLessonEvent  $event
     * @return void
     */
    public function handle(QuizLessonEvent $event)
    {
        //
    }
}
