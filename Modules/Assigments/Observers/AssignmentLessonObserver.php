<?php

namespace Modules\Assigments\Observers;

use App\Repositories\RepportsRepositoryInterface;
use Modules\Assigments\Entities\AssignmentLesson;
use Modules\Assigments\Entities\Assignment;
use App\Timeline;
use App\Lesson;
use App\GradeCategory;
use App\User;
use App\Events\MassLogsEvent;
use App\Log;
use carbon\Carbon;
use App\LessonComponent;
use Illuminate\Support\Facades\Auth;
use App\UserSeen;
use App\SecondaryChain;
use App\Course;

class AssignmentLessonObserver
{
    protected $report;

    public function __construct(RepportsRepositoryInterface $report)
    {
        $this->report = $report;
    }
    /**
     * Handle the assignment lesson "created" event.
     *
     * @param  \App\AssignmentLesson  $assignmentLesson
     * @return void
     */
    public function created(AssignmentLesson $assignmentLesson)
    {
        $secondary_chains = SecondaryChain::where('lesson_id',$assignmentLesson->lesson_id)->get()->keyBy('group_id');
        $assignment = Assignment::where('id',$assignmentLesson->assignment_id)->first();

        foreach($secondary_chains as $secondary_chain){
            $courseID = $secondary_chain->course_id;
            $class_id = $secondary_chain->group_id;
            $level_id = Course::find($courseID)->level_id;
            Timeline::firstOrCreate([
                'item_id' => $assignmentLesson->assignment_id,
                'name' => $assignment->name,
                'start_date' => $assignmentLesson->start_date,
                'due_date' => $assignmentLesson->due_date,
                'publish_date' => isset($assignmentLesson->publish_date)? $assignmentLesson->publish_date : Carbon::now(),
                'course_id' => $courseID,
                'class_id' => $class_id,
                'lesson_id' => $assignmentLesson->lesson_id,
                'level_id' => $level_id,
                'type' => 'assignment',  
                'visible' => isset($assignmentLesson->visible)?$assignmentLesson->visible:1
            ]);
            $this->report->calculate_course_progress($courseID);
        }


        // if($assignmentLesson->is_graded == 1){
        //     $grade_category=GradeCategory::find($assignmentLesson->grade_category);
        //     $grade_category->GradeItems()->create([
        //         'type' => 'Assignment',
        //         'item_id' => $assignment->id,
        //         'name' => $assignment->name,
        //     ]);
        // }
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
            $forLogs=Timeline::where('item_id',$assignmentLesson->assignment_id)->where('lesson_id',$assignmentLesson->getOriginal('lesson_id'))->where('type' , 'assignment')->first();
            $forLogs->update([
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

        if($assignmentLesson->isDirty('lesson_id')){

            $lesson = Lesson::find($assignmentLesson->lesson_id);
            $course_id = $lesson->courseSegment->course_id;
            $class_id = $lesson->courseSegment->segmentClasses[0]->classLevel[0]->class_id;

            $old_lesson = Lesson::find($assignmentLesson->getOriginal('lesson_id'));
            $old_class_id = $old_lesson->courseSegment->segmentClasses[0]->classLevel[0]->class_id;
            
            if($old_class_id != $class_id)
                UserSeen::where('lesson_id',$assignmentLesson->getOriginal('lesson_id'))->where('item_id',$assignmentLesson->assignment_id)->where('type','assignment')->delete();
            
            if($old_class_id == $class_id){
                UserSeen::where('lesson_id',$assignmentLesson->getOriginal('lesson_id'))->where('item_id',$assignmentLesson->assignment_id)->where('type','assignment')->update([
                    'lesson_id' => $assignmentLesson->lesson_id
                ]);
            }

            $this->report->calculate_course_progress($course_id);
        }
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

        LessonComponent::where('lesson_id',$assignmentLesson->lesson_id)->where('comp_id',$assignmentLesson->assignment_id)
        ->where('module','Assignment')->delete();

        $lesson = Lesson::find($assignmentLesson->lesson_id);
        $course_id = $lesson->course_id;

        UserSeen::where('lesson_id',$assignmentLesson->lesson_id)->where('item_id',$assignmentLesson->assignment_id)->where('type','assignment')->delete();
        $this->report->calculate_course_progress($course_id);
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
