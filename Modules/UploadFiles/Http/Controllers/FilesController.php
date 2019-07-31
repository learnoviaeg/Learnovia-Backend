<?php

namespace Modules\UploadFiles\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\UploadFiles\Entities\file;
use Modules\UploadFiles\Entities\FileCourseSegment;
use Modules\UploadFiles\Entities\MediaCourseSegment;
use Modules\UploadFiles\Entities\FileLesson;
use Modules\UploadFiles\Entities\MediaLesson;
use App\ClassLevel;
use App\Enroll;
use App\Lesson;


use Illuminate\Support\Facades\Storage;
use URL;
use Auth;
use checkEnroll;
use App\Http\Controllers\HelperController;
use Carbon\Carbon;

class FilesController extends Controller
{

    /**
     * Store a array of files to specific course segment.
     * @param Request $request
     * Following sending in the request
     * @param description of the file
     * @param Imported_file of the array of files
     * @param course_segment_id id of the course segment
     * @param from as the start date of showing this file.
     * @param to as the end date of showing this file
     * @return Response as success Message
     */
    public function store(Request $request)
    {
        try{
            $request->validate([
                'description' => 'required|string|min:1',
                'Imported_file' => 'required|array',
                'Imported_file.*' => 'required|file|distinct|mimes:pdf,docx,doc,xls,xlsx,ppt,pptx,zip,rar',
                'class_level' => 'required|array',
                'class_level.*' => 'required|integer|exists:class_levels,id',
                'lesson_id'=>'required|integer|exists:lessons,id',
                'visibility' => 'required|boolean'
            ]);

            // activeCourseSgement
            $activeCourseSegments = collect([]);

            foreach($request->class_level as $class_level_id){
                $class_level = ClassLevel::find($class_level_id);
                $activeSegmentClass = $class_level->segmentClass->where('is_active',1)->first();
                if(isset($activeSegmentClass)){
                    $activeCourseSegment = $activeSegmentClass->courseSegment->where('is_active',1)->first();
                    if(isset($activeCourseSegment)){
                        // check Enroll
                        $checkTeacherEnroll = checkEnroll::checkEnrollmentAuthorization($activeCourseSegment->id);

                        if($checkTeacherEnroll == true){
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

            foreach($request->Imported_file as $singlefile){
                $extension = $singlefile->getClientOriginalExtension();

                $fileName = $singlefile->getClientOriginalName();
                $size = $singlefile->getSize();
                $description = $request->description;

                $name = uniqid().'.'.$extension;

                $file = new file;
                $file->type = $extension;
                $file->name = $name;
                $file->description = $description;
                $file->size = $size;
                $file->visibility = $request->visibility;
                $file->user_id = Auth::user()->id;
                $check = $file->save();

                if($check){
                    foreach($activeCourseSegments as $courseSegment){
                        $filesegment = new FileCourseSegment;
                        $filesegment->course_segment_id = $courseSegment->id;
                        $filesegment->file_id = $file->id;
                        $filesegment->save();
                    }

                    $maxIndex = FileLesson::where('lesson_id',$request->lesson_id)->max('index');

                    if($maxIndex == null){
                        $newIndex = 1;
                    }
                    else{
                        $newIndex = ++$maxIndex;
                    }

                    $fileLesson = new FileLesson;
                    $fileLesson->lesson_id = $request->lesson_id;
                    $fileLesson->file_id = $file->id;
                    $fileLesson->index = $newIndex;
                    $fileLesson->save();

                    Storage::disk('public')->putFileAs(
                        'files/'.$request->lesson_id.'/'.$file->id,
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
     * Get All files and Media assigned to specific course segment.
     * @param Request $request
     * Following sending in the request
     * @param course_segment_id id of the course segment
     * @return Response as all files and media that are avaliable and between the from and to date
     */
    public function show(Request $request)
    {
        $request->validate([
            'lesson_id' => 'required|integer|exists:lessons,id',
        ]);

        $MEDIA = collect([]);
        $FILES = collect([]);

        $lesson = Lesson::find($request->lesson_id);
        $checkEnroll = checkEnroll::checkEnrollment($lesson->course_segment_id);

        if($checkEnroll == true){
            $mediaLessons = MediaLesson::where('lesson_id', $request->lesson_id)->orderBy('index','asc')->get();
            $fileLessons = FileLesson::where('lesson_id', $request->lesson_id)->orderBy('index','asc')->get();

            foreach ($mediaLessons as $mediaLesson) {
                $allMedia = $mediaLesson->Media->where('visibility',1);

                foreach ($allMedia as $media) {
                    $lesson_id = $media->MediaLesson->lesson_id;
                    if(!isset($media->link)){
                        $media->path  = URL::asset('storage/media/'.$lesson_id.'/'.$media->id.'/'.$media->name);
                    }
                    $userid = $media->user->id;
                    $firstname = $media->user->firstname;
                    $lastname = $media->user->lastname;
                    $user = collect([
                        'user_id' => $userid,
                        'firstname' => $firstname,
                        'lastname' => $lastname
                    ]);
                    unset($media->user);
                    unset($media->MediaLesson);
                    $media->owner = $user;

                    $MEDIA->push($media);
                }
            }

            foreach ($fileLessons as $fileLesson) {
                $allFiles = $fileLesson->File->where('visibility',1);

                foreach ($allFiles as $file) {
                    $lesson_id = $file->FileLesson->lesson_id;
                    $file->path  = URL::asset('storage/files/'.$lesson_id.'/'.$file->id.'/'.$file->name);

                    $userid = $file->user->id;
                    $firstname = $file->user->firstname;
                    $lastname = $file->user->lastname;
                    $user = collect([
                        'user_id' => $userid,
                        'firstname' => $firstname,
                        'lastname' => $lastname
                    ]);
                    unset($file->user);
                    unset($file->FileLesson);
                    $file->owner = $user;

                    $FILES->push($file);
                }
            }
        }

        $Files_media = collect([
            'media' => $MEDIA,
            'files' => $FILES
        ]);

        return HelperController::api_response_format(200,$Files_media);
    }

    /**
     * Update data of specific file
     * @param Request $request
     * Following sending in the request
     * @param fileID ID of the file that wanted to update
     * @param description of the file
     * @param Imported_file (optional) to change the file itself
     * @param from as the start date of showing this file.
     * @param to as the end date of showing this file
     * @return Response as success Message
     */
    public function update(Request $request)
    {
        try{
            $request->validate([
                'fileID' => 'required|integer|exists:files,id',
                'description' => 'required|string|min:1',
                'Imported_file' => 'nullable|file|mimes:pdf,docx,doc,xls,xlsx,ppt,pptx,zip,rar',
                'visibility' => 'required|boolean'
            ]);

            $file = file::find($request->fileID);

            $courseSegmentID = $file->FileCourseSegment->course_segment_id;

            // check Enroll
            $checkTeacherEnroll = checkEnroll::checkEnrollmentAuthorization($courseSegmentID);

            if($checkTeacherEnroll == false){
                return HelperController::api_response_format(400,null,'You\'re unauthorize');
            }

            if(isset($request->Imported_file)){
                $oldname = $file->name;

                $extension = $request->Imported_file->getClientOriginalExtension();
                $fileName = uniqid().'.'.$extension;

               // $fileName = $request->Imported_file->getClientOriginalName();
                $size = $request->Imported_file->getSize();

                $file->type = $extension;
                $file->name = $fileName;
                $file->size = $size;
            }

            $file->description = $request->description;
            $file->visibility = $request->visibility;
            $check = $file->save();

            if($check){
                if(isset($request->Imported_file)){
                    $fileId = $file->id;
                    $lesson_id = $file->FileLesson->lesson_id;

                    $filePath = 'storage\files\\'.$lesson_id.'\\'.$fileId.'\\'.$oldname;
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }

                    Storage::disk('public')->putFileAs(
                       'files/'.$lesson_id.'/'.$fileId,
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

   // return response()->json();

    /**
     * Delete Specifc File
     * @param Request $request
     * Following sending in the request
     * @param fileID ID of the file that wanted to update
     * @return Response as success Message
     */
    public function destroy(Request $request)
    {
        try{
            $request->validate([
                'fileID' => 'required|integer|exists:files,id',
            ]);

            $file = file::find($request->fileID);

            //check Authotizing
            $courseSegmentID = $file->FileCourseSegment->course_segment_id;

            // check Enroll
            $checkTeacherEnroll = checkEnroll::checkEnrollmentAuthorization($courseSegmentID);

            if($checkTeacherEnroll == false){
                return HelperController::api_response_format(400,null,'You\'re unauthorize');
            }

            $oldname = $file->name;
            $fileId = $file->id;
            $lesson_id = $file->FileLesson->lesson_id;

            $check = $file->delete();

            if($check){
                $filePath = 'storage\files\\'.$lesson_id.'\\'.$fileId.'\\'.$oldname;
                if (file_exists($filePath)) {
                    unlink($filePath);
                    unlink('storage\files\\'.$lesson_id.'\\'.$fileId);
                }
            }
            return HelperController::api_response_format(200,null,'Deleted Successfully');
        }catch (Exception $ex){
            return HelperController::api_response_format(400,null,'Please Try again');
        }
    }

    /**
     * Toggle Visibility of specific File
     * @param Request $request
     * Following sending in the request
     * @param fileID ID of the file that wanted to toggle its visibility
     * @return Response as success Message
     */
    public function toggleVisibility(Request $request)
    {
        try{
            $request->validate([
                'fileID' => 'required|integer|exists:files,id',
            ]);

            $file = file::find($request->fileID);

            //check Authotizing
            $courseSegmentID = $file->FileCourseSegment->course_segment_id;

            // check Enroll
            $checkTeacherEnroll = checkEnroll::checkEnrollmentAuthorization($courseSegmentID);
            if($checkTeacherEnroll == false){
                return HelperController::api_response_format(400,null,'You\'re unauthorize');
            }

            $file->visibility = ($file->visibility == 1)? 0 : 1;
            $file->save();

            return HelperController::api_response_format(200,$file,'Toggle Successfully');
        }catch (Exception $ex){
            return HelperController::api_response_format(400,null,'Please Try again');
        }
    }

    public function sortLessonFile(Request $request){
        $request->validate([
            'file_lesson_id' => 'required|integer|exists:file_lessons,id',
            'index'=>'required|integer'
        ]);
        $fileLesson = FileLesson::find($request->file_lesson_id);
        $maxIndex = $fileLesson->max('index');
        $minIndex = $fileLesson->min('index');

        if(!($request->index <= $maxIndex && $request->index >= $minIndex)){
            return HelperController::api_response_format(400, null,' invalid index');
        }

        $currentIndex = $fileLesson->index;
        if($currentIndex > $request->index){
            $this->sortDown($fileLesson,$currentIndex,$request->index);
        }
        else{
            $this->sortUp($fileLesson,$currentIndex,$request->index);
        }
        return HelperController::api_response_format(200, null,' Successfully');
    }

    public function sortDown($fileLesson,$currentIndex,$newIndex){

        $lesson_id = $fileLesson->lesson_id;

        $fileLessons = FileLesson::where('lesson_id',$lesson_id)->get();

        foreach ($fileLessons as $singleFileLesson ){
            if($singleFileLesson->index < $newIndex || $singleFileLesson->index > $currentIndex ){
                continue;
            }
            elseif ($singleFileLesson->index  !=  $currentIndex){
                $singleFileLesson->update([
                    'index'=>$singleFileLesson->index+1
                ]);
            }else{
                $singleFileLesson->update([
                    'index'=>$newIndex
                ]);
            }
        }
        return $fileLessons ;

    }

    public function sortUp($fileLesson,$currentIndex,$newIndex){

        $lesson_id = $fileLesson->lesson_id;

        $fileLessons = FileLesson::where('lesson_id',$lesson_id)->get();

        foreach ($fileLessons as $singleFileLesson ){
            if($singleFileLesson->index > $newIndex || $singleFileLesson->index < $currentIndex ){
                continue;
            }
            elseif ($singleFileLesson->index  !=  $currentIndex){
                $singleFileLesson->update([
                    'index'=>$singleFileLesson->index-1
                ]);
            }else{
                $singleFileLesson->update([
                    'index'=>$newIndex
                ]);
            }
        }
        return $fileLessons ;
    }



}
