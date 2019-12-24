<?php

namespace Modules\QuestionBank\Observers;

use App\LessonComponent;
use Modules\QuestionBank\Entities\QuizLesson;

class QuizObserver
{
    public function deleted(QuizLesson $lesson)
    {
        LessonComponent::where('comp_id',$lesson->quiz_id)->where('lesson_id',$lesson->lesson_id)
        ->where('module','Quiz')->delete();
    }
}