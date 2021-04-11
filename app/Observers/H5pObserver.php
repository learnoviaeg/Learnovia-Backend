<?php

namespace App\Observers;

use App\h5pLesson;
use App\Repositories\RepportsRepositoryInterface;
use App\Lesson;

class H5pObserver
{
    protected $report;

    public function __construct(RepportsRepositoryInterface $report)
    {
        $this->report = $report;
    }
    /**
     * Handle the h5p lesson "created" event.
     *
     * @param  \App\h5pLesson  $h5pLesson
     * @return void
     */
    public function created(h5pLesson $h5pLesson)
    {
        //
    }

    /**
     * Handle the h5p lesson "updated" event.
     *
     * @param  \App\h5pLesson  $h5pLesson
     * @return void
     */
    public function updated(h5pLesson $h5pLesson)
    {
        if($h5pLesson->isDirty('seen_number')){
            $lesson = Lesson::find($h5pLesson->lesson_id);
            $course_id = $lesson->courseSegment->course_id;
            $this->report->calculate_course_progress($course_id);
        }
    }

    /**
     * Handle the h5p lesson "deleted" event.
     *
     * @param  \App\h5pLesson  $h5pLesson
     * @return void
     */
    public function deleted(h5pLesson $h5pLesson)
    {
        $lesson = Lesson::find($h5pLesson->lesson_id);
        $course_id = $lesson->courseSegment->course_id;
        $this->report->calculate_course_progress($course_id);
    }

    /**
     * Handle the h5p lesson "restored" event.
     *
     * @param  \App\h5pLesson  $h5pLesson
     * @return void
     */
    public function restored(h5pLesson $h5pLesson)
    {
        //
    }

    /**
     * Handle the h5p lesson "force deleted" event.
     *
     * @param  \App\h5pLesson  $h5pLesson
     * @return void
     */
    public function forceDeleted(h5pLesson $h5pLesson)
    {
        //
    }
}
