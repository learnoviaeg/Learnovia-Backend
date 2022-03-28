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

    public function deleted(Media $media)
    {

    }
}
