<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\File;
use App\Notifications\Announcment;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Announcement;
use Carbon\Carbon;
use App\Classes;
use App\Course;
use App\Level;
use App\User;
use App\CourseSegment;
use App\Enroll;
use App\ClassLevel;
use App\SegmentClass;
use App\YearLevel;


class AnnouncementController extends Controller
{
    /*

    */
    public function announcement(Request $request)
    {

        //Validtaion
        $request->validate([
            'title'=>'required',
            'description' => 'required',
            'attached_file' => 'nullable|file|mimes:pdf,docx,doc,xls,xlsx,ppt,pptx,zip,rar,txt',
            'start_date'=>'before:due_date|after:'.Carbon::now(),
            'due_date'=>'after:'.Carbon::now(),
            'assign'=> 'required'
        ]);

        //Files uploading
        if (Input::hasFile('attached_file'))
        {

            $destinationPath = public_path();
            $name = Input::file('attached_file')->getClientOriginalName();
            $extension = Input::file('attached_file')->getClientOriginalExtension();
            $fileName = $name.'.'.uniqid($request->id).'.'.$extension;

            //store the file in the $destinationPath
            $file = Input::file('attached_file')->move($destinationPath, $fileName);
        }
        else
        {
            $fileName=null;
        }

        //Assign Conditions
        if($request->assign == 'all')
        {
            $toUser = User::get();
            Notification::send($toUser, new Announcment($request));
        }
        else if ($request->assign == 'class')
        {
            $request->validate([
                'class_id'=>'required|exists:classes,id',
            ]);

            $class_level_id=ClassLevel::GetClassLevel($request->class_id);
            $segmeny_class_id=SegmentClass::GetClasseLevel($class_level_id);
            $course_segment_id=CourseSegment::GetCourseSegmentId($segmeny_class_id);
            $users=Enroll::Get_User_ID($course_segment_id);
            $noti=User::find($users);
            Notification::send($noti, new Announcment($request));

        }
        else if ($request->assign == 'course')
        {
            $request->validate([
                'course_id'=>'required|exists:courses,id',
            ]);

            $course_segment=CourseSegment::getidfromcourse($request->course_id);
            $users=Enroll::Get_User_ID($course_segment);
            $noti=User::find($users);
            Notification::send($noti, new Announcment($request));

        }
        else if ($request->assign == 'level')
        {
            $request->validate([
                'level_id'=>'required|exists:levels,id',
            ]);

            $Year_level_id=YearLevel::GetYearLevelId($request->level_id);
            $class_level_id=ClassLevel::GetClassLevelid($Year_level_id);
            $segmeny_class_id=SegmentClass::GetClasseLevel($class_level_id);
            $course_segment_id=CourseSegment::GetCourseSegmentId($segmeny_class_id);
            $users=Enroll::Get_User_ID($course_segment_id);
            $noti=User::find($users);
            Notification::send($noti, new Announcment($request));

        }
        else
        {
            return ('Operation Fails!');
        }

        //Creating announcement in DB
        Announcement::create([
           'title' => $request->title,
           'description' =>$request->description,
           'attached_file' => $fileName,
           'start_date' => $request->start_date,
           'due_date' => $request->due_date,
           'assign'=>$request->assign,
           'class_id' => $request->class_id,
           'course_id' => $request->course_id,
           'level_id' => $request->level_id
        ]);

        return HelperController::api_response_format(201,'Announcement Sent Successfully');
     }

     public function delete_announcement(Request $request)
     {
        $request->validate([
            'id' => 'required|exists:announcements,id',
        ]);

        $announce = Announcement::find($request->id);
        $announce->delete();

        return HelperController::api_response_format(201, 'Announcement Deleted Successfully');

     }

}



