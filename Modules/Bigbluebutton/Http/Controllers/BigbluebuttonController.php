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
use App\CourseSegment;
use Modules\Attendance\Entities\AttendanceLog;
use BigBlueButton\Parameters\CreateMeetingParameters;
use BigBlueButton\Parameters\JoinMeetingParameters;
use BigBlueButton\Parameters\GetRecordingsParameters;
use Modules\Bigbluebutton\Entities\BigbluebuttonModel;
use BigBlueButton\Parameters\GetMeetingInfoParameters;
use Illuminate\Support\Carbon;
use App\Http\Controllers\HelperController;
use DB;
use GuzzleHttp\Client;
use App\Exports\BigBlueButtonAttendance;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;


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
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'bigbluebutton/get-all','title' => 'get meetings']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'bigbluebutton/getRecord','title' => 'get Record']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'bigbluebutton/delete','title' => 'Delete Record']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'bigbluebutton/toggle','title' => 'Toggle Record']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'bigbluebutton/attendance','title' => 'Bigbluebutton Attendance']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'bigbluebutton/get-attendance','title' => 'Bigbluebutton get Attendance']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'bigbluebutton/export','title' => 'Bigbluebutton Export Attendance']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'bigbluebutton/get-all','title' => 'Bigbluebutton Get All']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'bigbluebutton/session-moderator','title' => 'Bigbluebutton session moderator']);

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
        $role->givePermissionTo('bigbluebutton/get-all');
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
            'object.*.class_id' => 'required|exists:classes,id',
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
                $courseseg=CourseSegment::GetWithClassAndCourse($object['class_id'],$object['course_id']);
                if(!isset($courseseg))
                    return HelperController::api_response_format(200, null ,'Please check active course segments');
        
                $usersIDs=Enroll::where('course_segment',$courseseg->id)->pluck('user_id')->toarray();
               
                foreach($request->start_date as $start_date){
                    $last_date = $start_date;
                    if(isset($request->last_day)){
                        $last_date= $request->last_day;
                    }
        
                    $temp_start = Carbon::parse($start_date);
                    while(Carbon::parse($temp_start)->format('Y-m-d H:i:s') <= Carbon::parse($last_date)->format('Y-m-d H:i:s')){
                        $bigbb = new BigbluebuttonModel;
                        $bigbb->name=$request->name;
                        $bigbb->class_id=$object['class_id'];
                        $bigbb->course_id=$object['course_id'];
                        $bigbb->attendee_password=$attendee;
                        $bigbb->moderator_password=$request->moderator_password;
                        $bigbb->duration=$duration;
                        $bigbb->start_date=$temp_start->format('Y-m-d H:i:s');
                        $bigbb->user_id = Auth::user()->id;
                        $bigbb->save();
                        $bigbb['join'] = false;
        
                        $req = new Request([
                            'duration' => $request->duration,
                            'attendee' =>$attendee,
                            'id' => $bigbb->id,
                            'name' => $request->name,
                            'moderator_password' => $request->moderator_password,
                            'is_recorded' => $request->is_recorded,
                            'join' => $bigbb['join']
                        ]);
                
                        if(Carbon::parse($temp_start)->format('Y-m-d H:i:s') <= Carbon::now()->format('Y-m-d H:i:s') && Carbon::now()->format('Y-m-d H:i:s') <= Carbon::parse($temp_start)
                        ->addMinutes($request->duration)->format('Y-m-d H:i:s'))
                        {                            
                            $check =self::start_meeting($req);
                            if($check)
                                $bigbb['join'] = true;
                        }
                
                        User::notify([
                            'id' => $bigbb->id,
                            'message' => $request->name.' meeting is created',
                            'from' => Auth::user()->id,
                            'users' => $usersIDs,
                            'course_id' => $object['course_id'],
                            'class_id'=>$object['class_id'],
                            'lesson_id'=> null,
                            'type' => 'meeting',
                            'link' => url(route('getmeeting')) . '?id=' . $bigbb->id,
                            'publish_date'=> $temp_start
                        ]);
                        $created_meetings->push($bigbb);
                        $temp_start= Carbon::parse($temp_start)->addDays(7);
                    }
                }
            }
        }
        return HelperController::api_response_format(200, $created_meetings ,'Meeting created Successfully');
    }

    public function get_meetings()
    {
        $meetings=BigbluebuttonModel::where('start_date','<=', Carbon::now())->get();
        return HelperController::api_response_format(200, $meetings ,'all meetings');
    }

    public function start_meeting($request)
    {
        //Creating the meeting
        $bbb = new BigBlueButton();

        $bbb->getJSessionId();

     
        $createMeetingParams = new CreateMeetingParameters($request['id'], $request['name']);
        $createMeetingParams->setAttendeePassword($request['attendee']);
        $createMeetingParams->setModeratorPassword($request['moderator_password']);
        $createMeetingParams->setDuration($request['duration']);
        // $createMeetingParams->setRedirect(false);
        $createMeetingParams->setLogoutUrl('https://dev.learnovia.com');
        if($request['is_recorded'] == 1){
            $createMeetingParams->setRecord(true);
            $createMeetingParams->setAllowStartStopRecording(true);
            $createMeetingParams->setAutoStartRecording(true);
        }
        $response = $bbb->createMeeting($createMeetingParams);
        if ($response->getReturnCode() == 'FAILED') {
            return 'Can\'t create room! please contact our administrator.';
        } else {
            // moderator join the meeting
            $joinMeetingParams = new JoinMeetingParameters($request['id'], Auth::user()->username , $request->moderator_password);
            $joinMeetingParams->setRedirect(true);
            $joinMeetingParams->setJoinViaHtml5(true);
            $url = $bbb->getJoinMeetingURL($joinMeetingParams);

            if($request->is_recorded == 1){
                $createrecordParams = new GetRecordingsParameters();
                $createrecordParams->setMeetingId($request['id']);
                $createrecordParams->setRecordId($request['id']);
                $createrecordParams->setState(true);
                $res= $bbb->getRecordings($createrecordParams);
            }

            $output = array(
                'name' => $request['name'],
                'duration' => $request['duration'],
                'link'=> $url,
            );

            $final_out = BigbluebuttonModel::find($request['id']);
            $courseseg=CourseSegment::GetWithClassAndCourse($final_out->class_id,$final_out->course_id);
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
                            ->where('session_id',$final_out->id)->where('type','online')->first();

                    if(!isset($founded)){
                        $attendance=AttendanceLog::create([
                            'ip_address' => \Request::ip(),
                            'student_id' => $user,
                            'taker_id' => $final_out->user_id,
                            'session_id' => $final_out->id,
                            'type' => 'online',
                            'taken_at' => Carbon::now()->format('Y-m-d H:i:s')
                        ]);
                    }
                }
            }
            $getMeetingInfoParams = new GetMeetingInfoParameters($request['id'], '', $request['moderator_password']);
            $response = $bbb->getMeetingInfo($getMeetingInfoParams);
            if ($response->getReturnCode() == 'FAILED') {
                $request['join'] = false;
            } else {
                $request['join'] = true;
            }

            return 1;
        }
    }

    //Join the meeting
    public function join(Request $request)
    {
        $bbb = new BigBlueButton();

        //Validating the Input
        $request->validate([
            'id'=>'required|exists:bigbluebutton_models,id',
        ]);

        $user_name = Auth::user()->username;
        $bigbb=BigbluebuttonModel::find($request->id);
        if($request->user()->can('bigbluebutton/session-moderator')){
            $joinMeetingParams = new JoinMeetingParameters($request->id, $user_name, $bigbb->moderator_password);
        }else{
            $attendance=AttendanceLog::where('student_id',Auth::id())->where('session_id',$bigbb->id)->where('type','online')->update([
                'ip_address' => \Request::ip(),
                'taken_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'entered_date' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
            $joinMeetingParams = new JoinMeetingParameters($request->id, $user_name, $bigbb->attendee_password);
        }
        $joinMeetingParams->setRedirect(true);
        $joinMeetingParams->setJoinViaHtml5(true);
        $url = $bbb->getJoinMeetingURL($joinMeetingParams);

        $output = array(
            'name' => $bigbb->name,
            'duration' => $bigbb->duration,
            'link'=> $url
        );
        return HelperController::api_response_format(200, $output,'Join The Meeting');
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
            'class'=> 'exists:bigbluebutton_models,class_id|required_with:course',
            'course'=> 'exists:bigbluebutton_models,course_id|required_with:class',
        ]);

        $user_id = Auth::user()->id;
        $role_id = DB::table('model_has_roles')->where('model_id',$user_id)->pluck('role_id')->first();
        $permission_id = DB::table('permissions')->where('name','bigbluebutton/toggle')->pluck('id')->first();
        $hasornot = DB::table('role_has_permissions')->where('role_id', $role_id)->where('permission_id', $permission_id)->get();


        if($request->filled('id'))
        {
            $bbb = new BigBlueButton();
            $meet = BigbluebuttonModel::whereId($request->id)->first();
            $meet['join'] = false;

            $req = new Request([
                'duration' => $meet->duration,
                'attendee' =>$meet->attendee,
                'id' => $meet->id,
                'name' => $meet->name,
                'moderator_password' => $meet->moderator_password,
                'is_recorded' => $meet->is_recorded
            ]);
            // dd($meet->start_date);
            if(Carbon::parse($meet->start_date)->format('Y-m-d H:i:s') <= Carbon::now()->format('Y-m-d H:i:s') && Carbon::now()->format('Y-m-d H:i:s') <= Carbon::parse($meet->start_date)
            ->addMinutes($meet->duration)->format('Y-m-d H:i:s'))
            {
                $check =self::start_meeting($req);
                if($check)
                    $meet['join'] = true;
            }
            $getMeetingInfoParams = new GetMeetingInfoParameters($request->id, '', $meet->moderator_password);
            $response = $bbb->getMeetingInfo($getMeetingInfoParams);
            if ($response->getReturnCode() == 'FAILED') {
                $meet['join'] = false;
            }
            
            if(count($hasornot) > 0 )
            {
                $meet['student_view']=$meet['show'];
                $meet['show']=1;
            }

            if($meet == null)
                return HelperController::api_response_format(200 , null , 'This Meeting is not found');
            return HelperController::api_response_format(200 , $meet);
        }
        if($request->filled('course') && $request->filled('class'))
        {
            $bbb = new BigBlueButton();
            // $meet = BigbluebuttonModel::whereId($request->id)->first();
            $meet = BigbluebuttonModel::where('class_id',$request->class)->where('course_id',$request->course)->get();

            foreach($meet as $m)
            {
                $m['join'] = false;
                if(Carbon::parse($m->start_date)->format('Y-m-d H:i:s') <= Carbon::now()->format('Y-m-d H:i:s') && Carbon::now()->format('Y-m-d H:i:s') <= Carbon::parse($m->start_date)
                ->addMinutes($m->duration)->format('Y-m-d H:i:s'))
                {
                    $req = new Request([
                        'duration' => $m->duration,
                        'attendee' =>$m->attendee,
                        'id' => $m->id,
                        'name' => $m->name,
                        'moderator_password' => $m->moderator_password,
                        'is_recorded' => $m->is_recorded
                    ]);
                    $check=self::start_meeting($req);
                    if($check)
                        $m['join'] = true;
                }
                $getMeetingInfoParams = new GetMeetingInfoParameters($m->id, '', $m->moderator_password);
                $response = $bbb->getMeetingInfo($getMeetingInfoParams);
                if ($response->getReturnCode() == 'FAILED') {
                    $m['join'] = false;
                }
                if(count($hasornot) > 0 )
                {
                    $m['student_view']=$m['show'];
                    $m['show']=1;
                }
            }

            if($meet == null)
                return HelperController::api_response_format(200 , null , 'This Meeting is not found');
            return HelperController::api_response_format(200 , $meet);
        }

        $CourseSeg = Enroll::where('user_id', Auth::id())->pluck('course_segment');
        $courses=collect();
        foreach($CourseSeg as $cs){
            $cs_object = CourseSegment::find($cs);
            if($cs_object->end_date > Carbon::now() && $cs_object->start_date < Carbon::now()){
                $courses_cs = $cs_object->courses;
                foreach($courses_cs as $c){
                    $courses->push($c->id);
                }
            }
        }
        
        $bbb = new BigBlueButton();
        $meet = BigbluebuttonModel::whereIn('course_id',$courses)->get(); 
        if($request->user()->can('bigbluebutton/get-all')){
            $all_meetings = $bbb = new BigBlueButton();
            $meet = BigbluebuttonModel::get();       
        }

        foreach($meet as $m)
            {
                $m['join'] = false;
                if(Carbon::parse($m->start_date)->format('Y-m-d H:i:s') <= Carbon::now()->format('Y-m-d H:i:s') && Carbon::now()->format('Y-m-d H:i:s') <= Carbon::parse($m->start_date)
                ->addMinutes($m->duration)->format('Y-m-d H:i:s'))
                {
                    $req = new Request([
                        'duration' => $m->duration,
                        'attendee' =>$m->attendee,
                        'id' => $m->id,
                        'name' => $m->name,
                        'moderator_password' => $m->moderator_password,
                        'is_recorded' => $m->is_recorded
                    ]);
                    $check=self::start_meeting($req);
                    if($check)
                        $m['join'] = true;
                }
                $getMeetingInfoParams = new GetMeetingInfoParameters($m->id, '', $m->moderator_password);
                $response = $bbb->getMeetingInfo($getMeetingInfoParams);
                if ($response->getReturnCode() == 'FAILED') {
                    $m['join'] = false;
                }
                if(count($hasornot) > 0 )
                {
                    $m['student_view']=$m['show'];
                    $m['show']=1;
                }
            }

        if($meet == null)
            return HelperController::api_response_format(200 , null , 'This Meeting is not found');
        return HelperController::api_response_format(200 , $meet);
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
                if($recording->meetingID == $request->id)
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
        $meet = BigbluebuttonModel::whereId($request->id)->delete();
        return HelperController::api_response_format(200 , null , 'Meeting Deleted!');
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
            'id' => 'exists:bigbluebutton_models,id|required_without:class,course',
        ]);

        $user_id = Auth::user()->id;
        $role_id = DB::table('model_has_roles')->where('model_id',$user_id)->pluck('role_id')->first();
        $permission_id = DB::table('permissions')->where('name','bigbluebutton/toggle')->pluck('id')->first();
        $hasornot = DB::table('role_has_permissions')->where('role_id', $role_id)->where('permission_id', $permission_id)->get();


        if($request->filled('id'))
        {
            $bbb = new BigBlueButton();
            $meet = BigbluebuttonModel::whereId($request->id)->first();

            $getMeetingInfoParams = new GetMeetingInfoParameters($request->id, $meet->moderator_password);
            $response = $bbb->getMeetingInfo($getMeetingInfoParams);
            if ($response->getReturnCode() == 'FAILED') {
                return 'failed';
            } else {
                $getMeetingInfoParams = new GetMeetingInfoParameters($request->id, $meet->moderator_password);
                $response = $bbb->getMeetingInfoURL($getMeetingInfoParams);
                $guzzleClient = new Client();
                $response = $guzzleClient->get($response);
                $body = $response->getBody();
                $body->seek(0);
                $response  = json_decode(json_encode(simplexml_load_string($response->getBody()->getContents())), true);
                return $response;
                $atendees=collect();
                $names=collect();
                if(count($response['attendees']) >= 1){

                    foreach($response['attendees']['attendee'] as $attend){
                        $names->push($attend['fullName']);
                    }

                }else{
                    foreach($response['attendees'] as $attend){
                    
                       $names->push($attend['fullName']);
                    }
                }
                
                // return $response['attendees']['attendee'];
                return $names;
            }
        }
    }

    public function takeattendance(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:bigbluebutton_models,id',
        ]);

        $bbb = new BigBlueButton();
        $meet = BigbluebuttonModel::whereId($request->id)->first();
        $getMeetingInfoParams = new GetMeetingInfoParameters($request->id, $meet->moderator_password);
        $response = $bbb->getMeetingInfo($getMeetingInfoParams);

        if ($response->getReturnCode() == 'FAILED') {
            return HelperController::api_response_format(200 , null , 'This meeting not found we cant find attendees!');
        } 

        $response = $bbb->getMeetingInfoURL($getMeetingInfoParams);
        $guzzleClient = new Client();
        $response = $guzzleClient->get($response);
        $response  = json_decode(json_encode(simplexml_load_string($response->getBody()->getContents())), true);

        if(!isset($response['attendees']['attendee'][0]['fullName'])){
            $all_attendees = AttendanceLog::where('session_id',$request->id)->where('type','online')->update([
                'taken_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'taker_id' => Auth::id(),
                'status' => 'Absent'
            ]);

            return HelperController::api_response_format(200 , null , 'Attendance taken successfully!');
        }

        $attendance_status=AttendanceLog::where('session_id',$request->id)->where('type','online')->update([
            'taken_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'taker_id' => Auth::id(),
            'status' => null
        ]);

        foreach($response['attendees']['attendee'] as $attend){
            $user=User::where('username',$attend['fullName'])->first();
            $attendance=AttendanceLog::where('student_id',$user->id)->where('session_id',$request->id)->where('type','online')->update([
                'taken_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'taker_id' => Auth::id(),
                'status' => 'Present'
            ]);
        }

        $attendance_absent=AttendanceLog::where('status',null)->where('session_id',$request->id)->where('type','online')->update([
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

        $all_logs=AttendanceLog::where('session_id',$request->id)->where('type','online')->with('User')->get();
        $attendance_log['Total_Logs'] = $all_logs->count();
        $attendance_log['Present']['count']= $all_logs->where('status','Present')->count();
        $attendance_log['Absent']['count']= $all_logs->where('status','Absent')->count();
        if($all_logs->count() != 0)
        {
            $attendance_log['Present']['precentage'] = ($attendance_log['Present']['count']/$all_logs->count())*100 ;
            $attendance_log['Absent']['precentage'] =  ($attendance_log['Absent']['count']/$all_logs->count())*100 ;
        }
        $attendance_log['logs'] = $all_logs;

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
        \Artisan::call('config:cache');
        \Artisan::call('cache:clear');
        \Artisan::call('config:clear');
    }
}
