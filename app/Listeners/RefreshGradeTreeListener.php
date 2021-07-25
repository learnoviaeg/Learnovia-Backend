<?php

namespace App\Listeners;

use App\Events\RefreshGradeTreeEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\GradeCategory;
use App\GradeItems;
use App\UserGrader;
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
        if(!is_null($event->grade_category->calculation_type)){
            foreach($event->grade_category->calculation_type as $calculation_type){
                Log::debug('listener');
                $calculator = resolve($calculation_type);
                $grade = ($calculator->calculate($event->user , $event->grade_category));
                UserGrader::updateOrCreate(
                    ['item_id'=>$event->grade_category->id, 'item_type' => 'category', 'user_id' => $event->user->id],
                    ['grade' =>  $grade]
                );

                Log::debug($event->grade_category->id .'  \   '.  $event->user->id . ' \ '. $grade);

            }
    }

        
        
    }
}
