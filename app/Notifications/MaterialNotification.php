<?php

namespace App\Notifications;

use App\Enroll;
use App\Material;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class MaterialNotification extends SendNotification
{

    public $material, $message;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Material $material,$message)
    {
        $this->material = $material;
        $this->message = $message;
    }

    public function send(){

        $users = Enroll::whereIn('group',$this->material->lesson->shared_classes->pluck('id'))
                        ->where('course',$this->material->course_id)
                        ->where('user_id','!=',Auth::user()->id)
                        ->where('role_id','!=', 1 )->select('user_id')->distinct()->pluck('user_id')->toArray();

        $publish_date = $this->material->publish_date;
        if(Carbon::parse($publish_date)->isPast()){
            $publish_date = Carbon::now();
        }


        $link = null;
        if(isset($this->material->getAttributes()['link'])){
            $link = $this->material->getAttributes()['link'];
        }

        if($this->material->type == 'page'){
            $link = url(route('getPage')) . '?id=' . $this->material->item_id;
        }

        //Start preparing notifications object
        $notification = [
            'type' => 'notification',
            'item_id' => $this->material->item_id,
            'item_type' => $this->material->type,
            'message' => $this->message,
            'publish_date' => $publish_date,
            'created_by' => $this->material->created_by,
            'lesson_id' => $this->material->lesson->id,
            'course_id' => $this->material->lesson->course_id,
            'classes' => json_encode($this->material->lesson->shared_classes->pluck('id')),
            'link' => $link,
        ];
        

        //assign notification to given users
        $createdNotification = $this->toDatabase($notification,$users);
        
        //firebase Notifications
        $this->toFirebase($createdNotification);
    }
}
