<?php

namespace App\Http\Controllers;

use App\Http\Controllers\GradeCategoryController;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\File;
use App\Notifications\Announcment;
use Illuminate\Http\Request;
use App\Announcement;
use App\userAnnouncement;
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
    /**
     *
     * @Description : create an announcement.
     * @param : Title, description, start_date, due_date and assign are required parameters.
     *          assign has 4 cases : all => announcement for all users.
     *                               level => announcement for all users in a specific level.
     *                               class => announcement for all users in a specific class.
     *                               year => announcement for all users in a specific year.
     *          attached_file and publish_date as optional parameters.
     * @return : return all announcement of this user.
     */
    public function announcement(Request $request)
    {
        //Validtaion
        $request->validate([
            'title' => 'required',
            'description' => 'required',
            'attached_file' => 'nullable|file|mimes:pdf,docx,doc,xls,xlsx,ppt,pptx,zip,rar,txt,mp4,ogg,mpga,jpg,jpeg,png',
            'start_date' => 'before:due_date',
            'due_date' => 'after:' . Carbon::now(),
            'publish_date' => 'nullable|after:' . Carbon::now(),
            'role' => 'nullable',
            'year' => 'nullable|exists:academic_years,id',
            'type' => 'nullable|exists:academic_types,id',
            'level' => 'nullable|exists:levels,id',
            'class' => 'nullable|exists:classes,id',
            'segment' => 'nullable|exists:segments,id',
            'course' => 'nullable|exists:courses,id',
        ]);

        if (isset($request->publish_date)) {
            $publishdate = $request->publish_date;
        }else {
            $date=Carbon::now();
            $publishdate = Carbon::parse($date)->format('Y-m-d H:i:s');
        }
        $start_date = $request->start_date;
        if($request->start_date < Carbon::now())
        {
            $start_date = Carbon::now();
        }


        $users = array();
        //Files uploading
        if (isset($request->attached_file)) {
            $fileName = attachment::upload_attachment($request->attached_file, 'Announcement');
            $file_id = $fileName->id;
        } else {
            $file_id = null;
        }
        // return $courseSegments;
        $userr = Enroll::where('user_id','!=' ,Auth::id());
        if($request->filled('year'))
            $userr->where('year',$request->year);
        if($request->filled('type'))
            $userr->where('type',$request->type);
        if($request->filled('level'))
            $userr->where('level',$request->level);
        if($request->filled('segment'))
            $userr->where('segment',$request->segment);
        if($request->filled('course'))
            $userr->where('course',$request->course);
        if($request->filled('class'))
            $userr->where('class',$request->class);
            // $users->get();
        $users =  $userr->pluck('user_id')->unique('user_id');
        //Creating announcement in DB
        $ann = Announcement::create([
            'title' => $request->title,
            'description' => $request->description,
            'attached_file' => $file_id,
            'assign' => $request->assign,
            'class_id' => $request->class,
            'course_id' => $request->courses[0],
            'level_id' => $request->level,
            'year_id' => $request->year,
            'type_id' => $request->type,
            'segment_id' => $request->segment_id,
            'publish_date' => $publishdate,
            'created_by' => Auth::id(),
        ]);
        foreach ($users as $user){
            userAnnouncement::create([
                'announcement_id' => $ann->id,
                'user_id' => $user
            ]);
        }

        $ann->start_date = null ;
        if ($request->filled('start_date')) {
            $ann->start_date = $start_date;
            $ann->save();
        }

        if ($request->filled('due_date')) {
            $ann->due_date = $request->due_date;
            $ann->save();
        }

        if($file_id == null )
        {
            $requ = ([
                'id' => $ann->id,
                'type' => 'announcement',
                'publish_date' => $publishdate,
                'title' => $request->title,
                'description' => $request->description,
                'attached_file' => $file_id,
                'start_date' => $ann->start_date,
                'due_date' => $ann->due_date,
                'message' => $request->title.' announcement is added'
            ]);
        }else{
            $attached = attachment::where('id', $file_id)->first();
            $requ = ([
                'id' => $ann->id,
                'type' => 'announcement',
                'publish_date' => $publishdate,
                'title' => $request->title,
                'description' => $request->description,
                'attached_file' => $attached,
                'start_date' => $ann->start_date,
                'due_date' => $ann->due_date,
                'message' => $request->title.' announcement is added'
            ]);

        }
            $user = array_unique($users->toArray());
            if($request->filled('role'))
            {
                foreach($user as $use)
                {
                    if($use != Auth::id()){

                        $user_obj=User::where('id',$use)->get()->first();
                        $role_id=$user_obj->roles->pluck('id')->first();
                        if($role_id == $request->role)
                            $requ['users'][] = $use;
                        else
                            continue;
                    }
                }
                if(!isset($requ['users']))
                    return HelperController::api_response_format(201,'No User');
            }
            else{
                foreach($user as $use)
                {
                    if($use != Auth::id())
                    {
                         $requ['users'] = $user;
                    }
                }
            }
            $notificatin = User::notify($requ);
            // return $notificatin;

        $anounce = AnnouncementController::get_announcement($request);
        $myAnnouncements = Announcement::where('created_by',Auth::id())->get();
        foreach ($myAnnouncements as $ann) {
            $ann->attached_file = attachment::where('id', $ann->attached_file)->first();
        }
        return HelperController::api_response_format(201, ['notify' => $anounce , 'created'=>$myAnnouncements ],'Announcement Sent Successfully');
    }

    /**
     *
     * @Description : update an announcement.
     * @param : id, title and description required parameters.
     *          attached_file, publish_date, start_date and due_date as optional parameters.
     * @return : return all announcement of this user.
    */
    public function update_announce(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:announcements,id',
        ]);

        //get the announcement to be deleted
        $announce = Announcement::whereId($request->id)->first();
        $old_name = $announce->title;

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
            'publish_date' => 'nullable|after:' . Carbon::now()->addMinutes(1),
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

        $anounce = AnnouncementController::get_announcement($request);
        $anouncenew = AnnouncementController::new_user_announcements($request);
        $myAnnouncements = Announcement::where('created_by',Auth::id())->get();
        foreach ($myAnnouncements as $ann) {
            $ann->attached_file = attachment::where('id', $ann->attached_file)->first();
        }
        $usersIDs= userAnnouncement::where('announcement_id', $announce->id)->pluck('user_id')->unique('user_id');
        if($announce->attached_file == null){
            $requ = ([
                'id' => $announce->id,
                'type' => 'announcement',
                'publish_date' => $publishdate,
                'title' => $announce->title,
                'description' => $announce->description,
                'attached_file' => $announce->attached_file,
                'start_date' => $announce->start_date,
                'due_date' => $announce->due_date,
                'users' => isset($usersIDs) ? $usersIDs->toArray() : [null],
                'message' => $old_name.' announcement is updated'
            ]);

        }else{
            $attached = attachment::where('id', $announce->attached_file)->first();
            $requ = ([
                'id' => $announce->id,
                'type' => 'announcement',
                'publish_date' => $publishdate,
                'title' => $announce->title,
                'description' => $announce->description,
                'attached_file' => $attached,
                'start_date' => $announce->start_date,
                'due_date' => $announce->due_date,
                'users' => isset($usersIDs) ? $usersIDs->toArray() : [null],
                'message' => $announce->title.' announcement is updated'
            ]);
        }
        User::notify($requ);

        return HelperController::api_response_format(201,  ['notify' => $anounce , 'created'=>$myAnnouncements , 'assigned' => $anouncenew],'Announcement Updated Successfully');

    }

    /**
     *
     * @Description : delete_announcement deletes an announcement.
     * @param : id of the announcement as a required parameter.
     * @return : return all announcement of this user.
    */
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
        $anounce = AnnouncementController::get_announcement($request);
        $anouncenew = AnnouncementController::new_user_announcements($request);
        $myAnnouncements = Announcement::where('created_by',Auth::id())->get();
        foreach ($myAnnouncements as $ann) {
            $ann->attached_file = attachment::where('id', $ann->attached_file)->first();
        }
        return HelperController::api_response_format(201,  ['notify' => $anounce , 'created'=>$myAnnouncements , 'assigned' => $anouncenew ],'Announcement Deleted Successfully');
    }

    /**
     *
     * @Description :new_user_announcements gets all announcements for a new user.
     * @param : No parameters.
     * @return : return all announcement for this user.
     */
    public function new_user_announcements(Request $request)
    {
        $request->validate([
            'search' => 'nullable',
        ]);
       $announcements_ids =  userAnnouncement::where('user_id', Auth::id())->pluck('announcement_id');
       $announcements = Announcement::whereIn('id',$announcements_ids)->where('title', 'LIKE' , "%$request->search%")
                   ->where('publish_date', '<=', Carbon::now());

                   if($request->filled('search')){
                    $announcements->where(function ($query) use ($request) {
                        $query->where('title', 'LIKE' , "%$request->search%");
                    });
                }
        return $announcements->get();
    }
    
    /**
     *
     * @Description :get_announcement gets all announcements for logged in user.
     * @param : No parameters.
     * @return : return all currently published announcement for this user .
     */
    public function get_announcement(Request $request)
    {
        $request->validate([
            'search' => 'nullable',
        ]);
        $user_id = Auth::user()->id;
        $noti = DB::table('notifications')->where('notifiable_id', $user_id)
            ->orderBy('created_at')
            ->get(['data' , 'read_at','id'])->unique('id');
        $notif = collect([]);
        $count = 0;
        foreach ($noti as $not) {
            $not->data = json_decode($not->data, true);
            if ($not->data['type'] == 'announcement') {
                $announce_id = $not->data['id'];
                $annocument = announcement::find($announce_id);
                if($annocument!= null){
                    if ($annocument->publish_date <= Carbon::now()) {
                        $customize = announcement::where('title', 'LIKE' , "%$request->search%")->whereId($announce_id)
                                    ->first(['id', 'title', 'description', 'attached_file','start_date','due_date','publish_date']);
                        if(!$customize)
                            continue;
                        $customize->read_at = $not->read_at;
                        $customize->notification_id = $not->id;
                        $customize->message = $not->data['message'];
                        $customize->type = $not->data['type'];
                        $customize->attached_file = attachment::where('id', $customize->attached_file)->first();
                        $customize->year_id = $annocument->year_id;
                        $customize->type_id = $annocument->type_id;
                        $customize->segment_id = $annocument->segment_id;
                        $customize->level_id = $annocument->level_id;
                        $customize->class_id = $annocument->class_id;
                        $customize->course_id = $annocument->course_id;

                        $notif->push($customize);
                    }
                }
            }
            $count++;
        }
        return $notif;
    }

    /**
     *
     * @Description :get all announcements for logged in user.
     * @param : No parameters.
     * @return : return all currently published announcement for this user .
     */
    public function get(Request $request)
    {
        $anounce = AnnouncementController::get_announcement($request);
        $anouncenew = AnnouncementController::new_user_announcements($request);
        $myAnnouncements = Announcement::where('created_by',Auth::id())->get();
        foreach ($myAnnouncements as $ann) {
                    $ann->attached_file = attachment::where('id', $ann->attached_file)->first();
                }
        return HelperController::api_response_format(201, ['notify' => $anounce , 'created'=>$myAnnouncements , 'assigned' => $anouncenew]);
    }

     /**
     *
     * @Description :get an announcement  for logged in user.
     * @param : No parameters.
     * @return : return all currently published announcement for this user .
     */
    public function getAnnounceByID(Request $request){
        $request->validate([
        'announce_id' => 'required|integer|exists:announcements,id',
        ]);

        $announce=Announcement::where ('id',$request->announce_id)
        ->with('attachment')
        ->first(['id','title','description','start_date','due_date','assign','class_id','year_id','level_id','course_id','type_id','segment_id' , 'publish_date' , 'attached_file']);
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



    public function unreadannouncements(Request $request)
    {
        $data = [];
        $noti = DB::table('notifications')->select('data')->where('notifiable_id', $request->user()->id)->where('read_at', null)->get();
        foreach ($noti as $not) {
            $not->data= json_decode($not->data, true);
            if($not->data['type'] == 'announcement')
            {
                $parse=Carbon::parse($not->data['publish_date']);

                if(!isset($parse)){
                    $data[] = $not->data;
                }
                elseif($parse < Carbon::now())
                {
                    $data[] = $not->data;
                }
            }
        }
        return HelperController::api_response_format(200, $data,'all user unread announcements');
    }

    public function markasread(Request $request)
    {
        $request->validate([
            // 'id' => 'exists:notifications,id',
            'type' => 'string|in:announcement',
            'id' => 'int',
            'message' => 'string'
        ]);
        $session_id = Auth::User()->id;
        if(isset($request->id))
        {
            // $note = DB::table('notifications')->where('id', $request->id)->first();
            // if ($note->notifiable_id == $session_id){
            //     $notify =  DB::table('notifications')->where('id', $request->id)->update(['read_at' =>  Carbon::now()]);
            //     $print=self::get($request);
            //     return $print;
            // }
            $noti = DB::table('notifications')->where('notifiable_id', $request->user()->id)->get();
            foreach ($noti as $not) {
                $not->data= json_decode($not->data, true);
                if($not->data['type'] == 'announcement')
                {
                    if($not->data['id'] == $request->id && $not->data['type'] == $request->type && $not->data['message'] == $request->message)
                    {
                        DB::table('notifications')->where('id', $not->id)->update(array('read_at' => Carbon::now()->toDateTimeString()));
                    }
                }
            }
            $print=self::get($request);
            return $print;
        }
        else
        {
            // $noti = DB::table('notifications')->where('notifiable_id', $request->user()->id)->update(array('read_at' => Carbon::now()->toDateTimeString()));
            $noti = DB::table('notifications')->where('notifiable_id', $request->user()->id)->get();
            foreach ($noti as $not) {
                $not->data= json_decode($not->data, true);
                if($not->data['type'] == 'announcement')
                {
                    DB::table('notifications')->where('id', $not->id)->update(array('read_at' => Carbon::now()->toDateTimeString()));
                }
            }
            $print=self::get($request);
            return $print;

        }
        return HelperController::api_response_format(400, $body = [], $message = 'You cannot seen this announcement');
    }

    public function My_announc(Request $request)
    {
        $my = Announcement::where('created_by',Auth::id());
           if($request->filled('search')){
            $my->where(function ($query) use ($request) {
                $query->where('title', 'LIKE' , "%$request->search%");
            });
        }
        return HelperController::api_response_format(200, $my->get());

    }
}
