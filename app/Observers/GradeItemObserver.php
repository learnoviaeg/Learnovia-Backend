<?php

namespace App\Observers;

use App\GradeItems;
use App\Enroll;
use App\UserGrade;

class GradeItemObserver
{
    /**
     * Handle the grade items "created" event.
     *
     * @param  \App\GradeItems  $gradeItems
     * @return void
     */
    public function created(GradeItems $gradeItems)
    {
            // $gradeItems->keepWeight();
        $course_segment=($gradeItems->gradeCategory->course_segment_id);
        $grade_item_id=$gradeItems->id;
        $users=Enroll::where('course_segment',$course_segment)->pluck('user_id');
        foreach($users as $user)
        {
            $usr_grade=UserGrade::create([
                'user_id' => $user,
                'grade_item_id' => $grade_item_id
            ]);
        }
    }

    /**
     * Handle the grade items "updated" event.
     *
     * @param  \App\GradeItems  $gradeItems
     * @return void
     */
    public function updated(GradeItems $gradeItems)
    {
            $gradeItems->keepWeight();
    }

    /**
     * Handle the grade items "deleted" event.
     *
     * @param  \App\GradeItems  $gradeItems
     * @return void
     */
    public function deleted(GradeItems $gradeItems)
    {
        //
    }

    /**
     * Handle the grade items "restored" event.
     *
     * @param  \App\GradeItems  $gradeItems
     * @return void
     */
    public function restored(GradeItems $gradeItems)
    {
        //
    }

    /**
     * Handle the grade items "force deleted" event.
     *
     * @param  \App\GradeItems  $gradeItems
     * @return void
     */
    public function forceDeleted(GradeItems $gradeItems)
    {
        //
    }
}
