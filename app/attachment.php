<?php

namespace App;

use Illuminate\Support\Facades\Storage;

use Illuminate\Database\Eloquent\Model;

class attachment extends Model
{
    //
    protected $fillable = ['name', 'path', 'description', 'type', 'extension','mime_type'];
    public function assignment()
    {
        return $this->belongsTo('Modules\Assigments\Entities\assignment', 'attachment_id', 'id');
    }
    public function UserAssigment()
    {
        return $this->belongsTo('Modules\Assigments\Entities\UserAssigment', 'attachment_id', 'id');
    }

    public static function upload_attachment($file, $type, $description = null,$school_name=null)
    {
        $attachment = new attachment;
        $singlefile = $file;
        $extension = $singlefile->extension();

        $Name = basename($singlefile->getClientOriginalName(), '.'.$singlefile->getClientOriginalExtension()). '.' .$extension;

        $fileName = uniqid() . $Name;

        $size = $singlefile->getSize();
        if($school_name != null)
            $Name=$school_name;
        $attachment->name = $Name;
        $attachment->path = $type . '/' . $fileName;
        $attachment->description = $description;
        $attachment->type = $type;
        $attachment->extension = $extension;
        $attachment->mime_type = $file->getClientMimeType();
        $attachment->save();
        Storage::disk('public')->putFileAs($type, $singlefile, $fileName);

        return $attachment;
    }
    public function getPathAttribute() {
      return url(Storage::url($this->attributes['path']));
    }
}
