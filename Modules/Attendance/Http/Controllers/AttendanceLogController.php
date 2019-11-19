<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\HelperController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
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
            'student_ids' => 'required|exists:users,id|array',
            'status_ids' => 'required|exists:attendance_statuses,id|array',
        ]);
        if(count($request->student_ids) !=count($request->status_ids) ){
            return HelperController::api_response_format(404, null , 'size of students array not equal size of status');

        }
        $allowed_status = AttendanceSession::find($request->session_id)->attendance;
        dd($allowed_status);
        $request->validate([
            'status_ids.*' => 'required|in:' . implode(),
        ]);

        AttendanceSession::find($request->session_id)->update(['last_time_taken' => $date]);
        for ($i = 0; $i < count($request->student_ids); $i++) {
            $AttendanceLog[] = AttendanceLog::create([
                'ip_address' => $ip,
                'session_id' => $request->session_id,
                'student_id' => $request->student_ids[$i],
                'status_id' => $request->status_ids[$i],
                'taker_id' => $user_id,
                'taken_at' => $date
            ]);
        }
        return HelperController::api_response_format(200, $AttendanceLog , 'Attendance Log ...');


    }


}
