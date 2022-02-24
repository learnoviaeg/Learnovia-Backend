<?php

namespace App\Listeners;

use App\Events\SessionCreatedEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Enroll;
use App\SessionLog;
use Auth;


class LogsCreatedListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  SessionCreatedEvent  $event
     * @return void
     */
    public function handle(SessionCreatedEvent $event)
    {
        $users = Enroll::where('role_id',3)->where('group',$event->session->class_id)->where('course',$event->session->course_id)->pluck('user_id');
        // dd($event->session->class_id);
        foreach($users as $user_id)
        {
            SessionLog::firstOrCreate([
                'user_id'   => $user_id,
                'status' => null,
                'session_id' =>$event->session->id,
                'taken_by'     => Auth::id()
            ]);
        }
    }
}
