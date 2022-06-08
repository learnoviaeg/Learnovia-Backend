<?php

namespace App\Listeners;

use App\Events\CreateCourseItemEvent;
use Illuminate\Queue\InteractsWithQueue;
use App\Repositories\NotificationRepoInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\UserCourseItem;
use Carbon\Carbon;

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
        dd($event->usercourseItem->courseItem->item->lessons);
        $reqNot=[
            'message' => $event->usercourseItem->courseItem->item->name . ' ' . $event->usercourseItem->courseItem->type . ' is created',
            'item_id' => $event->usercourseItem->courseItem->item_id,
            'item_type' => $event->usercourseItem->courseItem->type,
            'type' => 'notification',
            'publish_date' => Carbon::now()->format('Y-m-d H:i:s'), // must be on itemLesson ... met2gela
            // 'lesson_id' => null, //same publish_date
            // 'course_name' => null, // same issue
            'lesson_id' => $event->usercourseItem->courseItem->item->Lesson[0]->id, //same publish_date
            'course_name' => $event->usercourseItem->courseItem->item->Lesson[0]->course->name, // same issue
        ];
        $users=UserCourseItem::where('course_item_id',$event->usercourseItem->courseItem->id)->pluck('user_id');

        $this->notification->sendNotify($users,$reqNot);

        // $this->notification->sendNotify($users->toArray(), $event->usercourseItem->courseItem->type.' is created', $event->usercourseItem->courseItem->item_id, 'notification', $event->usercourseItem->courseItem->type);
    }
}
