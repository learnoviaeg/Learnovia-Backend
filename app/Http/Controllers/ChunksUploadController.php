<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ChunkUploads;
use Illuminate\Support\Facades\Storage;
use App\attachment;
// use File;
use Modules\UploadFiles\Entities\file;
use Modules\UploadFiles\Entities\media;

use Illuminate\Support\Facades\Auth;

class ChunksUploadController extends Controller
{
    // public function __construct(SettingsReposiotryInterface $setting)
    // {
    //     $this->setting = $setting;
    //     $this->middleware('auth');
    // }

    public function uploads(Request $request)
    {
        $request->validate([
            'content' => 'required',
        ]);
  
        if(!$request->filled('id')){  

            $request->validate([
                //file name
                'name' => 'required',
                'type' => 'required|in:assignment,files,media',
                // 'assignment_type' => 'required_if:type,==,assignment',
            ]);
            $fileName = $request->name.uniqid();

            $uploaded_file  = ChunkUploads::create([
                'name' => $fileName,
                // 'data'=> $request->content,
                'type' => $request->type,
            ]);
            //creating text file to save base64 chunks  
            Storage::disk('public')->put('uploads/'.$fileName.'.txt', $request->content);
        }

        if($request->filled('id')){
            $uploaded_file = ChunkUploads::whereId($request->id)->first();
            //apppending text to file
            Storage::disk('public')->append('uploads/'.$uploaded_file->name.'.txt', $request->content , null);
            $uploaded_file->save();
        }

        if($request->filled('last') && $request->last == 1){

            $uploaded_file->uploaded = 1;
            $uploaded_file->path = $uploaded_file->type.'/'.$uploaded_file->name;
            $uploaded_file->save();
            $base64_whole_string = Storage::disk('public')->get('uploads/'.$uploaded_file->name.'.txt');
            $base64_encoded_string = base64_decode(($base64_whole_string));
            $extension = finfo_buffer(finfo_open(), $base64_encoded_string, FILEINFO_MIME_TYPE);
            $ext = substr($extension,strrpos($extension,"/")+1);            
            Storage::disk('public')->put($uploaded_file->type.'/'.$uploaded_file->name .'.'. $ext, $base64_encoded_string);
            ///////////////////moving file to attachment table 
            if($uploaded_file->type == 'assignment'){
                $attachment = new attachment;
                $attachment->name = $uploaded_file->name.'.'. $ext;
                $attachment->path = $uploaded_file->getOriginal('path').'.'. $ext;
                $attachment->type =  $uploaded_file->type;
                $attachment->extension = $ext;
                $attachment->mime_type = $extension;
                $attachment->save();
            }
            ///////////////////moving file to files table 
            if($uploaded_file->type == 'files'){
                $name = $uploaded_file->name.'.'. $ext;
                $attachment = new file;
                $attachment->type = $ext;
                // $attachment->description = $name;
                $attachment->name = $uploaded_file->name;
                $attachment->attachment_name = $name;
                $attachment->user_id = Auth::user()->id;
                $attachment->url = 'https://docs.google.com/viewer?url=' . url('storage/files/' . $name);
                $attachment->url2 = 'files/' . $name;
                $attachment->save();
            }

                  /////////////////moving file to media table 
                  if($uploaded_file->type == 'media'){
                    $name = $uploaded_file->name.'.'. $ext;
                    $attachment = new media;
                    $attachment->type = finfo_buffer(finfo_open(), $base64_encoded_string, FILEINFO_MIME_TYPE);
                    // $attachment->description = $name;
                    $attachment->name = $uploaded_file->name;
                    $attachment->attachment_name = $name;
                    $attachment->user_id = Auth::user()->id;
                    $attachment->link = url('storage/media/' . $name);
                    $attachment->save();
                }
    

            //////////////////////////////////////////////////
            //removing db record and text file after moving uploaded file to the proper table
            ChunkUploads::whereId($uploaded_file->id)->delete();
            Storage::disk('public')->delete('uploads/'.$uploaded_file->name.'.txt');

            return response()->json(['message' =>null, 'body' => $attachment ], 200);
        }
        return response()->json(['message' =>null, 'body' => $uploaded_file ], 200);

    }
}
