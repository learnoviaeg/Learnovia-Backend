<?php
 
namespace App\Observers;

use App\LessonComponent;
use Modules\Page\Entities\PageLesson;

class PageObserver
{
    public function deleted(PageLesson $lesson)
    {
        LessonComponent::where('comp_id',$lesson->page_id)->where('lesson_id',$lesson->lesson_id)
        ->where('module','Page')->delete();
    }
}