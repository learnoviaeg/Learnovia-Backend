<?php

namespace App\Listeners;

use App\Events\AssignmentEndReminderEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Carbon\Carbon;

class AssignmentEndNotificationListener
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
     * @param  AssignmentEndReminderEvent  $event
     * @return void
     */
    public function handle(AssignmentEndReminderEvent $event)
    {
        $interval = (new \DateTime($event->assignmentLesson->due_date))->diff(new \DateTime($event->assignmentLesson->start_date));
        if($interval->days == 0 && $interval->h < 1 )
            return ;

        if($interval->days < 1 && $interval->h >= 1 ){
                ///send notification before assignment emds by an hour
            $notification_date = Carbon::parse($event->assignmentLesson->due_date)->subHour();
            $resulted_date = Carbon::parse($notification_date);
            $seconds = Carbon::parse($resulted_date->diffInSeconds(Carbon::now()));
            $job = (new \App\Jobs\AssignmentEndNotificationJob($event->assignmentLesson))->delay($seconds);
            dispatch($job);
        }

        if($interval->days >= 1){
            ///send notification before assignment emds by a day
            $notification_date = Carbon::parse($event->assignmentLesson->due_date)->subDays(1);
            $resulted_date = Carbon::parse($notification_date);
            $seconds = Carbon::parse($resulted_date->diffInSeconds(Carbon::now()));
            $job = (new \App\Jobs\AssignmentEndNotificationJob($event->assignmentLesson))->delay($seconds);
            dispatch($job);
        }
    }
}
