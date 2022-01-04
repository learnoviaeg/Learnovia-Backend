<?php

namespace App\Listeners;

use App\Events\GradeCalculatedEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
// use App\GradeCategory;
use App\LetterDetails;
use App\UserGrader;
// use App\User;

class LetterPercentageListener
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
     * @param  GradeCalculatedEvent  $event
     * @return void
     */
    public function handle(GradeCalculatedEvent $event)
    {
        if(isset($event->userGrade->category->course->letter)){
            $letter = $event->userGrade->category->course->letter;
            $details = LetterDetails::select('evaluation')->where('letter_id', $letter->id)->where('lower_boundary', '<=', $event->userGrade->grade)
                      ->where('higher_boundary', '>', $event->userGrade->grade)->first();
            $event->userGrade->update([
                'letter' => isset($details->evaluation) ? $details->evaluation : null,
            ]);
        }
    }
}
