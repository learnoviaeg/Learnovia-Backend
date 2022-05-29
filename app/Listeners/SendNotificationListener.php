<?php

namespace App\Listeners;

use App\Events\CreateCourseItemEvent;
use Illuminate\Queue\InteractsWithQueue;
use App\Repositories\NotificationRepoInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\UserCourseItem;

class SendNotificationListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(NotificationRepoInterface $notification)
    {
        $this->notification=$notification;
    }

    /**
     * Handle the event.
     *
     * @param  CreateCourseItemEvent  $event
     * @return void
     */
    public function handle(CreateCourseItemEvent $event)
    {
        $users=UserCourseItem::where('course_item_id',$event->usercourseItem->courseItem->id)->pluck('user_id');
        // dd($users);
        $this->notification->sendNotify($users->toArray(), $event->usercourseItem->courseItem->type.' is created', $event->usercourseItem->courseItem->item_id, 'notification', $event->usercourseItem->courseItem->type);
    }
}
