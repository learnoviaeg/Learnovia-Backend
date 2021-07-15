<?php

namespace App\Listeners;

use App\Events\RefreshGradeTreeEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\GradeCategory;
use App\GradeItems;
use App\UserGrader;

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
        foreach(json_decode($event->grade_category->calculation_type) as $calculation_type){
            // dd($calculation_type);
        }
        
        $grade = $event->gradeMethodsInterface->calculate($user , $grade_category);
        dd($grade);
        UserGrader::updateOrCreate(
            ['item_id'=>$grade_category->id, 'item_type' => 'category', 'user_id' => $user->id],
            ['grade' =>  $grade]
        );
    }
}
