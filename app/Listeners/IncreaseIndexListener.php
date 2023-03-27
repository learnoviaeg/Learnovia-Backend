<?php

namespace App\Listeners;

use App\Events\CreatedGradeCatEvent;
use Illuminate\Queue\InteractsWithQueue;
use App\GradeCategory;
use Illuminate\Contracts\Queue\ShouldQueue;

class IncreaseIndexListener
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
     * @param  CreatedGradeCatEvent  $event
     * @return void
     */
    public function handle(CreatedGradeCatEvent $event)
    {
        // in case in it's category total
        if($event->gradeCat->parent == null)
            $event->gradeCat->index=1;
        else{
          $maxIndex=GradeCategory::where('parent',$event->gradeCat->parent)->max('index');
          $event->gradeCat->index=$maxIndex+1;  
        }
        $event->gradeCat->save();
    }
}
