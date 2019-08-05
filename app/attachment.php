<?php

namespace App;
use Illuminate\Support\Facades\Storage;

use Illuminate\Database\Eloquent\Model;

class attachment extends Model
{
    //
    protected $fillable = ['name', 'path','description', 'type','extension'];
public function assignment()
{
    return $this->belongsTo('Modules\Assigments\Entities\assignment', 'attachment_id', 'id');
}
public function UserAssigment()
{
    return $this->belongsTo('Modules\Assigments\Entities\UserAssigment', 'attachment_id', 'id');
}

public static function upload_attachment($file,$type,$description=null)
{
    $attachment=new attachment;
    $singlefile= $file;
    $extension = $singlefile->getClientOriginalExtension();

    $fileName = uniqid().$singlefile->getClientOriginalName();
    $size = $singlefile->getSize();

    if($type=='Assignment')
    {
        $attachment->path = 'assigments/'.$fileName;
        Storage::disk('public')->putFileAs('assigments/', $singlefile, $fileName);
    }
    else if($type=='Message')
    {
        $attachment->path = 'Message/'.$fileName;
        Storage::disk('public')->putFileAs('Message/', $singlefile, $fileName);

    }
    $attachment->name = $fileName;
    $attachment->description = $description;
    $attachment->type = $type;
    $attachment->extension = $extension;
    $attachment->save();
    
    return $attachment;
}

}
