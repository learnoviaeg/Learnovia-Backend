<?php

namespace App\Listeners;

use App\Events\UserGradeEvent;
use App\Enroll;
use App\UserGrade;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UserGradeListener implements ShouldQueue
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
     * @param  UserGradeEvent  $event
     * @return void
     */
    public function handle(UserGradeEvent $event)
    {
        $course_segment=($event->grade->gradeCategory->course_segment_id);
        $grade_item_id=$event->grade->id;
        $users=Enroll::where('course_segment',$course_segment)->pluck('user_id');
        foreach($users as $user)
        {
            $usr_grade=UserGrade::create([
                'user_id' => $user,
                'grade_item_id' => $grade_item_id
            ]);
        }
    }
}
