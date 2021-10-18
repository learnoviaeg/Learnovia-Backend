<?php

namespace App\Notifications;

use App\Announcement;
use App\userAnnouncement;
use Carbon\Carbon;

class AnnouncementNotification extends SendNotification
{
    public $announcement, $message, $users;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Announcement $announcement, $message)
    {
        $this->announcement = $announcement;
        $this->message = $message;
        $this->users =  userAnnouncement::where('announcement_id', $this->announcement->id)->select('user_id')->distinct()->pluck('user_id')->toArray();
    }

    public function setUsers(array $users){
        $this->users = $users;
    }

    public function send(){

        $publish_date = $this->announcement->publish_date;
        if(Carbon::parse($publish_date)->isPast()){
            $publish_date = Carbon::now();
        }

        //Start preparing notifications object
        $notification = [
            'type' => 'announcement',
            'item_id' => $this->announcement->id,
            'item_type' => 'announcement',
            'message' => $this->message,
            'publish_date' => $publish_date,
            'created_by' => $this->announcement->created_by['id'],
        ];

        //assign notification to given users
        $createdNotification = $this->toDatabase($notification,$this->users);
        
        //firebase Notifications
        $this->toFirebase($createdNotification);
    }
}
