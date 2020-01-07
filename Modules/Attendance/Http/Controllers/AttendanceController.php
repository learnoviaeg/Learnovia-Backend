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
use App\Level;
use Modules\Attendance\Entities\AttendanceStatus;
use Modules\Attendance\Jobs\Attendance_sessions;
use Modules\Attendance\Jobs\AttendanceGradeItems;
use Modules\Attendance\Jobs\AttendanceSessionsJob;
use Modules\QuestionBank\Entities\quiz;

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
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/get-attendence', 'title' => 'get attendence']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/get-users-in-session', 'title' => 'get all users in session']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/get-all-taken-users-in-session', 'title' => 'get all taken users in session']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/add-session', 'title' => 'add session']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'site/attendance/bulck/attendace', 'title' => 'add session']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/status/add', 'title' => 'add attendance status']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/status/update', 'title' => 'update attendance status']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/status/delete', 'title' => 'delete attendance status']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/attend-report', 'title' => 'report of attendance']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/status/get', 'title' => 'get attendance status']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'attendance/get-session', 'title' => 'get session and status']);
        $role = \Spatie\Permission\Models\Role::find(1);
        $role->givePermissionTo('attendance/add');
        $role->givePermissionTo('attendance/add-log');
        $role->givePermissionTo('attendance/get-users-in-attendence');
        $role->givePermissionTo('attendance/get-users-in-session');
        $role->givePermissionTo('attendance/get-all-taken-users-in-session');
        $role->givePermissionTo('attendance/add-session');
        $role->givePermissionTo('site/attendance/bulck/attendace');
        $role->givePermissionTo('attendance/status/add');
        $role->givePermissionTo('attendance/status/update');
        $role->givePermissionTo('attendance/status/delete');
        $role->givePermissionTo('attendance/status/get');

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
        $attendane = Attendance::find($request->id);
        $Course_Segments = Attendance::get_CourseSegments_by_AttendenceID($request->id);
        $users =   User::whereIn('level', unserialize($attendane->allowed_levels))->get();
        if(($Course_Segments)== null){
        $users = Enroll::whereIn('course_segment', $Course_Segments)->with('user')->get();
        }
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
        } elseif ($request->attendance_type == Attendance::$SECOND_TYPE ) {
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
            'allowed_levels' => isset($levels)?serialize($levels):null,
            'allowed_courses' => isset($courses)?serialize($courses):null ,
            'allowed_classes' => isset($classes)?serialize($classes):null ,
            'year_id' => $request->year,
            'segment_id' => $request->segment,
            'type_id' => $request->type,
            'start_date' => $request->start,
            'end_date' => $request->end

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
        $user_id = Auth::User()->id;
        $attendance = self::createAttendance($request);
        $attendance->allowed_levels =isset($attendance->allowed_levels)? $attendance->allowed_levels:null;
        $attendance->allowed_courses = isset($attendance->allowed_courses)? unserialize($attendance->allowed_courses):null;
        if ($request->attendance_type == Attendance::$FIRST_TYPE && $request->graded == 1) {
            $jop = (new  AttendanceGradeItems($request->all(), Attendance::$FIRST_TYPE, null));
            dispatch($jop);
            return HelperController::api_response_format(200, $attendance, 'attendance created successfully with grade Items');
        } elseif ($request->attendance_type == Attendance::$SECOND_TYPE &&  $request->graded == 1) {
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
                    $job = new AttendanceGradeItems($request->all(), Attendance::$SECOND_TYPE, $gradeCategories);
                    dispatch($job);
                    $job = new AttendanceSessionsJob($request->all(), $attendance->id, $course_segments, $period, $user_id);
                    dispatch($job);
                }
            }
        }elseif ($request->attendance_type == Attendance::$SECOND_TYPE  && $request->graded == 0){
            $request->validate([
                'allowed_levels' => 'array',
                'allowed_levels.*' => 'exists:levels,id',
            ]);

            if(isset($request->allowed_levels)){
            $attendance->update([
                'allowed_levels' => serialize($request->allowed_levels)
            ]);
                $attendance->allowed_levels = unserialize($attendance->allowed_levels);
            }
            $alldays = Attendance::getAllWorkingDays($request->start, $request->end);
                foreach ($alldays as $day) {
                    for ($i = 0; $i < $request->sessions['times']; $i++) {
                        AttendanceSession::create([
                            'attendance_id' =>  $attendance->id,
                            'taker_id' => $user_id,
                            'date' => $day,
                            'course_segment_id' => null,
                            'from' => $request->sessions['time'][$i]['start'],
                            'to' => $request->sessions['time'][$i]['end']
                        ]);
                    }
                }
            }
        return HelperController::api_response_format(200, $attendance, 'attendance created successfully');
    }

    public function createSession(Request $request)
    {
        $request->validate([
            'attendance_id' => 'required|exists:attendances,id',
        ]);
        $attendance = Attendance::find($request->attendance_id);
        $user_id = Auth::User()->id;
        $attendance->allowed_levels = unserialize($attendance->allowed_levels);
        switch ($attendance->type) {
            case  1 :
                $array = [
                    'days' => 'array|required|min:1',
                    'days.*.name' => 'required|string',
                    'days.*.from' => 'required|regex:/(\d+\:\d+)/',
                    'days.*.to' => 'required|regex:/(\d+\:\d+)/',
                    'days.*.date' => 'required|date',
                    'repeat_untill' => 'date',
                    'levels' => 'required|array|min:1',
                    'levels.*.id' => 'exists:levels,id',
                    'levels.*.class' => 'required|exists:classes,id',
                    'levels.*.course' => 'required|exists:courses,id',
                ];
                $request->validate($array);
                foreach ($request->levels as $level) {
                    $req = new Request([
                        'year' => $attendance->year_id,
                        'segments' => [$attendance->segment_id],
                        'type' => [$attendance->type_id],
                        'levels' => [$level['id']],
                        'classes' => [$level['class']],
                        'courses' => [$level['course']]
                    ]);
                    $course_segments = GradeCategoryController::getCourseSegmentWithArray($req);
                    if (!((Attendance::check_in_array($attendance->allowed_classes, $req->classes)) &&
                        (Attendance::check_in_array($attendance->allowed_levels, $req->levels)) &&
                        (Attendance::check_in_array($attendance->allowed_courses, $req->courses)))
                    ) {
                        return HelperController::api_response_format(400, 'Something wrong with untill date');
                    }

                    if (!isset($request->repeat_untill)) {
                        foreach ($course_segments as $course_segment) {
                            foreach ($request->days as $day) {
                                AttendanceSession::create([
                                    'attendance_id' => $attendance->id,
                                    'taker_id' => $user_id,
                                    'date' => $day['date'],
                                    'from' => $day['from'],
                                    'to' => $day['to'],
                                    'course_segment_id' => $course_segment
                                ]);
                            }
                        }
                        return HelperController::api_response_format(200, 'Sessions are created successfully');
                    }
                    if ($request->repeat_untill > $attendance->end_date) {
                        return HelperController::api_response_format(400, 'Something wrong with data');
                    }

                    foreach ($course_segments as $course_segment) {
                        foreach ($request->days as $day) {
                            $startDate = Carbon::parse(Carbon::parse($day['date']))->next(Attendance::GetCarbonDay($day['name']));
                            $endDate = Carbon::parse($request->repeat_untill);

                            for ($date = $startDate; $date->lte($endDate); $date->addWeek()) {
                                $alldays[] = $date->format('Y-m-d');
                                AttendanceSession::create([
                                    'attendance_id' => $attendance->id,
                                    'taker_id' => $user_id,
                                    'date' => $date->format('Y-m-d'),
                                    'from' => $day['from'],
                                    'to' => $day['to'],
                                    'course_segment_id' => $course_segment
                                ]);
                            }
                        }
                    }
                }
                break;
            case 2:
                $request->validate([
                    'sessions.times' => 'required|integer',
                ]);

                $array = [
                    'levels' => 'required|array|min:1',
                    'levels.*.id' => 'exists:levels,id',
                    'levels.*.classes' => 'required|array',
                    'levels.*.classes.*' => 'required|exists:classes,id',
                    'levels.*.periods' => 'required|array',
                    'levels.*.periods.*.courses' => 'required|exists:courses,id',
                    'levels.*.periods.*.from' => 'required|date',
                    'levels.*.periods.*.to' => 'required|date',
                    'levels.*.periods.*.grade_category_name' => 'required|string|exists:grade_categories,name',
                    'sessions' => 'required',
                    'sessions.time.*.start' => 'required|regex:/(\d+\:\d+)/',
                    'sessions.time.*.end' => 'required|regex:/(\d+\:\d+)/',
                ];
                $array['sessions.time'] = 'required|array|size:' . $request->sessions['times'];
                $request->validate($array);
                $request['start'] = $attendance->start_date;
                $request['end'] = $attendance->end_date;
                foreach ($request->levels as $level) {
                    foreach ($level['periods'] as $period) {
                        $req = new Request([
                            'year' => $attendance->year_id,
                            'segments' => [$attendance->segment_id],
                            'type' => [$attendance->type_id],
                            'levels' => [$level['id']],
                            'classes' => $level['classes'],
                            'courses' => [$period['courses']]
                        ]);
                        if (!((Attendance::check_in_array($attendance->allowed_classes, $req->classes)) &&
                            (Attendance::check_in_array($attendance->allowed_levels, $req->levels)) &&
                            (Attendance::check_in_array($attendance->allowed_courses, $req->courses)))
                        ) {
                            return HelperController::api_response_format(400, 'Something wrong with data');
                        }
                        $course_segments = GradeCategoryController::getCourseSegmentWithArray($req);
                        $job = new AttendanceSessionsJob($request->all(), $attendance->id, $course_segments, $period, $user_id);
                        dispatch($job);
                    }
                }
                return HelperController::api_response_format(200, 'Sessions are created successfully');
        }
        return HelperController::api_response_format(200, 'Sessions are created successfully');
    }

    public function viewstudentsinsessions(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:attendance_sessions,id',
        ]);
        $course_segment = AttendanceSession::where('id', $request->session_id)->pluck('course_segment_id');
        $users_ids = Enroll::where('course_segment', $course_segment[0])->pluck('user_id');
        $logs = AttendanceLog::where('session_id', $request->session_id)->whereIn('student_id', $users_ids)->get();
        $users = User::whereIn('id', $users_ids)->get();
        foreach ($users as $user) {
            $user['flag'] = false;
            $temp = collect();
            foreach ($logs as $log) {
                if ($log->student_id == $user->id) {
                    $user['flag'] = true;
                    $temp->push($log);
                }
            }
            $user['log'] = $temp->toArray();
        }
        return HelperController::api_response_format(200, $users, 'Users are.....');
    }


    public function GetAllSessionDay(Request $request)
    {
        $data=array();
        $i=0;
        $courses=Enroll::where('user_id',Auth::id())->pluck('course_segment');
        $Sessions=AttendanceSession::whereIn('course_segment_id',$courses)->where('date',Carbon::today())->get();
        if($request->filled('id'))
            $Sessions=AttendanceSession::where('id',$request->id)->get();
        foreach($Sessions as $session)
        {
            $data[$i]['course']=$session->Course_Segment->courses[0]->name;
            $data[$i]['from']= $session->from;
            $data[$i]['to']= $session->to;
            $data[$i]['status']= '-';
            if(count($session->logs)>0)
                $data[$i]['status']= $session->logs[0]->status->letter;
            if($request->user()->can('site/course/teacher'))
                $data[$i]['status']= '-';
            $i++;
        }
        return HelperController::api_response_format(200, $data, 'there is your session & status');
    }
    public function Attendance_Report(Request $request)
    {
        $enrolls=Enroll::where('user_id',Auth::id())->get();
        $CourseSeg=$enrolls->pluck('course_segment');
        $role=$enrolls->pluck('role_id')->first();

        $All_Sessions=AttendanceSession::whereIn('course_segment_id', $CourseSeg)->get();

        ////if user is teacher ///if($role == 4)
        if($request->user()->can('site/course/teacher'))
            return HelperController::api_response_format(200, $All_Sessions, 'there is all your sessions');

        ////get all attendance assoicated the user|student ///if($role == 3)
        if($request->user()->can('site/course/student'))
        {
            $All_Attendance=AttendanceLog::where('student_id', Auth::id())->with('status')->get();
            if($request->filled('session'))
                $All_Attendance=AttendanceLog::where('session_id',$request->session)->where('student_id', Auth::id())
                                                ->with('status')->get();

            foreach($All_Attendance as $all)
                $all->getPrecentageStatus(count($All_Attendance));

            return HelperController::api_response_format(200, $All_Attendance, 'there is all your logs');
        }
    }
    public function getAttendance()
    {
        $attendance=Attendance::get(['name','allowed_levels','type']);
        foreach($attendance as $attend)
        {
            $i=0;
            $attends=unserialize($attend->allowed_levels);
            foreach($attends as $levels)
            {
                $level=Level::find($levels);
                $att[$i]=$level->name;
                $attend->allowed_levels=$att;
                $i++;
            }
            if($attend->type == 1)
                $attend->type = 'per session';
            if($attend->type == 2)
                $attend->type = 'Daily';
        }
        return HelperController::api_response_format(200, $attendance, 'there is all your logs');
    }
}
