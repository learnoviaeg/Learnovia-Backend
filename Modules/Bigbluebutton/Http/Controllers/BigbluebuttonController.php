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
use BigBlueButton\Parameters\CreateMeetingParameters;
use BigBlueButton\Parameters\JoinMeetingParameters;
use BigBlueButton\Parameters\GetRecordingsParameters;
use Modules\Bigbluebutton\Entities\BigbluebuttonModel;
use BigBlueButton\Parameters\GetMeetingInfoParameters;
use Illuminate\Support\Carbon;
use App\Http\Controllers\HelperController;
use DB;



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


        $role = \Spatie\Permission\Models\Role::find(1);
        $role->givePermissionTo('bigbluebutton/create');
        $role->givePermissionTo('bigbluebutton/join');
        $role->givePermissionTo('bigbluebutton/get');
        $role->givePermissionTo('bigbluebutton/getRecord');
        $role->givePermissionTo('bigbluebutton/delete');
        $role->givePermissionTo('bigbluebutton/toggle');


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
            'class_id'=>'required|exists:classes,id',
            'course_id'=>'required|exists:courses,id',
            'attendee_password' => 'nullable|string',
            'moderator_password' => 'required|string',
            'duration' => 'nullable',
        ]);

        if(isset($request->attendee_password)){
            $attendee= $request->attendee_password;
        }
        else{
            $attendee= 'learnovia123';
        }

        if(isset($request->duration)){
            $duration= $request->duration;
        }
        else{
            $duration= '00:40:00';
        }

        //Creating the meeting in DB
        $bigbb = new BigbluebuttonModel;
        $bigbb->name=$request->name;
        $bigbb->class_id=$request->class_id;
        $bigbb->course_id=$request->course_id;
        $bigbb->attendee_password=$attendee;
        $bigbb->moderator_password=$request->moderator_password;
        $bigbb->duration=$duration;
        $bigbb->save();

        //Creating the meeting
        $bbb = new BigBlueButton();


        $bbb->getJSessionId();
        $createMeetingParams = new CreateMeetingParameters($bigbb->id, $request->name);
        $createMeetingParams->setAttendeePassword($attendee);
        $createMeetingParams->setModeratorPassword($request->moderator_password);
        $createMeetingParams->setDuration($duration);
        // $createMeetingParams->setRedirect(false);
        $createMeetingParams->setLogoutUrl('dev.learnovia.com/#/');
        $createMeetingParams->setRecord(true);
        $createMeetingParams->setAllowStartStopRecording(true);
        $createMeetingParams->setAutoStartRecording(true);

        $response = $bbb->createMeeting($createMeetingParams);

        if ($response->getReturnCode() == 'FAILED') {
            return 'Can\'t create room! please contact our administrator.';
        } else {

            //Notify students for the Meeting
            $courseseg=CourseSegment::GetWithClassAndCourse($request->class_id,$request->course_id);
            if(isset($courseseg))
            {
                $usersIDs=Enroll::where('course_segment',$courseseg->id)->pluck('user_id')->toarray();

                User::notify([
                    'id' => $bigbb->id,
                    'message' => $request->name.' meeting is created',
                    'from' => Auth::user()->id,
                    'users' => $usersIDs,
                    'course_id' => $request->course_id,
                    'class_id'=>$request->class_id,
                    'type' => 'meeting',
                    'link' => url(route('getmeeting')) . '?id=' . $bigbb->id,
                    'publish_date'=>Carbon::now()
                ]);

                // moderator join the meeting
                $joinMeetingParams = new JoinMeetingParameters($bigbb->id, Auth::user()->username , $request->moderator_password);
                $joinMeetingParams->setRedirect(true);
                $url = $bbb->getJoinMeetingURL($joinMeetingParams);

                $createrecordParams = new GetRecordingsParameters();
                $createrecordParams->setMeetingId($bigbb->id);
                $createrecordParams->setRecordId($bigbb->id);
                $createrecordParams->setState(true);

                $res= $bbb->getRecordings($createrecordParams);

                $output = array(
                    'name' => $request->name,
                    'duration' => $duration,
                    'link'=> $url,
                );

                return HelperController::api_response_format(200, $output,'Meeting created Successfully');
            }
            else
            {
                return HelperController::api_response_format(200, null ,'Please check active course segments');
            }
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

        $joinMeetingParams = new JoinMeetingParameters($request->id, $user_name, $bigbb->attendee_password);
        $joinMeetingParams->setRedirect(true);
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
            'id' => 'exists:bigbluebutton_models,id|required_without:class,course',
            'class'=> 'exists:bigbluebutton_models,class_id|required_without:id',
            'course'=> 'exists:bigbluebutton_models,course_id|required_without:id',
        ]);

        $user_id = Auth::user()->id;
        $role_id = DB::table('model_has_roles')->where('model_id',$user_id)->pluck('role_id')->first();
        $permission_id = DB::table('permissions')->where('name','bigbluebutton/toggle')->pluck('id')->first();
        $hasornot = DB::table('role_has_permissions')->where('role_id', $role_id)->where('permission_id', $permission_id)->get();


        if($request->filled('id'))
        {
            $bbb = new BigBlueButton();
            $meet = BigbluebuttonModel::whereId($request->id)->first();
            $getMeetingInfoParams = new GetMeetingInfoParameters($request->id, '', $meet->moderator_password);
            $response = $bbb->getMeetingInfo($getMeetingInfoParams);
            if ($response->getReturnCode() == 'FAILED') {
                $meet['join'] = false;
            } else {
                $meet['join'] = true;
            }
            
            if(count($hasornot) > 0 )
            {
                $meet['show']=1;
            }
          
        }
        if($request->filled('course') && $request->filled('class'))
        {
            $bbb = new BigBlueButton();
            $meet = BigbluebuttonModel::where('class_id',$request->class)->where('course_id',$request->course)->get();
            // return $meet;
            foreach($meet as $m)
            {
                $getMeetingInfoParams = new GetMeetingInfoParameters($m->id, '', $m->moderator_password);
                $response = $bbb->getMeetingInfo($getMeetingInfoParams);
                if ($response->getReturnCode() == 'FAILED') {
                    $m['join'] = false;
                } else {
                    $m['join'] = true;
                }
                if(count($hasornot) > 0 )
                {
                    $m['show']=1;
                }
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
}
