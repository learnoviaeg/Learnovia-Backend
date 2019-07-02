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
use Carbon\Carbon;

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

            $file = new file;
            $file->type = $extension;
            $file->name = $fileName;
            $file->description = $description;
            $file->size = $size;
            $file->from = $request->from;
            $file->to = $request->to;
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

        $MEDIA = collect([]);
        $FILES = collect([]);

        $year = Carbon::now()->year;
        $month = Carbon::now()->month;
        $day = Carbon::now()->day;

        foreach ($mediaSegment as $segement) {
            $allMedia = $segement->Media;
            foreach ($allMedia as $index => $media) {
                if($media->visibility == 0){
                    continue;
                }

                $from = explode('-',$media->from);
                $to = explode('-',$media->to);

                $start = Carbon::create($from[0], $from[1], $from[2]);
                $end = Carbon::create($to[0], $to[1], $to[2]);

                $checkDate = Carbon::create($year,$month,$day)->between($start, $end);

                if($checkDate == false){
                    continue;
                }

                $media->url  = URL::asset('storage/media/'.$media->id.'/'.$media->name);
                unset($media->updated_at);
                $MEDIA->push($media);
            }
            unset($segement->created_at,$segement->updated_at);
        }

        foreach ($fileSegment as $segement) {
            $segement->File;
            foreach ($segement->File as $file) {
                if($file->visibility == 0){
                    continue;
                }

                $from = explode('-',$file->from);
                $to = explode('-',$file->to);

                $start = Carbon::create($from[0], $from[1], $from[2]);
                $end = Carbon::create($to[0], $to[1], $to[2]);

                $checkDate = Carbon::create($year,$month,$day)->between($start, $end);

                if($checkDate == false){
                    continue;
                }

                $file->url  = URL::asset('storage/files/'.$file->id.'/'.$file->name);
                unset($file->updated_at);
                $FILES->push($file);
            }
            unset($segement->created_at,$segement->updated_at);
        }

        return response()->json([
            'media' => $MEDIA,
            'files' => $FILES
        ],200);
    }

    public function update(Request $request)
    {
        try{
            $validater=Validator::make($request->all(),[
                'fileID' => 'required|integer|exists:files,id',
                'description' => 'required|string|min:1',
                'Imported_file' => 'nullable|file|mimes:pdf,docx,doc,xls,xlsx,ppt,pptx,zip,rar',
                'from' => 'required|date',
                'to' => 'required|date|after:from',
            ]);
            if ($validater->fails())
            {
                $errors=$validater->errors();
                return response()->json($errors,400);
            }

            $file = file::find($request->fileID);
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

                    $filePath = 'storage\files\\'.$file->id.'\\'.$oldname;
                    if (File::exists($filePath)) {
                        unlink($filePath);
                    }

                    Storage::disk('public')->putFileAs(
                        'files/'.$file->id,
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
                'fileID' => 'required|integer|exists:files,id',
            ]);
            if ($validater->fails())
            {
                $errors=$validater->errors();
                return response()->json($errors,400);
            }

            $file = file::find($request->fileID);
            $oldname = $file->name;
            $oldId = $file->id;
            $check = $file->delete();
           // $check = 1;
            if($check){
                $filePath = 'storage\files\\'.$oldId.'\\'.$oldname;
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
                'fileID' => 'required|integer|exists:files,id',
            ]);
            if ($validater->fails())
            {
                $errors=$validater->errors();
                return response()->json($errors,400);
            }

            $file = file::find($request->fileID);
            $file->visibility = ($file->visibility == 1)? 0 : 1;
            $file->save();

            return response()->json(['msg'=>$file->visibility],200);
        }catch (Exception $ex){
            return response()->json(['msg'=>'Please Try again'],400);
        }
    }

}
