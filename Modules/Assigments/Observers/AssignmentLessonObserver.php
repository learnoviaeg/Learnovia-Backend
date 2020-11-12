<?php

namespace Modules\Assigments\Observers;

use Modules\Assigments\Entities\AssignmentLesson;
use Modules\Assigments\Entities\Assignment;
use App\Timeline;
use App\Lesson;
use App\User;
use App\Log;
use carbon\Carbon;
use App\LessonComponent;
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

        $log=Log::create([
            'user' => User::find(Auth::id())->username,
            'action' => 'created',
            'model' => 'AssignmentLesson',
            'data' => serialize($assignmentLesson),
        ]);
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
            Timeline::where('item_id',$assignmentLesson->assignment_id)->where('lesson_id',$assignmentLesson->lesson_id)->where('type' , 'assignment')->first()
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

        $arr=array();
        $arr['before']=$assignmentLesson->getOriginal();
        $arr['after']=$assignmentLesson;

        Log::create([
            'user' => User::find(Auth::id())->username,
            'action' => 'updated',
            'model' => 'AssignmentLesson',
            'data' => serialize($arr),
        ]);
    }

    /**
     * Handle the assignment lesson "deleted" event.
     *
     * @param  \App\AssignmentLesson  $assignmentLesson
     * @return void
     */
    public function deleted(AssignmentLesson $assignmentLesson)
    {

        //for log event
        $logsbefore=Timeline::where('lesson_id',$assignmentLesson->lesson_id)->where('item_id',$assignmentLesson->assignment_id)->where('type','assignment')->get();
        $all = Timeline::where('lesson_id',$assignmentLesson->lesson_id)->where('item_id',$assignmentLesson->assignment_id)->where('type','assignment')->delete();
        if($all > 0)
            event(new MassLogsEvent($logsbefore,'deleted'));
        
        $log=Log::create([
            'user' => User::find(Auth::id())->username,
            'action' => 'deleted',
            'model' => 'AssignmentLesson',
            'data' => serialize($assignmentLesson),
        ]);

        LessonComponent::where('lesson_id',$assignmentLesson->lesson_id)->where('comp_id',$assignmentLesson->assignment_id)
        ->where('module','Assignment')->delete();
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
