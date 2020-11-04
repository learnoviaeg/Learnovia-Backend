<?php

namespace App\Observers;

use Modules\Assigments\Entities\AssignmentLesson;
use Modules\Assigments\Entities\assignment;
use App\Timeline;
use App\Lesson;
use App\User;
use Log;
use carbon\Carbon;
use Illuminate\Support\Facades\Auth;


class AssignmentLessonObserver
{
    /**
     * Handle the assignment lesson "created" event.
     *
     * @param  \App\AssignmentLesson  $assignmentLesson
     * @return void
     */
    public function created(AssignmentLesson $assignmentLesson)
    {
        $assignment = Assignment::where('id',$assignmentLesson->assignment_id)->first();
        $lesson = Lesson::find($assignmentLesson->lesson_id);
        $course_id = $lesson->courseSegment->course_id;
        $class_id = $lesson->courseSegment->segmentClasses[0]->classLevel[0]->class_id;
        $level_id = $lesson->courseSegment->segmentClasses[0]->classLevel[0]->yearLevels[0]->level_id;
        if(isset($assignment)){
            Timeline::firstOrCreate([
                'item_id' => $assignmentLesson->assignment_id,
                'name' => $assignment->name,
                'start_date' => $assignmentLesson->start_date,
                'due_date' => $assignmentLesson->due_date,
                'publish_date' => isset($assignmentLesson->publish_date)? $assignmentLesson->publish_date : Carbon::now(),
                'course_id' => $course_id,
                'class_id' => $class_id,
                'lesson_id' => $assignmentLesson->lesson_id,
                'level_id' => $level_id,
                'type' => 'assignment'
            ]);
        }

        Log::info(User::find(Auth::id())->username.' created '.$assignmentLesson);
    }

    /**
     * Handle the assignment lesson "updated" event.
     *
     * @param  \App\AssignmentLesson  $assignmentLesson
     * @return void
     */
    public function updated(AssignmentLesson $assignmentLesson)
    {
        $assignment = Assignment::where('id',$assignmentLesson->assignment_id)->first();
        if(isset($assignment)){
            Timeline::where('item_id',$assignmentLesson->assignment_id)->where('lesson_id',$assignmentLesson->lesson_id)->where('type' , 'assignment')
            ->update([
                'item_id' => $assignmentLesson->assignment_id,
                'name' => $assignment->name,
                'start_date' => $assignmentLesson->start_date,
                'due_date' => $assignmentLesson->due_date,
                'publish_date' => isset($assignmentLesson->publish_date)? $assignmentLesson->publish_date : Carbon::now(),
                'lesson_id' => $assignmentLesson->lesson_id,
                'type' => 'assignment',
                'visible' => $assignmentLesson->visible
            ]);
        }

        Log::info(User::find(Auth::id())->username.' updated '.$assignmentLesson);
    }

    /**
     * Handle the assignment lesson "deleted" event.
     *
     * @param  \App\AssignmentLesson  $assignmentLesson
     * @return void
     */
    public function deleted(AssignmentLesson $assignmentLesson)
    {
        Timeline::where('lesson_id',$assignmentLesson->lesson_id)->where('item_id',$assignmentLesson->assignment_id)->where('type','assignment')->delete();
    
        Log::info(User::find(Auth::id())->username.' deleted '.$assignmentLesson);
    }

    /**
     * Handle the assignment lesson "restored" event.
     *
     * @param  \App\AssignmentLesson  $assignmentLesson
     * @return void
     */
    public function restored(AssignmentLesson $assignmentLesson)
    {
        //
    }

    /**
     * Handle the assignment lesson "force deleted" event.
     *
     * @param  \App\AssignmentLesson  $assignmentLesson
     * @return void
     */
    public function forceDeleted(AssignmentLesson $assignmentLesson)
    {
        //
    }
}
