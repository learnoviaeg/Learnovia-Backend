<?php

namespace Modules\Attendance\Http\Controllers;

use App\Http\Controllers\HelperController;
use Carbon\Carbon;
use App\Http\Controllers\GradeCategoryController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Attendance\Entities\Attendance;
use Modules\Attendance\Entities\AttendanceSession;
use Modules\Attendance\Jobs\Attendance_sessions;
use ParagonIE\Sodium\Core\Curve25519\H;

class AttendanceController extends Controller
{
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
