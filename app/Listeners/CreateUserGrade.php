<?php

namespace App\Listeners;

use App\Events\CreatedGradeItem;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateUserGrade
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
     * @param  CreatedGradeItem  $event
     * @return void
     */
    public function handle(CreatedGradeItem $event)
    {
        dd('hj');
        // dd($event);
    }
}
