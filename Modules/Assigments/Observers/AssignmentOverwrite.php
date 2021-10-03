<?php

namespace Modules\Assigments\Observers;

use Modules\Assigments\Entities\assignmentOverride;
use Modules\Assigments\Entities\AssignmentLesson;
use Modules\Assigments\Entities\Assignment;
use carbon\Carbon;
use App\Timeline;
use App\Lesson;
use App\Course;
use App\SecondaryChain;

class AssignmentOverwrite
{
    /**
     * Handle the assignment override "created" event.
     *
     * @param  \App\assignmentOverride  $assignmentOverride
     * @return void
     */
    public function created(assignmentOverride $assignmentOverride)
    {
        $assignmentLesson = AssignmentLesson::whereId($assignmentOverride->assignment_lesson_id)->first();
        if(isset($assignmentLesson)){
            $assignment = Assignment::where('id',$assignmentLesson->assignment_id)->first();
            $lesson = Lesson::find($assignmentLesson->lesson_id);
            $course_id = $lesson->course_id;
            $secondary_chains = SecondaryChain::where('lesson_id',$assignmentLesson->lesson_id)
                                ->where('user_id',$assignmentOverride->user_id)->get()->keyBy('group_id');
            foreach($secondary_chains as $secondary_chain){
                if(isset($assignment)){
                    Timeline::firstOrCreate([
                        'item_id' => $assignmentLesson->assignment_id,
                        'name' => $assignment->name,
                        'start_date' => $assignmentOverride->start_date,
                        'due_date' => $assignmentOverride->due_date,
                        'publish_date' => isset($assignmentLesson->publish_date)? $assignmentLesson->publish_date : Carbon::now(),
                        'lesson_id' => $assignmentLesson->lesson_id,
                        'course_id' => $secondary_chain->course_id,
                        'class_id' => $secondary_chain->group_id,
                        'level_id' => $secondary_chain->Enroll->level,
                        'type' => 'assignment',
                        'overwrite_user_id' => $assignmentOverride->user_id
                    ]);
                }
            }
        }
    }

    /**
     * Handle the assignment override "updated" event.
     *
     * @param  \App\assignmentOverride  $assignmentOverride
     * @return void
     */
    public function updated(assignmentOverride $assignmentOverride)
    {
        //
    }

    /**
     * Handle the assignment override "deleted" event.
     *
     * @param  \App\assignmentOverride  $assignmentOverride
     * @return void
     */
    public function deleted(assignmentOverride $assignmentOverride)
    {
        //
    }

    /**
     * Handle the assignment override "restored" event.
     *
     * @param  \App\assignmentOverride  $assignmentOverride
     * @return void
     */
    public function restored(assignmentOverride $assignmentOverride)
    {
        //
    }

    /**
     * Handle the assignment override "force deleted" event.
     *
     * @param  \App\assignmentOverride  $assignmentOverride
     * @return void
     */
    public function forceDeleted(assignmentOverride $assignmentOverride)
    {
        //
    }
}
