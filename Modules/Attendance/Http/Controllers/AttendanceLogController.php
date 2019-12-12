<?php

namespace Modules\Attendance\Http\Controllers;

use App\Enroll;
use App\Http\Controllers\HelperController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Modules\Attendance\Entities\AttendanceLog;
use Modules\Attendance\Entities\AttendanceSession;

class AttendanceLogController extends Controller
{

    public function create(Request $request)
    {

        $ip = \Request::ip();
        $user_id = Auth::User()->id;
        $date = Carbon::now();
        $request->validate([
            'session_id' => 'required|exists:attendance_sessions,id',
            'users' => 'required|array',
            'users.*.id' => 'required|exists:users,id',
            'users.*.status_id' => 'required|exists:attendance_statuses,id',
        ]);

        $attendance_sessions = AttendanceSession::find($request->session_id);
        $attendance_sessions->update(['last_time_taken' => $date]);
        $enroll = Enroll::where('course_segment', $attendance_sessions->course_segment_id)->pluck('user_id');
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
            }
        }
        return HelperController::api_response_format(200, $AttendanceLog, 'Attendance Log ...');
    }


}
