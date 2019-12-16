<?php

namespace App\Observers;

use App\GradeItems;
use App\UserGrade;

class UserGradeObserver
{
    /**
     * Handle the user grade "created" event.
     *
     * @param  \App\UserGrade $userGrade
     * @return void
     */
    public function created(UserGrade $userGrade)
    {
        if (in_array($userGrade->GradeItems->calculation, GradeItems::rads())) {
            $userGrade->final_grade = GradeItems::clacWitheval($userGrade->GradeItems->calculation, $userGrade->raw_grade);
        } else {

            $userGrade->final_grade = GradeItems::clacWitheval($userGrade->GradeItems->calculation, deg2rad($userGrade->raw_grade));
        }
        $userGrade->raw_grade_min = $userGrade->GradeItems->grademax;
        $userGrade->raw_grade_max = $userGrade->GradeItems->grademin;
        $userGrade->save();
    }

    /**
     * Handle the user grade "updated" event.
     *
     * @param  \App\UserGrade $userGrade
     * @return void
     */
    public function updated(UserGrade $userGrade)
    {
        //
    }

    /**
     * Handle the user grade "deleted" event.
     *
     * @param  \App\UserGrade $userGrade
     * @return void
     */
    public function deleted(UserGrade $userGrade)
    {
        //
    }

    /**
     * Handle the user grade "restored" event.
     *
     * @param  \App\UserGrade $userGrade
     * @return void
     */
    public function restored(UserGrade $userGrade)
    {
        //
    }

    /**
     * Handle the user grade "force deleted" event.
     *
     * @param  \App\UserGrade $userGrade
     * @return void
     */
    public function forceDeleted(UserGrade $userGrade)
    {
        //
    }
}
