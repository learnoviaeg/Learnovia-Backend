<?php

namespace App\Listeners;

use App\Events\TakeAttendanceEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CalculateSessionsListener
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
     * @param  TakeAttendanceEvent  $event
     * @return void
     */
    public function handle(TakeAttendanceEvent $event)
    {
        $grader = UserGrader::updateOrCreate(
            ['item_id'=>$user['item_id'], 'item_type' => 'category', 'user_id' => $user['user_id']],
            ['grade' =>  $user['grade'] , 'percentage' => $percentage ]
        );
    }
}
