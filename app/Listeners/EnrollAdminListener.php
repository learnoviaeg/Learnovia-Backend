<?php

namespace App\Listeners;

use App\Events\CourseCreatedEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\LessonCreatedEvent;
use App\Segment;
use App\Classes;
use App\User;
use App\Enroll;
use App\Lesson;

class EnrollAdminListener
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
     * @param  CourseCreatedEvent  $event
     * @return void
     */
    public function handle(CourseCreatedEvent $event)
    {
        $level_id=$event->course->level_id;
        $segment=Segment::find($event->course->segment_id);
        $segment_id=$segment->id;
        $year_id=$segment->academic_year_id;
        $type_id=$segment->academic_type_id;
        $classes=Classes::where('level_id',$event->course->level_id)->get();
        // dd($classes);

        $classes=$event->course->classes;
        foreach($classes as $class)
        {
            $users=User::whereHas('roles',function($q){  $q->where('id',1);  })->get();

            foreach($users as $user){
                $enroll=Enroll::firstOrCreate([
                    'user_id'=> $user->id,
                    'role_id' => 1,
                    'year' => $year_id,
                    'type' => $type_id,
                    'segment' => $segment_id,
                    'level' => $level_id,
                    'group' => $class,
                    'course' => $event->course->id
                ]);
            }
        }
    }
}
