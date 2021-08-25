<?php

namespace App\Listeners;

use App\Events\LessonCreatedEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class AddSecondChainListener
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
     * @param  LessonCreatedEvent  $event
     * @return void
     */
    public function handle(LessonCreatedEvent $event)
    {
        SecondaryChain::firstOrCreate([
            'user_id' => 1,
            'role_id' => 1,
            'group_id' => $class->id,
            'course_id' => $event->enroll->course,
            'lesson_id' => $event->lesson->id,
            'enroll_id' => $event->enroll->id
        ]);
    }
}
