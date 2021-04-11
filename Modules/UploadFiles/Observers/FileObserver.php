<?php

namespace Modules\UploadFiles\Observers;

use App\LessonComponent;
use Modules\UploadFiles\Entities\FileLesson;

class FileObserver
{
    public function deleted(FileLesson $lesson)
    {
        LessonComponent::where('comp_id',$lesson->media_id)
        ->where('lesson_id',$lesson->lesson_id)
        ->where('module','UploadFiles')
        ->where('model' , 'file')
        ->delete();
    }
}
