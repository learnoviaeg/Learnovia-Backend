<?php

namespace Modules\QuestionBank\Observers;

use Modules\QuestionBank\Entities\userQuiz;
use Modules\QuestionBank\Entities\UserQuizAnswer;

class UserQuizAnswerObserver
{
    public function updated(UserQuizAnswer $user_quiz_answer)
    {
        $q_A = UserQuizAnswer::where('user_quiz_id',$user_quiz_answer->user_quiz_id)->pluck('user_grade');
        userQuiz::where('id',$user_quiz_answer->user_quiz_id)->update(['grade' => array_sum($q_A->toArray())]);
    }
}