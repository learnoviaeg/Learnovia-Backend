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
                $calculator->weightAdjustCheck($event->grade_category);
                $grade = $calculator->calculateMark($event->grade_category);
                $event->grade_category->update(['max' => $grade]);
                $calculator->calculateWeight($event->grade_category);
                if($event->grade_category->parent != null)
                    event(new GraderSetupEvent(GradeCategory::find($event->grade_category->parent)));
                    
                if($event->grade_category->parent == null){
                    $course_total = GradeCategory::find($event->grade_category->parent);
                    $Grade = $calculator->calculateMark($course_total);
                    $course_total->update(['max' => $Grade]);
                }   
            }
        }
    }
}
