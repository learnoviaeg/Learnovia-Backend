<?php

namespace App\Listeners;

use App\Events\CreateCourseItemEvent;
use Illuminate\Queue\InteractsWithQueue;
use App\Repositories\NotificationRepoInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\UserCourseItem;
use Carbon\Carbon;
use App\Lesson;
use App\h5pLesson;

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
        if($event->usercourseItem->courseItem->type == 'h5p_content'){
            $h5pLesson=h5pLesson::find($event->usercourseItem->courseItem->item_id);

            $reqNot=[
                'message' => 'Interactive is created',
                'item_id' => $event->usercourseItem->courseItem->item_id, //h5pLesson
                'item_type' => $event->usercourseItem->courseItem->type,
                'type' => 'notification',
                'publish_date' => $h5pLesson->publish_date,
                'lesson_id' => $h5pLesson->lesson_id,
                'course_name' => Lesson::find($h5pLesson->lesson_id)->course->name,
            ];
        }
        else
            $reqNot=[
                'message' => isset($event->usercourseItem->courseItem->item->name) ? $event->usercourseItem->courseItem->item->name . ' ' . $event->usercourseItem->courseItem->type . ' is created' :
                            $event->usercourseItem->courseItem->item->title . ' ' . $event->usercourseItem->courseItem->type . ' is created',
                'item_id' => $event->usercourseItem->courseItem->item_id,
                'item_type' => $event->usercourseItem->courseItem->type,
                'type' => 'notification',
                'publish_date' => Carbon::now()->format('Y-m-d H:i:s'), // must be on itemLesson ... met2gela
                // 'lesson_id' => null, //same publish_date
                // 'course_name' => null, // same issue
                'lesson_id' => isset($event->usercourseItem->courseItem->item->Lesson[0]->id), //same publish_date
                'course_name' => $event->usercourseItem->courseItem->item->Lesson[0]->course->name, // same issue
            ];
        $users=UserCourseItem::where('course_item_id',$event->usercourseItem->courseItem->id)->pluck('user_id');

        $this->notification->sendNotify($users,$reqNot);
    }
}
