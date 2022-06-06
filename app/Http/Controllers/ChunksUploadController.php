<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ChunkUploads;
use Illuminate\Support\Facades\Storage;
use App\attachment;

class ChunksUploadController extends Controller
{
    public function uploads(Request $request)
    {
        $request->validate([
            'content' => 'required',
            'id' => 'exists:chunk_uploads,id',
        ]);
  
        if(!$request->filled('id')){  

            $request->validate([
                'type' => 'required|in:assignment,files',
                'name' => 'required',
            ]);

            $fileName = uniqid() . $request->name;
            $uploaded_file  = ChunkUploads::create([
                'name' => $fileName,
                'data'=> $request->content,
                'type' => $request->type,
            ]);
        }

        if($request->filled('id')){
            $uploaded_file = ChunkUploads::whereId($request->id)->first();
            $array = ($uploaded_file->content) ;
            $uploaded_file->data =  ($uploaded_file->data.$request->content);
            $uploaded_file->save();
        }

        if($request->filled('last') && $request->last == 1){
            $uploaded_file->uploaded = 1;
            $uploaded_file->path = $uploaded_file->type.'/'.$uploaded_file->name;
            $uploaded_file->save();
            $base64_encoded_string = base64_decode(($uploaded_file->data));
            $extension = finfo_buffer(finfo_open(), $base64_encoded_string, FILEINFO_MIME_TYPE);
            $ext = substr($extension,strrpos($extension,"/")+1);            

            Storage::disk('public')->put($uploaded_file->type.'/'.$uploaded_file->name, $base64_encoded_string);

            ///////////////////moving file to attachment table 
            $attachment = new attachment;
            $attachment->name = $uploaded_file->name;
            $attachment->path = $uploaded_file->getOriginal('path');
            $attachment->type =  $uploaded_file->type;
            $attachment->extension = $ext;
            $attachment->mime_type = $extension;
            $attachment->save();
            //////////////////////////////////////////////////
            ChunkUploads::whereId($uploaded_file->id)->delete();
            return response()->json(['message' =>null, 'body' => $attachment ], 200);
        }
        return response()->json(['message' =>null, 'body' => $uploaded_file ], 200);

    }
}
