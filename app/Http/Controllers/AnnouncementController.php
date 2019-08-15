<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\File;
use App\Notifications\Announcment;
use Illuminate\Http\Request;
use App\Announcement;
use App\CourseSegment;
use Carbon\Carbon;
use App\Enroll;
use App\ClassLevel;
use App\SegmentClass;
use App\YearLevel;
use App\AcademicYearType;
use App\User;
use App\Course;
use App\Classes;
use App\Level;
use App\Segment;
use App\AcademicType;
use App\AcademicYear;
use App\attachment;
use Auth;

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

            attachment::upload_attachment($request->attached_file,'Announcement');
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
            $segmeny_class_id=array();
            foreach($class_level_id as $cl)
            {
                $segmeny_class_id[]=SegmentClass::GetClasseLevel($cl);
            }

            $course_segment_id=array();
            foreach($segmeny_class_id as $sc)
            {
                $course_segment_id[]=CourseSegment::GetCourseSegmentId($sc);
            }

            $users=collect([]);
            foreach($course_segment_id as $cs)
            {
                foreach($cs as $c)
                {
                    $users->push(Enroll::Get_User_ID($c));
                }
            }

            $uniq=collect([]);
            foreach($users as $u)
            {
                foreach($u as $un)
                {
                    $uniq->push($un);
                }
            }
            $usersid=array();
            $usersid=$uniq->unique();

            $noti=array();
            foreach($usersid as $id)
            {
                $noti[]=User::find($id);
            }
            Notification::send($noti, new Announcment($request));

        }
        else if ($request->assign == 'course')
        {
            $request->validate([
                'course_id'=>'required|exists:courses,id',
            ]);

            $course_segment=CourseSegment::getidfromcourse($request->course_id);
            $users=collect([]);
            foreach($course_segment as $cs)
            {
                $users->push(Enroll::Get_User_ID($cs));
            }
            $uniq=collect([]);
            foreach($users as $u)
            {
                foreach($u as $un)
                {
                    $uniq->push($un);
                }
            }
            $usersid=array();
            $usersid=$uniq->unique();

            $noti=array();
            foreach($usersid as $id)
            {
                $noti[]=User::find($id);
            }
            Notification::send($noti, new Announcment($request));

        }
        else if ($request->assign == 'level')
        {
            $request->validate([
                'level_id'=>'required|exists:levels,id',
            ]);

            $Year_level_id=YearLevel::GetYearLevelId($request->level_id);

            $class_level_id=array();
            foreach($Year_level_id as $yl)
            {
                $class_level_id[]=ClassLevel::GetClassLevelid($Year_level_id);
            }

            $segmeny_class_id=array();
            foreach($class_level_id as $cl)
            {
                foreach($cl as $c)
                {
                    $segmeny_class_id[]=SegmentClass::GetClasseLevel($c);
                }
            }

            $course_segment_id=array();
            foreach($segmeny_class_id as $sc)
            {
                $course_segment_id[]=CourseSegment::GetCourseSegmentId($sc);
            }

            $users=collect([]);
            foreach($course_segment_id as $cs)
            {
                foreach($cs as $c)
                {
                    $users->push(Enroll::Get_User_ID($c));
                }
            }

            $uniq=collect([]);
            foreach($users as $u)
            {
                foreach($u as $un)
                {
                    $uniq->push($un);
                }
            }
            $usersid=array();
            $usersid=$uniq->unique();

            $noti=array();
            foreach($usersid as $id)
            {
                $noti[]=User::find($id);
            }
            Notification::send($noti, new Announcment($request));
        }
        else if ($request->assign == 'year')
        {
            $request->validate([
                'year_id'=>'required|exists:academic_years,id',
            ]);

            $academic_year_type_id=AcademicYearType::get_yaer_type_by_year($request->year_id);

            $Year_level_id=array();
            foreach($academic_year_type_id as $ay)
            {
                $Year_level_id[]=YearLevel::get_year_level_id($ay);
            }

            $class_level_id=array();
            foreach($Year_level_id as $yl)
            {
                foreach($yl as $y)
                {
                    $class_level_id[]=ClassLevel::GetClassLevelid($y);
                }
            }

            $segmeny_class_id=array();
            foreach($class_level_id as $cl)
            {
                foreach($cl as $c)
                {
                    $segmeny_class_id[]=SegmentClass::GetClasseLevel($c);
                }
            }

            $course_segment_id=array();
            foreach($segmeny_class_id as $sc)
            {
                foreach($sc as $s)
                {
                    $course_segment_id[]=CourseSegment::GetCourseSegmentId($s);
                }
            }

            $users=collect([]);
            foreach($course_segment_id as $cs)
            {
                foreach($cs as $c)
                {
                    $users->push(Enroll::Get_User_ID($c));
                }
            }

            $uniq=collect([]);
            foreach($users as $u)
            {
                foreach($u as $un)
                {
                    $uniq->push($un);
                }
            }
            $usersid=array();
            $usersid=$uniq->unique();

            $noti=array();
            foreach($usersid as $id)
            {
                $noti[]=User::find($id);
            }
            Notification::send($noti, new Announcment($request));
        }
        else if ($request->assign == 'type')
        {
            $request->validate([
                'type_id'=>'required|exists:academic_types,id',
            ]);

            $academic_year_type_id=AcademicYearType::get_yaer_type_by_type($request->type_id);

            $Year_level_id=array();
            foreach($academic_year_type_id as $ay)
            {
                $Year_level_id[]=YearLevel::get_year_level_id($ay);
            }

            $class_level_id=array();
            foreach($Year_level_id as $yl)
            {
                foreach($yl as $y)
                {
                    $class_level_id[]=ClassLevel::GetClassLevelid($y);
                }
            }

            $segmeny_class_id=array();
            foreach($class_level_id as $cl)
            {
                foreach($cl as $c)
                {
                    $segmeny_class_id[]=SegmentClass::GetClasseLevel($c);
                }
            }

            $course_segment_id=array();
            foreach($segmeny_class_id as $sc)
            {
                foreach($sc as $s)
                {
                    $course_segment_id[]=CourseSegment::GetCourseSegmentId($s);
                }
            }

            $users=collect([]);
            foreach($course_segment_id as $cs)
            {
                foreach($cs as $c)
                {
                    $users->push(Enroll::Get_User_ID($c));
                }
            }

            $uniq=collect([]);
            foreach($users as $u)
            {
                foreach($u as $un)
                {
                    $uniq->push($un);
                }
            }
            $usersid=array();
            $usersid=$uniq->unique();

            $noti=array();
            foreach($usersid as $id)
            {
                $noti[]=User::find($id);
            }
            Notification::send($noti, new Announcment($request));

        }
        else if($request->assign == 'segment')
        {
            $request->validate([
                'segment_id'=>'required|exists:segments,id',
            ]);

            $segmentclass=SegmentClass::find($request->segment_id);
            $users=array();
            foreach($segmentclass->courseSegment as $cs)
            {
                foreach($cs->enroll as $enroll)
                {
                    $users[]=$enroll->user_id;
                }
            }
            $user=array_unique($users);
            $noti=array();
            foreach($user as $id)
            {
                $noti[]=User::find($id);
            }
            Notification::send($noti, new Announcment($request));
        }
        else
        {
            return ('Operation Fails!');
        }

        //Creating announcement in DB
        $ann= Announcement::create([
           'title' => $request->title,
           'description' =>$request->description,
           'attached_file' => $fileName,
           'start_date' => $request->start_date,
           'due_date' => $request->due_date,
           'assign'=>$request->assign,
           'class_id' => $request->class_id,
           'course_id' => $request->course_id,
           'level_id' => $request->level_id,
           'year_id' => $request->year_id,
           'type_id' => $request->type_id,
           'segment_id' => $request->segment_id
        ]);

        return HelperController::api_response_format(201,$ann,'Announcement Sent Successfully');
     }

     public function delete_announcement(Request $request)
     {
        $request->validate([
            'id' => 'required|exists:announcements,id',
        ]);

        $announce = Announcement::find($request->id);
        $announce->delete();

        return HelperController::api_response_format(200, $announce,'Announcement Deleted Successfully');

     }

     public function new_user_announcements()
     {
        $user_id=Auth::user()->id;
        $user=User::find($user_id);
        $courses=array();
        $seg_class=array();
        foreach($user->enroll as $enroll)
        {
            $course_seg=CourseSegment::find($enroll->course_segment);
            $courses[]=$course_seg->courses[0]->id;
            foreach($course_seg->segmentClasses as $csc)
            {
                $seg_class[]=$csc->id;
            }
        }

        //get general Announcements
        $all_ann=array();
        $all_ann['General Announcements']=Announcement::where('assign','all')->get(['title','description','attached_file']);

        //get Class announcements
        $uniq_seg=array_unique($seg_class);
        $class_level_id=array();
        $year_level_id=array();

        foreach($uniq_seg as $seg)
        {
            $segmentclass=SegmentClass::find($seg);
            $segmentname=Segment::find($segmentclass->segment_id);
            $all_ann[$segmentname->name]=Announcement::where('segment_id',$segmentclass->segment_id)->get(['title','description','attached_file']);

            foreach($segmentclass->classLevel as $scl)
            {
                $classname=Classes::find($scl->class_id);
                $all_ann[$classname->name]=Announcement::where('class_id',$scl->class_id)->get(['title','description','attached_file']);
                $class_level_id[]=$scl->id;
            }
        }

        //get level Announcements
        foreach($class_level_id as $cd)
        {
            $class_level=ClassLevel::find($cd);
            foreach($class_level->yearLevels as $yl)
            {
                $levelename=Level::find($yl->level_id);
                $all_ann[$levelename->name]=Announcement::where('level_id',$yl->level_id)->get(['title','description','attached_file']);
                $year_level_id[]=$yl->id;
            }
        }

        //get year/type announcements
        foreach($year_level_id as $yd)
        {
            $Year_level=YearLevel::find($yd);
            foreach($Year_level->yearType as $ayt)
            {
                $typename=AcademicType::find($ayt->academic_type_id);
                $yearname=AcademicYear::find($ayt->academic_year_id);
                $all_ann[$yearname->name]=Announcement::where('year_id',$ayt->academic_year_id)->get(['title','description','attached_file']);
                $all_ann[$typename->name]=Announcement::where('type_id',$ayt->academic_type_id)->get(['title','description','attached_file']);
            }
        }

        //get courses Announcements
        foreach($courses as $cou)
        {
            $coursename=Course::find($cou);
            $all_ann[$coursename->name]=Announcement::where('course_id',$cou)->get(['title','description','attached_file']);
        }

        return HelperController::api_response_format(201,$all_ann);
     }

}
