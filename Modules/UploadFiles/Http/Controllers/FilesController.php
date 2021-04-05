<?php

namespace Modules\UploadFiles\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
// use Illuminate\Routing\Controller;
use App\Http\Controllers\Controller;
use Modules\UploadFiles\Entities\file;
use Modules\UploadFiles\Entities\media;
use Modules\UploadFiles\Entities\FileLesson;
use Modules\UploadFiles\Entities\MediaLesson;
use App\Lesson;
use App\Classes;
use Illuminate\Support\Facades\Storage;
use URL;
use Auth;
use Log;
use checkEnroll;
use Carbon\Carbon;
use App\CourseSegment;
use App\Enroll;
use App\User;
use App\Http\Controllers\HelperController;
use App\Component;
use App\LessonComponent;
use  Modules\Page\Entities\pageLesson;
use  Modules\Page\Entities\page;
use App\Material;
use  App\LastAction;
use App\Repositories\SettingsReposiotryInterface;

class FilesController extends Controller
{

    protected $setting;

    /**
     *constructor.
     *
     * @param SettingsReposiotryInterface $setting
     */
    public function __construct(SettingsReposiotryInterface $setting)
    {
        $this->setting = $setting;        
    }

    public function install_file()
    {
        if (\Spatie\Permission\Models\Permission::whereName('file/add')->first() != null) {
            return \App\Http\Controllers\HelperController::api_response_format(400, null, 'This Component is installed before');
        }
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'file/add', 'title' => 'add file']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'file/assign', 'title' => 'assign file']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'file/update', 'title' => 'update file']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'file/delete', 'title' => 'delete file']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'file/toggle', 'title' => 'toggle file']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'media/add', 'title' => 'add media']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'media/update', 'title' => 'update media']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'media/delete', 'title' => 'delete media']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'media/toggle', 'title' => 'toggle media']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'file/media/get', 'title' => 'get file and media']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'link/add', 'title' => 'add link']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'link/update', 'title' => 'update link']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'file/sort', 'title' => 'sort file']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'media/sort', 'title' => 'sort media']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'file/get-all', 'title' => 'get all files']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'media/get-all', 'title' => 'get all media']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'media/get', 'title' => 'get media']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'file/get', 'title' => 'get file']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'site/file/edit', 'title' => 'update file']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'site/media/edit', 'title' => 'update media']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'media/assign', 'title' => 'assign media']);

        $teacher_permissions=['file/add','file/assign','file/update','file/delete','file/toggle','media/add','media/update','media/delete',
        'media/toggle','file/media/get','link/add','link/update','file/sort','media/sort','media/get','file/get','site/file/edit','site/media/edit',
        'media/assign'];

        $student_permissions=['page/get','media/get','file/get'];

        $student = \Spatie\Permission\Models\Role::find(3);
        $student->givePermissionTo(\Spatie\Permission\Models\Permission::whereIn('name', $student_permissions)->get());
        $parent = \Spatie\Permission\Models\Role::find(7);
        $parent->givePermissionTo(\Spatie\Permission\Models\Permission::whereIn('name', $student_permissions)->get());


        $tecaher = \Spatie\Permission\Models\Role::find(4);
        $tecaher->givePermissionTo(\Spatie\Permission\Models\Permission::whereIn('name', $teacher_permissions)->get());

        $role = \Spatie\Permission\Models\Role::find(1);
        $role->givePermissionTo('file/add');
        $role->givePermissionTo('file/update');
        $role->givePermissionTo('file/delete');
        $role->givePermissionTo('file/toggle');
        $role->givePermissionTo('media/add');
        $role->givePermissionTo('media/update');
        $role->givePermissionTo('media/delete');
        $role->givePermissionTo('media/toggle');
        $role->givePermissionTo('file/media/get');
        $role->givePermissionTo('link/add');
        $role->givePermissionTo('link/update');
        $role->givePermissionTo('file/sort');
        $role->givePermissionTo('media/sort');
        $role->givePermissionTo('file/get-all');
        $role->givePermissionTo('media/get-all');
        $role->givePermissionTo('media/get');
        $role->givePermissionTo('file/get');
        $role->givePermissionTo('site/file/edit');
        $role->givePermissionTo('site/media/edit');
        $role->givePermissionTo('file/assign');
        $role->givePermissionTo('media/assign');

        Component::create([
            'name' => 'Media',
            'module' => 'UploadFiles',
            'model' => 'media',
            'type' => 1,
            'active' => 1
        ]);

        Component::create([
            'name' => 'File',
            'module' => 'UploadFiles',
            'model' => 'file',
            'type' => 1,
            'active' => 1
        ]);

        return \App\Http\Controllers\HelperController::api_response_format(200, null, 'Component Installed Successfully');
    }


    public function getAllFiles(Request $request)
    {
        $request->validate([
            'course' => 'required_with:class|integer|exists:courses,id',
            'class' => 'required_with:course|integer|exists:classes,id',
        ]);
        $FILES = collect([]);

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

                        foreach ($lesson->fileLesson as $fileLesson) {
                            $allFiles = $fileLesson->File;

                            foreach ($allFiles as $file) {
                                $lesson_id = $file->FileLesson->lesson_id;
                                $file->path  = URL::asset('storage/files/' . $lesson_id . '/' . $file->id . '/' . $file->name);

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
                }
            }
        } else {
            $allFiles = File::all();

            foreach ($allFiles as $file) {
             if(isset($file->FileLesson)){
                $lesson_id = $file->FileLesson->lesson_id;
                $file->path  = URL::asset('storage/files/' . $lesson_id . '/' . $file->id . '/' . $file->name);

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
        return HelperController::api_response_format(200, $FILES);
    }

    /**
     * Store a array of files to specific lessons.
     * @param Request $request
     * Following sending in the request
     * @param description of the file
     * @param Imported_file of the array of files
     * @param from as the start date of showing this file.
     * @param to as the end date of showing this file
     * @return Response as success Message
     */
    public function store(Request $request)
    {
        $settings = $this->setting->get_value('upload_file_extensions');

        $request->validate([
            'name' => 'string|min:1',
            'Imported_file' => 'required|array',
            'Imported_file.*' => 'required|file|distinct|mimes:'.$settings,
            'lesson_id' => 'required|array',
            'lesson_id.*' => 'exists:lessons,id',
            'publish_date' => 'nullable|date',
            'visible' =>'in:0,1'
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
            foreach ($request->Imported_file as $singlefile) {
                $extension = $singlefile->getClientOriginalExtension();
                $fileName = $singlefile->getClientOriginalName();
                $size = $singlefile->getSize();
                $name = uniqid() . '.' . $extension;
                $file = new file;
                $file->type = $extension;
                $file->description = $name;
                $file->name = ($request->filled('name')) ? $request->name : $fileName;
                $file->size = $size;
                $file->attachment_name = $fileName;
                $file->user_id = Auth::user()->id;
                $file->url = 'https://docs.google.com/viewer?url=' . url('storage/files/' . $name);
                $file->url2 = 'files/' . $name;
                $check = $file->save();
                Log::debug('file heeeeeeeeeeeere '. $file);
                $courseID = CourseSegment::where('id', $tempLesson->courseSegment->id)->pluck('course_id')->first();
                $class_id=$tempLesson->courseSegment->segmentClasses[0]->classLevel[0]->class_id;
                $usersIDs = User::whereIn('id' , Enroll::where('course_segment', $tempLesson->courseSegment->id)->where('user_id','!=',Auth::user()->id)->pluck('user_id')->toArray())->pluck('id');
                LastAction::lastActionInCourse($courseID);

                User::notify([
                    'id' => $file->id,
                    'message' => $file->name.' file is added',
                    'from' => Auth::user()->id,
                    'users' => isset($usersIDs) ? $usersIDs->toArray() : [null],
                    'course_id' => $courseID,
                    'class_id' => $class_id,
                    'lesson_id' => $lesson,
                    'type' => 'file',
                    'link' => $file->url,
                    'publish_date' => Carbon::parse($publishdate),
                ]);
                if ($check) {
                    $fileLesson = new FileLesson;
                    $fileLesson->lesson_id = $lesson;
                    $fileLesson->file_id = $file->id;
                    $fileLesson->index = FileLesson::getNextIndex($lesson);
                    $fileLesson->publish_date = $publishdate;
                    $fileLesson->visible = isset($request->visible)?$request->visible:1;

                    $fileLesson->save();
                    LessonComponent::create([
                        'lesson_id' => $fileLesson->lesson_id,
                        'comp_id'   => $fileLesson->file_id,
                        'module'    => 'UploadFiles',
                        'model'     => 'file',
                        'index'     => LessonComponent::getNextIndex($fileLesson->lesson_id)
                    ]);
                    Storage::disk('public')->putFileAs(
                        'files/' . $request->$lesson,
                        $singlefile,
                        $name
                    );
                }
            }
        }
        $file = Lesson::find($request->lesson_id[0])->module('UploadFiles', 'file')->get();;

        return HelperController::api_response_format(200,$file , __('messages.file.add'));
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

        if ($checkEnroll == true) {
            $mediaLessons = MediaLesson::where('lesson_id', $request->lesson_id)->orderBy('index', 'asc')->get();
            $fileLessons = FileLesson::where('lesson_id', $request->lesson_id)->orderBy('index', 'asc')->get();

            foreach ($mediaLessons as $mediaLesson) {
                $allMedia = $mediaLesson->Media->where('visibility', 1);

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

            foreach ($fileLessons as $fileLesson) {
                $allFiles = $fileLesson->File->where('visibility', 1);

                foreach ($allFiles as $file) {
                    $lesson_id = $file->FileLesson->lesson_id;
                    $file->path  = URL::asset('storage/files/' . $lesson_id . '/' . $file->id . '/' . $file->name);

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

        return HelperController::api_response_format(200, $Files_media);
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
        $settings = $this->setting->get_value('upload_file_extensions');

        $request->validate([
            'id'            => 'required|exists:files,id',
            'name'          => 'nullable|string|max:190',
            'description'   => 'nullable|string|min:1',
            'Imported_file' => 'nullable|file|distinct|mimes:'.$settings,
            'lesson_id'        => 'required|exists:lessons,id',
            'publish_date'  => 'nullable|date',
            'updated_lesson_id' =>'nullable|exists:lessons,id',
            'visible' =>'in:0,1'
        ]);
        $file = file::find($request->id);

        if ($request->filled('name'))
            $file->name = $request->name;
        if (isset($request->Imported_file)) {
            $extension = $request->Imported_file->getClientOriginalExtension();
            $name = uniqid() . '.' . $extension;
            Storage::disk('public')->putFileAs('files/', $request->Imported_file, $name);
            $file->url = 'https://docs.google.com/viewer?url=' . url('storage/files/' . $name);
            $file->url2 = 'files/' . $name;
            $file->type = $extension;
            $fileName =  $request->Imported_file->getClientOriginalName();
            $file->description = $name;
            $file->attachment_name = $fileName;

        }
        $tempReturn = null;
        $fileLesson = FileLesson::where('file_id', $request->id)->where('lesson_id', $request->lesson_id)->first();
        if(!isset($fileLesson))
            return HelperController::api_response_format(200, null , __('messages.file.file_not_belong'));
        if ($request->filled('publish_date')) {
            $publishdate = $request->publish_date;
            if (Carbon::parse($request->publish_date)->isPast()) {
                $publishdate = Carbon::now();
            } else {
                $publishdate = Carbon::parse($request->publish_date);
            }
            
            $fileLesson->update([
                'publish_date' => $publishdate,
            ]);
        }
        if ($request->filled('visible')) {
            $fileLesson->update([
                'visible' => $request->visible,
            ]);
          }
        
        if (!$request->filled('updated_lesson_id')) {
          $request->updated_lesson_id= $request->lesson_id;
        }
        $fileLesson->update([
            'lesson_id' => $request->updated_lesson_id
        ]);
        $fileLesson->updated_at = Carbon::now();
        $file->save();
        $course_seg_drag = Lesson::where('id',$request->lesson_id)->pluck('course_segment_id')->first();
        $courseID_drag = CourseSegment::where('id', $course_seg_drag)->pluck('course_id')->first();
        LastAction::lastActionInCourse($courseID_drag);
        $fileLesson->save();
        $lesson = Lesson::find($request->updated_lesson_id);
        $course_seg = Lesson::where('id',$request->updated_lesson_id)->pluck('course_segment_id')->first();
        $courseID = CourseSegment::where('id', $course_seg)->pluck('course_id')->first();
        $class_id=$lesson->courseSegment->segmentClasses[0]->classLevel[0]->class_id;
        $usersIDs = User::whereIn('id' , Enroll::where('course_segment', $course_seg)->where('user_id','!=',Auth::user()->id)->pluck('user_id')->toArray())->pluck('id');
        LastAction::lastActionInCourse($courseID);

        $publish_date=$fileLesson->publish_date;
        if(carbon::parse($publish_date)->isPast())
            $publish_date=Carbon::now();

        User::notify([
                'id' => $file->id,
                'message' => $file->name.' file is updated',
                'from' => Auth::user()->id,
                'users' => isset($usersIDs) ? $usersIDs->toArray() : [null],
                'course_id' => $courseID,
                'class_id' => $class_id,
                'lesson_id' => $request->updated_lesson_id,
                'type' => 'file',
                'link' => $file->url,
                'publish_date' => carbon::parse($publish_date),
        ]);
        $tempReturn = Lesson::find($request->updated_lesson_id)->module('UploadFiles', 'file')->get();
        return HelperController::api_response_format(200, $tempReturn, __('messages.file.update'));
    }

    /**
     * Delete Specifc File
     * @param Request $request
     * Following sending in the request
     * @param fileID ID of the file that wanted to update
     * @return Response as success Message
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'fileID' => 'required|integer|exists:file_lessons,file_id',
            'lesson_id' => 'required|exists:file_lessons,lesson_id'
        ]);

        $file = FileLesson::where('file_id', $request->fileID)->where('lesson_id', $request->lesson_id)->first();
        $lesson = Lesson::find($request->lesson_id);
        $courseID = CourseSegment::where('id', $lesson->course_segment_id)->pluck('course_id')->first();
        LastAction::lastActionInCourse($courseID);
        $file->delete();
        File::whereId($request->fileID)->delete();
        $tempReturn = Lesson::find($request->lesson_id)->module('UploadFiles', 'file')->get();
        return HelperController::api_response_format(200, $tempReturn, $message = __('messages.file.delete'));
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
        $request->validate([
            'fileID' => 'required|integer|exists:files,id',
            'lesson_id' => 'required|integer|exists:file_lessons,lesson_id',
        ]);
        $fileLesson = FileLesson::where('file_id', $request->fileID)->where('lesson_id', '=', $request->lesson_id)->first();
        if (!isset($fileLesson)) {
            return HelperController::api_response_format(400, null, __('messages.error.data_invalid'));
        }
        $lesson = Lesson::find($request->lesson_id);
        $courseID = CourseSegment::where('id', $lesson->course_segment_id)->pluck('course_id')->first();
        LastAction::lastActionInCourse($courseID);
        $fileLesson->visible = ($fileLesson->visible == 1) ? 0 : 1;
        $fileLesson->save();
        $tempReturn = Lesson::find($request->lesson_id)->module('UploadFiles', 'file')->get();
        return HelperController::api_response_format(200, $tempReturn, __('messages.success.toggle'));
    }

    public function sortLessonFile(Request $request)
    {
        $request->validate([
            'file_lesson_id' => 'required|integer|exists:file_lessons,id',
            'index' => 'required|integer'
        ]);
        $fileLesson = FileLesson::find($request->file_lesson_id);
        $maxIndex = $fileLesson->max('index');
        $minIndex = $fileLesson->min('index');

        if (!($request->index <= $maxIndex && $request->index >= $minIndex)) {
            return HelperController::api_response_format(400, null, __('messages.error.data_invalid'));
        }

        $currentIndex = $fileLesson->index;
        if ($currentIndex > $request->index) {
            $this->sortDown($fileLesson, $currentIndex, $request->index);
        } else {
            $this->sortUp($fileLesson, $currentIndex, $request->index);
        }
        $tempReturn = Lesson::find($fileLesson->lesson_id)->module('UploadFiles', 'file')->get();
        return HelperController::api_response_format(200, $tempReturn, ' Successfully');
    }

    public function sortDown($fileLesson, $currentIndex, $newIndex)
    {

        $lesson_id = $fileLesson->lesson_id;

        $fileLessons = FileLesson::where('lesson_id', $lesson_id)->get();

        foreach ($fileLessons as $singleFileLesson) {
            if ($singleFileLesson->index < $newIndex || $singleFileLesson->index > $currentIndex) {
                continue;
            } elseif ($singleFileLesson->index  !=  $currentIndex) {
                $singleFileLesson->update([
                    'index' => $singleFileLesson->index + 1
                ]);
            } else {
                $singleFileLesson->update([
                    'index' => $newIndex
                ]);
            }
        }
        return $fileLessons;
    }

    public function sortUp($fileLesson, $currentIndex, $newIndex)
    {

        $lesson_id = $fileLesson->lesson_id;

        $fileLessons = FileLesson::where('lesson_id', $lesson_id)->get();

        foreach ($fileLessons as $singleFileLesson) {
            if ($singleFileLesson->index > $newIndex || $singleFileLesson->index < $currentIndex) {
                continue;
            } elseif ($singleFileLesson->index  !=  $currentIndex) {
                $singleFileLesson->update([
                    'index' => $singleFileLesson->index - 1
                ]);
            } else {
                $singleFileLesson->update([
                    'index' => $newIndex
                ]);
            }
        }
        return $fileLessons;
    }
    public function GetFileByID(Request $request)
    {

        $rules = [
            'id' => 'required|integer|exists:files,id',
        ];
        $customMessages = [
            'exists' => __('messages.error.item_deleted')
        ];
    
        $this->validate($request, $rules, $customMessages);
        $File = file::with('FileLesson')->find($request->id);
        if( $request->user()->can('site/course/student') && $File->FileLesson->visible==0)
             return HelperController::api_response_format(301,null, __('messages.file.file_hidden'));

        return HelperController::api_response_format(200, $File);
    }
    public function AssignFileToLesson(Request $request)
    {
        try {
            $request->validate([
                'file_id' => 'required|exists:files,id',
                'lesson_id' => 'required|exists:lessons,id',
                'publish_date' => 'required|date'
            ]);
            $file_lessons = FileLesson::create([
                'lesson_id' => $request->lesson_id, 'file_id' => $request->file_id, 'publish_date' => $request->publish_date
            ]);
            return HelperController::api_response_format(200, $file_lessons, __('messages.file.add'));
        } catch (Exception $ex) {
            return HelperController::api_response_format(400, null, __('messages.error.try_again'));
        }
    }
    public function AssignFileMediaPAgeLesson(Request $request)


    {
        $materials['page']=pageLesson::whereNotIn('page_id',Material::where('type','page')->pluck('item_id'))->get();
        $materials['files']= fileLesson::whereNotIn('file_id',Material::where('type','file')->pluck('item_id'))->get();
        $materials['media']= mediaLesson::whereNotIn('media_id',Material::where('type','media')->pluck('item_id'))->get();

        $Allmaterials=[];
        foreach($materials['page'] as $page){
            $material = collect([]);
            $material['item_id'] = $page->page_id;
            $material['name'] =page::find($page->page_id)->title;
            $material['course_id'] =Lesson::find($page->lesson_id)->courseSegment->course_id;
            $material['lesson_id']=$page->lesson_id;
            $material['publish_date']= $page->publish_date;
            $material['type']='page';
            $material['visible'] = $page->visible;
            $material['link'] = null;
            $material['mime_type']= null;
            $Allmaterials[] =  $material;
        }
        foreach($materials['files'] as $file){
            $fileObj=file::find($file->file_id);
            $material = collect([]);
            $material['item_id'] = $file->file_id;
            $material['name'] =$fileObj->name;
            $material['course_id'] =Lesson::find($file->lesson_id)->courseSegment->course_id;
            $material['lesson_id']=$file->lesson_id;
            $material['publish_date']= $file->publish_date;
            $material['type']='file';
            $material['visible'] = $file->visible;
            $material['link'] = $fileObj->url;
            $material['mime_type']= $fileObj->type;
            $Allmaterials[] =  $material;
        }
        foreach($materials['media'] as $media){
            $mediaObj=media::find($media->media_id); 
            $material = collect([]);
            $material['item_id'] = $media->media_id;
            $material['name'] =$mediaObj->name;
            $material['course_id'] =Lesson::find($media->lesson_id)->courseSegment->course_id;
            $material['lesson_id']=$media->lesson_id;
            $material['publish_date']= $media->publish_date;
            $material['type']='media';
            $material['visible'] = $media->visible;
            $material['link'] = $mediaObj->link;
            $material['mime_type']= ($mediaObj->show&&$mediaObj->type==null )?'media link':$mediaObj->type;
            $Allmaterials[] =  $material;
        
        }
        $Allmaterials = collect($Allmaterials)->sortBy('publish_date')->values();
        Material::insert($Allmaterials->toArray());
        if(count($Allmaterials->toArray())==0)
            return response()->json(['message' => 'all materials is assigned before ', 'body' => $Allmaterials], 200);

        return response()->json(['message' => 'all materials is assigned', 'body' => $Allmaterials], 200);


    }

}
