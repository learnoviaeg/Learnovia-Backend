<?php

namespace Modules\Attendance\Http\Controllers;

use App\Component;
use App\Http\Controllers\HelperController;
use App\Http\Controllers\GradeCategoryController;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Attendance\Entities\Attendance;
use Modules\Attendance\Entities\AttendanceLog;
use Modules\Attendance\Entities\AttendanceSession;

use App\Enroll;
use Modules\Attendance\Entities\AttendanceStatus;
use Modules\Attendance\Jobs\Attendance_sessions;

class AttendanceController extends Controller
{

    public function install()
    {
        if (\Spatie\Permission\Models\Permission::whereName('attendance/add')->first() != null) {
            return \App\Http\Controllers\HelperController::api_response_format(400, null, 'This Component is installed before');
        }

        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/add', 'title' => 'add attendance']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/add-log', 'title' => 'add attendance log']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/get-users-in-attendence', 'title' => 'get  all users in attendence']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/get-users-in-session', 'title' => 'get all users in session']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/get-all-taken-users-in-session', 'title' => 'get all taken users in session']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/add-session', 'title' => 'add session']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'site/attendance/bulck/attendace', 'title' => 'add session']);

        $role = \Spatie\Permission\Models\Role::find(1);
        $role->givePermissionTo('attendance/add');
        $role->givePermissionTo('attendance/add-log');
        $role->givePermissionTo('attendance/get-users-in-attendence');
        $role->givePermissionTo('attendance/get-users-in-session');
        $role->givePermissionTo('attendance/get-all-taken-users-in-session');
        $role->givePermissionTo('attendance/add-session');
        $role->givePermissionTo('site/attendance/bulck/attendace');


        Component::create([
            'name' => 'Attendance',
            'module' => 'Attendance',
            'model' => 'Attendance',
            'type' => 1,
            'active' => 1
        ]);

        return \App\Http\Controllers\HelperController::api_response_format(200, null, 'Component Installed Successfully');
    }

    public function get_all_users_in_attendence(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:attendances,id',
        ]);
        $Course_Segments = Attendance::get_CourseSegments_by_AttendenceID($request->id);
        $users = Enroll::whereIn('course_segment', $Course_Segments)->with('user')->get();
        return HelperController::api_response_format(200,$users , 'Users are.....');
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
        $AlreadyTakenUsers=AttendanceLog::where('session_id',$request->session_id)->pluck('student_id');
        $course_segments = [];
        $course_segments[] = $session->course_segment_id;
        if(is_null($course_segments[0])){
            $course_segments = GradeCategoryController::getCourseSegment($request);
        }
        $users=Enroll::whereIn('course_segment',$course_segments)->with(['users' => function($query)use ($AlreadyTakenUsers){
            $query->whereNotIn('id' , $AlreadyTakenUsers);
        }])->get()->pluck('users');
        return HelperController::api_response_format(200,$users , 'Users are.....');
    }
    
    public function get_all_taken_users_in_session(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:attendance_sessions,id',
        ]);
        $AlreadyTakenUsers=AttendanceLog::where('session_id',$request->session_id)->pluck('student_id');
        $users = User::whereIn('id' , $AlreadyTakenUsers)->get();
        return HelperController::api_response_format(200,$users , 'Users are....');
    }

    /**
     * @param  \Illuminate\Http\Request $request
     * @return message to tell that all session with or without course segments  are created
     */
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'attendance_type' => 'required|integer|min:1|max:2',
            'grade' => 'integer',
            'days' => 'required|array|min:1|max:5', // all dayes must be small
            'days.*' => 'required|in:sunday,monday,tuesday,wednesday,thursday',
            'end_date' => 'required|date',
            'times' => 'required_if:attendance_type,==,2'
        ]);
        if ($request->type == 2 && !Auth::User()->can('site/attendance/bulck/attendace')) {
            return HelperController::api_response_format(200, 'does not have the right permissions ');
        }
        $user_id = Auth::User()->id;
        $attendance = Attendance::create(['name' => $request->name,
            'type' => $request->attendance_type,
            'grade' => ($request->grade) ? $request->grade : null
        ]);

        $default = AttendanceStatus::defaultStatus();
        foreach ($default as $status) {
            $status['attendance_id'] = $attendance->id;
            AttendanceStatus::create($status);
        }
        $req = $request->all();
        $req['attendance_id'] = $attendance->id;
        if ($request->attendance_type == 1) {
            $course_segments = GradeCategoryController::getCourseSegment($request);
            $jop = (new Attendance_sessions($req, $user_id, $course_segments));
            dispatch($jop);
            return HelperController::api_response_format(200, 'all sesions with all course segments');
        }
        $jop = (new Attendance_sessions($req, $user_id, null));
        dispatch($jop);
        return HelperController::api_response_format(200, 'all sesions without  course segments');

    }

    public function createSession(Request $request)
    {
        $request->validate([
            'attendance_id' => 'required|exists:attendances,id',
            'days' => 'required|array|min:1|max:5', // all dayes must be small
            'days.*' => 'required|in:sunday,monday,tuesday,wednesday,thursday',
            'end_date' => 'required|date',
        ]);
        $attendance = Attendance::find($request->attendance_id);
        $req = $request->all();
        $req['attendance_type'] = $attendance->type;
        $req['attendance_id'] = $attendance->id;
        $user_id = Auth::User()->id;
        switch ($attendance->type) {
            case  1 :
                $course_segments = GradeCategoryController::getCourseSegment($request);
                $jop = (new Attendance_sessions($req, $user_id, $course_segments));
                dispatch($jop);
                return HelperController::api_response_format(200, 'all sesions with all course segments');
            case 2 :
                if (!Auth::User()->can('site/attendance/bulck/attendace')) {
                    return HelperController::api_response_format(200, 'does not have the right permissions ');
                }
                $request->validate(['times' => 'required']);
                $jop = (new Attendance_sessions($req, $user_id, null));
                dispatch($jop);
                return HelperController::api_response_format(200, 'all sesions without  course segments');

        }
    }
}
