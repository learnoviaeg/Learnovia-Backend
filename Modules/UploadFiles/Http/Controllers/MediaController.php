<?php

namespace Modules\UploadFiles\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
// use Illuminate\Routing\Controller;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Modules\UploadFiles\Entities\media;
use Modules\UploadFiles\Entities\MediaLesson;
use checkEnroll;
use URL;
use App\Classes;
use App\CourseItem;
use App\CourseSegment;
use App\Enroll;
use App\User;
use App\LastAction;
use App\Material;
use App\Http\Controllers\HelperController;
use App\LessonComponent;
use Auth;
use Illuminate\Support\Facades\Storage;
use App\Lesson;
use Modules\Page\Entities\pageLesson;
use App\Repositories\SettingsReposiotryInterface;
use App\SecondaryChain;
use App\Helpers\CoursesHelper;
use App\Repositories\NotificationRepoInterface;
use App\UserCourseItem;

class MediaController extends Controller
{
    protected $setting;

    /**
     *constructor.
     *
     * @param SettingsReposiotryInterface $setting
     */
    public function __construct(SettingsReposiotryInterface $setting, NotificationRepoInterface $notification)
    {
        $this->setting = $setting;
        $this->notification = $notification;
    }

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
             if(isset($media->MediaLesson)){
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
        $settings = $this->setting->get_value('upload_media_extensions');

        $exts = ['mpeg','mp3'];

        //search for mime type of file in exts array
        //key value and check in settings string if contains

        $rules = [
            'description' => 'nullable|string|min:1',
            'Imported_file' => 'required_if:type,==,0|array',
            'Imported_file.*' => 'required|file|distinct|mimes:'.$settings,
            'lesson_id' => 'required|array',
            'lesson_id.*' => 'required|exists:lessons,id',
            'url' => 'required_if:type,==,1|array',
            'url.*' => 'required|active_url',
            'type' => 'required|in:0,1',
            'name' => 'required',
            'show' => 'nullable|in:0,1',
            'visible' =>'in:0,1',
            'users_ids' => 'array',
            'users_ids.*' => 'exists:users,id'
        ];

        $customMessages = [
            'Imported_file.*.mimes' => __('messages.error.extension_not_supported')
        ];

        if ($request->hasFile('Imported_file')) {
            $customMessages = [
                'Imported_file.*.mimes' => $request->Imported_file[0]->getClientOriginalExtension() . ' ' .__('messages.error.extension_not_supported')
            ];
        }

        if($request->hasFile('Imported_file') && !in_array($request->Imported_file[0]->getClientOriginalExtension(),$exts))
            $this->validate($request, $rules, $customMessages);

        if ($request->filled('publish_date')) {
            $publishdate = $request->publish_date;
            if (Carbon::parse($request->publish_date)->isPast()) {
                $publishdate = Carbon::now();
            }
        } else {
            $publishdate = Carbon::now();
        }

        if ($request->type == 0)
            $array = $request->Imported_file;
        else if ($request->type == 1)
            $array = $request->url;
        foreach ($array as $item) {
            $media = new media;
            $media->user_id = Auth::user()->id;
            if ($request->type == 0) {
                $formsg=$item->getClientMimeType();
                $extension = $item->getClientOriginalExtension();
                $fileName = $item->getClientOriginalName();
                $size = $item->getSize();
                $name = uniqid() . '.' . $extension;
                $media->type = $item->getClientMimeType();
                // $media->name = $name;
                $media->size = $size;
                $media->attachment_name = $fileName;
                $media->link = url('storage/media/' . $name);
            }

            if ($request->type == 1) {
                // $avaiableHosts = collect([
                //     'www.youtube.com',
                //     'vimeo.com',
                //     'soundcloud.com',
                // ]);

                // $urlparts = parse_url($item);
                // if (!$avaiableHosts->contains($urlparts['host'])) {
                //     return HelperController::api_response_format(400, $item, 'Link is invalid');
                // }

                // if (!isset($urlparts['path'])) {
                //     return HelperController::api_response_format(400, $item, 'Link is invalid');
                // }
                // $media->name = $request->name;
                $media->attachment_name = $request->name;
                $media->link = $item;
            }

            $media->name = $request->name;

            if ($request->filled('description'))
                $media->description = $request->description;
            if ($request->filled('show'))
                $media->show = $request->show;
            $media->save();

            //bra l foreach beta3et l lesson 3l4an tarteb l observers
            if(isset($request->users_ids))
                CoursesHelper::giveUsersAccessToViewCourseItem($media->id, 'media', $request->users_ids);
            
            foreach ($request->lesson_id as $lesson) {

                $tempLesson = Lesson::find($lesson);
                $mediaLesson = new MediaLesson;
                $mediaLesson->lesson_id = $lesson;
                $mediaLesson->media_id = $media->id;
                $mediaLesson->index = MediaLesson::getNextIndex($lesson);
                $mediaLesson->publish_date = $publishdate;
                $mediaLesson->visible = isset($request->visible)?$request->visible:1;

                $mediaLesson->save();

                if ($request->type == 0) {
                    Storage::disk('public')->putFileAs('media/', $item, $name);
                }

                // dd($request->users_ids);
                // if(!isset($request->users_ids)){
                //     $users=SecondaryChain::select('user_id')->where('lesson_id',$lesson)->pluck('user_id');
                //     $this->notification->sendNotify($users->toArray(),$media->name. ' media is created',$media->id,'notification','media');    
                // }
            }
        }
        $tempReturn = Lesson::find($mediaLesson->lesson_id)->module('UploadFiles', 'media')->get();
        if($request->type == 0)
        {
            if(str_contains($formsg , 'image'))
            {
                return HelperController::api_response_format(200, $tempReturn, __('messages.media.image.add'));
            }else if(str_contains($formsg , 'video'))
            {
                return HelperController::api_response_format(200, $tempReturn, __('messages.media.video.add'));
            }else if(str_contains($formsg , 'audio'))
            {
                return HelperController::api_response_format(200, $tempReturn, __('messages.media.audio.add'));
            }else{
                return HelperController::api_response_format(200, $tempReturn, __('messages.media.add'));
            }
        }else if($request->type == 1){
            if($request->show == 1){
                return HelperController::api_response_format(200, $tempReturn, __('messages.media.url.add'));
            }else{
                return HelperController::api_response_format(200, $tempReturn, __('messages.media.link.add'));
            }
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
        $settings = $this->setting->get_value('upload_media_extensions');

        $rules = [
            'id' => 'required|integer|exists:media,id',
            'name' => 'nullable|string|max:190',
            'description' => 'nullable|string|min:1',
            'Imported_file' => 'nullable|file|mimes:'.$settings,
            'url' => 'nullable|active_url',
            'lesson_id' => 'required|array',
            'lesson_id.*' => 'required|exists:lessons,id',
            'publish_date' => 'nullable|date',
            'updated_lesson_id' =>'nullable|exists:lessons,id',
            'type' => 'in:0,1',
            'visible' => 'in:0,1',
        ];

        $customMessages = [
            'Imported_file.*.mimes' => __('messages.error.extension_not_supported')
        ];
        if(isset($request->Imported_file)){
            $customMessages = [
                'Imported_file.mimes' => $request->Imported_file->extension() . ' ' .__('messages.error.extension_not_supported')
            ];
        }

        $this->validate($request, $rules, $customMessages);

        $media = media::find($request->id);
        $mediaLesson = MediaLesson::whereIn('lesson_id' , $request->lesson_id)->where('media_id' , $request->id)->first();
        if(!isset($mediaLesson))
            return HelperController::api_response_format(400, null, __('messages.media.media_not_belong'));

        if(isset($request->Imported_file) && $request->filled('url'))
            return HelperController::api_response_format(400, null, __('messages.media.only_url_or_media'));

        if (isset($request->Imported_file)) {
            $extension = $request->Imported_file->getClientOriginalExtension();
            $fileName = $request->Imported_file->getClientOriginalName();
            $size = $request->Imported_file->getSize();
            $name = uniqid() . '.' . $extension;
            $media->type = $request->Imported_file->getClientMimeType();
            $media->size = $size;
            $media->attachment_name = $fileName;
            $media->link = url('storage/media/' . $name);
            Storage::disk('public')->putFileAs('media/', $request->Imported_file, $name);
        }

        if ($request->filled('url')){
            $media->link = $request->url;
            $media->size = null;
            $media->type = null;
        }

        if ($request->filled('description'))
            $media->description = $request->description;

        $media->update (['name' => isset($request->name) ? $request->name :$media->name]);
        $media->save();
        if ($request->filled('publish_date')) {
            $publishdate = $request->publish_date;
            if (Carbon::parse($request->publish_date)->isPast()) {
                $publishdate = Carbon::now();
            }
            $mediaLesson->update(['publish_date' => $publishdate]);
        }
        if ($request->filled('visible')) {
            $mediaLesson->update(['visible' => $request->visible]);
        }

        if (!$request->filled('updated_lesson_id')) {
            $request->updated_lesson_id= $request->lesson_id[0];
          }
        $mediaLesson->update([
            'lesson_id' => $request->updated_lesson_id
        ]);
        $mediaLesson->updated_at = Carbon::now();
        $mediaLesson->save();

        // //send notification
        // $users=SecondaryChain::select('user_id')->whereIn('lesson_id',$request->lesson_id)->pluck('user_id');
        // $courseItem = CourseItem::where('item_id', $media->id)->where('type', 'media')->first();
        // if(isset($courseItem))
        //     $users = UserCourseItem::where('course_item_id', $courseItem->id)->pluck('user_id');
        //     // dd($users);
        // $this->notification->sendNotify($users->toArray(),$media->name. ' media is updated',$media->id,'notification','media');    
        
        $tempReturn = Lesson::find($request->updated_lesson_id)->module('UploadFiles', 'media')->get();
        $lesson = Lesson::find($request->updated_lesson_id);
        $courseID = $lesson->course_id;
        LastAction::lastActionInCourse($courseID);

        if($media->type != null)
        {
            if(str_contains($media->type , 'image'))
            {
                return HelperController::api_response_format(200, $tempReturn, __('messages.media.image.update'));
            }else if(str_contains($media->type , 'video'))
            {
                return HelperController::api_response_format(200, $tempReturn, __('messages.media.video.update'));
            }else if(str_contains($media->type , 'audio'))
            {
                return HelperController::api_response_format(200, $tempReturn, __('messages.media.audio.update'));
            }else{
                return HelperController::api_response_format(200, $tempReturn, __('messages.media.update'));
            }
        }else{
            if($media->show == 1)
            {
                return HelperController::api_response_format(200, $tempReturn, __('messages.media.url.update'));
            }else{
                return HelperController::api_response_format(200, $tempReturn, __('messages.media.link.update'));
            }
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

        $media_type = media::whereId($request->mediaId)->pluck('type')->first();
        $media_show = media::whereId($request->mediaId)->pluck('show')->first();

        $media = media::whereId($request->mediaId)->first();
        $tempReturn = Lesson::find($request->lesson_id)->module('UploadFiles', 'media')->get();
        
        if($media !=null)
        $media->delete();
        
        $file = MediaLesson::where('media_id', $request->mediaId)->where('lesson_id', $request->lesson_id)->first();
        if ($file != null) {
            $file->delete();
        }

        Material::where('item_id',$request->mediaId)->where('type','media')->delete();
        $lesson = Lesson::find($request->lesson_id);
        LastAction::lastActionInCourse($lesson->course_id);

        if($media_type != null)
        {
            if(str_contains($media_type , 'image'))
            {
                return HelperController::api_response_format(200, $tempReturn, __('messages.media.image.delete'));
            }else if(str_contains($media_type , 'video'))
            {
                return HelperController::api_response_format(200, $tempReturn, __('messages.media.video.delete'));
            }else if(str_contains($media_type , 'audio'))
            {
                return HelperController::api_response_format(200, $tempReturn, __('messages.media.audio.delete'));
            }else{
                return HelperController::api_response_format(200, $tempReturn, __('messages.media.delete'));
            }
        }else{
            if($media_show == 1)
            {
                 return HelperController::api_response_format(200, $tempReturn, __('messages.media.url.delete'));
            }else{
                return HelperController::api_response_format(200, $tempReturn, __('messages.media.link.delete'));
            }
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
        try {
            $request->validate([
                'mediaId' => 'required|integer|exists:media,id',
                'LessonID' => 'required|integer|exists:media_lessons,lesson_id',

            ]);
            $media = media::find($request->mediaId);
            $mediaLesson = MediaLesson::where('media_id', $request->mediaId)->where('lesson_id', '=', $request->LessonID)->first();
            if (!isset($mediaLesson)) {
                return HelperController::api_response_format(400, null, __('messages.error.data_invalid'));
            }
            $lesson = Lesson::find($request->LessonID);
            LastAction::lastActionInCourse($lesson->course_id);
            $mediaLesson->visible = ($mediaLesson->visible == 1) ? 0 : 1;
            $mediaLesson->save();

            return HelperController::api_response_format(200, $media, __('messages.success.toggle'));
        } catch (Exception $ex) {
            return HelperController::api_response_format(400, null, __('messages.error.try_again'));
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
            return HelperController::api_response_format(400, null, __('messages.error.data_invalid'));
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

        $rules = [
            'id' => 'required|integer|exists:media,id',
        ];
        $customMessages = [
            'exists' => __('messages.error.item_deleted')
        ];

        $this->validate($request, $rules, $customMessages);
        $Media = media::with('MediaLesson')->with('courseItem.courseItemUsers')->find($request->id);
        if( $request->user()->can('site/course/student')){
            $courseItem = CourseItem::where('item_id', $Media->id)->where('type', 'media')->first();
            if(isset($courseItem)){
                $users = UserCourseItem::where('course_item_id', $courseItem->id)->pluck('user_id')->toArray();
                if(!in_array(Auth::id(), $users))
                    return response()->json(['message' => __('messages.error.no_permission'), 'body' => null], 403);
            }
            if($Media->MediaLesson[0]->visible==0)
                return HelperController::api_response_format(301,null, __('messages.media.media_hidden'));
        }

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
            return HelperController::api_response_format(200, $media_lessons, __('messages.media.add'));
        } catch (Exception $ex) {
            return HelperController::api_response_format(400, null, __('messages.error.try_again'));
        }
    }
}
