<?php

namespace Modules\Bigbluebutton\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use BigBlueButton\BigBlueButton;
use App\Component;
use App\User;
use App\Enroll;
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
use App\Http\Controllers\HelperController;
use DB;
use GuzzleHttp\Client;
use App\Exports\BigBlueButtonAttendance;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\GradeCategoryController;
use Illuminate\Support\Str;
use App\Classes;

class BigbluebuttonController extends Controller
{

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
            'attendee_password' => 'required|string|different:moderator_password',
            'moderator_password' => 'required|string',
            'duration' => 'nullable',
            'is_recorded' => 'required|bool',
            'start_date' => 'required|array',
        ]);

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
                $course_segments_ids=collect();
                $meeting_id = 'Learnovia'.env('DB_DATABASE').uniqid();
                foreach($object['class_id'] as $class){
                    $i=0;
                    $courseseg = CourseSegment::GetWithClassAndCourse($class,$object['course_id']);
                    if(isset($courseseg))
                        $course_segments_ids->push($courseseg->id);

                    if(count($course_segments_ids) <= 0)
                        return HelperController::api_response_format(200, null ,'Please check active course segments');
            
                    $usersIDs=Enroll::whereIn('course_segment',$course_segments_ids)->pluck('user_id')->unique()->values()->toarray();
                    foreach($request->start_date as $start_date){
                        $last_date = $start_date;
                        if(isset($request->last_day)){
                            $last_date= $request->last_day;
                        }
            
                        $temp_start = Carbon::parse($start_date);
                        while(Carbon::parse($temp_start)->format('Y-m-d H:i:s') <= Carbon::parse($last_date)->format('Y-m-d H:i:s')){
                            $bigbb = new BigbluebuttonModel;
                            $bigbb->name=$request->name;
                            $bigbb->class_id=$class;
                            $bigbb->course_id=$object['course_id'];
                            $bigbb->attendee_password=$attendee;
                            $bigbb->moderator_password=$request->moderator_password;
                            $bigbb->duration=$duration;
                            $bigbb->start_date=$temp_start->format('Y-m-d H:i:s');
                            $bigbb->meeting_id = $i == 0 ? $meeting_id : $meeting_id.'repeat'.$i;
                            $bigbb->user_id = Auth::user()->id;
                            $bigbb->is_recorded = $request->is_recorded;
                            $bigbb->started = 0;
                            $bigbb->save();

                            $bigbb['join'] = $bigbb->started == 1 ? true: false;

                            if(Carbon::parse($temp_start)->format('Y-m-d H:i:s') <= Carbon::now()->format('Y-m-d H:i:s') && Carbon::now()->format('Y-m-d H:i:s') <= Carbon::parse($temp_start)
                            ->addMinutes($request->duration)->format('Y-m-d H:i:s'))
                            {
                                self::clear();
                                self::create_hook($request);     
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
        return HelperController::api_response_format(200, $created_meetings ,'Class room created Successfully');
    }

    public function get_meetings()
    {
        $meetings=BigbluebuttonModel::where('start_date','<=', Carbon::now())->get();
        return HelperController::api_response_format(200, $meetings ,'all meetings');
    }

    public function start_meeting(Request $request)
    {
        $request->validate([
            'id'=>'required|exists:bigbluebutton_models,id',
        ]);

        $bigbb=BigbluebuttonModel::find($request->id);

        //Creating the meeting
        $bbb = new BigBlueButton();
        $createMeetingParams = new CreateMeetingParameters($bigbb->meeting_id, $bigbb->name);
        $createMeetingParams->setAttendeePassword($bigbb->attendee_password);
        $createMeetingParams->setModeratorPassword($bigbb->moderator_password);
        $createMeetingParams->setDuration($bigbb->duration);
        // $createMeetingParams->setRedirect(false);
        $createMeetingParams->setLogoutUrl('https://learnovia.com/');
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
                return HelperController::api_response_format(200, null ,'Please check active course segments');
    
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
        self::create_hook($request);
        $bbb = new BigBlueButton();

        //Validating the Input
        $request->validate([
            'id'=>'required|exists:bigbluebutton_models,id',
        ]);

        $bigbb=BigbluebuttonModel::find($request->id);
        $meeting_start = isset($bigbb->actutal_start_date) ? $bigbb->actutal_start_date : $bigbb->start_date;
        $check=Carbon::parse($meeting_start)->addMinutes($bigbb->duration);

        if(($check < Carbon::now()) || (!$request->user()->can('bigbluebutton/session-moderator') && $bigbb->started == 0))
            return HelperController::api_response_format(200,null ,'you can\'t join this classroom');

        if($request->user()->can('bigbluebutton/session-moderator') && $bigbb->started == 0){
            $start_meeting = self::start_meeting($request);
            if(!$start_meeting)
                return HelperController::api_response_format(200, [],'Sorry, there is a problem while starting classroom.');
        }
            
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

        $output = array(
            'name' => $bigbb->name,
            'duration' => $bigbb->duration,
            'link'=> $url
        );
        return HelperController::api_response_format(200, $output,'Joining class room...');
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function get(Request $request)
    {
        $request->validate([
            'id' => 'exists:bigbluebutton_models,id',
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
            'level' => 'exists:levels,id',
            'class' => 'exists:classes,id',
            'segment' => 'exists:segments,id',
            'course'    => 'exists:courses,id',
            'status'    => 'in:past,future,current',
            'start_date' => 'date',
        ]);

        $classes = [];
        if(isset($request->class)){
            $classes = [$request->class];
        }

        if(isset($request->course)){
            $request['courses']= [$request->course];
        }

        self::clear(); 
        $CS_ids=GradeCategoryController::getCourseSegment($request);
        $CourseSeg = Enroll::where('user_id', Auth::id())->pluck('course_segment');
        $CourseSeg = array_intersect($CS_ids->toArray(),$CourseSeg->toArray());
        if($request->user()->can('site/show-all-courses')){
            $CourseSeg = $CS_ids;
            $classes = count($classes) == 0? Classes::pluck('id') : $classes;
        }
        $classes = count($classes) == 0 ? Enroll::where('user_id', Auth::id())->pluck('class') : $classes;
        
        $courses=CourseSegment::whereIn('id',$CourseSeg)->where('end_date','>',Carbon::now())
                                                        ->where('start_date','<',Carbon::now())
                                                        ->pluck('course_id')->unique()->values();

        $meeting = BigbluebuttonModel::whereIn('course_id',$courses)->whereIn('class_id',$classes)->orderBy('start_date');

        if($request->user()->can('site/course/student'))
            $meeting->where('show',1);
            
        if($request->has('status'))
            $meeting->where('status',$request->status);

        if($request->has('start_date')){
            $to = Carbon::parse($request->start_date)->addMinutes(1);
            $meeting->where('start_date', '>=', $request->start_date)->where('start_date','<',$to);
        }

        if($request->has('id'))
            $meeting->where('id',$request->id);

        $meetings = $meeting->get();
        foreach($meetings as $m)
            {
                $m['join'] = $m->started == 1 ? true: false;
                if(Carbon::parse($m->start_date)->format('Y-m-d H:i:s') <= Carbon::now()->format('Y-m-d H:i:s') && Carbon::now()->format('Y-m-d H:i:s') <= Carbon::parse($m->start_date)
                ->addMinutes($m->duration)->format('Y-m-d H:i:s'))
                {
                    self::create_hook($request);
                    if($request->user()->can('bigbluebutton/session-moderator') && $m->started == 0)
                        $m['join'] = true; //startmeeting has arrived but meeting didn't start yet
                }
            }

        if(count($meetings) == 0)
            return HelperController::api_response_format(200 , [] , 'Classroom is not found');
        return HelperController::api_response_format(200 , $meetings,'Classrooms list');
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
            return HelperController::api_response_format(200 ,$output, 'Here is Record Found' );
        }
        return HelperController::api_response_format(200 , null , 'No Records Found!');

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
        if(count($logs) > 0)
            return HelperController::api_response_format(404 , null , 'This Class room has students logs, cannot be deleted!');
            
        $meet = BigbluebuttonModel::whereId($request->id)->delete();
        return HelperController::api_response_format(200 , null , 'Class room deleted!');
    }

    public function toggle (Request $request)
    {
        //Validating the Input
        $request->validate([
            'id'=>'required|exists:bigbluebutton_models,id',
        ]);
        $bigbb=BigbluebuttonModel::find($request->id);

        if($bigbb->show == 1){
            BigbluebuttonModel::where('id',$request->id)->update(['show' => 0]);
        }
        else{
            BigbluebuttonModel::where('id',$request->id)->update(['show' => 1]);
        }

        $b=BigbluebuttonModel::find($request->id);

        return HelperController::api_response_format(200 , $b , 'Toggled!');
    }

    public function getmeetingInfo(Request $request)
    {
        $request->validate([
            'id' => 'exists:bigbluebutton_models,id',
        ]);
        
        if($request->filled('id'))
        {
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
        $getMeetingInfoParams = new GetMeetingInfoParameters($meet->meeting_id, $meet->moderator_password);
        $response = $bbb->getMeetingInfo($getMeetingInfoParams);
        
        if ($response->getReturnCode() == 'FAILED') {
            return HelperController::api_response_format(200 , null , 'This meeting not found we cant find attendees!');
        } 

        $meetings_ids = BigbluebuttonModel::where('meeting_id',$meet->meeting_id)->pluck('id');
        $response = $bbb->getMeetingInfoURL($getMeetingInfoParams);
        $guzzleClient = new Client();
        $response = $guzzleClient->get($response);
        $response  = json_decode(json_encode(simplexml_load_string($response->getBody()->getContents())), true);

        if(!isset($response['attendees']['attendee'][0]['userID'])){
            $all_attendees = AttendanceLog::whereIn('session_id',$meetings_ids)->where('type','online')->update([
                'taken_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'taker_id' => Auth::id(),
                'status' => 'Absent'
            ]);

            return HelperController::api_response_format(200 , null , 'Attendance taken successfully!');
        }

        $attendance_status=AttendanceLog::whereIn('session_id',$meetings_ids)->where('type','online')->update([
            'taken_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'taker_id' => Auth::id(),
            'status' => null
        ]);

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
        AttendanceLog::whereIn('id',$absent_ids)->update([
            'taken_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'taker_id' => Auth::id(),
            'status' => 'Absent'
        ]);

        return HelperController::api_response_format(200 , null , 'Attendance taken successfully!');

    }


    public function viewAttendence(Request $request,$call = 0)
    {
        $request->validate([
            'id' => 'required|exists:bigbluebutton_models,id',
        ]);

        $meeting = BigbluebuttonModel::whereId($request->id)->first();
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
                    $enter = Carbon::parse($log['entered_date']);
                    $left = Carbon::parse($log['left_date']);
                    $diffrence = $diffrence +  $left->diffInMinutes($enter);
                    $logs_time->push([
                        'entered_date' => $log['entered_date'],
                        'left_date' => $log['left_date']
                    ]);
                }
            }

            $first_login=null;
            $meeting_start = isset($meeting->actutal_start_date) ? $meeting->actutal_start_date : $meeting->start_date;
            if(isset($logs[0]['entered_date']))
                $first_login = Carbon::parse($logs[0]['entered_date'])->diffInMinutes(Carbon::parse($meeting_start));

            $last_logout=null;
            if(isset($logs[count($logs)-1]['left_date']))
                $last_logout = Carbon::parse($meeting_start)->addMinutes($meeting->duration)->diffInMinutes(Carbon::parse($logs[count($logs)-1]['left_date']));

            $final_logs->push([
                'username' => $logs[0]['User']['username'],
                'fullname' => $logs[0]['User']['fullname'],
                'attend_duration' => $diffrence . ' Minute/s',
                'duration_percentage' => round(($diffrence/$meeting->duration)*100,2) . ' %',
                'first_login' => isset($first_login)? $first_login . ' Minute/s' : null,
                'last_logout' => isset($last_logout)? $last_logout . ' Minute/s' : null,
                'log_times' => $logs_time,
                'status' => $logs[0]['status'],
            ]);
        }
        $attendance_log['logs'] = $final_logs;

        if($call == 1)
            return $attendance_log;
        return HelperController::api_response_format(200 , $attendance_log , 'Attendance records');
    }


    public function export(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:bigbluebutton_models,id',
        ]); 

        $bbb_object = self::viewAttendence($request,1);
        $filename = uniqid();
        $file = Excel::store(new BigBlueButtonAttendance($bbb_object), 'bbb'.$filename.'.xls','public');
        $file = url(Storage::url('bbb'.$filename.'.xls'));
        return HelperController::api_response_format(201,$file, 'Link to file ....');
    }

    public function clear(){
        //change peemission bootsttap directory l 775 if it didn't work on server
        // \Artisan::call('config:cache', ['--env' => 'local']);
        \Artisan::call('cache:clear', ['--env' => 'local']);
        \Artisan::call('config:clear', ['--env' => 'local']);
    }

    public function callback_function(Request $request){
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
                ]);
            }
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
}
