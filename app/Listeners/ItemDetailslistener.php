<?php

namespace App\Listeners;

use App\Events\GradeItemEvent;
use Modules\QuestionBank\Entities\quiz;
use Modules\QuestionBank\Entities\UserQuizAnswer;
use App\GradeItems;
use App\GradeCategory;
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
        $gradeCat=GradeCategory::find($event->grade_item->grade_category_id);
        $questions=Quiz::find($gradeCat->instance_id)->Question;
        if($event->grade_item->type == 'Attempts'){
            foreach($questions as $question)
                ItemDetail::firstOrCreate([
                    'type' => 'Question',
                    'item_id' => $question->id,
                    'parent_item_id' => $event->grade_item->id,
                    // 'weight_details' => $question['mark'],
                ]);
        }

        elseif($event->grade_item->type == 'Assignment'){
            ItemDetail::firstOrCreate([
                'type' => $event->type,
                'item_id' => $event->grade_item->item_id,
                'parent_item_id' => $event->grade_item->id,
                'weight_details' => json_encode($event->grade_item->assignmentLessson[0]->mark),
            ]);
        }
    }
}
