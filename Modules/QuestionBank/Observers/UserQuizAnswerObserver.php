<?php

namespace Modules\QuestionBank\Observers;

use Modules\QuestionBank\Entities\UserQuizAnswer;

class UserQuizAnswerObserver
{
    public function updated(UserQuizAnswer $answer)
    {
        dd($answer);
    }
}