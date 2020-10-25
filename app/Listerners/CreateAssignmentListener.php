<?php

namespace App\Listerners;

use App\Events\UserGradeEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateAssignmentListener
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
     * @param  UserGradeEvent  $event
     * @return void
     */
    public function handle(UserGradeEvent $event)
    {
        //
    }
}
