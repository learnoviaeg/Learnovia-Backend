<?php

namespace App\Listeners;

use App\Events\CourseCreatedEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Events\LessonCreatedEvent;
use App\Segment;
use App\Classes;
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

        foreach($classes as $class)
        {
            $enroll=Enroll::firstOrCreate([
                'user_id'=> 1,
                'role_id' => 1,
                'year' => $year_id,
                'type' => $type_id,
                'segment' => $segment_id,
                'level' => $level_id,
                'group' => $class->id,
                'course' => $event->course->id
            ]);

            for ($i = 1; $i <= $event->no_of_lessons; $i++) {
                $lesson=lesson::firstOrCreate([
                    'name' => 'Lesson ' . $i,
                    'index' => $i,
                    // 'shared_lesson' => ,
                ]);

                // event(new LessonCreatedEvent($lesson,$enroll));
            }
        }
    }
}
