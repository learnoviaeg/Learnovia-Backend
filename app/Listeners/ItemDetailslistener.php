<?php

namespace App\Listeners;

use App\Events\GradeItemEvent;
use Modules\QuestionBank\Entities\quiz;
use Modules\QuestionBank\Entities\UserQuizAnswer;
use Modules\QuestionBank\Entities\quiz_questions;
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
        if($event->grade_item->type == 'Attempt'){
            
            $gradeCat=GradeCategory::find($event->grade_item->grade_category_id);
            $quiz=Quiz::find($gradeCat->instance_id);
            $questions=$quiz->Question;

            foreach($questions as $question){
                if($question->question_type_id == 5){
                    $quest=$question->children->pluck('id');
                    foreach($quest as $child){
                        $child_question=quiz_questions::where('quiz_id',$quiz->id)->where('question_id',$child)->first();

                        $item=ItemDetail::firstOrCreate([
                            'type' => 'Question',
                            'item_id' => $child,
                            'parent_item_id' => $event->grade_item->id,
                            'weight_details' => json_encode($child_question->grade_details),
                        ]);
                    }
                }
                else // because parent question(comprehension) not have answer
                {
                    $quiz_question=quiz_questions::where('quiz_id',$quiz->id)->where('question_id',$question->id)->first();
                    
                    // dd($quiz_question->grade_details);
                    $item=ItemDetail::firstOrCreate([
                        'type' => 'Question',
                        'item_id' => $question->id,
                        'parent_item_id' => $event->grade_item->id,
                        'weight_details' => json_encode($quiz_question->grade_details),
                    ]);
                }
            }
        }

        elseif($event->grade_item->type == 'Assignment'){
            ItemDetail::firstOrCreate([
                'type' => $event->type,
                'item_id' => $event->grade_item->item_id,
                'parent_item_id' => $event->grade_item->id,
                'weight_details' => json_encode($event->grade_item->assignmentLesson[0]->mark),
            ]);
        }
    }
}
