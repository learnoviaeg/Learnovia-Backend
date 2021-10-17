<?php

namespace App;

use App\Jobs\SendNotifications;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Notification extends Model
{
    protected $fillable = ['item_id','item_type','message','created_by','course_id','classes','lesson_id','type','publish_date','link'];

    protected $appends = ['course_name'];

    public function users():BelongsToMany
    {
        return $this->BelongsToMany(User::class)->withPivot('read_at');
    }

    public function course()
    {
        return $this->belongsTo('App\Course');
    }

    public function lesson()
    {
        return $this->belongsTo('App\Lesson');
    }

    public function getCourseNameAttribute()
    {
        return $this->course ? $this->course->name : null;
    }

    public static function toFirebase($notification){

        //calculate time the job should fire at
        $notificationDelaySeconds = Carbon::parse($notification->publish_date)->diffInSeconds(Carbon::now()); 
        if($notificationDelaySeconds < 0) {
            $notificationDelaySeconds = 0;
        }

        //this job is for sending firebase notifications 
        $notificationJob = (new SendNotifications($notification))->delay($notificationDelaySeconds);
        dispatch($notificationJob);
    }

    public static function toDatabase($notification,$users){

        $createdNotification = Notification::create($notification);
        $createdNotification->users()->attach($users);
        return $createdNotification;
    }
}
