<?php

namespace Modules\UploadFiles\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\UploadFiles\Entities\media;
use Modules\UploadFiles\Entities\MediaCourseSegment;

use Illuminate\Support\Facades\Storage;
use Validator;
use File;

class MediaController extends Controller
{

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        try{
            $validater=Validator::make($request->all(),[
                'description' => 'required|string|min:1',
                'Imported_file' => 'required|file|mimes:mp4,wmv,avi,flv,mp3,ogg,wma,jpg,jpeg,png,gif',
                'course_segment_id'=>'required|integer|exists:course_segments,id',
                'from' => 'required|date',
                'to' => 'required|date|after:from',
            ]);
            if ($validater->fails())
            {
                $errors=$validater->errors();
                return response()->json($errors,400);
            }

            $extension = $request->Imported_file->getClientOriginalExtension();

            $fileName = $request->Imported_file->getClientOriginalName();
            $size = $request->Imported_file->getSize();
            $description = $request->description;

            $file = new media;
            $file->type = $extension;
            $file->name = $fileName;
            $file->description = $description;
            $file->size = $size;
            $file->from = $request->from;
            $file->to = $request->to;
            $check = $file->save();
            if($check){

                $filesegment = new MediaCourseSegment;
                $filesegment->course_segment_id = $request->course_segment_id;
                $filesegment->media_id = $file->id;
                $filesegment->save();


                Storage::disk('public')->putFileAs(
                    'media/'.$file->id,
                    $request->Imported_file,
                    $request->Imported_file->getClientOriginalName()
                );
            }
            return response()->json(['msg'=>'Upload Successfully'],200);
        }catch (Exception $ex){
            return response()->json(['msg'=>'Please Try again'],400);
        }
    }

    public function update(Request $request)
    {
        try{
            $validater=Validator::make($request->all(),[
                'mediaId' => 'required|integer|exists:media,id',
                'description' => 'required|string|min:1',
                'Imported_file' => 'nullable|file|mimes:mp4,wmv,avi,flv,mp3,ogg,wma,jpg,jpeg,png,gif',
                'from' => 'required|date',
                'to' => 'required|date|after:from',
            ]);
            if ($validater->fails())
            {
                $errors=$validater->errors();
                return response()->json($errors,400);
            }

            $file = media::find($request->mediaId);
            if(isset($request->Imported_file)){
                $oldname = $file->name;

                $extension = $request->Imported_file->getClientOriginalExtension();
                $fileName = $request->Imported_file->getClientOriginalName();
                $size = $request->Imported_file->getSize();

                $file->type = $extension;
                $file->name = $fileName;
                $file->size = $size;
            }


            $file->description = $request->description;
            $file->from = $request->from;
            $file->to = $request->to;
            $check = $file->save();

            if($check){
                if(isset($request->Imported_file)){

                    $filePath = 'storage\media\\'.$file->id.'\\'.$oldname;
                    if (File::exists($filePath)) {
                        unlink($filePath);
                    }

                    Storage::disk('public')->putFileAs(
                        'media/'.$file->id,
                        $request->Imported_file,
                        $request->Imported_file->getClientOriginalName()
                    );
                }
            }
            return response()->json(['msg'=>'Updated Successfully'],200);
        }catch (Exception $ex){
            return response()->json(['msg'=>'Please Try again'],400);
        }
    }

    public function destroy(Request $request)
    {
        try{
            $validater=Validator::make($request->all(),[
                'mediaId' => 'required|integer|exists:media,id',
            ]);
            if ($validater->fails())
            {
                $errors=$validater->errors();
                return response()->json($errors,400);
            }

            $file = media::find($request->mediaId);
            $oldname = $file->name;
            $oldId = $file->id;
            $check = $file->delete();

            if($check){
                $filePath = 'storage\media\\'.$oldId.'\\'.$oldname;
                if (File::exists($filePath)) {
                    unlink($filePath);
                }
            }
            return response()->json(['msg'=>'Delete Successfully'],200);
        }catch (Exception $ex){
            return response()->json(['msg'=>'Please Try again'],400);
        }
    }

    public function toggleVisibility(Request $request)
    {
        try{
            $validater=Validator::make($request->all(),[
                'mediaId' => 'required|integer|exists:media,id',
            ]);
            if ($validater->fails())
            {
                $errors=$validater->errors();
                return response()->json($errors,400);
            }

            $file = media::find($request->mediaId);
            $file->visibility = ($file->visibility == 1)? 0 : 1;
            $file->save();

            return response()->json(['msg'=>$file->visibility],200);
        }catch (Exception $ex){
            return response()->json(['msg'=>'Please Try again'],400);
        }
    }


}
