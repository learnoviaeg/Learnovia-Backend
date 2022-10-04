<?php

namespace App\Helpers;

use App\Constants\StorageTypes;
use Illuminate\Support\Facades\Storage;

class UploadHelper
{
    public static function upload($file, $type): string
    {
        $fileName = time() . '_' . $file->getClientOriginalName();
        switch ($type)
        {
            case StorageTypes::ASSIGNMENT:
                $path = StorageTypes::ASSIGNMENT . '/' . $fileName;
                break;
            case StorageTypes::FILE:
                $path = StorageTypes::FILE . '/' . $fileName;
                break;
            case StorageTypes::MEDIA:
                $path = StorageTypes::MEDIA . '/' . $fileName;
                break;
            default:
                $path = StorageTypes::DEFAULT . '/' . $fileName;
                break;
        }
        Storage::disk('azure')->put($path, file_get_contents($file));
        return Storage::disk('azure')->url($path);
    }
}
