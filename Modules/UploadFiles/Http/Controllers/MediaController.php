<?php

namespace Modules\UploadFiles\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\UploadFiles\Entities\media;
use Modules\UploadFiles\Entities\MediaCourseSegment;

use Illuminate\Support\Facades\Storage;
use Validator;

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

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('uploadfiles::show');
    }


}
