<?php
 
namespace App\Observers;

use App\LessonComponent;
use Modules\Assigments\Entities\AssignmentLesson;

class AssignmentObserver
{
    public function deleted(AssignmentLesson $lesson)
    {
        LessonComponent::where('lesson_id',$lesson->lesson_id)->where('comp_id',$lesson->assignment_id)
        ->where('module','Assignment')->delete();
    }
}