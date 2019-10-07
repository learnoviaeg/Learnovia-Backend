<?php

namespace Modules\UploadFiles\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Carbon\Carbon;
use Modules\UploadFiles\Entities\media;
use Modules\UploadFiles\Entities\MediaCourseSegment;
use Modules\UploadFiles\Entities\MediaLesson;
use checkEnroll;
use URL;
use App\Classes;
use App\CourseSegment;
use App\Enroll;
use App\User;
use App\Http\Controllers\HelperController;
use App\LessonComponent;
use Auth;
use Illuminate\Support\Facades\Storage;


class MediaController extends Controller
{

    public function getAllMedia(Request $request){
        $request->validate([
            'course' => 'required_with:class|integer|exists:courses,id',
            'class' => 'required_with:course|integer|exists:classes,id',
        ]);
        $MEDIA = collect([]);

        if(isset($request->class)){

            $class = Classes::with([
                'classlevel.segmentClass.courseSegment' =>
                    function ($query) use ($request) {
                        $query->with(['lessons'])->where('course_id',$request->course);
                    }])->whereId($request->class)->first();

            foreach($class->classlevel->segmentClass as $segmentClass){
                foreach($segmentClass->courseSegment as $courseSegment){
                    foreach($courseSegment->lessons as $lesson){

                        foreach($lesson->MediaLesson as $mediaLesson){
                            $allMedia = $mediaLesson->Media;

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
                    }

                }
            }
        }
        else{
            $allMedia = media::all();

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
        return HelperController::api_response_format(200,$MEDIA);

    }

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
        try {
            $request->validate([
                'description' => 'string|min:1',
                'Imported_file' => 'required|array',
                'Imported_file.*' => 'required|file|distinct|mimes:mp4,avi,flv,mpga,ogg,ogv,oga,jpg,jpeg,png,gif',
                'lesson_id' => 'required|integer|exists:lessons,id',
                //'year' => 'required|integer|exists:academic_years,id',
                //'type' => 'required|integer|exists:academic_types,id',
                //'level' => 'required|integer|exists:levels,id',
                //'class' => 'required|array',
                //'class.*' => 'required|integer|exists:classes,id',
            ]);

            if($request->filled('publish_date'))
            {
                $publishdate=$request->publish_date;
            }
            else
            {
                $publishdate=Carbon::now();
            }

            // activeCourseSgement
            $activeCourseSegments = HelperController::Get_Course_segment_Course($request);
            if ($activeCourseSegments['result'] == false) {
                return HelperController::api_response_format(400, null, $activeCourseSegments['value']);
            }
            if ($activeCourseSegments['value'] == null) {
                return HelperController::api_response_format(400, null, 'No Course active in segment');
            }

            $activeCourseSegments = $activeCourseSegments['value'];
            // $checkTeacherEnroll = checkEnroll::checkEnrollmentAuthorization($activeCourseSegments->id);
            // if (!$checkTeacherEnroll == true) {
            //     return HelperController::api_response_format(400, null, 'You\'re unauthorize');
            // }

            // foreach($request->class as $class){

            //     $newRequest = new Request();
            //     $newRequest->setMethod('POST');
            //     $newRequest->request->add(['year' => $request->year]);
            //     $newRequest->request->add(['type' => $request->type]);
            //     $newRequest->request->add(['level' => $request->level]);
            //     $newRequest->request->add(['class' => $class]);

            //     $class_level = HelperController::Get_class_LEVELS($newRequest);

            //     $activeSegmentClass = $class_level->segmentClass->where('is_active',1)->first();

            //     if(isset($activeSegmentClass)){
            //         $activeCourseSegment = $activeSegmentClass->courseSegment->where('is_active',1)->first();
            //         if(isset($activeCourseSegment)){
            //             // check Enroll
            //             $checkTeacherEnroll = checkEnroll::checkEnrollmentAuthorization($activeCourseSegment->id);
            //             if($checkTeacherEnroll == true){
            //                 $activeCourseSegments->push($activeCourseSegment);
            //             }
            //             else{
            //                 return HelperController::api_response_format(400,null,'You\'re unauthorize');
            //             }
            //         }
            //         else{
            //             return HelperController::api_response_format(400,null,'No Course active in segment');
            //         }
            //     }
            //     else{
            //         return HelperController::api_response_format(400,null,'No Class active in segment');
            //     }
            // }

            foreach ($request->Imported_file as $singlefile) {
                $extension = $singlefile->getClientOriginalExtension();

                $fileName = $singlefile->getClientOriginalName();
                $size = $singlefile->getSize();
                $description = $request->description;

                $name = uniqid() . '.' . $extension;

                $file = new media;
                $file->type = $extension;
                $file->name = $name;
                $file->description = $description;
                $file->size = $size;
                $file->attachment_name = $fileName;
                $file->user_id = Auth::user()->id;
                $file->link = url('public/storage/media/' . $request->lesson_id . '/' . $name);
                $check = $file->save();
                $courseID=CourseSegment::where('id',$activeCourseSegments->id)->pluck('course_id')->first();
                $usersIDs=Enroll::where('course_segment',$activeCourseSegments->id)->pluck('user_id')->toarray();
                User::notify([
                    'message' => 'This media is added',
                    'from' => Auth::user()->id,
                    'users' => $usersIDs,
                    'course_id' => $courseID,
                    'type' => 'media',
                    'publish_date' => $publishdate,
                ]);

                if ($check) {
                    $filesegment = new MediaCourseSegment;
                    $filesegment->course_segment_id = $activeCourseSegments->id;
                    $filesegment->media_id = $file->id;
                    $filesegment->save();
                    $maxIndex = MediaLesson::where('lesson_id', $request->lesson_id)->max('index');
                    if ($maxIndex == null) {
                        $newIndex = 1;
                    } else {
                        $newIndex = ++$maxIndex;
                    }

                    $fileLesson = new MediaLesson;
                    $fileLesson->lesson_id = $request->lesson_id;
                    $fileLesson->media_id = $file->id;
                    $fileLesson->index = $newIndex;
                    $fileLesson->publish_date = $publishdate;

                    $fileLesson->save();
                    LessonComponent::create([
                        'lesson_id' => $fileLesson->lesson_id,
                        'comp_id'   => $fileLesson->media_id,
                        'module'    => 'UploadFiles',
                        'model'     => 'media',
                        'index'     => LessonComponent::getNextIndex($fileLesson->lesson_id)
                    ]);
                    Storage::disk('public')->putFileAs(
                        'media/' . $request->lesson_id ,
                        $singlefile,
                        $name
                    );
                }
            }

            return HelperController::api_response_format(200, $file, 'Upload Successfully');
        } catch (Exception $ex) {
            return HelperController::api_response_format(400, null, 'Please Try again');
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
        try {
            $request->validate([
                'mediaId' => 'required|integer|exists:media,id',
                'attachment_name' => 'required|string|max:190',
                'description' => 'required|string|min:1',
                'Imported_file' => 'nullable|file|mimes:mp4,avi,flv,mpga,ogg,ogv,oga,jpg,jpeg,png,gif',
            ]);

            $file = media::find($request->mediaId);

            if(!isset($file->MediaCourseSegment)){
                return HelperController::api_response_format(404, null,'No Media Found');
            }

            //check Authotizing

            $courseSegmentID = $file->MediaCourseSegment->course_segment_id;

            // check Enroll
            $checkTeacherEnroll = checkEnroll::checkEnrollmentAuthorization($courseSegmentID);
            if ($checkTeacherEnroll == false) {
                return HelperController::api_response_format(400, null, 'You\'re unauthorize');
            }

            if (isset($request->Imported_file)) {
                $oldname = $file->name;

                $extension = $request->Imported_file->getClientOriginalExtension();
                $fileName = uniqid() . '.' . $extension;

                //  $fileName = $request->Imported_file->getClientOriginalName();
                $size = $request->Imported_file->getSize();

                $file->type = $extension;
                $file->name = $fileName;
                $file->size = $size;
                $lesson_id = $file->MediaLesson->lesson_id;
                $file->link = url('public/storage/media/' . $lesson_id . '/' . $fileName);
            }
            $file->attachment_name = $request->attachment_name;
            $file->description = $request->description;
            $check = $file->save();
            $courseID=CourseSegment::where('id',$courseSegmentID)->pluck('course_id')->first();
            $usersIDs=Enroll::where('course_segment',$courseSegmentID)->pluck('user_id')->toarray();
                User::notify([
                'message' => 'This media is Updated',
                'from' => Auth::user()->id,
                'users' => $usersIDs,
                'course_id' => $courseID,
                'type' => 'media',
                'publish_date' => Carbon::now(),
            ]);


            if ($check) {
                if (isset($request->Imported_file)) {
                    $fileId = $file->id;
                    $lesson_id = $file->MediaLesson->lesson_id;

                    $filePath = 'storage\media\\' . $lesson_id . '\\' . $oldname;
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }

                    Storage::disk('public')->putFileAs(
                        'media/' . $lesson_id,
                        $request->Imported_file,
                        $fileName
                    );
                }
            }
            return HelperController::api_response_format(200, null, 'Update Successfully');
        } catch (Exception $ex) {
            return HelperController::api_response_format(400, null, 'Please Try again');
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
            $request->validate([
                'mediaId' => 'required|integer|exists:media_lessons,media_id',
                'lesson_id' => 'required|exists:media_lessons,lesson_id'
                ]);

            $file = MediaLesson::where('media_id', $request->mediaId)->where('lesson_id',$request->lesson_id)->first();
            $file->delete();

            return HelperController::api_response_format(200, $body = [], $message = 'File deleted succesfully');
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
        try {
            $request->validate([
                'mediaId' => 'required|integer|exists:media,id',
                'LessonID' => 'required|integer|exists:media_lessons,lesson_id',

            ]);

            $media = media::find($request->mediaId);

            if(!isset($media->MediaCourseSegment)){
                return HelperController::api_response_format(404, null,'No Media Found');
            }

            //check Authotizing
            $courseSegmentID = $media->MediaCourseSegment->course_segment_id;

            // check Enroll
            $checkTeacherEnroll = checkEnroll::checkEnrollmentAuthorization($courseSegmentID);
            if ($checkTeacherEnroll == false) {
                return HelperController::api_response_format(400, null, 'You\'re unauthorize');
            }
            $mediaLesson= MediaLesson::where('media_id',$request->mediaId)->where('lesson_id','=',$request->LessonID)->first();
            if(!isset($mediaLesson)){
                return HelperController::api_response_format(400, null, 'Try again , Data invalid');
            }

            $mediaLesson->visible = ($mediaLesson->visible == 1) ? 0 : 1;
            $mediaLesson->save();

            return HelperController::api_response_format(200, $media, 'Toggle Successfully');
        } catch (Exception $ex) {
            return HelperController::api_response_format(400, null, 'Please Try again');
        }
    }


    public function storeMediaLink(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:130',
                'description' => 'nullable|string|min:1',
                'url' => 'required|active_url',
                'lesson_id' => 'required|integer|exists:lessons,id',
                'attachment_name' => 'required|string|max:190',
                //'year' => 'required|integer|exists:academic_years,id',
                //'type' => 'required|integer|exists:academic_types,id',
                //'level' => 'required|integer|exists:levels,id',
                //'class' => 'required|array',
                //'class.*' => 'required|integer|exists:classes,id',
                ]);

            $avaiableHosts = collect([
                'www.youtube.com',
                'vimeo.com',
                'soundcloud.com',
            ]);

            $urlparts = parse_url($request->url);
            if (!$avaiableHosts->contains($urlparts['host'])) {
                return HelperController::api_response_format(400, $request->url, 'Link is invalid');
            }

            if(!isset($urlparts['path'])){
                return HelperController::api_response_format(400, $request->url, 'Link is invalid');
            }
             // activeCourseSgement
             $activeCourseSegments = HelperController::Get_Course_segment_Course($request);
             if ($activeCourseSegments['result'] == false || $activeCourseSegments['value'] == null) {
                 return HelperController::api_response_format(400, null, 'No Course active in segment');
             }
             $activeCourseSegments = $activeCourseSegments['value'];
            //  $checkTeacherEnroll = checkEnroll::checkEnrollmentAuthorization($activeCourseSegments->id);
            //  if (!$checkTeacherEnroll == true) {
            //      return HelperController::api_response_format(400, null, 'You\'re unauthorize');
            //  }

            // foreach ($request->class as $class) {

            //     $newRequest = new Request();
            //     $newRequest->setMethod('POST');
            //     $newRequest->request->add(['year' => $request->year]);
            //     $newRequest->request->add(['type' => $request->type]);
            //     $newRequest->request->add(['level' => $request->level]);
            //     $newRequest->request->add(['class' => $class]);

            //     $class_level = HelperController::Get_class_LEVELS($newRequest);

            //     $activeSegmentClass = $class_level->segmentClass->where('is_active', 1)->first();
            //     if (isset($activeSegmentClass)) {
            //         $activeCourseSegment = $activeSegmentClass->courseSegment->where('is_active', 1)->first();
            //         if (isset($activeCourseSegment)) {
            //             // check Enroll
            //             $checkTeacherEnroll = checkEnroll::checkEnrollmentAuthorization($activeCourseSegment->id);
            //             if ($checkTeacherEnroll == true) {
            //                 $activeCourseSegments->push($activeCourseSegment);
            //             } else {
            //                 return HelperController::api_response_format(400, null, 'You\'re unauthorize');
            //             }
            //         } else {
            //             return HelperController::api_response_format(400, null, 'No Course active in segment');
            //         }
            //     } else {
            //         return HelperController::api_response_format(400, null, 'No Class active in segment');
            //     }
            // }

            $file = new media;
            $file->name = $request->name;
            $file->description = $request->description;
            $file->link = $request->url;
            $file->attachment_name = $request->attachment_name;
            $file->user_id = Auth::user()->id;
            $check = $file->save();

            if ($check) {
                    $filesegment = new MediaCourseSegment;
                    $filesegment->course_segment_id = $activeCourseSegments->id;
                    $filesegment->media_id = $file->id;
                    $filesegment->save();


                $maxIndex = MediaLesson::where('lesson_id', $request->lesson_id)->max('index');

                if ($maxIndex == null) {
                    $newIndex = 1;
                } else {
                    $newIndex = ++$maxIndex;
                }

                $fileLesson = new MediaLesson;
                $fileLesson->lesson_id = $request->lesson_id;
                $fileLesson->media_id = $file->id;
                $fileLesson->index = $newIndex;
                $fileLesson->publish_date = Carbon::now();
                $fileLesson->save();
            }

            return HelperController::api_response_format(200, $file, 'Link added Successfully');
        } catch (Exception $ex) {
            return HelperController::api_response_format(400, null, 'Please Try again');
        }
    }

    public function updateMediaLink(Request $request)
    {
        try {
            $request->validate([
                'mediaId' => 'required|integer|exists:media,id',
                'name' => 'required|string|max:130',
                'description' => 'nullable|string|min:1',
                'url' => 'required|active_url',
                'attachment_name' => 'required|string|max:190',
            ]);

            $file = media::find($request->mediaId);

            $avaiableHosts = collect([
                'www.youtube.com',
                'vimeo.com',
                'soundcloud.com',
            ]);

            $urlparts = parse_url($request->url);
            if (!$avaiableHosts->contains($urlparts['host'])) {
                return HelperController::api_response_format(400, $request->url, 'Link is invalid');
            }

            if(!isset($urlparts['path'])){
                return HelperController::api_response_format(400, $request->url, 'Link is invalid');
            }

            if(!isset($file->MediaCourseSegment)){
                return HelperController::api_response_format(404, null,'No Media Found');
            }

            //check Authotizing
            $courseSegmentID = $file->MediaCourseSegment->course_segment_id;

            // check Enroll
            $checkTeacherEnroll = checkEnroll::checkEnrollmentAuthorization($courseSegmentID);
            if ($checkTeacherEnroll == false) {
                return HelperController::api_response_format(400, null, 'You\'re unauthorize');
            }

            $file->name = $request->name;
            $file->description = $request->description;
            $file->link = $request->url;
            $file->attachment_name = $request->attachment_name;
            $file->save();

            return HelperController::api_response_format(200, null, 'Update Link Successfully');
        } catch (Exception $ex) {
            return HelperController::api_response_format(400, null, 'Please Try again');
        }
    }

    public function sortLessonMedia(Request $request)
    {
        $request->validate([
            'media_lesson_id' => 'required|integer|exists:media_lessons,id',
            'index' => 'required|integer'
        ]);
        $mediaLesson = MediaLesson::find($request->media_lesson_id);
        $maxIndex = $mediaLesson->max('index');
        $minIndex = $mediaLesson->min('index');

        if (!($request->index <= $maxIndex && $request->index >= $minIndex)) {
            return HelperController::api_response_format(400, null, ' invalid index');
        }

        $currentIndex = $mediaLesson->index;
        if ($currentIndex > $request->index) {
            $this->sortDown($mediaLesson, $currentIndex, $request->index);
        } else {
            $this->sortUp($mediaLesson, $currentIndex, $request->index);
        }
        return HelperController::api_response_format(200, null, ' Successfully');
    }

    public function sortDown($mediaLesson, $currentIndex, $newIndex)
    {

        $lesson_id = $mediaLesson->lesson_id;

        $MediaLessons = MediaLesson::where('lesson_id', $lesson_id)->get();

        foreach ($MediaLessons as $singleMediaLesson) {
            if ($singleMediaLesson->index < $newIndex || $singleMediaLesson->index > $currentIndex) {
                continue;
            } elseif ($singleMediaLesson->index  !=  $currentIndex) {
                $singleMediaLesson->update([
                    'index' => $singleMediaLesson->index + 1
                ]);
            } else {
                $singleMediaLesson->update([
                    'index' => $newIndex
                ]);
            }
        }
        return $MediaLessons;
    }

    public function sortUp($mediaLesson, $currentIndex, $newIndex)
    {

        $lesson_id = $mediaLesson->lesson_id;

        $MediaLessons = MediaLesson::where('lesson_id', $lesson_id)->get();

        foreach ($MediaLessons as $singleMediaLesson) {
            if ($singleMediaLesson->index > $newIndex || $singleMediaLesson->index < $currentIndex) {
                continue;
            } elseif ($singleMediaLesson->index  !=  $currentIndex) {
                $singleMediaLesson->update([
                    'index' => $singleMediaLesson->index - 1
                ]);
            } else {
                $singleMediaLesson->update([
                    'index' => $newIndex
                ]);
            }
        }
        return $MediaLessons;
    }
    Public function GetMediaByID(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:media,id',
        ]);
        $Media=media::find($request->id);
        return HelperController::api_response_format(200, $Media);
    }
}
