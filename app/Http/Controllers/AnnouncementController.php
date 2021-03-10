<?php

namespace App\Http\Controllers;

use App\Http\Controllers\GradeCategoryController;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\File;
use App\Notifications\Announcment;
use App\Events\MassLogsEvent;
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
            //all mimetypes in this link
            // https://github.com/symfony/symfony/blob/d94d837e9ea75d76eeb0a43e0535a5e7a7a01542/src/Symfony/Component/HttpFoundation/File/MimeType/MimeTypeExtensionGuesser.php
            'attached_file' => 'nullable|file|mimetypes:mp3,application/pdf,
                                                        application/vnd.openxmlformats-officedocument.wordprocessingml.document,
                                                        application/msword,
                                                        application/vnd.ms-excel,
                                                        application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,
                                                        application/vnd.ms-powerpoint,
                                                        application/vnd.openxmlformats-officedocument.presentationml.presentation,
                                                        application/zip,application/x-rar,text/plain,video/mp4,audio/ogg,audio/mpeg,video/mpeg,
                                                        video/ogg,jpg,image/jpeg,image/png',
            'start_date' => 'before:due_date',
            'due_date' => 'after:' . Carbon::now(),
            'publish_date' => 'nullable|after:' . Carbon::now(),
            'role' => 'nullable',
            'chains.*.year' => 'array',
            'chains.*.year.*' => 'exists:academic_years,id',
            'chains.*.type' => 'array',
            'chains.*.type.*' => 'exists:academic_types,id',
            'chains.*.level' => 'array',
            'chains.*.level.*' => 'exists:levels,id',
            'chains.*.class' => 'array',
            'chains.*.class.*' => 'exists:classes,id',
            'chains.*.segment' => 'array',
            'chains.*.segment.*' => 'exists:segments,id',
            'chains.*.course.*' => 'exists:courses,id',
            'chains.*.role.*' => 'exists:roles,id',
        ]);
        if($request->user()->can('announcements/filter-chain')){
            $request->validate([
                'chains.*.year' => 'required|array',
                'chains.*.year.*' => 'exists:academic_years,id',
                'chains.*.type' => 'required|array',
                'chains.*.type.*' => 'exists:academic_types,id',
                'chains.*.level' => 'required|array',
                'chains.*.level.*' => 'exists:levels,id',
                'chains.*.class' => 'required|array',
                'chains.*.class.*' => 'exists:classes,id',
                'chains.*.segment' => 'required|array',
                'chains.*.segment.*' => 'exists:segments,id',
                'chains.*.course.*' => 'required|exists:courses,id',
            ]);
        }
        $date=Carbon::now();
        $publishdate = Carbon::parse($date);
        if (isset($request->publish_date)) 
            $publishdate = $request->publish_date;

        $users = array();
        //Files uploading
        $file_id = null;
        if (isset($request->attached_file))
            $file_id = attachment::upload_attachment($request->attached_file, 'Announcement')->id;

        $userr = Enroll::where('user_id','!=' ,Auth::id());
        $users =  $userr->pluck('user_id')->toArray();
        $users =  array_values(array_unique($users));
        $chains[0]['year'][0]=AcademicYear::where('current',1)->pluck('id')->first();
        if(isset($request->chains))
            $chains=$request->chains;
        foreach($chains as $chain){
            if(!isset($chain['year']))
                $chain['year']=AcademicYear::where('current',1)->pluck('id');
            foreach ($chain['year'] as $year) {
                if(!isset($chain['type']))
                    $chain['type']=AcademicYearType::where('academic_year_id',$year)->pluck('academic_type_id');
                foreach ($chain['type'] as $type) {
                    $yeartype = AcademicYearType::checkRelation($year, $type);
                    if(!isset($chain['level']))
                        $chain['level']=YearLevel::where('academic_year_type_id',$yeartype->id)->pluck('level_id');

                    foreach ($chain['level'] as $level) {
                        $yearlevel = YearLevel::checkRelation($yeartype->id, $level);
                        if(!isset($chain['class']))
                            $chain['class'] =  ClassLevel::where('year_level_id',$yearlevel->id)->pluck('class_id');
    
                        foreach ($chain['class'] as $class) {
                            $classLevel = ClassLevel::checkRelation($class, $yearlevel->id);
                            if(!isset($chain['segment']))
                                $chain['segment']=SegmentClass::where('class_level_id',$classLevel->id)->pluck('segment_id');

                            foreach ($chain['segment'] as $segment) {
                                $segmentClass = SegmentClass::checkRelation($classLevel->id, $segment);
                                if(!isset($chain['course']))
                                    $chain['course'] = CourseSegment::where('segment_class_id',$segmentClass->id)->pluck('course_id');

                                foreach($chain['course'] as $course){
                                    if(!isset($chain['role']))
                                        $chain['role']=Role::pluck('id');
                                    foreach($chain['role'] as $role)
                                    {
                                        $userr->where('year',$year);
                                        $userr->where('type',$type);
                                        $userr->where('level',$level);
                                        $userr->where('segment',$segment);
                                        $userr->where('course',$course);
                                        $userr->where('class',$class);
                                        $userr->where('role_id',$role);
                                        // return $userr->get();
    
                                        $ann = Announcement::firstOrCreate([
                                            'title' => $request->title,
                                            'description' => $request->description,
                                            'attached_file' => $file_id,
                                            'class_id' => $class,
                                            'course_id' => $course,
                                            'level_id' => $level,
                                            'year_id' => $year,
                                            'type_id' => $type,
                                            'segment_id' => $segment,
                                            'publish_date' => Carbon::parse($publishdate),
                                            'created_by' => Auth::id(),
                                        ]);
                                        foreach ($userr->get() as $user){
                                            userAnnouncement::create([
                                                'announcement_id' => $ann->id,
                                                'user_id' => $user->user_id
                                            ]);
                                        }
                                    
                                        $ann->start_date = Carbon::parse(Carbon::now())->format('Y-m-d H:i:s') ;
                                        if($request->filled('start_date')){
                                            if($request->start_date < Carbon::now())
                                                $ann->start_date = Carbon::parse(Carbon::now())->format('Y-m-d H:i:s');
                                        }
                                
                                        if($request->filled('due_date'))
                                            $ann->due_date = $request->due_date;
                                        $ann->save();
                                        
                                        $attached=$file_id;
                                        if(isset($attached))
                                            $attached = attachment::where('id', $file_id)->first();                                        
                                            
                                        $requ = ([
                                            'id' => $ann->id,
                                            'type' => 'announcement',
                                            'publish_date' => Carbon::parse($publishdate),
                                            'title' => $request->title,
                                            'description' => $request->description,
                                            'attached_file' => $attached,
                                            'start_date' => $ann->start_date,
                                            'due_date' => $ann->due_date,
                                            'message' => $request->title.' announcement is added'
                                        ]);
                                                // $user = array_unique($users->toArray());
                                        if($request->filled('role'))
                                        {
                                            foreach($userr->get() as $use)
                                            {
                                                if($use->user_id != Auth::id()){
                                                    $user_obj=User::where('id',$use->user_id)->get()->first();
                                                    $role_id=$user_obj->roles->pluck('id')->first();
                                                    if($role_id == $request->role)
                                                        $requ['users'][] = $use->user_id;
                                                    else
                                                        continue;
                                                }
                                            }
                                            if(!isset($requ['users']))
                                                return HelperController::api_response_format(201,'No User');
                                        }
                                        $requ['users'] = $userr->pluck('user_id')->toArray();
                                        $notificatin = User::notify($requ);
                                        // return $notificatin;
                                    }
                                }
                            }
                        }
                    }
                }
            }                
        }
        $anounce = AnnouncementController::get_announcement($request);
        $myAnnouncements = Announcement::where('created_by',Auth::id())->get();
        foreach ($myAnnouncements as $ann) 
            $ann->attached_file = attachment::where('id', $ann->attached_file)->first();

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
            
            //for log event
            // $logsbefore=DB::table('notifications')->where('id', $de->id)->get();
            $check=DB::table('notifications')->where('id', $de->id)->update(['read_at' => null]);
            // if($check > 0)
                // event(new MassLogsEvent($logsbefore,'updated'));
        }
        //Validtaionof updated data
        $request->validate([
            'title' => 'required',
            'description' => 'required',
            'attached_file' => 'nullable|file|mimetypes:mp3,application/pdf,
                                application/vnd.openxmlformats-officedocument.wordprocessingml.document,
                                application/msword,
                                application/vnd.ms-excel,
                                application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,
                                application/vnd.ms-powerpoint,
                                application/vnd.openxmlformats-officedocument.presentationml.presentation,
                                application/zip,application/x-rar,text/plain,video/mp4,audio/ogg,audio/mpeg,video/mpeg,
                                video/ogg,jpg,image/jpeg,image/png',
            'start_date' => 'before:due_date',
            'due_date' => 'after:' . Carbon::now(),
            'publish_date' => 'nullable|after:' . Carbon::now()->addMinutes(1),
        ]);

        $publishdate = Carbon::now();
        if (isset($request->publish_date)) 
            $publishdate = $request->publish_date;

        if($request->start_date < Carbon::now())
        {
            $start_date = Carbon::now();
        }else {
            $start_date = $request->start_date;
        }

        $due_date = $announce->due_date;
        if(isset($request->due_date))
            $due_date = $request->due_date;

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
            'publish_date' => Carbon::parse($publishdate),
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
                'publish_date' => Carbon::parse($publishdate),
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
                'publish_date' => Carbon::parse($publishdate),
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
        foreach ($deleted as $de) 
            DB::table('notifications')->where('id', $de->id)->first()->delete();
        
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
        if($request->user()->can('site/show-all-courses'))
            $announcements_ids = Announcement::where('created_by',Auth::id())->pluck('id');
        $announcements = Announcement::whereIn('id',$announcements_ids)->where('title', 'LIKE' , "%$request->search%")
                                        ->where('publish_date', '<=', Carbon::now());

        if($request->filled('search')){
            $announcements->where(function ($query) use ($request) {$query->where('title', 'LIKE' , "%$request->search%");});
        }
        $all= $announcements->get();
        foreach($all as $one)
            $one->attached_file=attachment::where('id', $one->attached_file)->first();
        return $all;
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
            ->orderBy('created_at','DESC')
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
                    //for log event
                    // $logsbefore=DB::table('notifications')->where('id', $not->id)->get();

                    if($not->data['id'] == $request->id && $not->data['type'] == $request->type && $not->data['message'] == $request->message){
                        $check=DB::table('notifications')->where('id', $not->id)->update(['read_at' => Carbon::now()->toDateTimeString()]);
                    // if($check > 0)
                    //     event(new MassLogsEvent($logsbefore,'updated'));
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
                    //for log event
                    // $logsbefore=DB::table('notifications')->where('id', $not->id)->get();
                    $check=DB::table('notifications')->where('id', $not->id)->update(['read_at' => Carbon::now()->toDateTimeString()]);
                    // if($check > 0)
                    //     event(new MassLogsEvent($logsbefore,'updated'));
                }
            }
            $print=self::get($request);
            return $print;
        }
        return HelperController::api_response_format(400, $body = [], $message = 'You cannot seen this announcement');
    }

    public function My_announc(Request $request)
    {
        $my = Announcement::where('created_by',Auth::id())->whereHas('UserAnnouncement');
        
        if($request->filled('search')){
            $my->where(function ($query) use ($request) {
                $query->where('title', 'LIKE' , "%$request->search%");
            });
        }
        $announcements=$my->get();
        foreach($announcements as $one)
            if(isset($one->attached_file))
                $one->attached_file = attachment::where('id', $one->attached_file)->first();
        
        return HelperController::api_response_format(200, $announcements,'my announcement');
    }
}
