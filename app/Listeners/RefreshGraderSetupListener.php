<?php

namespace App\Listeners;

use App\Events\GraderSetupEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\GradeCategory;

class RefreshGraderSetupListener
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
     * @param  GraderSetupEvent  $event
     * @return void
     */
    public function handle(GraderSetupEvent $event)
    {
        if(is_array($event->grade_category->calculation_type)){    
            foreach($event->grade_category->calculation_type as $calculation_type){
                $calculator = resolve($calculation_type);
                $grade = $calculator->calculate($event->grade_category);
                $event->grade_category->update(['max' => $grade]);
                if($event->grade_category->parent != null)
                    event(new GraderSetupEvent(GradeCategory::find($event->grade_category->parent)));
            }
        }
    }
}