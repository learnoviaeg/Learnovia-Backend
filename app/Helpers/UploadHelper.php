<?php

namespace App\Helpers;

use App\Constants\StorageTypes;
use Illuminate\Support\Facades\Storage;

class UploadHelper
{
    /**
     * @throws \Exception
     */
    public static function upload($file, $type, $fileName): string
    {
        $fileName = time() . '_' . $fileName;
        switch ($type) {
            case StorageTypes::ASSIGNMENT:
                $path = StorageTypes::ASSIGNMENT . '/' . $fileName;
                break;
            case StorageTypes::FILE:
                $path = StorageTypes::FILE . '/' . $fileName;
                break;
            case StorageTypes::MEDIA:
                $path = StorageTypes::MEDIA . '/' . $fileName;
                break;
            case StorageTypes::ANNOUNCEMENT:
                $path = StorageTypes::ANNOUNCEMENT . '/' . $fileName;
                break;
            case StorageTypes::COURSE:
                $path = StorageTypes::COURSE . '/' . $fileName;
                break;
            case StorageTypes::LOGO:
                $path = StorageTypes::LOGO . '/' . $fileName;
                break;
            case StorageTypes::FOR_EDITOR:
                $path = StorageTypes::FOR_EDITOR . '/' . $fileName;
                break;
            default:
                $path = StorageTypes::DEFAULT . '/' . $fileName;
                break;
        }
        try {
            // dd($file);
            Storage::disk('azure')->put($path, file_get_contents($file));
        } catch (\Exception $e) {
            logger()->error($e->getMessage(), [
                'file' => $file,
                'type' => $type,
                'path' => $path,
            ]);
            throw $e;
        }
        return Storage::disk('azure')->url($path);
    }
}
