<?php

namespace Modules\Attendance\Http\Controllers;

use App\CourseSegment;
use App\Enroll;
use App\Http\Controllers\HelperController;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Modules\Attendance\Entities\Attendance;
use Modules\Attendance\Entities\AttendanceLog;
use Modules\Attendance\Entities\AttendanceSession;
use Modules\Attendance\Entities\AttendanceStatus;

class AttendanceLogController extends Controller
{

    public function create(Request $request)
    {
        $ip = \Request::ip();
        $user_id = Auth::User()->id;
        $date = Carbon::now();
        $AttendanceLog=array();
        $request->validate([
            'session_id' => 'required|exists:attendance_sessions,id',
            'users' => 'required|array',
            'users.*.id' => 'required|exists:users,id',
            'users.*.status_id' => 'required|exists:attendance_statuses,id',
        ]);
        $attendance_sessions = AttendanceSession::find($request->session_id);
        $attendance_sessions->update(['last_time_taken' => $date]);
        $enroll = Enroll::where('course_segment', $attendance_sessions->course_segment_id)->pluck('user_id');
        $courseID = CourseSegment::where('id',$attendance_sessions->course_segment_id)->where('is_active',1)->first('course_id');
        $attendance = Attendance::find($attendance_sessions->attendance_id);
        $result = [];
        $result['message'] = 'Attendance Logged Successfully';
        $result['users'] = [];
        foreach ($request->users as $user) {
            if (in_array($user['id'], $enroll->toArray())) {
                $AttendanceLog[] = AttendanceLog::create([
                    'ip_address' => $ip,
                    'session_id' => $request->session_id,
                    'student_id' => $user['id'],
                    'status_id' => $user['status_id'],
                    'taker_id' => $user_id,
                    'taken_at' => $date
                ]);
            }else{
                $result['users'][] = $user['id'];
                $result['message'] = 'Those Users are not belong to this Attendance';
            }
            foreach ($attendance->allowed_classes as $classID) {
                $courseSeg= CourseSegment::GetWithClassAndCourse($classID,$courseID->course_id);
                if(!isset($courseSeg))
                    continue;

                if($courseSeg->id==$attendance_sessions->course_segment_id)
                {
                    User::notify([
                        'id' => isset($AttendanceLog) ? $AttendanceLog[0]->id : null,
                        'message' => 'Attendance is taken with status '. AttendanceStatus::find($user['status_id'])->letter,
                        'from' => Auth::User()->id,
                        'users' => array($user['id']),
                        'course_id' => $courseID->course_id,
                        'class_id' => (int)$classID[0],
                        'lesson_id' => null,
                        'type' => 'Attendance',
                        'publish_date' => $date,
                    ]);
                }
            }
        }
        if(count($result['users']) == 0)
            $result['users'] = $AttendanceLog;
        return HelperController::api_response_format(200, $result['users'], $result['message']);
    }
}
