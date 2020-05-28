<?php

namespace App\Observers;

use App\GradeItems;

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
            $gradeItems->keepWeight();
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
