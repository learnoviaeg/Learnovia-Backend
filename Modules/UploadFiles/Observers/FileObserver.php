<?php

namespace Modules\UploadFiles\Observers;

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
            ]);
    }

    public function deleted(File $file)
    {
        //
    }
}
