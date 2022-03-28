<?php

namespace Modules\UploadFiles\Observers;

use App\LessonComponent;
use Modules\UploadFiles\Entities\FileLesson;
use Modules\UploadFiles\Entities\File;
use App\Material;

class FileObserver
{
    public function updated(File $file)
    {
        Material::where('item_id',$file->id)->where('type' , 'file')
        ->update([
            'name' => $file->name,
            'description' => $file->description,
        ]);
    }

    public function deleted(FileLesson $lesson)
    {
        LessonComponent::where('comp_id',$lesson->media_id)
        ->where('lesson_id',$lesson->lesson_id)
        ->where('module','UploadFiles')
        ->where('model' , 'file')
        ->delete();
    }
}
