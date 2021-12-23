<?php

namespace App\Listeners;

use App\Events\UserGradesEditedEvent;
use App\Events\RefreshGradeTreeEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\GradeCategory;
use App\GradeItems;
use App\UserGrader;
use App\User;
use Log;

class RefreshGradeTreeListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     *
     * @param  RefreshGradeTreeEvent  $event
     * @return void
     */
    public function handle(RefreshGradeTreeEvent $event)
    { 
        foreach($event->grade_category->calculation_type as $calculation_type){
            $calculator = resolve($calculation_type);
            $grade = ($calculator->calculate($event->user , $event->grade_category));
            UserGrader::updateOrCreate(
                ['item_id'=>$event->grade_category->id, 'item_type' => 'category', 'user_id' => $event->user->id],
                ['grade' =>  $grade]
            );
            event(new UserGradesEditedEvent(User::find($event->user->id) ,GradeCategory::find($event->grade_category->parent)));
        }
    }
}
