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
use stdClass;

class AnnouncementController extends Controller
{
    /*

    */
    public function announcement(Request $request)
    {

        //Validtaion
        $request->validate([
            'title' => 'required',
            'description' => 'required',
            'attached_file' => 'nullable|file|mimes:pdf,docx,doc,xls,xlsx,ppt,pptx,zip,rar,txt',
            'start_date' => 'required|before:due_date',
            'due_date' => 'required|after:' . Carbon::now(),
            'publish_date' => 'nullable|after:' . Carbon::now(),
            'assign' => 'required'
        ]);

        if (isset($request->publish_date)) {
            $publishdate = $request->publish_date;
        } else {
            $publishdate = Carbon::now();
        }

        if($request->start_date < Carbon::now())
        {
            $start_date = Carbon::now();
        }else {
            $start_date = $request->start_date;
        }

        $users = array();
        //Files uploading
        if (isset($request->attached_file)) {
            $fileName = attachment::upload_attachment($request->attached_file, 'Announcement');
            $file_id = $fileName->id;
        } else {
            $file_id = null;
        }

        //Assign Conditions
        if ($request->assign == 'all') {
            $toUser = User::get();
        } else if ($request->assign == 'course') {
            $request->validate([
                'course_id' => 'required|exists:courses,id',
            ]);

            $course = Course::find($request->course_id);
            $course_seg = $course->courseSegments;
            foreach ($course_seg as $cs) {
                foreach ($cs->enroll as $enroll) {
                    $users[] = $enroll->user_id;
                }
            }
        } else if ($request->assign == 'class') {
            $request->validate([
                'class_id' => 'required|exists:classes,id',
            ]);

            $class = Classes::find($request->class_id);
            $seg_class = $class->classlevel->segmentClass;
            $course_seg_id = array();
            foreach ($seg_class as $sg) {
                $course_seg_id[] = $sg->courseSegment;
            }

            foreach ($course_seg_id as $cour) {
                foreach ($cour as $c) {
                    foreach ($c->enroll as $enroll) {
                        $users[] = $enroll->user_id;
                    }
                }
            }
        } else if ($request->assign == 'level') {
            $request->validate([
                'level_id' => 'required|exists:levels,id',
            ]);

            $level = Level::find($request->level_id);
            $class_level_id = $level->yearlevel->classLevels;

            foreach ($class_level_id as $cl) {
                foreach ($cl->segmentClass as $sg) {
                    foreach ($sg->courseSegment as $cs) {
                        foreach ($cs->enroll as $enroll) {
                            $users[] = $enroll->user_id;
                        }
                    }
                }
            }
        } else if ($request->assign == 'year') {
            $request->validate([
                'year_id' => 'required|exists:academic_years,id',
            ]);

            $year = AcademicYear::find($request->year_id);
            $year_level = $year->Acyeartype->yearLevel;

            foreach ($year_level as $yea) {
                foreach ($yea->classLevels as $cl) {
                    foreach ($cl->segmentClass as $sc) {
                        foreach ($sc->courseSegment as $c) {
                            foreach ($c->enroll as $enroll) {
                                $users[] = $enroll->user_id;
                            }
                        }
                    }
                }
            }
        } else if ($request->assign == 'type') {
            $request->validate([
                'type_id' => 'required|exists:academic_types,id',
            ]);

            $type = AcademicType::find($request->type_id);
            $year_level = $type->Actypeyear->yearLevel;

            foreach ($year_level as $yea) {
                foreach ($yea->classLevels as $cl) {
                    foreach ($cl->segmentClass as $sc) {
                        foreach ($sc->courseSegment as $c) {
                            foreach ($c->enroll as $enroll) {
                                $users[] = $enroll->user_id;
                            }
                        }
                    }
                }
            }
        } else if ($request->assign == 'segment') {
            $request->validate([
                'segment_id' => 'required|exists:segments,id',
            ]);

            $segmentclass = SegmentClass::find($request->segment_id);
            foreach ($segmentclass->courseSegment as $cs) {
                foreach ($cs->enroll as $enroll) {
                    $users[] = $enroll->user_id;
                }
            }
        } else {
            return HelperController::api_response_format(400, null, 'Something went wrong please check your data');
        }


        //Creating announcement in DB
        $ann = Announcement::create([
            'title' => $request->title,
            'description' => $request->description,
            'attached_file' => $file_id,
            'assign' => $request->assign,
            'class_id' => $request->class_id,
            'course_id' => $request->course_id,
            'level_id' => $request->level_id,
            'year_id' => $request->year_id,
            'type_id' => $request->type_id,
            'segment_id' => $request->segment_id,
            'publish_date' => $publishdate,
        ]);

        if ($request->filled('start_date')) {
            $ann->start_date = $start_date;
            $ann->save();
        }

        if ($request->filled('due_date')) {
            $ann->due_date = $request->due_date;
            $ann->save();
        }

        $requ = ([
            'id' => "$ann->id",
            'type' => 'announcement',
            'publish_date'=>$publishdate
        ]);

        //sending announcements
        if ($request->assign == 'all') {
            foreach ($toUser as $use) {
                $requ['users'][] = $use->id;
            }
            $notificatin = User::notify($requ);
        } else {
            $user = array_unique($users);
            $requ['users'] = $user;
            $notificatin = User::notify($requ);
        }

       /* if ($notificatin == '1') {

            return HelperController::api_response_format(201, $ann, 'Announcement Sent Successfully');
        }
        return HelperController::api_response_format(201, $notificatin, 'Announcement Sent Successfully');*/

        $anounce = AnnouncementController::get_announcement();
        //return $anounce;
        $anouncenew = AnnouncementController::new_user_announcements();
        return HelperController::api_response_format(201, ['notify' => $anounce, 'assoicate' => $anouncenew],'Announcement Sent Successfully');
    }

    public function update_announce(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:announcements,id',
        ]);

        //get the announcement to be deleted
        $announce = Announcement::whereId($request->id)->first();
        $data = ([
            'id' => $request->id,
            'type' => 'announcement',
        ]);
        $encode = json_encode($data);
        $deleted = DB::table('notifications')->where('data', $encode)->get();
        $users = array();
        foreach ($deleted as $de) {
            $users[] = $de->notifiable_id;

            DB::table('notifications')
                ->where('id', $de->id)
                ->update(['read_at' => null]);
        }
        //Validtaionof updated data
        $request->validate([
            'title' => 'required',
            'description' => 'required',
            'attached_file' => 'nullable|file|mimes:pdf,docx,doc,xls,xlsx,ppt,pptx,zip,rar,txt',
            'start_date' => 'before:due_date',
            'due_date' => 'after:' . Carbon::now(),
            'publish_date' => 'nullable|after:' . Carbon::now(),
        ]);

        if (isset($request->publish_date)) {
            $publishdate = $request->publish_date;
        } else {
            $publishdate = Carbon::now();
        }

        if($request->start_date < Carbon::now())
        {
            $start_date = Carbon::now();
        }else {
            $start_date = $request->start_date;
        }

        if(isset($request->due_date))
        {
            $due_date = $request->due_date;
        } else {
            $due_date = $announce->due_date;
        }

        //Files uploading
        if (Input::hasFile('attached_file')) {
            $fileName = attachment::upload_attachment($request->attached_file, 'Announcement');
            $file_id = $fileName->id;
        } else {
            $file_id = null;
        }

        $announce->update([
            'title' => $request->title,
            'description' => $request->description,
            'attached_file' => $file_id,
            'start_date'=>$start_date,
            'due_date'=> $due_date,
            'publish_date' => $publishdate
        ]);

        // return HelperController::api_response_format(201, $announce, 'Announcement Updated Successfully');
        $anounce = AnnouncementController::get_announcement();
        $anouncenew = AnnouncementController::new_user_announcements();
        return HelperController::api_response_format(201, ['notify' => $anounce, 'assoicate' => $anouncenew],'Announcement Sent Successfully');

    }
    public function delete_announcement(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:announcements,id',
        ]);

        //get the announcement to be deleted
        $announce = Announcement::find($request->id);
        //get data that sent to users
        $announcefinal['id'] = $request->id;
        $announcefinal['type'] = 'announcement';

        //encode that data to compare it with notifications data
        $dataencode = json_encode($announcefinal);
        //get data from notifications
        $deleted = DB::table('notifications')->where('data', $dataencode)->get();
        foreach ($deleted as $de) {
            DB::table('notifications')
                ->where('id', $de->id)
                ->delete();
        }
        $announce->delete();
        $anounce = AnnouncementController::get_announcement();
        $anouncenew = AnnouncementController::new_user_announcements();
        return HelperController::api_response_format(201, ['notify' => $anounce, 'assoicate' => $anouncenew],'Announcement Deleted Successfully');
//        return HelperController::api_response_format(200, $announce, 'Announcement Deleted Successfully');
    }

    public function new_user_announcements()
    {
        $user_id = Auth::user()->id;
        $user = User::find($user_id);
        $courses = array();
        $seg_class = array();
        foreach ($user->enroll as $enroll) {
            $course_seg = CourseSegment::find($enroll->course_segment);
            $courses[] = $course_seg->courses[0]->id;
            foreach ($course_seg->segmentClasses as $csc) {
                $seg_class[] = $csc->id;
            }
        }
        //get general Announcements
        $all_ann = array();
        $all_ann['General Announcements'] = Announcement::where('assign', 'all')->where('publish_date', '<=', Carbon::now())->get(['id', 'title', 'description', 'attached_file','start_date','due_date']);
        $g = 0;
        foreach ($all_ann['General Announcements'] as $all) {
            $all_ann['General Announcements'][$g]['attached_file'] = attachment::where('id', $all['attached_file'])->first();
            $g++;
        }

        //get Class announcements
        $uniq_seg = array_unique($seg_class);
        $class_level_id = array();
        $year_level_id = array();
        $s = 0;
        $cl = 0;
        foreach ($uniq_seg as $seg) {
            $segmentclass = SegmentClass::find($seg);
            $segmentname = Segment::find($segmentclass->segment_id);
            $all_ann['segment'][$s]['name'] = $segmentname->name;
            $all_ann['segment'][$s]['id'] = $segmentname->id;
            $all_ann['segment'][$s]['announcements'] = Announcement::where('segment_id', $segmentclass->segment_id)->where('publish_date', '<=', Carbon::now())->get(['id', 'title', 'description', 'attached_file','start_date','due_date']);
            foreach ($all_ann['segment'][$s]['announcements'] as $ann) {
                $ann->attached_file = attachment::where('id', $ann->attached_file)->first();
            }

            foreach ($segmentclass->classLevel as $scl) {
                $classname = Classes::find($scl->class_id);
                $all_ann['class'][$cl]['name'] = $classname->name;
                $all_ann['class'][$cl]['id'] = $classname->id;
                $all_ann['class'][$cl]['announcements'] = Announcement::where('class_id', $scl->class_id)->where('publish_date', '<=', Carbon::now())->get(['id', 'title', 'description', 'attached_file','start_date','due_date']);

                foreach ($all_ann['class'][$cl]['announcements'] as $ann) {
                    $ann->attached_file = attachment::where('id', $ann->attached_file)->first();
                }

                $class_level_id[] = $scl->id;
                $cl++;
            }
            $s++;
        }

        //get level Announcements
        $l = 0;
        foreach ($class_level_id as $cd) {
            $class_level = ClassLevel::find($cd);
            foreach ($class_level->yearLevels as $yl) {
                $levelename = Level::find($yl->level_id);
                $all_ann['level'][$l]['name'] = $levelename->name;
                $all_ann['level'][$l]['id'] = $levelename->id;
                $all_ann['level'][$l]['announcements'] = Announcement::where('level_id', $yl->level_id)->where('publish_date', '<=', Carbon::now())->get(['id', 'title', 'description', 'attached_file','start_date','due_date']);
                foreach ($all_ann['level'][$l]['announcements'] as $ann) {
                    $ann->attached_file = attachment::where('id', $ann->attached_file)->first();
                }

                $year_level_id[] = $yl->id;
                $l++;
            }
        }

        //get year/type announcements
        $y = 0;
        $t = 0;
        foreach ($year_level_id as $yd) {
            $Year_level = YearLevel::find($yd);
            foreach ($Year_level->yearType as $ayt) {
                $typename = AcademicType::find($ayt->academic_type_id);
                $all_ann['type'][$t]['name'] = $typename->name;
                $all_ann['type'][$t]['id'] = $typename->id;
                $all_ann['type'][$t]['announcements'] = Announcement::where('type_id', $ayt->academic_type_id)->where('publish_date', '<=', Carbon::now())->get(['id', 'title', 'description', 'attached_file','start_date','due_date']);
                foreach ($all_ann['type'][$t]['announcements'] as $ann) {
                    $ann->attached_file = attachment::where('id', $ann->attached_file)->first();
                }

                $yearname = AcademicYear::find($ayt->academic_year_id);
                $all_ann['year'][$y]['name'] = $yearname->name;
                $all_ann['year'][$y]['id'] = $yearname->id;
                $all_ann['year'][$y]['announcements'] = Announcement::where('year_id', $ayt->academic_year_id)->where('publish_date', '<=', Carbon::now())->get(['id', 'title', 'description', 'attached_file','start_date','due_date']);

                foreach ($all_ann['year'][$y]['announcements'] as $ann) {
                    $ann->attached_file = attachment::where('id', $ann->attached_file)->first();
                }

                $t++;
                $y++;
            }
        }

        //get courses Announcements
        $co = 0;
        foreach ($courses as $cou) {
            $coursename = Course::find($cou);
            $all_ann['courses'][$co]['name'] = $coursename->name;
            $all_ann['courses'][$co]['id'] = $coursename->id;
            $all_ann['courses'][$co]['announcements'] = Announcement::where('course_id', $cou)->where('publish_date', '<=', Carbon::now())->get(['id', 'title', 'description', 'attached_file','start_date','due_date']);

            foreach ($all_ann['courses'][$co]['announcements'] as $couann) {
                $couann->attached_file = attachment::where('id', $couann->attached_file)->first();
            }

            $co++;
        }

        return $all_ann;
    }

    public function get_announcement()
    {
        $user_id = Auth::user()->id;
        $noti = DB::table('notifications')->where('notifiable_id', $user_id)
            ->orderBy('created_at')
            ->get(['data' , 'read_at']);
      //  return $noti;
        $notif = collect([]);
        $count = 0;
        foreach ($noti as $not) {
            $not->data = json_decode($not->data, true);
            if ($not->data['type'] == 'announcement') {
                $announce_id = $not->data['id'];
                $annocument = announcement::find($announce_id);
                if($annocument!= null){
                    if ($annocument->publish_date <= Carbon::now()) {
                        $customize = announcement::whereId($announce_id)->first(['id', 'title', 'description', 'attached_file','start_date','due_date']);
                        $customize->seen = $not->read_at;
                        $notif->push($customize);
                    }
                }
            }
            $count++;
        }
        return $notif;
    }

    public function get()
    {
        $anounce = AnnouncementController::get_announcement();
        $anouncenew = AnnouncementController::new_user_announcements();
        return HelperController::api_response_format(201, ['notify' => $anounce, 'assoicate' => $anouncenew]);
    }
    public function getAnnounceByID(Request $request){
        $request->validate([
        'announce_id' => 'required|integer|exists:announcements,id',
        ]);

        $announce=Announcement::where ('id',$request->announce_id)->first(['id','title','description','start_date','due_date','assign',
            'class_id','year_id','level_id','course_id','type_id','segment_id']);
       // if(isset($announce->))

        switch ($announce->assign){
            case 'class':
                $class = Classes::where('id',$announce->class_id)->first(['name','id']);
                $class->type = 'class';
                $announce['type']=$class;
                break;
            case 'year':
                $year = AcademicYear::where('id',$announce->year_id)->first(['name','id']);
                $year->type = 'year';
                $announce['type']=$year;
                break;
            case 'level':
                $level= Level::where('id',$announce->level_id)->first(['name','id']);
                $level->type = 'level';
                $announce['type']=$level;
                break;
            case 'course':
                $course= Course::where('id',$announce->course_id)->first(['name','id']);
                $course->type = 'course';
                $announce['type']=$course;
                break;
            case 'type':
                $type= AcademicType::where('id',$announce->type_id)->first(['name','id']);
                $type->type = 'type';
                $announce['type']=$type;
                break;
            case 'segment':
                $segment= Segment::where('id',$announce->segment_id)->first(['name','id']);
                $segment->type = 'segment';
                $announce['type']=$segment;
                break;
            default:
                $type = new stdClass();
                $type->type = 'All';
                $type->name = "General Announcement";
                $announce['type']=$type;
        }
        return HelperController::api_response_format(200, $announce);


    }
}
