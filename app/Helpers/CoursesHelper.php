<?php

namespace App\Helpers;
use App\CourseItem;
use App\UserCourseItem;
use App\Material;

class CoursesHelper{

    public static function giveUsersAccessToViewCourseItem($itemId, $type , array $usersIds){
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
