<?php

namespace App\Listeners;

use App\Events\LessonCreatedEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\SecondaryChain;
use App\Enroll;

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
        $enrollsOfCourse=Enroll::where('course',$event->lesson->course_id);//->get();
        if(($event->lesson->shared_classes)!= null)
            $enrollsOfCourse->whereIn('group',$event->lesson->shared_classes);
        foreach($enrollsOfCourse->cursor() as $enroll)
        {
            SecondaryChain::firstOrCreate([
                'user_id' => $enroll->user_id,
                'role_id' => $enroll->role_id,
                'group_id' => $enroll->group,
                'course_id' => $enroll->course,
                'lesson_id' => $event->lesson->id,
                'enroll_id' => $enroll->id
            ]);
        }
    }
}
