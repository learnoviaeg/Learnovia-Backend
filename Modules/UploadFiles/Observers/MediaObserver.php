<?php

namespace Modules\UploadFiles\Observers;

use App\LessonComponent;
use Modules\UploadFiles\Entities\MediaLesson;
use Modules\UploadFiles\Entities\Media;
use App\Material;

class MediaObserver
{
    public function updated(Media $media)
    {
        Material::where('item_id',$media->id)->where('type' , 'media')
        ->update([
            'name' => $media->name,
            'description' => $media->description,
            'link' => $media->link,
        ]);
    }

    public function deleted(MediaLesson $lesson)
    {
        LessonComponent::where('comp_id',$lesson->media_id)
        ->where('lesson_id',$lesson->lesson_id)
        ->where('module','UploadFiles')
        ->where('model' , 'media')
        ->delete();
    }
}
