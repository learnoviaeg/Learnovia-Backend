<?php

namespace App\Grader;

use App\Grader\ItemGraderInterface;
use App\GradeItems;
use App\ItemDetail;
use Modules\QuestionBank\Entities\Quiz;

class QuizGrader implements ItemGraderInterface
{
    // $array['main']['answers']=1;
    // $array['main']['grade']=10;
    // $array['why']['grade']=5;
    // ItemDetail::where('parent_item_id',$this->item->id)->update(['weight_details' => json_encode($array)]);

    // get all grade items details (with) question relation
    // loop over item details
        // exrac the grads json
        // extract correction json(contains the right answers)
        // merge the two jsons 
        /**
         * {
         * 
         * }
         */
    
    public function __construct(GradeItems $item)
    {
        $this->item=$item;
    }

    public function grade($user){
        $grade=0;
        $quiz=Quiz::find($this->item->item_id);
        foreach($quiz->Question as $quest)
        {
            $item_details=ItemDetail::where('parent_item_id',$this->item->id)->first();
            dd($item_details->weight_details);
        }
    }

}