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
            $calculator = resolve($calculation_type);
            $grade = $calculator->calculateUserGrade($event->user , $event->grade_category);
            UserGrader::updateOrCreate(
                ['item_id'=>$event->grade_category->id, 'item_type' => 'category', 'user_id' => $event->user->id],
                ['grade' =>  $grade]
            );
            if($event->grade_category->parent != null)
                    event(new UserGradesEditedEvent($event->user ,GradeCategory::find($event->grade_category->parent)));
        }
    }
}
