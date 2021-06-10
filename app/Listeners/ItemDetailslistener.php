<?php

namespace App\Listeners;

use App\Events\GradeItemEvent;
use Modules\QuestionBank\Entities\quiz;
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
        // $event->grade_item is quiz (type=>quiz)
        if($event->type == 'Quiz'){
            $quiz=Quiz::find($event->grade_item->item_id);
            // if($quiz->is_graded == 1){
                $grade_item=GradeItems::where('item_id',$event->grade_item->id)->where('type',$event->type)->first();
                foreach($event->grade_item->Question as $question)
                    ItemDetail::firstOrCreate([
                        'type' => 'Question',
                        'item_id' => $question->id,
                        'parent_item_id' => $grade_item,
                        // 'weight_details' => $question['mark'],
                    ]);
            // }
        }

        elseif($event->type == 'Assignment'){
            $grade_item=GradeItems::where('item_id',$event->grade_item->id)->where('type',$event->type)->first();
            ItemDetail::firstOrCreate([
                'type' => $event->type,
                'item_id' => $grade_item->item_id,
                'parent_item_id' => $grade_item->id,
                'weight_details' => json_encode($event->grade_item->assignmentLessson[0]->mark),
            ]);
        }
    }
}
