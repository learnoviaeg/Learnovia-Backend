<?php
 
namespace App\Observers;

use App\LessonComponent;
use Modules\UploadFiles\Entities\FileLesson;

class FileObserver
{
    public function deleted(FileLesson $lesson)
    {
        LessonComponent::where('comp_id',$lesson->file_id)->where('lesson_id',$lesson->lesson_id)
        ->where('module','File')->delete();
    }
}