<?php

namespace Modules\UploadFiles\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\UploadFiles\Entities\file;
use Modules\UploadFiles\Entities\FileCourseSegment;
use Modules\UploadFiles\Entities\MediaCourseSegment;


use Illuminate\Support\Facades\Storage;
use Validator;
use URL;

class FilesController extends Controller
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
                'Imported_file' => 'required|file|mimes:pdf,docx,doc,xls,xlsx,ppt,pptx,zip,rar',
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

            $file = new file;
            $file->type = $extension;
            $file->name = $fileName;
            $file->description = $description;
            $file->size = $size;
            $check = $file->save();
            if($check){

                $filesegment = new FileCourseSegment;
                $filesegment->course_segment_id = $request->course_segment_id;
                $filesegment->file_id = $file->id;
                $filesegment->save();


                Storage::disk('public')->putFileAs(
                    'files/'.$file->id,
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
    public function show(Request $request)
    {
        $validater=Validator::make($request->all(),[
            'course_segment_id'=>'required|integer|exists:course_segments,id',
        ]);
        if ($validater->fails())
        {
            $errors=$validater->errors();
            return response()->json($errors,400);
        }

        $mediaSegment = MediaCourseSegment::where('course_segment_id', $request->course_segment_id)->get();
        $fileSegment = FileCourseSegment::where('course_segment_id', $request->course_segment_id)->get();

        foreach ($mediaSegment as $segement) {
            $segement->Media;
            foreach ($segement->Media as $media) {
                $media->url  = URL::asset('storage/media/'.$media->id.'/'.$media->name);
                unset($media->id,$media->updated_at);
            }
            unset($segement->id,$segement->created_at,$segement->updated_at);
        }

        foreach ($fileSegment as $segement) {
            $segement->File;
            foreach ($segement->File as $file) {
                $file->url  = URL::asset('storage/files/'.$file->id.'/'.$file->name);
                unset($file->id,$file->updated_at);
            }
            unset($segement->id,$segement->created_at,$segement->updated_at);
        }

        return response()->json([
            'media' => $mediaSegment,
            'files' => $fileSegment
        ],200);
    }

}
