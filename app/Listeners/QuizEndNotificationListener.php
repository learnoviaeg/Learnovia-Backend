<?php

namespace App\Listeners;

use App\Events\QuizEndReminderEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Carbon\Carbon;

class QuizEndNotificationListener
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
     * @param  QuizEndReminderEvent  $event
     * @return void
     */
    public function handle(QuizEndReminderEvent $event)
    {
        $interval = (new \DateTime($event->quizLesson->due_date))->diff(new \DateTime($event->quizLesson->start_date));
        // dd($interval);
        if($interval->days == 1){
            ///send notification before quiz emds by an hour
            $notification_date = Carbon::parse($event->quizLesson->due_date)->subHour();
            $resulted_date = Carbon::parse($notification_date);
            $seconds = Carbon::parse($resulted_date->diffInSeconds(Carbon::now()));
            $job = (new \App\Jobs\QuizEndNotificationJob($event->quizLesson))->delay($seconds);
            dispatch($job);
        }

        if($interval->days > 1){
            ///send notification before quiz emds by a day
            $notification_date = Carbon::parse($event->quizLesson->due_date)->subDays(1);
            $resulted_date = Carbon::parse($notification_date);
            $seconds = Carbon::parse($resulted_date->diffInSeconds(Carbon::now()));
            $job = (new \App\Jobs\QuizEndNotificationJob($event->quizLesson))->delay($seconds);
            dispatch($job);
        }

    }
}
