<?php

namespace Modules\UploadFiles\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Carbon\Carbon;
use Modules\UploadFiles\Entities\media;
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
use App\Lesson;
use Modules\Page\Entities\pageLesson;

class MediaController extends Controller
{

    public function getAllMedia(Request $request)
    {
        $request->validate([
            'course' => 'required_with:class|integer|exists:courses,id',
            'class' => 'required_with:course|integer|exists:classes,id',
        ]);
        $MEDIA = collect([]);

        if (isset($request->class)) {

            $class = Classes::with([
                'classlevel.segmentClass.courseSegment' =>
                function ($query) use ($request) {
                    $query->with(['lessons'])->where('course_id', $request->course);
                }
            ])->whereId($request->class)->first();

            foreach ($class->classlevel->segmentClass as $segmentClass) {
                foreach ($segmentClass->courseSegment as $courseSegment) {
                    foreach ($courseSegment->lessons as $lesson) {

                        foreach ($lesson->MediaLesson as $mediaLesson) {
                            $allMedia = $mediaLesson->Media;

                            foreach ($allMedia as $media) {
                                $lesson_id = $media->MediaLesson->lesson_id;
                                if (!isset($media->link)) {
                                    $media->path  = URL::asset('storage/media/' . $lesson_id . '/' . $media->id . '/' . $media->name);
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
        } else {
            $allMedia = media::all();

            foreach ($allMedia as $media) {
                $lesson_id = $media->MediaLesson->lesson_id;
                if (!isset($media->link)) {
                    $media->path  = URL::asset('storage/media/' . $lesson_id . '/' . $media->id . '/' . $media->name);
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
        return HelperController::api_response_format(200, $MEDIA);
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
        $request->validate([
            'description' => 'nullable|string|min:1',
            'Imported_file' => 'required_if:type,==,0|array',
            'Imported_file.*' => 'required|file|distinct|mimes:mp4,avi,flv,mpga,ogg,ogv,oga,jpg,jpeg,png,gif,doc',
            'lesson_id' => 'required|array',
            'lesson_id.*' => 'required|exists:lessons,id',
            'url' => 'required_if:type,==,1|array',
            'url.*' => 'required|active_url',
            'type' => 'required|in:0,1',
            'name' => 'required_if:type,==,1',
            'show' => 'nullable|in:0,1'
        ]);

        if ($request->filled('publish_date')) {
            $publishdate = $request->publish_date;
            if (Carbon::parse($request->publish_date)->isPast()) {
                $publishdate = Carbon::now();
            }
        } else {
            $publishdate = Carbon::now();
        }

        foreach ($request->lesson_id as $lesson) {
            $tempLesson = Lesson::find($lesson);
            if ($request->type == 0)
                $array = $request->Imported_file;
            else if ($request->type == 1)
                $array = $request->url;
            foreach ($array as $item) {
                $media = new media;
                $media->user_id = Auth::user()->id;
                if ($request->type == 0) {
                    $extension = $item->getClientOriginalExtension();
                    $fileName = $item->getClientOriginalName();
                    $size = $item->getSize();
                    $name = uniqid() . '.' . $extension;
                    $media->type = $item->getClientMimeType();
                    $media->name = $name;
                    $media->size = $size;
                    $media->attachment_name = $fileName;
                    $media->link = url('public/storage/media/' . $name);
                }

                if ($request->type == 1) {
                    $avaiableHosts = collect([
                        'www.youtube.com',
                        'vimeo.com',
                        'soundcloud.com',
                    ]);

                    $urlparts = parse_url($item);
                    if (!$avaiableHosts->contains($urlparts['host'])) {
                        return HelperController::api_response_format(400, $item, 'Link is invalid');
                    }

                    if (!isset($urlparts['path'])) {
                        return HelperController::api_response_format(400, $item, 'Link is invalid');
                    }
                    $media->name = $request->name;
                    $media->attachment_name = $request->name;
                    $media->link = $item;
                }

                if ($request->filled('description'))
                    $media->description = $request->description;
                if ($request->filled('show'))
                    $media->show = $request->show;
                $media->save();
                $mediaLesson = new MediaLesson;
                $mediaLesson->lesson_id = $lesson;
                $mediaLesson->media_id = $media->id;
                $mediaLesson->index = MediaLesson::getNextIndex($lesson);
                $mediaLesson->publish_date = $publishdate;
                $mediaLesson->save();
                $courseID = CourseSegment::where('id', $tempLesson->courseSegment->id)->pluck('course_id')->first();
                $class_id=$tempLesson->courseSegment->segmentClasses[0]->classLevel[0]->class_id;
                $usersIDs = Enroll::where('course_segment', $tempLesson->courseSegment->id)->pluck('user_id')->toarray();
                User::notify([
                    'id' => $media->id,
                    'message' => 'This media is added',
                    'from' => Auth::user()->id,
                    'users' => $usersIDs,
                    'course_id' => $courseID,
                    'class_id' => $class_id,
                    'type' => 'media',
                    'publish_date' => $publishdate,
                ]);
                LessonComponent::create([
                    'lesson_id' => $mediaLesson->lesson_id,
                    'comp_id'   => $mediaLesson->media_id,
                    'module'    => 'UploadFiles',
                    'model'     => 'media',
                    'index'     => LessonComponent::getNextIndex($mediaLesson->lesson_id)
                ]);
                if ($request->type == 0) {
                    Storage::disk('public')->putFileAs('media/', $item, $name);
                }
            }
        }
        $tempReturn = Lesson::find($mediaLesson->lesson_id)->module('UploadFiles', 'media')->get();
        return HelperController::api_response_format(200, $tempReturn, 'Upload Successfully');
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
        $request->validate([
            'id' => 'required|integer|exists:media,id',
            'name' => 'nullable|string|max:190',
            'description' => 'nullable|string|min:1',
            'Imported_file' => 'nullable|file|mimes:mp4,avi,flv,mpga,ogg,ogv,oga,jpg,jpeg,png,gif',
            'url' => 'nullable|active_url',
            'lesson_id' => 'required|array',
            'lesson_id.*' => 'required|exists:lessons,id',
            'publish_date' => 'nullable|date'
        ]);

        $media = media::find($request->id);
        $mediaLesson = MediaLesson::whereIn('lesson_id' , $request->lesson_id)->where('media_id' , $request->id)->first();
        if ($media->type != null && $request->hasFile('Imported_file')) {
            $extension = $request->Imported_file->getClientOriginalExtension();
            $fileName = $request->Imported_file->getClientOriginalName();
            $size = $request->Imported_file->getSize();
            $name = uniqid() . '.' . $extension;
            $media->type = $request->Imported_file->getClientMimeType();
            $media->size = $size;
            $media->attachment_name = $fileName;
            $media->link = url('public/storage/media/' . $name);
            Storage::disk('public')->putFileAs('media/', $request->Imported_file, $fileName);
        }

        if ($media->type == null && $request->filled('url')) {
            $media->link = $request->url;
        }

        if ($request->filled('description'))
            $media->description = $request->description;

        if ($request->filled('name'))
            $media->name = $request->name;
        $media->save();
        if ($request->filled('publish_date')) {
            $publishdate = $request->publish_date;
            if (Carbon::parse($request->publish_date)->isPast()) {
                $publishdate = Carbon::now();
            }
            $mediaLesson->update(['publish_date' => $publishdate]);
        }
        $tempReturn = Lesson::find($request->lesson_id[0])->module('UploadFiles', 'media')->get();;
        return HelperController::api_response_format(200, $tempReturn, 'Update Successfully');
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

        $file = MediaLesson::where('media_id', $request->mediaId)->where('lesson_id', $request->lesson_id)->first();
        $file->delete();
        $tempReturn = Lesson::find($request->lesson_id)->module('UploadFiles', 'media')->get();;
        return HelperController::api_response_format(200, $tempReturn, $message = 'File deleted succesfully');
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
            $mediaLesson = MediaLesson::where('media_id', $request->mediaId)->where('lesson_id', '=', $request->LessonID)->first();
            if (!isset($mediaLesson)) {
                return HelperController::api_response_format(400, null, 'Try again , Data invalid');
            }

            $mediaLesson->visible = ($mediaLesson->visible == 1) ? 0 : 1;
            $mediaLesson->save();

            return HelperController::api_response_format(200, $media, 'Toggle Successfully');
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
    public function GetMediaByID(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:media,id',
        ]);
        $Media = media::find($request->id);
        return HelperController::api_response_format(200, $Media);
    }
    public function AssignMediaToLesson(Request $request)
    {
        try {
            $request->validate([
                'media_id' => 'required|exists:media,id',
                'lesson_id' => 'required|exists:lessons,id',
                'publish_date' => 'required|date'
            ]);
            $media_lessons = MediaLesson::create([
                'lesson_id' => $request->lesson_id, 'media_id' => $request->media_id, 'publish_date' => $request->publish_date
            ]);
            return HelperController::api_response_format(200, $media_lessons, 'Assigned Successfully');
        } catch (Exception $ex) {
            return HelperController::api_response_format(400, null, 'Please Try again');
        }
    }
}
