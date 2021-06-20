<?php

namespace App\Grader;

use App\Grader\ItemGraderInterface;
use App\GradeItems;
use App\GradeCategory;
use App\ItemDetail;
use Modules\QuestionBank\Entities\Quiz;
use Modules\QuestionBank\Entities\UserQuiz;
use Modules\QuestionBank\Entities\UserQuizAnswer;

class QuizGrader implements ItemGraderInterface
{
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
    
    public function __construct(UserQuiz $item) //attempt
    {
        $this->item=$item; //attempt
    }

    public function grade(){
        $grade=0;
        $user_quiz_answers=UserQuizAnswer::where('user_quiz_id',$this->item->id)->get();
        // dd($user_quiz_answers);
        $grade_cat=GradeCategory::where('instance_type','Quiz')->where('instance_id',$this->item->quiz_lesson->quiz_id)->where('lesson_id',$this->item->quiz_lesson->lesson_id)->first();
        //grade item ( attempt_item )
        $gradeitem=GradeItems::where('index',$this->item->attempt_index)->where('grade_category_id',$grade_cat->id)->first();

        $item_details=ItemDetail::where('parent_item_id',$gradeitem->id)->get();
        // dd($gradeitem);
        foreach($item_details as $item_detail)
        {
           // dd($item_detail);
        }
    }

}