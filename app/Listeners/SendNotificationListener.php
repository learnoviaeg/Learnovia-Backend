<?php

namespace App\Listeners;

use App\Events\CreateCourseItemEvent;
use Illuminate\Queue\InteractsWithQueue;
use App\Repositories\NotificationRepoInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\UserCourseItem;
use Carbon\Carbon;
use Modules\UploadFiles\Entities\FileLesson;
use Modules\UploadFiles\Entities\MediaLesson;
use Modules\Page\Entities\pageLesson;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\Assigments\Entities\AssignmentLesson;
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
        if($event->usercourseItem->courseItem->type == 'quiz')
        {
            $QuizLesson=QuizLesson::where('quiz_id',$event->usercourseItem->courseItem->item_id)
                ->where('lesson_id',$event->usercourseItem->courseItem->item->Lesson[0]->id)->first();
            $publish_date=$QuizLesson->publish_date;
        }
        if($event->usercourseItem->courseItem->type == 'assignment')
        {
            $AssignmentLesson=AssignmentLesson::where('assignment_id',$event->usercourseItem->courseItem->item_id)
                ->where('lesson_id',$event->usercourseItem->courseItem->item->Lesson[0]->id)->first();
            $publish_date=$AssignmentLesson->publish_date;
        }
        if($event->usercourseItem->courseItem->type == 'file')
        {
            $FileLesson=FileLesson::where('file_id',$event->usercourseItem->courseItem->item_id)
                ->where('lesson_id',$event->usercourseItem->courseItem->item->Lesson[0]->id)->first();
            $publish_date=$FileLesson->publish_date;
        }
        if($event->usercourseItem->courseItem->type == 'media')
        {
            $MediaLesson=MediaLesson::where('media_id',$event->usercourseItem->courseItem->item_id)
                ->where('lesson_id',$event->usercourseItem->courseItem->item->Lesson[0]->id)->first();
            $publish_date=$MediaLesson->publish_date;
        }
        if($event->usercourseItem->courseItem->type == 'page')
        {
            $PageLesson=PageLesson::where('page_id',$event->usercourseItem->courseItem->item_id)
                ->where('lesson_id',$event->usercourseItem->courseItem->item->Lesson[0]->id)->first();
            $publish_date=$PageLesson->publish_date;
        }
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
                'course_id' => Lesson::find($h5pLesson->lesson_id)->course->id,
            ];
        }
        else
            $reqNot=[
                'message' => isset($event->usercourseItem->courseItem->item->name) ? $event->usercourseItem->courseItem->item->name . ' ' . $event->usercourseItem->courseItem->type . ' is created' :
                            $event->usercourseItem->courseItem->item->title . ' ' . $event->usercourseItem->courseItem->type . ' is created',
                'item_id' => $event->usercourseItem->courseItem->item_id,
                'item_type' => $event->usercourseItem->courseItem->type,
                'type' => 'notification',
                'publish_date' => $publish_date,
                'lesson_id' => isset($event->usercourseItem->courseItem->item->Lesson[0]->id),
                'course_name' => $event->usercourseItem->courseItem->item->Lesson[0]->course->name,
                'course_id' => $event->usercourseItem->courseItem->item->Lesson[0]->course->id,
            ];
        $users=UserCourseItem::where('course_item_id',$event->usercourseItem->courseItem->id)->pluck('user_id');

        $this->notification->sendNotify($users,$reqNot);
    }
}
