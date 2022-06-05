<?php

namespace App\Helpers;
use App\CourseItem;
use App\UserCourseItem;
use App\Material;
use App\Repositories\NotificationRepoInterface;

class CoursesHelper{

    public $notification;

    public function __construct(NotificationRepoInterface $notification)
    {
        $this->notification=$notification;
    }

    public function giveUsersAccessToViewCourseItem($itemId, $type , array $usersIds,$lesson=null,$publish_date=null){
        $item = CourseItem::create([
            'item_id' => $itemId,
            'type' => $type
        ]);

        foreach($usersIds as $userId){
            UserCourseItem::create([
                'user_id' => $userId,
                'course_item_id' => $item->id,
            ]);
        }

        //send notification
        $reqNot=[
            'title' => $item->item->name . ' ' . $type.' is created',
            'item_id' => $itemId,
            'item_type' => 'notification',
            'type' => $type,
            'course_name' => $lesson->course->name,
            'lesson_id' => $lesson->id,
            'publish_date' => $publish_date
        ];
        $this->notification->sendNotify($usersIds , $reqNot);
    }

    public static function updateCourseItem($itemId, $type, $usersIds){
        $courseItem = CourseItem::where('item_id', $itemId)->where('type', $type)->first();
        if(isset($usersIds)){
            if(isset($courseItem)){
                $courseItem->courseItemUsers()->delete();
                foreach($usersIds as $userId){
                    UserCourseItem::create([
                        'user_id' => $userId,
                        'course_item_id' => $courseItem->id,
                    ]);
                }
            } else{
                self::giveUsersAccessToViewCourseItem($itemId, $type, $usersIds);
            }
        } else{
            if(isset($courseItem)){
                $courseItem->courseItemUsers()->delete();
                $courseItem->delete();
                Material::where('item_id',$itemId)->where('type',$type)->update(['restricted'=>0]);
            }
        }
    }
}
