<?php

namespace Modules\Bigbluebutton\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use BigBlueButton\BigBlueButton;
use App\Component;
use App\User;
use App\Events\MassLogsEvent;
use App\Enroll;
use App\ZoomAccount;
use Auth;
use Log;
use App\CourseSegment;
use Modules\Attendance\Entities\AttendanceLog;
use BigBlueButton\Parameters\CreateMeetingParameters;
use BigBlueButton\Parameters\JoinMeetingParameters;
use BigBlueButton\Parameters\GetRecordingsParameters;
use BigBlueButton\Parameters\HooksCreateParameters;
use Modules\Bigbluebutton\Entities\BigbluebuttonModel;
use BigBlueButton\Parameters\GetMeetingInfoParameters;
use BigBlueButton\Parameters\HooksDestroyParameters;
use Illuminate\Support\Carbon;
use App\Repositories\ChainRepositoryInterface;
use App\Http\Controllers\HelperController;
use DB;
use GuzzleHttp\Client;
use App\Exports\BigBlueButtonAttendance;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\GradeCategoryController;
use Illuminate\Support\Str;
use App\Classes;
use App\Course;
use App\Paginate;
use App\Http\Controllers\Controller;
use App\LastAction;
use App\Exports\BigbluebuttonGeneralReport;

class BigbluebuttonController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
    }

    public function install()
    {
        if (\Spatie\Permission\Models\Permission::whereName('bigbluebutton/create')->first() != null) {
            return \App\Http\Controllers\HelperController::api_response_format(400, null, 'This Component is installed before');
        }
        
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'bigbluebutton/create','title' => 'create meeting']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'bigbluebutton/join','title' => 'join meeting']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'bigbluebutton/get','title' => 'get meeting']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'bigbluebutton/getRecord','title' => 'get Record']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'bigbluebutton/delete','title' => 'Delete Record']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'bigbluebutton/toggle','title' => 'Toggle Record']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'bigbluebutton/attendance','title' => 'Bigbluebutton Attendance']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'bigbluebutton/get-attendance','title' => 'Bigbluebutton get Attendance']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'bigbluebutton/export','title' => 'Bigbluebutton Export Attendance']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'bigbluebutton/get-all','title' => 'Bigbluebutton Get All']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'bigbluebutton/session-moderator','title' => 'Bigbluebutton session moderator']);

        $teacher_permissions=['bigbluebutton/create','bigbluebutton/join','bigbluebutton/get','bigbluebutton/getRecord','bigbluebutton/delete','bigbluebutton/toggle',
        'bigbluebutton/attendance','bigbluebutton/get-attendance','bigbluebutton/export','bigbluebutton/session-moderator'];
        $tecaher = \Spatie\Permission\Models\Role::find(4);
        $tecaher->givePermissionTo(\Spatie\Permission\Models\Permission::whereIn('name', $teacher_permissions)->get());

        $student_permissions=['bigbluebutton/join','bigbluebutton/get','bigbluebutton/getRecord'];
        $student = \Spatie\Permission\Models\Role::find(3);
        $student->givePermissionTo(\Spatie\Permission\Models\Permission::whereIn('name', $student_permissions)->get());

        $parent_permissions=['bigbluebutton/get','bigbluebutton/getRecord'];
        $parent = \Spatie\Permission\Models\Role::find(7);
        $parent->givePermissionTo(\Spatie\Permission\Models\Permission::whereIn('name', $parent_permissions)->get());

        $role = \Spatie\Permission\Models\Role::find(1);
        $role->givePermissionTo('bigbluebutton/create');
        $role->givePermissionTo('bigbluebutton/join');
        $role->givePermissionTo('bigbluebutton/get');
        $role->givePermissionTo('bigbluebutton/get-all');
        $role->givePermissionTo('bigbluebutton/getRecord');
        $role->givePermissionTo('bigbluebutton/delete');
        $role->givePermissionTo('bigbluebutton/toggle');
        $role->givePermissionTo('bigbluebutton/attendance');
        $role->givePermissionTo('bigbluebutton/get-attendance');
        $role->givePermissionTo('bigbluebutton/export');
        $role->givePermissionTo('bigbluebutton/session-moderator');

        Component::create([
            'name' => 'Bigbluebutton',
            'module'=>'Bigbluebutton',
            'model' => 'BigbluebuttonModel',
            'type' => 2,
            'active' => 1
        ]);

        return \App\Http\Controllers\HelperController::api_response_format(200, null, 'Component Installed Successfully');
    }

    // function generate_signature ( $api_key, $api_secret, $meeting_number, $role){

    //     $time = time() * 1000 - 30000;//time in milliseconds (or close enough)
        
    //     $data = base64_encode($api_key . $meeting_number . $time . $role);
        
    //     $hash = hash_hmac('sha256', $data, $api_secret, true);
        
    //     $_sig = $api_key . "." . $meeting_number . "." . $time . "." . $role . "." . base64_encode($hash);
        
    //     //return signature, url safe base64 encoded
    //     return rtrim(strtr(base64_encode($_sig), '+/', '-'), '=');
    // }

    /**
     * Show the form for creating a new Meeting.
     * @return Response
     */
    public function create(Request $request)
    {
        //Validating the Input
        $request->validate([
            'name' => 'required|string',
            'object' => 'required|array',
            'object.*.class_id' => 'required|array',
            'object.*.class_id.*' => 'required|exists:classes,id',
            'object.*.course_id' => 'required|exists:courses,id',
            'type' => 'required|string|in:BBB,Zoom,teams',
            'attendee_password' => 'required_if:type,==,BBB|string|different:moderator_password',
            'moderator_password' => 'required_if:type,==,BBB|string',
            'duration' => 'nullable',
            'is_recorded' => 'required|in:0,1,2',
            'start_date' => 'required|array',
            'start_date.*' => 'date',
            'last_day' => 'date',
            'visible' => 'in:0,1',
            // 'host_id' => 'required_if:type,==,Zoom',
            'join_url' => 'required_if:type,==,teams'
        ]);
    
        if($request->type == 'BBB'){
            try{
                $try = self::create_hook($request);    
            }
            catch(\Exception $e){
                return HelperController::api_response_format(400, null ,__('messages.virtual.server_error'));
            }
        }
        
        $attendee= 'learnovia123';
        if(isset($request->attendee_password)){
            $attendee= $request->attendee_password;
        }

        $duration= '40';
        if(isset($request->duration)){
            $duration= $request->duration;
        }

        $created_meetings=collect();
        if(count($request->object) > 0){
            foreach($request->object as $object){
                // $course_segments_ids=collect();
                $meeting_id = 'Learnovia'.env('DB_DATABASE').uniqid();
                foreach($object['class_id'] as $class){
                    $i=0;
                    // $courseseg = CourseSegment::GetWithClassAndCourse($class,$object['course_id']);
                    LastAction::lastActionInCourse($object['course_id']);
                    // if(isset($courseseg))
                    //     $course_segments_ids->push($courseseg->id);

                    // if(count($course_segments_ids) <= 0)
                    //     return HelperController::api_response_format(404, null ,__('messages.error.no_active_segment'));
            
                    $usersIDs=Enroll::where('group',$class)->where('course',$object['course_id'])->where('user_id','!=', Auth::id())->pluck('user_id')->unique()->values()->toarray();
                    foreach($request->start_date as $start_date){
                        $last_date = $start_date;
                        if(isset($request->last_day))
                            $last_date= $request->last_day;
            
                        $temp_start = Carbon::parse($start_date);
                        while(Carbon::parse($temp_start)->format('Y-m-d H:i:s') <= Carbon::parse($last_date)->format('Y-m-d H:i:s')){
                            $bigbb = new BigbluebuttonModel;
                            $bigbb->name=$request->name;
                            $bigbb->type=$request->type;
                            $bigbb->class_id=$class;
                            $bigbb->course_id=$object['course_id'];
                            $bigbb->attendee_password=$attendee;
                            $bigbb->moderator_password=$request->moderator_password;
                            $bigbb->duration=$duration;
                            $bigbb->actual_duration = $duration;
                            $bigbb->start_date=$temp_start->format('Y-m-d H:i:s');
                            $bigbb->meeting_id = $i == 0 ? $meeting_id : $meeting_id.'repeat'.$i;
                            $bigbb->user_id = Auth::user()->id;
                            $bigbb->host_id = ($request->host_id) ? $request->host_id : Auth::id();
                            $bigbb->is_recorded = $request->is_recorded;
                            $bigbb->started = 0;
                            $bigbb->status = 'future';
                            $bigbb->join_url = $request->join_url ? $request->join_url : null;
                            $bigbb->show = isset($request->visible)?$request->visible:1;
                            $bigbb->save();

                            $bigbb['join'] = $bigbb->started == 1 ? true: false;

                            if(Carbon::parse($temp_start)->format('Y-m-d H:i:s') <= Carbon::now()->format('Y-m-d H:i:s') && Carbon::now()->format('Y-m-d H:i:s') <= Carbon::parse($temp_start)
                            ->addMinutes($request->duration)->format('Y-m-d H:i:s'))
                            {
                                if($request->type == 'BBB'){
                                    try{
                                        self::clear();
                                        self::create_hook($request);    
                                    }
                                    catch(\Exception $e){
                                        return HelperController::api_response_format(400, null ,__('messages.virtual.server_error'));
                                    }
                                }
                                if($request->user()->can('bigbluebutton/session-moderator') && $bigbb->started == 0)
                                    $bigbb['join'] = true; //startmeeting has arrived but meeting didn't start yet
                            }
                    
                            User::notify([
                                'id' => $bigbb->id,
                                'message' => $request->name.' meeting is created',
                                'from' => Auth::user()->id,
                                'users' => $usersIDs,
                                'course_id' => $object['course_id'],
                                'class_id'=>$class,
                                'lesson_id'=> null,
                                'type' => 'meeting',
                                'link' => url(route('getmeeting')) . '?id=' . $bigbb->id,
                                'publish_date'=> $temp_start,
                            ]);
                            $created_meetings->push($bigbb);
                            
                            $end_date = Carbon::parse($temp_start)->addMinutes($request->duration);
                            $seconds = $end_date->diffInSeconds(Carbon::now());
                            if($seconds < 0) {
                                $seconds = 0;
                            }
                            $job = (new \App\Jobs\bigbluebuttonEndMeeting($bigbb))->delay($seconds);
                            dispatch($job);

                            $temp_start= Carbon::parse($temp_start)->addDays(7);
                            $i++;
                        }
                    }
                }
            }
        }
        return HelperController::api_response_format(200, $created_meetings ,__('messages.virtual.add'));
    }

    public function get_meetings()
    {
        $meetings=BigbluebuttonModel::where('start_date','<=', Carbon::now())->get();
        return HelperController::api_response_format(200, $meetings ,__('messages.virtual.list'));
    }

    public function start_meeting_zoom(Request $request)
    {
        $request->validate([
            'id'=>'required|exists:bigbluebutton_models,id',
        ]);

        $bigbb=BigbluebuttonModel::find($request->id);
        $user=ZoomAccount::where('user_id',$bigbb->host_id)->first();
        if(!isset($user))
            throw new \Exception(__('messages.zoom.zoom_account'));

        $updatedUser=ZoomAccount::refresh_jwt_token($user);
        $jwtToken = $updatedUser->jwt_token;
        $zoomUserId = $updatedUser->user_zoom_id;

        switch($bigbb->is_recorded){
            case 0:
                $record= 'none';
            case 1:
                $record= 'cloud';
            case 2:
                $record= 'local';
        }

        $requestBody = [
            //https://marketplace.zoom.us/docs/api-reference/zoom-api/meetings/meetingcreate
            'topic'	=> $bigbb->name,
            // 1 >> instance meeting
            // 2 >> schedualed meeting
            // 3 >> meeting without fixed time
            // 8 >> meeting without fixed time
            'type'			=> 2,
            'start_time'	=> $bigbb->start_date	,
            'duration'		=> $bigbb->duration,
            'password'		=> '123456',
            'timezone'		=> 'Africa/Cairo',
            'agenda'		=> 'Learnovia',
            // 'recurrence'    => [
            //     'type'=> 2, // 1 >> Daily .. 2 >> Weekly .. 3 >> Monthly
            //     'repeat_interval'=> 1,
            //     'weekly_days'=>"3,5",
            //     'end_date_time'=> "2020-06-02T03:59:00Z",
            // ],
            'settings'		=> [
                'host_video'			=> false,
                'participant_video'		=> false,
                'cn_meeting'			=> false,
                'in_meeting'			=> false,
                'join_before_host'		=> false,
                'mute_upon_entry'		=> true,
                'watermark'				=> false,
                'use_pmi'				=> false,
                'approval_type'			=> 1,
                'registration_type'		=> 1,
                'audio'					=> 'voip',
                'auto_recording'		=> $record, //2:local, 1:cloud, 0:none
                'waiting_room'			=> false
            ]
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // Skip SSL Verification
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.zoom.us/v2/users/".$zoomUserId."/meetings",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($requestBody),
            CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer ".$jwtToken,
            "Content-Type: application/json",
            "cache-control: no-cache"
            ),
        ));

        $response = curl_exec($curl);

        if (!isset(json_decode($response,true)['join_url'])) 
            throw new \Exception(__('messages.zoom.Invalid'));

        curl_close($curl);

        $bigbb->join_url=json_decode($response,true)['join_url'];
        $bigbb->meeting_id=json_decode($response,true)['id'];
        $bigbb->status = 'current';
        $bigbb->started = 1;
        $bigbb->actutal_start_date = Carbon::now();
        $signature=ZoomAccount::generate_signature($updatedUser->api_key,$updatedUser->api_secret,$bigbb->meeting_id,0);
        if($request->user()->can('site/show-all-courses'))
            $signature=ZoomAccount::generate_signature($updatedUser->api_key,$updatedUser->api_secret,$bigbb->meeting_id,1);

        $bigbb->signature=$signature;
        $bigbb->save();
        return $response;
    }

    public function start_meeting(Request $request)
    {
        $request->validate([
            'id'=>'required|exists:bigbluebutton_models,id',
        ]);

        $bigbb=BigbluebuttonModel::find($request->id);
        LastAction::lastActionInCourse($bigbb->course_id);

        $url= config('app.url');
        $url = substr($url, 0, strpos($url, "api"));
        $open_link = 'https://learnovia.com/';
        if(isset($url)){
            $open_link = $url.'.learnovia.com/#/viewAllVirtualClassRoom';
        }

        //Creating the meeting
        $bbb = new BigBlueButton();
        $createMeetingParams = new CreateMeetingParameters($bigbb->meeting_id, $bigbb->name);
        $createMeetingParams->setAttendeePassword($bigbb->attendee_password);
        $createMeetingParams->setModeratorPassword($bigbb->moderator_password);
        $createMeetingParams->setDuration($bigbb->duration);
        // $createMeetingParams->setRedirect(false);
        $createMeetingParams->setLogoutUrl($open_link);
        $createMeetingParams->setWelcomeMessage('Welcome to Learnovia Class Room');
        if($bigbb->is_recorded == 1){
            $createMeetingParams->setRecord(true);
            $createMeetingParams->setAllowStartStopRecording(true);
            $createMeetingParams->setAutoStartRecording(true);
        }
        $response = $bbb->createMeeting($createMeetingParams);

        if ($response->getReturnCode() == 'FAILED') 
            return 'Can\'t create room! please contact our administrator.';

        $Meetings = BigbluebuttonModel::where('meeting_id',$bigbb->meeting_id)->get();
        foreach($Meetings as $meeting){
            $courseseg=CourseSegment::GetWithClassAndCourse($meeting->class_id,$meeting->course_id);
            if(!isset($courseseg))
                return HelperController::api_response_format(200, null ,__('messages.error.no_active_segment'));
    
            $usersIDs=Enroll::where('course_segment',$courseseg->id)->pluck('user_id')->toarray();
            foreach($usersIDs as $user)
            {
                $userObj=User::find($user);
                if(!isset($userObj))
                    continue;
                if($userObj->roles->pluck('id')->first()==3){
                    $founded=AttendanceLog::where('student_id',$user)
                            ->where('session_id',$meeting->id)->where('type','online')->first();
    
                    if(!isset($founded)){
                        $attendance=AttendanceLog::create([
                            'ip_address' => \Request::ip(),
                            'student_id' => $user,
                            'taker_id' => $meeting->user_id,
                            'session_id' => $meeting->id,
                            'type' => 'online',
                            'taken_at' => Carbon::now()->format('Y-m-d H:i:s')
                        ]);
                    }
                }
            }
        }

        // BigbluebuttonModel::where('meeting_id',$bigbb->meeting_id)->update([
        //     'started' => 1
        // ]);

        return 1;
    }

    //Join the meeting
    public function join(Request $request)
    {
        if($request->type == 'BBB'){
            try{
                self::clear();
                self::create_hook($request);    
            }
            catch(\Exception $e){
                return HelperController::api_response_format(400, null ,__('messages.virtual.server_error'));
            }
        }
        $bbb = new BigBlueButton();

        //Validating the Input
        $request->validate([
            'id'=>'required|exists:bigbluebutton_models,id',
        ]);

        $bigbb=BigbluebuttonModel::find($request->id);
        $meeting_start = isset($bigbb->actutal_start_date) ? $bigbb->actutal_start_date : $bigbb->start_date;
        $check=Carbon::parse($meeting_start)->addMinutes($bigbb->duration);

        $exist_meeting = 1;
        if($bigbb->type == 'BBB')
        {
            $getMeetingInfoParams = new GetMeetingInfoParameters($bigbb->meeting_id, $bigbb->moderator_password);
            $response = $bbb->getMeetingInfo($getMeetingInfoParams);
            if ($response->getReturnCode() == 'FAILED') {
                $exist_meeting = 0;
            }
        }
        
        if(($check < Carbon::now() && $exist_meeting == 0) || (!$request->user()->can('bigbluebutton/session-moderator') && $bigbb->started == 0))
            return HelperController::api_response_format(200,null ,__('messages.virtual.cannot_join'));

        if($request->user()->can('bigbluebutton/session-moderator') && $bigbb->started == 0 && $bigbb->type != 'teams'){
            if($bigbb->type == 'Zoom')
                $start_meeting = self::start_meeting_zoom($request);

            if($bigbb->type == 'BBB')
                $start_meeting = self::start_meeting($request);

            if(!$start_meeting)
                return HelperController::api_response_format(200, [],__('messages.error.try_again'));
        }

        LastAction::lastActionInCourse($bigbb->course_id);
            
        $url = null;
        if($bigbb->type == 'BBB'){
            $user_name = Auth::user()->username;
            $full_name = Auth::user()->fullname;
            
            $password = $bigbb->attendee_password;
            if($request->user()->can('bigbluebutton/session-moderator'))
                $password = $bigbb->moderator_password;
            
            $joinMeetingParams = new JoinMeetingParameters($bigbb->meeting_id, $full_name, $password);
            $joinMeetingParams->setRedirect(true);
            $joinMeetingParams->setJoinViaHtml5(true);
            $joinMeetingParams->setUserId($user_name);
            $url = $bbb->getJoinMeetingURL($joinMeetingParams);
        }

        if($bigbb->type == 'Zoom' || $bigbb->type == 'teams')
            $url= BigbluebuttonModel::find($request->id)->join_url;

        if($bigbb->type=='teams'){
            $bigbb->started=1;
            $bigbb->save();
        }
        
        $output = array(
            'name' => $bigbb->name,
            'duration' => $bigbb->duration,
            'link'=> $url
        );
        return HelperController::api_response_format(200, $output,__('messages.virtual.join'));
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function get(Request $request,$count = null)
    {
        $rules = [
            'id' => 'exists:bigbluebutton_models,id',
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
            'level' => 'exists:levels,id',
            'class' => 'exists:classes,id',
            'segment' => 'exists:segments,id',
            'course'    => 'exists:courses,id',
            'status'    => 'in:past,future,current',
            'start_date' => 'date|required_with:due_date',
            'due_date' => 'date|required_with:start_date',
            'sort_in' => 'in:asc,desc',
            'pagination' => 'boolean'
        ];

        $customMessages = [
            'id.exists' => __('messages.error.item_deleted')
        ];

        $this->validate($request, $rules, $customMessages);
            
        $classes = [];
        if(isset($request->class))
            $classes = [$request->class];

        if(isset($request->course))
            $request['courses']= [$request->course];
        
        if($request->filled('course'))
            LastAction::lastActionInCourse($request->course);

        $sort_in = 'desc';
        if($request->has('sort_in'))
            $sort_in = $request->sort_in;

        self::clear(); 

        $enrolls = $this->chain->getEnrollsByChain($request)->where('user_id',Auth::id());
        // $classes->where('type','class')->whereIn('id',$enrolls->pluck('group'));

        // $CS_ids=GradeCategoryController::getCourseSegment($request);

        // $CourseSeg = Enroll::where('user_id', Auth::id())->pluck('course_segment');

        // $CourseSeg = array_intersect($CS_ids->toArray(),$CourseSeg->toArray());

        // if($request->user()->can('site/show-all-courses')){
        //     $CourseSeg = $CS_ids;
        //     $classes = count($classes) == 0? Classes::pluck('id') : $classes;
        // }

        $classes = $enrolls->pluck('group')->unique()->values();
        
        // $courses=CourseSegment::whereIn('id',$CourseSeg)->where('end_date','>',Carbon::now())
        //                                                 ->where('start_date','<',Carbon::now())
        //                                                 ->pluck('course_id')->unique()->values();

        $courses=$enrolls->pluck('course')->unique()->values();

        $meeting = BigbluebuttonModel::whereIn('course_id',$courses)->whereIn('class_id',$classes)->orderBy('start_date',$sort_in);

        if($request->user()->can('site/course/student'))
            $meeting->where('show',1);
            
        if($request->has('status'))
            $meeting->where('status',$request->status);

        if($request->has('start_date'))
            $meeting->where('start_date', '>=', $request->start_date)->where('start_date','<=',$request->due_date);

        if($request->has('id'))
            $meeting->where('id',$request->id);

        if($count == 'count')
            return response()->json(['message' => 'Virtual classrooms count', 'body' => $meeting->count()], 200);
        
        $meetings = $meeting->get();
        
        if($request->has('id') && $request->user()->can('site/course/student') && count($meetings) == 0)
            return HelperController::api_response_format(301,null, __('messages.virtual.virtual_hidden'));
        
        foreach($meetings as $m){
            $m['join'] = $m->started == 1 ? true: false;
            $m->actutal_start_date = isset($m->actutal_start_date)?Carbon::parse($m->actutal_start_date)->format('Y-m-d H:i:s'): null;
            $m->start_date = Carbon::parse($m->start_date)->format('Y-m-d H:i:s');
            

            if(Carbon::now() >= Carbon::parse($m->start_date)->addMinutes($m->duration)){
                $m->status = 'past';
                $m['join'] = false;
            }

            if(Carbon::parse($m->start_date) <= Carbon::now() && Carbon::now() <= Carbon::parse($m->start_date)->addMinutes($m->duration))
            {
                try{
                    $try = self::create_hook($request);    
                }
                catch(\Exception $e){
                    //error
                }
                if($request->user()->can('bigbluebutton/session-moderator') && $m->started == 0)
                    $m['join'] = true; //startmeeting has arrived but meeting didn't start yet
            }

        }

        $meetings = $meetings->sortBy('status')->values();
        
        if($request->has('pagination') && $request->pagination==true)
            return HelperController::api_response_format(200 , $meetings->paginate(Paginate::GetPaginate($request)),__('messages.virtual.list'));
            
        if(count($meetings) == 0)
            return HelperController::api_response_format(200 , [] , __('messages.error.not_found'));

        return HelperController::api_response_format(200 , $meetings,__('messages.virtual.list'));
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return view('bigbluebutton::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function getRecord(Request $request)
    {
        //Validating the Input
        $request->validate([
            'id'=>'required|exists:bigbluebutton_models,id',
        ]);
        $urls=null;
        $bigbb=BigbluebuttonModel::find($request->id);
        LastAction::lastActionInCourse($bigbb->course_id);

        
        $meeting_start = isset($bigbb->actutal_start_date) ? $bigbb->actutal_start_date : $bigbb->start_date;
        $check=Carbon::parse($meeting_start)->addMinutes($bigbb->duration);

        if($check < Carbon::now() && !isset($bigbb->actutal_start_date))
            return HelperController::api_response_format(200,null ,__('messages.virtual.no_one_entered'));
          
        $bbb = new BigBlueButton();
        $recordingParams = new GetRecordingsParameters();
        $response = $bbb->getRecordings($recordingParams);
        if ($response->getReturnCode() == 'SUCCESS') {
            foreach ($response->getRawXml()->recordings->recording as $recording) {
                if($recording->meetingID == $bigbb->meeting_id)
                {
                    foreach($recording->playback->format as $form)
                    {
                        if($form->type == 'presentation')
                        {
                            $urls = $form->url;
                        }
                    }
                }
            }
        }
        if($urls)
        {
            $output = array(
                'name' => $bigbb->name,
                'duration' => $bigbb->duration,
                'created_at'=> $bigbb->created_at,
                'link'=> $urls
              
            );
            return HelperController::api_response_format(200 ,$output, __('messages.virtual.record.list') );
        }
        return HelperController::api_response_format(200 , null , __('messages.virtual.record.no_records'));

    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request)
    {
        //Validating the Input
        $request->validate([
            'id'=>'required|exists:bigbluebutton_models,id',
        ]);
        $logs = AttendanceLog::where('session_id',$request->id)->where('type','online')->get();
        $bigbb=BigbluebuttonModel::find($request->id);
        LastAction::lastActionInCourse($bigbb->course_id);

        if(count($logs) > 0)
            return HelperController::api_response_format(404 , null , __('messages.error.cannot_delete'));
            
        $meet = BigbluebuttonModel::whereId($request->id)->first()->delete();
        return HelperController::api_response_format(200 , null , __('messages.virtual.delete'));
    }

    public function toggle (Request $request)
    {
        //Validating the Input
        $request->validate([
            'id'=>'required|exists:bigbluebutton_models,id',
        ]);

        $bigbb=BigbluebuttonModel::find($request->id);
        $bigbb->show = ($bigbb->show == 1)? 0 : 1;
        $bigbb->save();
        LastAction::lastActionInCourse($bigbb->course_id);

        return HelperController::api_response_format(200 , $bigbb ,  __('messages.success.toggle'));
    }

    public function getmeetingInfo(Request $request)
    {
        $request->validate([
            'id' => 'exists:bigbluebutton_models,id',
        ]);
        
        
        if($request->filled('id'))
        {
            $bigbb=BigbluebuttonModel::find($request->id);
            LastAction::lastActionInCourse($bigbb->course_id);
            $bbb = new BigBlueButton();
            $meet = BigbluebuttonModel::whereId($request->id)->first();

            $getMeetingInfoParams = new GetMeetingInfoParameters($meet->meeting_id, $meet->moderator_password);
            $response = $bbb->getMeetingInfo($getMeetingInfoParams);
            if ($response->getReturnCode() == 'FAILED') {
                return 'failed';
            } else {
                $getMeetingInfoParams = new GetMeetingInfoParameters($meet->meeting_id, $meet->moderator_password);
                $response = $bbb->getMeetingInfoURL($getMeetingInfoParams);
                $guzzleClient = new Client();
                $response = $guzzleClient->get($response);
                $body = $response->getBody();
                $body->seek(0);
                $response  = json_decode(json_encode(simplexml_load_string($response->getBody()->getContents())), true);
                return $response;
            }
        }
    }

    public function takeattendance(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:bigbluebutton_models,id',
        ]);

        self::clear();
        $bbb = new BigBlueButton();
        $meet = BigbluebuttonModel::whereId($request->id)->first();
        LastAction::lastActionInCourse($meet->course_id);
        $getMeetingInfoParams = new GetMeetingInfoParameters($meet->meeting_id, $meet->moderator_password);
        $response = $bbb->getMeetingInfo($getMeetingInfoParams);
        
        if ($response->getReturnCode() == 'FAILED') {
            return HelperController::api_response_format(200 , null , __('messages.error.not_found'));
        } 

        $meetings_ids = BigbluebuttonModel::where('meeting_id',$meet->meeting_id)->pluck('id');
        $response = $bbb->getMeetingInfoURL($getMeetingInfoParams);
        $guzzleClient = new Client();
        $response = $guzzleClient->get($response);
        $response  = json_decode(json_encode(simplexml_load_string($response->getBody()->getContents())), true);

        if(!isset($response['attendees']['attendee'][0]['userID'])){
            //for log event
            $logsbefore=AttendanceLog::whereIn('session_id',$meetings_ids)->where('type','online')->get();
            $all_attendees = AttendanceLog::whereIn('session_id',$meetings_ids)->where('type','online')->update([
                'taken_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'taker_id' => Auth::id(),
                'status' => 'Absent'
            ]);
            if($all_attendees > 0)
                event(new MassLogsEvent($logsbefore,'updated'));

            return HelperController::api_response_format(200 , null , __('messages.attendance_session.taken'));
        }

        //for log event
        $logsbefore=AttendanceLog::whereIn('session_id',$meetings_ids)->where('type','online')->get();
        $attendance_status=AttendanceLog::whereIn('session_id',$meetings_ids)->where('type','online')->update([
            'taken_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'taker_id' => Auth::id(),
            'status' => null
        ]);
        if($attendance_status > 0)
            event(new MassLogsEvent($logsbefore,'updated'));

        $students_id=collect();
        foreach($response['attendees']['attendee'] as $attend){
            $user=User::where('username',$attend['userID'])->first();
            if(isset($user)){
                $students_id->push($user->id);
                $attendance=AttendanceLog::where('student_id',$user->id)->whereIn('session_id',$meetings_ids)->where('type','online')->first();
                if(isset($attendance)){
                    $attendance->update([
                        'taken_at' => Carbon::now()->format('Y-m-d H:i:s'),
                        'taker_id' => Auth::id(),
                        'status' => 'Present'
                    ]);
                }
            }
        }

        $attendance_absent=AttendanceLog::where('status',null)->whereIn('session_id',$meetings_ids)->where('type','online')->whereNotIn('student_id',$students_id)->get()->unique('student_id');
        $absent_ids = $attendance_absent->pluck('id');

        //for log event
        $logsbefore=AttendanceLog::whereIn('id',$absent_ids)->get();
        $all_attendees = AttendanceLog::whereIn('id',$absent_ids)->update([
            'taken_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'taker_id' => Auth::id(),
            'status' => 'Absent'
        ]);
        if($all_attendees > 0)
            event(new MassLogsEvent($logsbefore,'updated'));

        return HelperController::api_response_format(200 , null , __('messages.attendance_session.taken'));

    }


    public function viewAttendence(Request $request,$call = 0)
    {
        $request->validate([
            'id' => 'required|exists:bigbluebutton_models,id',
        ]);

        $meeting = BigbluebuttonModel::whereId($request->id)->first();
        LastAction::lastActionInCourse($meeting->course_id);
        $all_logs=AttendanceLog::where('session_id',$request->id)->where('type','online')->with('User')->get()->groupBy('student_id');
        $absent_present=AttendanceLog::where('session_id',$request->id)->where('type','online')->with('User')->get()->unique('student_id');
        $attendance_log['Total_Logs'] = $all_logs->count();
        $attendance_log['Present']['count']= $absent_present->where('status','Present')->count();
        $attendance_log['Absent']['count']= $absent_present->where('status','Absent')->count();
        $attendance_log['Present']['precentage'] = 0;
        $attendance_log['Absent']['precentage'] =  0;
        if($all_logs->count() != 0)
        {
            $attendance_log['Present']['precentage'] = round(($attendance_log['Present']['count']/$all_logs->count())*100,2) ;
            $attendance_log['Absent']['precentage'] =  round(($attendance_log['Absent']['count']/$all_logs->count())*100,2) ;
        }

        $final_logs=collect();
        foreach($all_logs as $logs){
            $logs_time=collect();
            $diffrence = 0;
            foreach($logs as $log){
                if(isset($log['entered_date']) && isset($log['left_date'])){
                    $check_exist = $logs_time->where('entered_date',$log['entered_date'])->where('left_date',$log['left_date']);
                    if(count($check_exist) == 0){
                        $enter = Carbon::parse($log['entered_date']);
                        $left = Carbon::parse($log['left_date']);
                        $diffrence = $diffrence +  $left->floatDiffInMinutes($enter);
                        $diffrence = round($diffrence,0);
                        $logs_time->push([
                            'entered_date' => $log['entered_date'],
                            'left_date' => $log['left_date']
                        ]);
                    }
                }
            }

            $first_login=null;
            $meeting_start = isset($meeting->actutal_start_date) ? $meeting->actutal_start_date : $meeting->start_date;
            if(isset($logs[0]['entered_date']))
                $first_login = Carbon::parse($logs[0]['entered_date'])->diffInMinutes(Carbon::parse($meeting_start));

            $last_logout=null;
            if(isset($logs[count($logs)-1]['left_date']))
                $last_logout = Carbon::parse($meeting_start)->addMinutes($meeting->duration)->diffInMinutes(Carbon::parse($logs[count($logs)-1]['left_date']));

            $duration_percentage = 0;
            if($meeting->duration != 0)
                $duration_percentage = round(($diffrence/$meeting->duration)*100,2);

            $final_logs->push([
                'username' => $logs[0]['User']['username'],
                'fullname' => $logs[0]['User']['fullname'],
                'attend_duration' => $diffrence,
                'duration_percentage' => $duration_percentage . ' %',
                'first_login' => isset($first_login)? $first_login : null,
                'last_logout' => isset($last_logout)? $last_logout : null,
                'log_times' => $logs_time,
                'status' => $logs[0]['status'],
            ]);
        }
        $attendance_log['logs'] = $final_logs;

        if($call == 1)
            return $attendance_log;
        return HelperController::api_response_format(200 , $attendance_log , __('messages.virtual.attendnace.list'));
    }


    public function export(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:bigbluebutton_models,id',
        ]); 
        
        $bbb_object = self::viewAttendence($request,1);
        $bigbb=BigbluebuttonModel::find($request->id);
        LastAction::lastActionInCourse($bigbb->course_id);
        $filename = uniqid();
        $file = Excel::store(new BigBlueButtonAttendance($bbb_object), 'bbb'.$filename.'.xls','public');
        $file = url(Storage::url('bbb'.$filename.'.xls'));
        return HelperController::api_response_format(201,$file, __('messages.success.link_to_file'));
    }

    public function clear(){
        //change peemission bootsttap directory l 775 if it didn't work on server
        // \Artisan::call('config:cache', ['--env' => 'local']);
        \Artisan::call('cache:clear', ['--env' => 'local']);
        \Artisan::call('config:clear', ['--env' => 'local']);
    }

    public function callback_function(Request $request){
        try {
            $arr=[];
            $arr=json_decode($request['event'],true);
    
            $found=BigbluebuttonModel::where('meeting_id',$arr[0]['data']['attributes']['meeting']['external-meeting-id'])->get();
            $meetings_ids = $found->pluck('id');
            if(count($found) > 0 && Carbon::parse($found[0]->start_date)->format('Y-m-d H:i:s') <= Carbon::now()->format('Y-m-d H:i:s')){
    
                if($arr[0]['data']['id'] == 'meeting-created'){
                    BigbluebuttonModel::whereIn('id',$meetings_ids)->update([
                        'started' => 1,
                        'status' => 'current',
                        'actutal_start_date' => Carbon::now()
                    ]);
                }
                    
                if($arr[0]['data']['id'] == 'user-joined'){
                    Log::debug($arr[0]['data']['id']);
                    Log::debug($arr[0]['data']['attributes']['meeting']['external-meeting-id']);
                    Log::debug($arr[0]['data']['attributes']['user']['external-user-id']);
    
                    $user_id = User::where('username',$arr[0]['data']['attributes']['user']['external-user-id'])->pluck('id')->first();
                    $log = AttendanceLog::whereIn('session_id',$meetings_ids)
                                        ->where('type','online')
                                        ->where('student_id',$user_id)->first();
                    if(isset($log)){
                        $attendance = AttendanceLog::updateOrCreate(['student_id' => $user_id,'session_id'=> $log->session_id,'type'=>'online','entered_date'=> null],
                        [
                            'ip_address' => \Request::ip(),
                            'student_id' => $user_id,
                            'session_id' => $log->session_id,
                            'taken_at' => Carbon::now()->format('Y-m-d H:i:s'),
                            'type' => 'online',
                            'entered_date' => Carbon::now()->format('Y-m-d H:i:s'),
                            'taker_id' => $found[0]->user_id
                        ]);
                    }
                }
                
                if($arr[0]['data']['id'] == 'user-left'){
                    Log::debug($arr[0]['data']['id']);
                    Log::debug($arr[0]['data']['attributes']['meeting']['external-meeting-id']);
                    Log::debug($arr[0]['data']['attributes']['user']['external-user-id']);
    
                    $user_id = User::where('username',$arr[0]['data']['attributes']['user']['external-user-id'])->pluck('id')->first();
                    $log = AttendanceLog::whereIn('session_id',$meetings_ids)
                                        ->where('type','online')
                                        ->where('student_id',$user_id)
                                        ->where('entered_date','!=',null)
                                        ->where('left_date',null)->first();
                    if(isset($log))
                        $log->update(['left_date' => Carbon::now()->format('Y-m-d H:i:s')]);
                }
        
                if($arr[0]['data']['id'] == 'meeting-ended'){
                    Log::debug($arr[0]['data']['id']);
                    $log = AttendanceLog::whereIn('session_id',$meetings_ids)
                                        ->where('type','online')
                                        ->where('entered_date','!=',null)
                                        ->where('left_date',null)->update([
                                            'left_date' => Carbon::now()->format('Y-m-d H:i:s')
                                        ]);
    
                    $meeting_start = isset($found[0]->actutal_start_date) ? $found[0]->actutal_start_date : $found[0]->start_date;
                    $start = Carbon::parse($meeting_start);
                    $end = Carbon::now();
                    $duration= $end->diffInMinutes($start);
                    BigbluebuttonModel::whereIn('id',$meetings_ids)->update([
                        'duration' => $duration,
                        'started' => 0,
                        'status' => 'past',
                        'actual_end_date' => Carbon::now()
                    ]);   
                }
            }

        } catch(\Exception $e) {

           Log::debug( $e->getMessage());
           return HelperController::api_response_format(200,null, 'success');
        }
    }

    public function create_hook(Request $request){
        
        // $hookParameter = new HooksCreateParameters("https://webhook.site/3fb81c64-5b58-4513-9fa3-622a9f7b17ea");
        $bbb = new BigBlueButton();
        $url= substr($request->url(), 0, strpos($request->url(), "/api"));
        $hookParameter = new HooksCreateParameters($url."/api/callback_function");
        $hookRes = $bbb->hooksCreate($hookParameter);
        return $hookRes->getHookId();
    }

    public function destroy_hook(Request $request){
        $bbb = new BigBlueButton();
        $hookdestroypar = new HooksDestroyParameters($request->id);
        $req = $bbb->hooksDestroy($hookdestroypar);
        return 'Destroyed';
    }

    public function list_hook(Request $request){
        $bbb = new BigBlueButton();
        $req=$bbb->getHooksListUrl();
        return $req;
    }

    public function refresh_meetings(Request $request){
        $bbb = new BigBlueButton();
        $response = $bbb->getMeetings();
        $current_meetings = collect();
        if ($response->getReturnCode() == 'SUCCESS') {
            foreach ($response->getRawXml()->meetings->meeting as $meeting) {
                $current_meetings->push($meeting->meetingID);
            }
        }

        //for log event
        $logsbefore=BigbluebuttonModel::whereIn('meeting_id',$current_meetings)->where('started',0)->where('status','future')->get();
        $meeting = BigbluebuttonModel::whereIn('meeting_id',$current_meetings)->where('started',0)->where('status','future')->update([
            'started' => 1,
            'status' => 'current',
            'actutal_start_date' => Carbon::now()
        ]);
        if($meeting > 0)
            event(new MassLogsEvent($logsbefore,'updated'));

        return HelperController::api_response_format(200 , $meeting , 'Classrooms refreshed successfully');
    }

    public function refresh_records(Request $request){
        $bbb = new BigBlueButton();
        $records_meetings = collect();
        
        $recordingParams = new GetRecordingsParameters();
        $response = $bbb->getRecordings($recordingParams);
        if ($response->getReturnCode() == 'SUCCESS') {
            foreach ($response->getRawXml()->recordings->recording as $recording) {

                $meetings = BigbluebuttonModel::where('meeting_id',$recording->meetingID)->whereNull('actutal_start_date')->get();

                if(count($meetings) > 0){
                    foreach($meetings as $meeting){
                        $meeting->update([
                            'actutal_start_date' => $meeting->start_date
                        ]);
                        $records_meetings->push($meeting);
                    }
                }
            }
        }

        return HelperController::api_response_format(200 , $records_meetings , 'Classrooms refreshed successfully');
    }

    public function close_meetings(Request $request){
        $bbb = new BigBlueButton();
        $response = $bbb->getMeetings();
        $current_meetings = collect();
        if ($response->getReturnCode() == 'SUCCESS') {
            foreach ($response->getRawXml()->meetings->meeting as $meeting) {
                $current_meetings->push($meeting->meetingID);
            }
        }

        $meeting = BigbluebuttonModel::whereNotIn('meeting_id',$current_meetings)->where('started',1)->where('status','current')->update([
            'started' => 0,
            'status' => 'past',
            'actual_end_date' => Carbon::now()
        ]);

        return HelperController::api_response_format(200 , $meeting , 'Classrooms closed successfully');
    }

    public function logs_meetings(Request $request){
        $present_logs = AttendanceLog::where('status','Present')->where('entered_date',null)->where('type','online')->get();
        foreach($present_logs as $log){

            $meetings=BigbluebuttonModel::where('id',$log->session_id)->first();
            $log->entered_date = $log->taken_at;
            $log->left_date = Carbon::parse($meetings->start_date)->addMinutes($meetings->duration)->format('Y-m-d H:i:s');
            $log->save();
        }
        
        return HelperController::api_response_format(200 , $present_logs , 'logs edited successfully');
    }

    public function general_report (Request $request, $call =0){

        $request->validate([
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
            'level' => 'exists:levels,id',
            'class' => 'exists:classes,id',
            'segment' => 'exists:segments,id',
            'courses'    => 'array',
            'courses.*'    => 'exists:courses,id',
        ]); 

        $CourseSeg=GradeCategoryController::getCourseSegment($request);

        $courses=CourseSegment::whereIn('id',$CourseSeg)->where('end_date','>',Carbon::now())
                                                        ->where('start_date','<',Carbon::now())
                                                        ->pluck('course_id')->unique()->values();

        $meetings = BigbluebuttonModel::whereIn('course_id',$courses)->orderBy('start_date','asc')->get();

        $report=collect();
        foreach($meetings as $meeting){
            $user = User::find($meeting->user_id);
            $course = Course::find($meeting->course_id);
            $class = Classes::find($meeting->class_id);
            $students = Enroll::where('class',$meeting->class_id)->where('course',$meeting->course_id)->where('role_id',3)->count();
            $present_student = AttendanceLog::where('session_id',$meeting->id)->where('type','online')
                                                                              ->whereNotNull('entered_date')
                                                                              ->select('student_id')->distinct()->count();
            $end_date = null;
            if(isset($meeting->actual_duration))
                $end_date = Carbon::parse($meeting->start_date)->addMinutes($meeting->actual_duration);

            $actutal_start_date = Carbon::parse($meeting->start_date);
            if(isset($meeting->actutal_start_date))
                $actutal_start_date = Carbon::parse($meeting->actutal_start_date);

            $actual_end_date = null;
            if(isset($meeting->actual_end_date))
                $actual_end_date = Carbon::parse($meeting->actual_end_date);


            $report->push([
                'creator_name' => isset($user) ? $user->fullname : null,
                'course' => isset($course) ? $course->name : null,
                'class' => isset($class) ? $class->name : null,
                'session_name' => $meeting->name,
                'students_number' => $students,
                'present_students' => $present_student,
                'absent_students' => $students - $present_student,
                'start_date' => $meeting->start_date,
                'actutal_start_date' => $meeting->actutal_start_date,
                'start_delay' => isset($meeting->actutal_start_date) ? round($actutal_start_date->diffInMinutes(Carbon::parse($meeting->start_date)),0) : null ,
                'end_date' => isset($end_date) ? $end_date->format('Y-m-d H:i:s') : null ,
                'actual_end_date' => isset($meeting->actual_end_date) ? $actual_end_date->format('Y-m-d H:i:s') : null ,
                'end_delay' => isset($end_date) && isset($actual_end_date) ? round($end_date->diffInMinutes($actual_end_date),0) : null ,
            ]);

        }
        
        if($call == 1)
            return $report;

        return HelperController::api_response_format(200 , $report , 'virtual classroom general report');
    }

    public function export_general_report(Request $request)
    {        
        $bbb_object = self::general_report($request,1);
        $filename = uniqid();
        $file = Excel::store(new BigbluebuttonGeneralReport($bbb_object), 'bbbgeneral'.$filename.'.xls','public');
        $file = url(Storage::url('bbbgeneral'.$filename.'.xls'));
        return HelperController::api_response_format(201,$file, __('messages.success.link_to_file'));
    }
    
    public function Script_type()
    {
        $allBBB=BigbluebuttonModel::whereNull('type')->update(['type' => 'BBB']);
        $allBBB=BigbluebuttonModel::whereNull('attendee_password')->where('type','BBB')->update([
            'attendee_password' => '2468',
            'moderator_password' => '1234'
            ]);
        return 'Done';
    }
}
