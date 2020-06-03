<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;

use App\Enroll;
use App\Events\GradeItem;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class RecordsUserGrade
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        dd($grade);
    }

    /**
     * Handle the event.
     *
     * @param  GradeItem  $event
     * @return void
     */
    public function handle(GradeItem $event)
    {
        dd($event);
        // Log::info($event);
        //  $grade_Cat=$event->gradeCategory;
        //  dd($grade_Cat);
        // $users=Enroll::where('course_segment',$grade)->pluck('user_id');
        // return $users;

        // $usergrade=UserGrade::create([

        // ]);
    }
}
