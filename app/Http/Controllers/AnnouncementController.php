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
use Illuminate\Support\Facades\DB;

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

        $users=array();
        //Files uploading
        if (Input::hasFile('attached_file'))
        {
            $fileName=attachment::upload_attachment($request->attached_file,'Announcement');
            $file_id=$fileName->id;
            $requ = new Request([
                'title' => $request->title,
                'description' =>$request->description,
                'attached_file' => $file_id,
            ]);
        }
        else
        {
            $file_id=null;
            $requ = new Request([
                'title' => $request->title,
                'description' =>$request->description,
            ]);
        }

        //Assign Conditions
        if($request->assign == 'all')
        {
            $toUser = User::get();
        }
        else if ($request->assign == 'course')
        {
            $request->validate([
                'course_id'=>'required|exists:courses,id',
            ]);

            $course=Course::find($request->course_id);
            $course_seg= $course->courseSegments;
            foreach($course_seg as $cs)
            {
                foreach($cs->enroll as $enroll)
                {
                    $users[]=$enroll->user_id;
                }
            }
        }
        else if ($request->assign == 'class')
        {
            $request->validate([
                'class_id'=>'required|exists:classes,id',
            ]);

            $class=Classes::find($request->class_id);
            $seg_class= $class->classlevel->segmentClass;
            $course_seg_id=array();
            foreach($seg_class as $sg)
            {
                $course_seg_id[]=$sg->courseSegment;
            }

            foreach($course_seg_id as $cour)
            {
                foreach($cour as $c)
                {
                    foreach ($c->enroll as $enroll)
                    {
                        $users[]=$enroll->user_id;
                    }
                }
            }
        }
        else if ($request->assign == 'level')
        {
            $request->validate([
                'level_id'=>'required|exists:levels,id',
            ]);

            $level=Level::find($request->level_id);
            $class_level_id= $level->yearlevel->classLevels;

            foreach($class_level_id as $cl)
            {
                foreach($cl->segmentClass as $sg)
                {
                    foreach($sg->courseSegment as $cs)
                    {
                        foreach($cs->enroll as $enroll)
                        {
                            $users[]=$enroll->user_id;
                        }
                    }
                }
            }
        }
        else if ($request->assign == 'year')
        {
            $request->validate([
                'year_id'=>'required|exists:academic_years,id',
            ]);

            $year=AcademicYear::find($request->year_id);
            $year_level= $year->Acyeartype->yearLevel;

            foreach($year_level as $yea)
            {
                foreach($yea->classLevels as $cl)
                {
                    foreach($cl->segmentClass as $sc)
                    {
                        foreach($sc->courseSegment as $c)
                        {
                            foreach($c->enroll as $enroll)
                            {
                                $users[]=$enroll->user_id;
                            }
                        }
                    }
                }
            }
        }
        else if ($request->assign == 'type')
        {
            $request->validate([
                'type_id'=>'required|exists:academic_types,id',
            ]);

            $type=AcademicType::find($request->type_id);
            $year_level= $type->Actypeyear->yearLevel;

            foreach($year_level as $yea)
            {
                foreach($yea->classLevels as $cl)
                {
                    foreach($cl->segmentClass as $sc)
                    {
                        foreach($sc->courseSegment as $c)
                        {
                            foreach($c->enroll as $enroll)
                            {
                                $users[]=$enroll->user_id;
                            }
                        }
                    }
                }
            }

        }
        else if($request->assign == 'segment')
        {
            $request->validate([
                'segment_id'=>'required|exists:segments,id',
            ]);

            $segmentclass=SegmentClass::find($request->segment_id);
            foreach($segmentclass->courseSegment as $cs)
            {
                foreach($cs->enroll as $enroll)
                {
                    $users[]=$enroll->user_id;
                }
            }
        }
        else
        {
            return ('Operation Fails! Please Choose Correct Filter.');
        }

        //sending announcements
        if($request->assign == 'all')
        {
            Notification::send($toUser, new Announcment($requ));
        }
        else
        {
            $user=array_unique($users);
            $noti=array();
                foreach($user as $id)
                {
                    $noti[]=User::find($id);
                }
                Notification::send($noti, new Announcment($requ));
        }

        //Creating announcement in DB
        $ann= Announcement::create([
           'title' => $request->title,
           'description' =>$request->description,
           'attached_file' => $file_id,
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

    public function update_announce(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:announcements,id',
        ]);

        //get the announcement to be deleted
        $announce = Announcement::whereId($request->id)->first();
        if($announce['attached_file'] != null)
        {
            $attch=attachment::find($announce['attached_file']);
            $data=([
                'title'=>$announce['title'],
                'description'=>$announce['description'],
                'attached_file'=>$announce['attached_file']
            ]);
        }
        else
        {
            $data=([
                'title'=>$announce['title'],
                'description'=>$announce['description'],
            ]);
        }

        $encode=json_encode($data);
        $deleted=DB::table('notifications')->where('data', $encode)->where('type','App\Notifications\Announcment')->get();
        $users=array();
        foreach($deleted as $de)
        {
            $users[]=$de->notifiable_id;
            foreach($de as $d)
            {
                DB::table('notifications')
                ->where('id', $d)
                ->delete();
            }
        }

        //Validtaionof updated data
         $request->validate([
            'title'=>'required',
            'description' => 'required',
            'attached_file' => 'nullable|file|mimes:pdf,docx,doc,xls,xlsx,ppt,pptx,zip,rar,txt',
        ]);

        //Files uploading
        if (Input::hasFile('attached_file'))
        {
            $fileName=attachment::upload_attachment($request->attached_file,'Announcement');
            $file_id=$fileName->id;
            $requ = new Request([
                'title' => $request->title,
                'description' =>$request->description,
                'attached_file' => $file_id,
            ]);
        }
        else
        {
            $file_id=null;
            $requ = new Request([
                'title' => $request->title,
                'description' =>$request->description,
            ]);
        }

        $noti=array();
        foreach($users as $id)
        {
            $noti[]=User::find($id);
        }
        Notification::send($noti, new Announcment($requ));

        $announce->update([
            'title' => $request->title,
            'description' =>$request->description,
            'attached_file' => $file_id,
        ]);
        $attch->delete();
        return HelperController::api_response_format(201, $announce,'Announcement Updated Successfully');

    }
    public function delete_announcement(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:announcements,id',
        ]);

        //get the announcement to be deleted
        $announce = Announcement::find($request->id);
        //get data that sent to users
        $announcefinal['title'] = $announce->title;
        $announcefinal['description'] = $announce->description;
        if($announce->attached_file != null)
        {
            $announcefinal['attached_file'] = $announce->attached_file;
            //delete attached file from attachement
            $attch=attachment::find($announce->attached_file);

        }
        //encode that data to compare it with notifications data
        $dataencode=json_encode($announcefinal);
        //get data from notifications
        $deleted=DB::table('notifications')->where('data', $dataencode)->where('type','App\Notifications\Announcment')->get();
        foreach($deleted as $de)
        {
            foreach($de as $d)
            {
                DB::table('notifications')
                ->where('id', $d)
                ->delete();
            }
        }
        $announce->delete();
        $attch->delete();
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
        $g=0;
        foreach($all_ann['General Announcements'] as $all)
        {
            $all_ann['General Announcements'][$g]['attached_file']=attachment::where('id',$all['attached_file'])->first();
            $g++;
        }

        //get Class announcements
        $uniq_seg=array_unique($seg_class);
        $class_level_id=array();
        $year_level_id=array();
        foreach($uniq_seg as $seg)
        {
            $segmentclass=SegmentClass::find($seg);
            $segmentname=Segment::find($segmentclass->segment_id);
            $all_ann[$segmentname->name]=Announcement::where('segment_id',$segmentclass->segment_id)->get(['title','description','attached_file']);
            $s=0;
            foreach($all_ann[$segmentname->name]as $all)
            {
                $all_ann[$segmentname->name][$s]['attached_file']=attachment::where('id',$all['attached_file'])->first();
                $s++;
            }

            foreach($segmentclass->classLevel as $scl)
            {
                $classname=Classes::find($scl->class_id);
                $all_ann[$classname->name]=Announcement::where('class_id',$scl->class_id)->get(['title','description','attached_file']);
                $cl=0;
                foreach($all_ann[$classname->name]as $all)
                {
                    $all_ann[$classname->name][$cl]['attached_file']=attachment::where('id',$all['attached_file'])->first();
                    $cl++;
                }
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
                $l=0;
                foreach($all_ann[$levelename->name]as $all)
                {
                    $all_ann[$levelename->name][$l]['attached_file']=attachment::where('id',$all['attached_file'])->first();
                    $l++;
                }
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
                $y=0;
                foreach($all_ann[$yearname->name]as $all)
                {
                    $all_ann[$yearname->name][$y]['attached_file']=attachment::where('id',$all['attached_file'])->first();
                    $y++;
                }
                $all_ann[$typename->name]=Announcement::where('type_id',$ayt->academic_type_id)->get(['title','description','attached_file']);
                $t=0;
                foreach($all_ann[$typename->name]as $all)
                {
                    $all_ann[$typename->name][$t]['attached_file']=attachment::where('id',$all['attached_file'])->first();
                    $t++;
                }
            }
        }

        //get courses Announcements
        foreach($courses as $cou)
        {
            $coursename=Course::find($cou);
            $all_ann[$coursename->name]=Announcement::where('course_id',$cou)->get(['title','description','attached_file']);
            $co=0;
            foreach($all_ann[$coursename->name]as $all)
            {
                $all_ann[$coursename->name][$co]['attached_file']=attachment::where('id',$all['attached_file'])->first();
                $co++;
            }
        }

         return $all_ann;
    }

    public function get_announcement()
    {
        $user_id=Auth::user()->id;
        $noti = DB::table('notifications')->where('notifiable_id', $user_id)
        ->where('type','App\Notifications\Announcment')
        ->orderBy('created_at')
        ->pluck('data');
        $data = array();
        $c=0;
        foreach ($noti as $not) {
         $data[]= json_decode($not, true);
         if(isset($data[$c]['attached_file']))
            $data[$c]['attached_file']=attachment::where('id',$data[$c]['attached_file'])->first();
         $c++;
        }
        return $data;
    }

    public function get()
    {
        $anounce=AnnouncementController::get_announcement();
        $anouncenew=AnnouncementController::new_user_announcements();
        return HelperController::api_response_format(201, ['notify' => $anounce, 'assoicate' => $anouncenew]);
    }

}
