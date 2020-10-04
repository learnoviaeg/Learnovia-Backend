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
                            self::create_hook($request);                            
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
        $createMeetingParams->setLogoutUrl('https://learnovia.com/');
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
        self::create_hook($request);
        $bbb = new BigBlueButton();

        //Validating the Input
        $request->validate([
            'id'=>'required|exists:bigbluebutton_models,id',
        ]);

        $user_name = Auth::user()->username;
        $bigbb=BigbluebuttonModel::find($request->id);
        $check=Carbon::parse($bigbb->start_date)->addMinutes($bigbb->duration);
        if($check < Carbon::now())
            return HelperController::api_response_format(200,null ,'you can\'t join this meeting any more');
        
        if($request->user()->can('bigbluebutton/session-moderator')){
            $joinMeetingParams = new JoinMeetingParameters($request->id, $user_name, $bigbb->moderator_password);
        }else{
            $attendance = AttendanceLog::updateOrCreate(['student_id' => Auth::id(),'session_id'=>$bigbb->id,'type'=>'online','entered_date'=> null],
            [
                'ip_address' => \Request::ip(),
                'student_id' => Auth::id(),
                'session_id' => $bigbb->id,
                'taken_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'type' => 'online',
                'entered_date' => Carbon::now()->format('Y-m-d H:i:s'),
                'taker_id' => $bigbb->user_id
            ]);
            $joinMeetingParams = new JoinMeetingParameters($request->id, $user_name, $bigbb->attendee_password);
        }
        $joinMeetingParams->setRedirect(true);
        $joinMeetingParams->setJoinViaHtml5(true);
        $joinMeetingParams->setUserId($user_name);
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
                self::create_hook($request);
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
            $meet = BigbluebuttonModel::where('class_id',$request->class)->where('course_id',$request->course)->orderBy('start_date')->get();

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
                    self::create_hook($request);
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

        $CS_ids=GradeCategoryController::getCourseSegment($request);
        $CourseSeg = Enroll::where('user_id', Auth::id())->pluck('course_segment');
        $courses=collect();
        foreach($CourseSeg as $cs){
            if(in_array($cs, $CS_ids->toArray())){
                $cs_object = CourseSegment::find($cs);
                if($cs_object->end_date > Carbon::now() && $cs_object->start_date < Carbon::now()){
                    $courses_cs = $cs_object->courses;
                    foreach($courses_cs as $c){
                        $courses->push($c->id);
                    }
                }
            }
        }
        
        $bbb = new BigBlueButton();
        $meet = BigbluebuttonModel::whereIn('course_id',$courses)->orderBy('start_date')->get(); 
        if($request->user()->can('site/show-all-courses')){
            $courses_id=collect();
            foreach ($CS_ids as $coco) {
                $cocos=CourseSegment::find($coco);
                if($cocos->end_date >= Carbon::now() && $cocos->start_date <= Carbon::now())
                    $courses_id->push($cocos->course_id);
            }
            $all_meetings = $bbb = new BigBlueButton();
            $meet = BigbluebuttonModel::whereIn('course_id',$courses_id)->orderBy('start_date')->get();       
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
                    self::create_hook($request);
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
        return HelperController::api_response_format(200 , $meet,'Meetings list');
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
            'id' => 'exists:bigbluebutton_models,id',
        ]);
        
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

        $students_id=collect();
        foreach($response['attendees']['attendee'] as $attend){
            $user=User::where('username',$attend['fullName'])->first();
            $students_id->push($user->id);
            $attendance=AttendanceLog::where('student_id',$user->id)->where('session_id',$request->id)->where('type','online')->first();
            if(isset($attendance)){
                $attendance->update([
                    'taken_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'taker_id' => Auth::id(),
                    'status' => 'Present'
                ]);
            }
        }

        $attendance_absent=AttendanceLog::where('status',null)->where('session_id',$request->id)->where('type','online')->whereNotIn('student_id',$students_id)->get()->unique('student_id');
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
        if($all_logs->count() != 0)
        {
            $attendance_log['Present']['precentage'] = ($attendance_log['Present']['count']/$all_logs->count())*100 ;
            $attendance_log['Absent']['precentage'] =  ($attendance_log['Absent']['count']/$all_logs->count())*100 ;
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
                }

                $logs_time->push([
                    'entered_date' => $log['entered_date'],
                    'left_date' => $log['left_date']
                ]);
            }

            $first_login=null;
            if(isset($logs[0]['entered_date']))
                $first_login = Carbon::parse($logs[0]['entered_date'])->diffInMinutes(Carbon::parse($meeting->start_date));

            $last_logout=null;
            if(isset($logs[count($logs)-1]['left_date']))
                $last_logout = Carbon::parse($meeting->start_date)->addMinutes($meeting->duration)->diffInMinutes(Carbon::parse($logs[count($logs)-1]['left_date']));

            $final_logs->push([
                'username' => $logs[0]['User']['username'],
                'fullname' => $logs[0]['User']['fullname'],
                'attend_duration' => $diffrence . ' Minute/s',
                'duration_percentage' => ($diffrence/$meeting->duration)*100 . ' %',
                'first_login' => isset($first_login)? $first_login . ' Minute/s' : '-',
                'last_logout' => isset($last_logout)? $last_logout . ' Minute/s' : '-',
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

        $found=BigbluebuttonModel::where('id',$arr[0]['data']['attributes']['meeting']['external-meeting-id'])->first();
        if(isset($found) && Carbon::parse($found->start_date)->format('Y-m-d H:i:s') <= Carbon::now()->format('Y-m-d H:i:s')){
            $req = new Request([
                'id' => $arr[0]['data']['attributes']['meeting']['external-meeting-id'] ,
            ]);
    
            $information = self::getmeetingInfo($req);
            if($information != 'failed' && $information['internalMeetingID'] == $arr[0]['data']['attributes']['meeting']['internal-meeting-id']){
    
                if($arr[0]['data']['id'] == 'user-left'){
                    Log::debug($arr[0]['data']['id']);
                    Log::debug($arr[0]['data']['attributes']['meeting']['external-meeting-id']);
                    Log::debug($arr[0]['data']['attributes']['user']['external-user-id']);
    
                    $user_id = User::where('username',$arr[0]['data']['attributes']['user']['external-user-id'])->pluck('id')->first();
                    $log = AttendanceLog::where('session_id',$arr[0]['data']['attributes']['meeting']['external-meeting-id'])
                                        ->where('type','online')
                                        ->where('student_id',$user_id)->where('left_date',null)->first();
                    if(isset($log))
                        $log->update(['left_date' => Carbon::now()->format('Y-m-d H:i:s')]);
                }
            }
    
            if($arr[0]['data']['id'] == 'meeting-ended'){
                Log::debug($arr[0]['data']['id']);
                $log = AttendanceLog::where('session_id',$arr[0]['data']['attributes']['meeting']['external-meeting-id'])
                                    ->where('type','online')
                                    ->where('entered_date','!=',null)
                                    ->where('left_date',null)->update([
                                        'left_date' => Carbon::now()->format('Y-m-d H:i:s')
                                    ]);

                $start = Carbon::parse($found->start_date);
                $end = Carbon::now();
                $duration= $end->diffInMinutes($start);
                BigbluebuttonModel::where('id',$arr[0]['data']['attributes']['meeting']['external-meeting-id'])->update([
                    'duration' => $duration
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
