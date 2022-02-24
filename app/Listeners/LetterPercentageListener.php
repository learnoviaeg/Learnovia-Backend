<?php

namespace App\Listeners;

use App\Events\GradeCalculatedEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\LetterDetails;
use App\UserGrader;

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
            $details = LetterDetails::select('evaluation')->where('letter_id', $letter->id)->where('lower_boundary', '<=', $event->userGrade->percentage)
                      ->where('higher_boundary', '>', $event->userGrade->percentage)->first();

            if($event->userGrade->percentage == 100)
                $details = LetterDetails::select('evaluation')->where('letter_id', $letter->id)->where('lower_boundary', '<=', $event->userGrade->percentage)
                ->where('higher_boundary', '>=', $event->userGrade->percentage)->first();

            $event->userGrade->update([
                'letter' => isset($details->evaluation) ? $details->evaluation : null,
            ]);

            if($event->userGrade->category->parent != null){
                $grade = UserGrader::where('user_id',$event->userGrade->user_id)->where('item_type','category')->where('item_id' , $event->userGrade->category->parent)->whereNotNull('grade')->first();
                if($grade != null)
                event(new GradeCalculatedEvent($grade));
            }
        }
    }
}
