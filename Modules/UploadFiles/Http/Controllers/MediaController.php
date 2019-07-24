<?php

namespace Modules\UploadFiles\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\UploadFiles\Entities\media;
use Modules\UploadFiles\Entities\MediaCourseSegment;
use Modules\UploadFiles\Entities\FileCourseSegment;
use Modules\UploadFiles\Entities\FileLesson;
use Modules\UploadFiles\Entities\MediaLesson;
use App\ClassLevel;
use App\Enroll;

use App\Http\Controllers\HelperController;
use Auth;
use Illuminate\Support\Facades\Storage;
use Validator;
use File;


class MediaController extends Controller
{

    /**
     * Store a array of Media to specific course segment.
     * @param Request $request
     * Following sending in the request
     * @param description of the Media
     * @param Imported_file of the array of Media
     * @param course_segment_id id of the course segment
     * @param from as the start date of showing this Media.
     * @param to as the end date of showing this Media
     * @return Response as success Message
     */
    public function store(Request $request)
    {
        try{
            $request->validate([
                'description' => 'required|string|min:1',
                'Imported_file' => 'required|array',
                'Imported_file.*' => 'required|file|distinct|mimes:mp4,wmv,avi,flv,mp3,ogg,wma,jpg,jpeg,png,gif',
                'class_level' => 'required|array',
                'class_level.*' => 'required|integer|exists:class_levels,id',
                'lesson_id'=>'required|integer|exists:lessons,id',
                'from' => 'required|date',
                'to' => 'required|date|after:from',
            ]);

            // activeCourseSgement
            $activeCourseSegments = collect([]);

            foreach($request->class_level as $class_level_id){
                $class_level = ClassLevel::find($class_level_id);
                $activeSegmentClass = $class_level->segmentClass->where('is_active',1)->first();
                if(isset($activeSegmentClass)){
                    $activeCourseSegment = $activeSegmentClass->courseSegment->where('is_active',1)->first();
                    if(isset($activeCourseSegment)){
                        $checkTeacherEnroll = Enroll::where('user_id', Auth::user()->id)
                            ->where('course_segment', $request->course_segment_id)
                            ->where('role_id', 4)
                            ->count();

                        if($checkTeacherEnroll > 0){
                            $activeCourseSegments->push($activeCourseSegment);
                        }
                        else{
                            return HelperController::api_response_format(400,null,'You\'re unauthorize');
                        }
                    }
                    else{
                        return HelperController::api_response_format(400,null,'No Course active in segment');
                    }
                }
                else{
                    return HelperController::api_response_format(400,null,'No Class active in segment');
                }
            }

            foreach ($request->Imported_file as $singlefile) {
                $extension = $singlefile->getClientOriginalExtension();

                $fileName = $singlefile->getClientOriginalName();
                $size = $singlefile->getSize();
                $description = $request->description;

                $name = uniqid().'.'.$extension;

                $file = new media;
                $file->type = $extension;
                $file->name = $name;
                $file->description = $description;
                $file->size = $size;
                $file->from = $request->from;
                $file->to = $request->to;
                $file->user_id = Auth::user()->id;
                $check = $file->save();

                if($check){

                    foreach($activeCourseSegments as $courseSegment){
                        $filesegment = new MediaCourseSegment;
                        $filesegment->course_segment_id = $courseSegment->id;
                        $filesegment->media_id = $file->id;
                        $filesegment->save();
                    }


                    $fileLesson = new MediaLesson;
                    $fileLesson->lesson_id = $request->lesson_id;
                    $fileLesson->media_id = $file->id;
                    $fileLesson->save();

                    Storage::disk('public')->putFileAs(
                        'media/'.$request->lesson_id.'/'.$file->id,
                        $singlefile,
                        $name
                    );
                }
            }

            return HelperController::api_response_format(200,null,'Upload Successfully');
        }catch (Exception $ex){
            return HelperController::api_response_format(400,null,'Please Try again');
        }
    }

    /**
     * Update data of specific Media
     * @param Request $request
     * Following sending in the request
     * @param mediaId ID of the file that wanted to update
     * @param description of the Media
     * @param Imported_file (optional) to change the Media itself
     * @param from as the start date of showing this Media.
     * @param to as the end date of showing this Media
     * @return Response as success Message
     */
    public function update(Request $request)
    {
        try{
            $request->validate([
                'mediaId' => 'required|integer|exists:media,id',
                'description' => 'required|string|min:1',
                'Imported_file' => 'nullable|file|mimes:mp4,wmv,avi,flv,mp3,ogg,wma,jpg,jpeg,png,gif',
                'from' => 'required|date',
                'to' => 'required|date|after:from',
            ]);

            $file = media::find($request->mediaId);

            //check Authotizing
            $courseSegmentID = $file->MediaCourseSegment->course_segment_id;
            $checkEnroll = Enroll::where('user_id', Auth::user()->id)
            ->where('course_segment', $courseSegmentID)
            ->where('role_id', 4)
            ->count();

            if($checkEnroll == 0){
                return HelperController::api_response_format(400,null,'You\'re unauthorize');
            }

            if(isset($request->Imported_file)){
                $oldname = $file->name;

                $extension = $request->Imported_file->getClientOriginalExtension();
                $fileName = uniqid().'.'.$extension;

              //  $fileName = $request->Imported_file->getClientOriginalName();
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
                    $fileId = $file->id;
                    $lesson_id = $file->MediaLesson->lesson_id;

                    $filePath = 'storage\media\\'.$lesson_id.'\\'.$fileId.'\\'.$oldname;
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }

                    Storage::disk('public')->putFileAs(
                        'media/'.$lesson_id.'/'.$fileId,
                        $request->Imported_file,
                        $fileName
                    );
                }
            }
            return HelperController::api_response_format(200,null,'Update Successfully');
        }catch (Exception $ex){
            return HelperController::api_response_format(400,null,'Please Try again');
        }
    }

    /**
     * Delete Specifc Media
     * @param Request $request
     * Following sending in the request
     * @param mediaId ID of the media that wanted to update
     * @return Response as success Message
     */
    public function destroy(Request $request)
    {
        try{
            $request->validate([
                'mediaId' => 'required|integer|exists:media,id',
            ]);

            $file = media::find($request->mediaId);

            //check Authotizing
            $courseSegmentID = $file->MediaCourseSegment->course_segment_id;
            $checkEnroll = Enroll::where('user_id', Auth::user()->id)
            ->where('course_segment', $courseSegmentID)
            ->where('role_id', 4)
            ->count();

            if($checkEnroll == 0){
                return HelperController::api_response_format(400,null,'You\'re unauthorize');
            }

            $oldname = $file->name;
            $fileId = $file->id;
            $lesson_id = $file->MediaLesson->lesson_id;

            $check = $file->delete();

            if($check){
                $filePath = 'storage\media\\'.$lesson_id.'\\'.$fileId.'\\'.$oldname;
                if (file_exists($filePath)) {
                    unlink($filePath);
                    unlink('storage\media\\'.$lesson_id.'\\'.$fileId);
                }
            }
            return HelperController::api_response_format(200,null,'Deleted Successfully');
        }catch (Exception $ex){
            return HelperController::api_response_format(400,null,'Please Try again');
        }
    }

    /**
     * Toggle Visibility of specific Media
     * @param Request $request
     * Following sending in the request
     * @param mediaId ID of the media that wanted to toggle its visibility
     * @return Response as success Message
     */
    public function toggleVisibility(Request $request)
    {
        try{
            $request->validate([
                'mediaId' => 'required|integer|exists:media,id',
            ]);

            $media = media::find($request->mediaId);

            //check Authotizing
            $courseSegmentID = $media->MediaCourseSegment->course_segment_id;
            $checkEnroll = Enroll::where('user_id', Auth::user()->id)
            ->where('course_segment', $courseSegmentID)
            ->where('role_id', 4)
            ->count();

            if($checkEnroll == 0){
                return HelperController::api_response_format(400,null,'You\'re unauthorize');
            }

            $media->visibility = ($media->visibility == 1)? 0 : 1;
            $media->save();

            return HelperController::api_response_format(200,$media,'Toggle Successfully');
        }catch (Exception $ex){
            return HelperController::api_response_format(400,null,'Please Try again');
        }
    }


}
