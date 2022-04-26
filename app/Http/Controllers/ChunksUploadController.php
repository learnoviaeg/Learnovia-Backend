<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ChunkUploads;

class ChunksUploadController extends Controller
{
    public function uploads(Request $request)
    {
        $request->validate([
            'content' => 'required',
            // 'id' => 'exists:.....,id',
        ]);
  
        if(!$request->filled('id')){  

            $request->validate([
                'type' => 'required|in:assignment,files'
            ]);
            $uploaded_file  = ChunkUploads::create([
                'name' => uniqid(),
                'data'=> $request->content,
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
            Storage::put($uploaded_file->type.'/'.$uploaded_file->name, $base64_encoded_string);
            return response()->json(['message' =>null, 'body' => $uploaded_file ], 200);
        }
        return response()->json(['message' =>null, 'body' => $uploaded_file ], 200);

    }
}
