<?php

namespace App\Notifications;

use App\Enroll;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\Bigbluebutton\Entities\BigbluebuttonModel;

class VirtualNotification extends SendNotification
{
    public $virual, $message, $users;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(BigbluebuttonModel $virual, $message)
    {
        $this->virtual = $virual;
        $this->message = $message;
        $this->users =  Enroll::where('group',$this->virtual->class_id)->where('course',$this->virtual->course_id)
                                ->where('user_id','!=', Auth::id())
                                ->where('role_id','!=', 1 )->select('user_id')->distinct()->pluck('user_id')->toArray();
    }

    public function setUsers(array $users){
        $this->users = $users;
    }
 
    public function send(){

        // $publish_date = $this->virtual->start_date;
        // if(Carbon::parse($publish_date)->isPast()){
        //     $publish_date = Carbon::now();
        // }

        // //Start preparing notifications object
        // $notification = [
        //     'type' => 'notification',
        //     'item_id' => $this->virtual->id,
        //     'item_type' => 'meeting',
        //     'message' => $this->message,
        //     'publish_date' => $publish_date,
        //     'created_by' => $this->virtual->user_id,
        //     'course_id' => $this->virtual->course_id,
        //     'classes' => json_encode([$this->virtual->class_id]),
        //     'link' => url(route('getmeeting')) . '?id=' . $this->virtual->id,
        // ];

        // //assign notification to given users
        // $createdNotification = $this->toDatabase($notification,$this->users);
        
        // //firebase Notifications
        // $this->toFirebase($createdNotification);
    }
}
