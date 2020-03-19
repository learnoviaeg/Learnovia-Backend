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
use Modules\Bigbluebutton\Entities\BigbluebuttonModel;
use Illuminate\Support\Carbon;
use App\Http\Controllers\HelperController;


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

        $role = \Spatie\Permission\Models\Role::find(1);
        $role->givePermissionTo('bigbluebutton/create');
        $role->givePermissionTo('bigbluebutton/join');
        $role->givePermissionTo('bigbluebutton/get');

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
        $createMeetingParams->setLogoutUrl('http://itsmart.com.eg');
        if ($request->isRecordingTrue) {
            $createMeetingParams->setRecord(true);
            $createMeetingParams->setAllowStartStopRecording(true);
            $createMeetingParams->setAutoStartRecording(true);
        }
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
                $joinMeetingParams = new JoinMeetingParameters($bigbb->id, $request->name, $request->moderator_password);
                $joinMeetingParams->setRedirect(true);
                $url = $bbb->getJoinMeetingURL($joinMeetingParams);

                $output = array(
                    'name' => $request->name,
                    'duration' => $duration,
                    'link'=> $url
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

        $bigbb=BigbluebuttonModel::find($request->id);

        $joinMeetingParams = new JoinMeetingParameters($request->id, $bigbb->name, $bigbb->attendee_password);
        $joinMeetingParams->setRedirect(true);
        $url = $bbb->getJoinMeetingURL($joinMeetingParams);

        $output = array(
            'name' => $request->name,
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
        if($request->filled('id'))
            $meet = BigbluebuttonModel::whereId($request->id)->first();
        if($request->filled('course') && $request->filled('class'))
            $meet = BigbluebuttonModel::where('class_id',$request->class)->where('course_id',$request->course)->get();
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
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
