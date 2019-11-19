<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\HelperController;
use Carbon\Carbon;
use App\Http\Controllers\GradeCategoryController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Attendance\Entities\Attendance;
use Modules\Attendance\Entities\AttendanceSession;
use Modules\Attendance\Entities\AttendanceStatus;
use Modules\Attendance\Entities\AttendanceLog;
use App\Enroll;

class AttendanceController extends Controller
{

    public function get_all_users_in_attendence(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:attendances,id',
        ]);
        $Course_Segments=Attendance::get_CourseSegments_by_AttendenceID($request->id);
        $users=Enroll::whereIn('course_segment',$Course_Segments)->with('user')->get();
        return $users;
    }

    public function get_all_users_in_session(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:attendance_sessions,id',
            'year'=>'exists:academic_years,id',
            'type'=>'exists:academic_types,id',
            'level'=>'exists:levels,id',
            'class'=>'exists:classes,id',

        ]);
        $session = AttendanceSession::where('id',$request->session_id)->first();
        $users=Enroll::where('course_segment',$session->course_segment_id)->with('user')->get();
        if(is_null($session->course_segment_id)){
            $course_segments = GradeCategoryController::getCourseSegment($request);
            $users=Enroll::whereIn('course_segment',$course_segments)->with('user')->get();
        }
        return $users;
        
    }
    public function get_all_taken_users_in_session(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:attendance_sessions,id',
        ]);
        $users =AttendanceSession::with('logs.User')->where('id',$request->session_id)->get();
        return $users[0]['logs'];

    }
/**
     * @param  \Illuminate\Http\Request  $request
     * @return message to tell that all session with or without course segments  are created 
     */
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'attendance_type' => 'required|integer|min:1|max:2',
            'grade' => 'integer',
            'days' => 'required|array|min:1|max:5', // all dayes must be small
            'days.*'=>'required|in:sunday,monday,tuesday,wednesday,thursday',
            'end_date' => 'required|date',
            'times' => 'required_if:attendance_type,==,2'
        ]);
        $user_id = Auth::User()->id;
        if ($request->attendance_type == 1) {
            $course_segments = GradeCategoryController::getCourseSegment($request);
            $jop = (new Attendance_sessions(collect($request->all()), $user_id, $course_segments));
            dispatch($jop);
            return HelperController::api_response_format(200, 'all sesions with all course segments');
        }
        $jop = (new Attendance_sessions(collect($request->all()), $user_id, null));
        dispatch($jop);
        return HelperController::api_response_format(200, 'all sesions without  course segments');

    }
}
