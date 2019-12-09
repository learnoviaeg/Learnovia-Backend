<?php

namespace Modules\Attendance\Http\Controllers;

use App\Component;
use App\Course;
use App\GradeCategory;
use App\Http\Controllers\HelperController;
use App\Http\Controllers\GradeCategoryController;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Attendance\Entities\Attendance;
use Modules\Attendance\Entities\AttendanceLog;
use Modules\Attendance\Entities\AttendanceSession;

use App\Enroll;
use Modules\Attendance\Entities\AttendanceStatus;
use Modules\Attendance\Jobs\Attendance_sessions;
use Modules\Attendance\Jobs\AttendanceGradeItems;
use Modules\Attendance\Jobs\AttendanceSessionsJob;

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
        return HelperController::api_response_format(200, $users, 'Users are.....');
    }

    public function get_all_users_in_session(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:attendance_sessions,id',
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
            'level' => 'exists:levels,id',
            'class' => 'exists:classes,id',
        ]);
        $session = AttendanceSession::where('id', $request->session_id)->first();
        $AlreadyTakenUsers = AttendanceLog::where('session_id', $request->session_id)->pluck('student_id');
        $course_segments = [];
        $course_segments[] = $session->course_segment_id;
        if (is_null($course_segments[0])) {
            $course_segments = GradeCategoryController::getCourseSegment($request);
        }
        $users = Enroll::whereIn('course_segment', $course_segments)->with(['users' => function ($query) use ($AlreadyTakenUsers) {
            $query->whereNotIn('id', $AlreadyTakenUsers);
        }])->get()->pluck('users');
        return HelperController::api_response_format(200, $users, 'Users are.....');
    }

    public function get_all_taken_users_in_session(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:attendance_sessions,id',
        ]);
        $AlreadyTakenUsers = AttendanceLog::where('session_id', $request->session_id)->pluck('student_id');
        $users = User::whereIn('id', $AlreadyTakenUsers)->get();
        return HelperController::api_response_format(200, $users, 'Users are....');
    }

    /**
     * @param  \Illuminate\Http\Request $request
     * @return message to tell that all session with or without course segments  are created
     */
    public static function createAttendance(Request $request)
    {
        if ($request->attendance_type == Attendance::$FIRST_TYPE) {
            foreach ($request->levels as $level) {
                $levels[] = $level['id'];
                $classes[] = $level['classes'];
                $courses[] = $level['courses'];
            }
        } elseif ($request->attendance_type == Attendance::$SECOND_TYPE) {
            foreach ($request->levels as $level) {
                foreach ($level['periods'] as $periods) {
                    $courses[] = $periods['courses'];
                }
                $levels[] = $level['id'];
                $classes[] = $level['classes'];
            }
        }
        $attendance = Attendance::create(['name' => $request->name,
            'type' => $request->attendance_type,
            'graded' => $request->graded,
            'allowed_levels' => serialize($levels),
            'allowed_courses' => serialize($courses),
            'allowed_classes' => serialize($classes),
            'year_id'=>$request->year,
            'segment_id'=>$request->segment,
            'type_id'=>$request->type,
            'start_date' =>$request->start,
            'end_date'=>$request->end

        ]);
        $default = AttendanceStatus::defaultStatus();
        foreach ($default as $status) {
            $status['attendance_id'] = $attendance->id;
            AttendanceStatus::create($status);
        }
        return $attendance;
    }

    public function create(Request $request)
    {
        $request->validate([
            'attendance_type' => 'required|integer|min:1|max:2',
        ]);
        if ($request->attendance_type == Attendance::$FIRST_TYPE)
            $request->validate(Attendance::FirstTypeRules());
        else if ($request->attendance_type == Attendance::$SECOND_TYPE) {
            $request->validate([
                'sessions.times' => 'required|integer',
            ]);
            $request->validate(Attendance::SecondTypeRules($request->sessions['times']));
        }
        if ($request->attendance_type == Attendance::$SECOND_TYPE && !Auth::User()->can('site/attendance/bulck/attendace')) {
            return HelperController::api_response_format(200, 'does not have the right permissions ');
        }
        $attendance = self::createAttendance($request);
        $attendance->allowed_levels = unserialize($attendance->allowed_levels);
        $attendance->allowed_courses = unserialize($attendance->allowed_courses);
        $attendance->allowed_classes = unserialize($attendance->allowed_classes);
        if ($request->attendance_type == Attendance::$FIRST_TYPE && $request->graded == 1) {
            $jop = (new  AttendanceGradeItems($request->all(),Attendance::$FIRST_TYPE,null));
            dispatch($jop);
            return HelperController::api_response_format(200, $attendance, 'attendance created successfully with grade Items');
        } elseif ($request->attendance_type == Attendance::$SECOND_TYPE) {
            $user_id = Auth::User()->id;

            foreach ($request['levels'] as $level) {
                $request['type'] = [$request['type']];
                $request['classes'] = $level['classes'];
                foreach ($level['periods'] as $period) {
                    $req = new Request([
                        'year' => $request['year'],
                        'segments' => [$request['segment']],
                        'type' => $request['type'],
                        'levels' => [$level['id']],
                        'classes' => $level['classes'],
                        'courses' => [$period['courses']]
                    ]);
                    $course_segments = GradeCategoryController::getCourseSegmentWithArray($req);
                    $gradeCategories = GradeCategory::where('name', $period['grade_category_name'])->whereIn('course_segment_id', $course_segments)->get();
                    $job = new AttendanceGradeItems($request->all(), Attendance::$SECOND_TYPE,$gradeCategories);
                    dispatch($job);
                    $job= new AttendanceSessionsJob($request->all(), $attendance->id, $course_segments, $period,$user_id);
                    dispatch($job);
                }
            }
        }
        return HelperController::api_response_format(200, $attendance, 'attendance created successfully');
    }

//    public static function second_Type_sessions($request, $attendance_id, $course_segments, $periods)
//    {
//        foreach ($course_segments as $course_segment) {
//            $alldays = Attendance::getAllWorkingDays($request['start'], $request['end']);
//            $FromTodays = Attendance::getAllWorkingDays($periods['from'], $periods['to']);
//            if (Attendance::check_in_array($alldays, $FromTodays)) {
//                foreach ($FromTodays as $day) {
//                    for ($i = 0; $i < $request['sessions']['times']; $i++) {
//                        AttendanceSession::create([
//                            'attendance_id' => $attendance_id,
//                            'taker_id' => $user_id,
//                            'date' => $day,
//                            'course_segment_id' => $course_segment,
//                            'from' => $request['sessions']['time'][$i]['start'],
//                            'to' => $request['sessions']['time'][$i]['end']
//                        ]);
//                    }
//                }
//            }
//        }
//    }


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
