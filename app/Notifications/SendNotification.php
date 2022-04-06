<?php

namespace App\Notifications;

use App\Jobs\SendNotifications;
use App\Jobs\createAndAttachNoti;
use App\Notification;
use Carbon\Carbon;

class SendNotification
{
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
    
    //sending notifications using firebase
    public function toFirebase($notification)
    {
        //calculate time the job should fire at
        $notificationDelaySeconds = Carbon::parse($notification->publish_date)->diffInSeconds(Carbon::now()); 
        if($notificationDelaySeconds < 0) {
            $notificationDelaySeconds = 0;
        }

        //this job is for sending firebase notifications
        // dd($notification);
        $notificationJob = (new SendNotifications($notification))->delay($notificationDelaySeconds);
        dispatch($notificationJob);
    }

    //store notifications in database
    public function toDatabase($notification,$users)
    {
        $createdNotification = Notification::create($notification);
        $attachedJob = (new createAndAttachNoti($createdNotification,$users));
        dispatch($attachedJob);
        //$createdNotification->users()->attach($users);

        return $createdNotification;
    }
}
