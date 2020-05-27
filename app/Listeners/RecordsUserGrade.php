<?php

namespace App\Listeners;

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
        //
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
        // $grade_Cat=$event->grade_category_id
        // $usergrade=UserGrade::create([

        // ]);
    }
}
