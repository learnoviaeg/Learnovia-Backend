<?php

namespace App\Observers;

use App\UserSeen;
use App\Repositories\RepportsRepositoryInterface;
use App\Lesson;

class UserSeenObserver
{
    protected $report;

    public function __construct(RepportsRepositoryInterface $report)
    {
        $this->report = $report;
    }
    /**
     * Handle the user seen "created" event.
     *
     * @param  \App\UserSeen  $userSeen
     * @return void
     */
    public function created(UserSeen $userSeen)
    {
        if($userSeen->lesson_id){
            $lesson = Lesson::find($userSeen->lesson_id);
            // $course_id = $lesson->courseSegment->course_id;
            $this->report->calculate_course_progress($lesson->course_id);
        }
    }

    /**
     * Handle the user seen "updated" event.
     *
     * @param  \App\UserSeen  $userSeen
     * @return void
     */
    public function updated(UserSeen $userSeen)
    {
        //
    }

    /**
     * Handle the user seen "deleted" event.
     *
     * @param  \App\UserSeen  $userSeen
     * @return void
     */
    public function deleted(UserSeen $userSeen)
    {
        if($userSeen->lesson_id){
            $lesson = Lesson::find($userSeen->lesson_id);
            $this->report->calculate_course_progress($lesson->course_id);
        }
    }

    /**
     * Handle the user seen "restored" event.
     *
     * @param  \App\UserSeen  $userSeen
     * @return void
     */
    public function restored(UserSeen $userSeen)
    {
        //
    }

    /**
     * Handle the user seen "force deleted" event.
     *
     * @param  \App\UserSeen  $userSeen
     * @return void
     */
    public function forceDeleted(UserSeen $userSeen)
    {
        //
    }
}
