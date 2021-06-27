<?php

namespace App\Grader;

use App\Grader\ItemGraderInterface;
use App\GradeItems;
use App\GradeCategory;
use Auth;
use App\ItemDetail;
use App\ItemDetailsUser;
use Modules\QuestionBank\Entities\Quiz;
use Modules\QuestionBank\Entities\Questions;
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
    

}