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
    protected $fillable = ['item_id','item_type','message','created_by','course_id','class_id','lesson_id','type','publish_date','link'];

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
    
    public function send(Request $request){

        $request->validate([
            'id' => 'required',
            'users'=>'required|array',
            'users.*' => 'required|integer|exists:users,id',
            'type' => 'required|string',
            'message' => 'required',
            'course_id' => 'integer|exists:courses,id',
            'class_id'=>'integer|exists:classes,id',
            'lesson_id'=>'integer|exists:lessons,id',
            'link' => 'string',
            'publish_date' => 'date',
            'from' => 'integer|exists:users,id',
        ]);

        //Start storing notifications object
        $notification = new Notification;

        $notification->type = $request->type != 'announcement' ? 'notification' : 'announcement';

        $notification->item_id = $request->id;

        $notification->item_type = $request->type;

        $notification->message = $request->message;

        $notification->publish_date = $request->publish_date ? $request->publish_date : Carbon::now();

        $notification->created_by = Auth::id() ? Auth::id() : 1;
        if($request->from){
            $notification->created_by = $request->from;
        }

        if($request->lesson_id){
            $notification->lesson_id = $request->lesson_id;
        }

        if($request->course_id){

            $notification->course_id = $request->course_id;

            //storing course last action
            LastAction::lastActionInCourse($request->course_id);
        }

        if($request->class_id){
            $notification->class_id = $request->class_id;
        }

        if($request->link){
            $notification->link = $request->link;
        }

        $notification->save();
        //End storing notification object

        //assign notification to given users
        $notification->users()->attach($request->users);

        //calculate time the job should fire at
        $notificationDelaySeconds = Carbon::parse($notification->publish_date)->diffInSeconds(Carbon::now()); 
        if($notificationDelaySeconds < 0) {
            $notificationDelaySeconds = 0;
        }

        //this job is for sending firebase notifications 
        $notificationJob = (new SendNotifications($notification))->delay($notificationDelaySeconds);
        dispatch($notificationJob);
    }
}
