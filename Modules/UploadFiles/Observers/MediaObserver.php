<?php

namespace Modules\UploadFiles\Observers;

use App\LessonComponent;
use Modules\UploadFiles\Entities\MediaLesson;

class MediaObserver
{
    public function deleted(MediaLesson $lesson)
    {
        LessonComponent::where('comp_id',$lesson->media_id)
        ->where('lesson_id',$lesson->lesson_id)
        ->where('module','UploadFiles')
        ->where('model' , 'media')
        ->delete();
    }
}
