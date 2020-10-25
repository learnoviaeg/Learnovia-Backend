<?php

namespace App\Listerners;

use App\Events\AssignmentLessonEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Assigments\Entities\assignment;
use App\Timeline;
use App\Lesson;

class CreateAssignmentLessonListener implements ShouldQueue
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
     * @param  AssignmentLessonEvent  $event
     * @return void
     */
    public function handle(AssignmentLessonEvent $event)
    {
        $assignment_lesson = $event->assignment_lesson;
        $assignment = Assignment::where('id',$assignment_lesson->assignment_id)->first();
        $lesson = Lesson::find($assignment_lesson->lesson_id);
        $course_id = $lesson->courseSegment->course_id;
        $class_id = $lesson->courseSegment->segmentClasses[0]->classLevel[0]->class_id;
        $level_id = $lesson->courseSegment->segmentClasses[0]->classLevel[0]->yearLevels[0]->level_id;
        if(isset($assignment)){
            Timeline::firstOrCreate([
                'item_id' => $assignment_lesson->assignment_id,
                'name' => $assignment->name,
                'start_date' => $assignment_lesson->start_date,
                'due_date' => $assignment_lesson->due_date,
                'publish_date' => isset($assignment_lesson->publish_date)? $assignment_lesson->publish_date : Carbon::now(),
                'course_id' => $course_id,
                'class_id' => $class_id,
                'lesson_id' => $assignment_lesson->lesson_id,
                'level_id' => $level_id,
                'type' => 'assignment'
            ]);
        }
    }
}
