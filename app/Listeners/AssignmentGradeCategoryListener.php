<?php

namespace App\Listeners;

use App\Events\AssignmentCreatedEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\GradeCategory;
use App\Events\GraderSetupEvent;

class AssignmentGradeCategoryListener
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
     * @param  AssignmentCreatedEvent  $event 
     * @return void
     */
    public function handle(AssignmentCreatedEvent $event)
    {
        $top_parent_category = GradeCategory::where('course_id', $event->assignment_lesson->lesson->course_id)
                            ->whereNull('parent')->where('type','category')->first();
        $assignment = GradeCategory::updateOrCreate(
            [
                'course_id' => $event->assignment_lesson->lesson->course_id,
                'instance_id'=> $event->assignment_lesson->assignment[0]->id, 
                'item_type' => 'Assignment',
                'instance_type' => 'Assignment',
                'type' => 'item',    
                'lesson_id' => $event->assignment_lesson->lesson->id
            ],
            [
                'parent' => isset($event->assignment_lesson->grade_category) ? $event->assignment_lesson->grade_category : $top_parent_category->id,
                'name'   => $event->assignment_lesson->assignment[0]->name, 
                'hidden' =>  $event->assignment_lesson->visible ,
                'max'    => $event->assignment_lesson->mark,
                'weight_adjust' => ((bool) $event->assignment_lesson->is_graded == false) ? 1 : 0,
                'weights' => ((bool) $event->assignment_lesson->is_graded == false) ? 0 : null,
            ]
        );

        $assignment->index=GradeCategory::where('parent',$assignment->parent)->max('index')+1;
        $assignment->save();

        event(new GraderSetupEvent($assignment->Parents));
    }
}
