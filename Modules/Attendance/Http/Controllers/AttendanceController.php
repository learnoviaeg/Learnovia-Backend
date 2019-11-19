<?php

namespace Modules\Attendance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Controllers\GradeCategoryController;
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

}
