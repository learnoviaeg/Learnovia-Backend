<?php

namespace App\Listeners;

use App\Events\GradeItemEvent;
use Modules\QuestionBank\Entities\quiz;
use Modules\QuestionBank\Entities\UserQuizAnswer;
use App\GradeItems;
use App\ItemDetail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ItemDetailslistener
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
     * @param  GradeItemEvent  $event
     * @return void
     */
    public function handle(GradeItemEvent $event)
    {
      
    }
}
