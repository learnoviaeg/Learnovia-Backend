<?php

namespace App\Listeners;

use App\Events\UserGradesEditedEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\GradeCategory;
use App\UserGrader;

class CalculateUserGradesListener
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
     * @param  UserGradesEditedEvent  $event
     * @return void
     */
    public function handle(UserGradesEditedEvent $event)
    {
        foreach($event->grade_category->calculation_type as $calculation_type){
            if($event->grade_category->instance_id === null)
            {
                $percentage = 0;
                $calculator = resolve($calculation_type);
                $grade = $calculator->calculateUserGrade($event->user , $event->grade_category);
                
                if($event->grade_category->max != null && $event->grade_category->max > 0)
                    $percentage = ($grade / $event->grade_category->max) * 100;

                UserGrader::updateOrCreate(
                    ['item_id'=>$event->grade_category->id, 'item_type' => 'category', 'user_id' => $event->user->id],
                    ['grade' =>  $grade , 'percentage' => $percentage ]
                );
            }
            if($event->grade_category->parent != null)
                    event(new UserGradesEditedEvent($event->user ,GradeCategory::find($event->grade_category->parent)));
        }
    }
}
