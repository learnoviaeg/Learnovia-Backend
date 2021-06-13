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
        // $event->grade_item is attempt of quiz (type=>attempt)
        // dd($event->grade_item);
        $questions=UserQuizAnswer::where('user_quiz_id',$event->grade_item->item_id)->pluck('question_id');

        if($event->type == 'Attempt'){
            foreach($questions as $question)
                ItemDetail::firstOrCreate([
                    'type' => 'Question',
                    'item_id' => $question,
                    'parent_item_id' => $event->grade_item->id,
                    // 'weight_details' => $question['mark'],
                ]);
        }

        elseif($event->type == 'Assignment'){
            ItemDetail::firstOrCreate([
                'type' => $event->type,
                'item_id' => $grade_item->item_id,
                'parent_item_id' => $grade_item->id,
                'weight_details' => json_encode($event->grade_item->assignmentLessson[0]->mark),
            ]);
        }
    }
}
