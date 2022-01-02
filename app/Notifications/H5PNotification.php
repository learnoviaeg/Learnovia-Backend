<?php

namespace App\Notifications;

use App\Enroll;
use App\h5pLesson;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class H5PNotification extends SendNotification
{
    public $H5PLesson, $message, $lesson, $users;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(h5pLesson $H5PLesson,$message)
    {
        $this->H5PLesson = $H5PLesson;
        $this->message = $message;
        $this->lesson = $this->H5PLesson->lesson;
        $this->users = Enroll::whereIn('group',$this->lesson->shared_classes->pluck('id'))
                        ->where('course',$this->lesson->course_id)
                        ->where('user_id','!=',Auth::user()->id)
                        ->where('role_id','!=', 1 )->select('user_id')->distinct()->pluck('user_id')->toArray();
    }

    public function setUsers(array $users){
        $this->users = $users;
    }

    public function send(){

        $publish_date = $this->H5PLesson->publish_date;
        if(Carbon::parse($publish_date)->isPast()){
            $publish_date = Carbon::now();
        }

        $url= substr(request()->url(), 0, strpos(request()->url(), "/api"));

        //Start preparing notifications object
        $notification = [
            'type' => 'notification',
            'item_id' => $this->H5PLesson->content_id,
            'item_type' => 'h5p',
            'message' => $this->message,
            'publish_date' => $publish_date,
            'created_by' => $this->H5PLesson->user_id,
            'lesson_id' => $this->lesson->id,
            'course_id' => $this->lesson->course_id,
            'classes' => json_encode($this->lesson->shared_classes->pluck('id')),
            'link' => $url.'/api/h5p/'.$this->H5PLesson->content_id,
        ];

        //assign notification to given users
        $createdNotification = $this->toDatabase($notification,$this->users);
        
        //firebase Notifications
        $this->toFirebase($createdNotification);
    }  
}
