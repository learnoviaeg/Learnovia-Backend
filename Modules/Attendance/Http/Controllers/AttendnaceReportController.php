<?php

namespace Modules\Attendance\Http\Controllers;
use App\Http\Controllers\GradeCategoryController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Attendance\Entities\AttendanceSession;
use Illuminate\Routing\Controller;
use App\CourseSegment;
use Modules\Bigbluebutton\Entities\BigbluebuttonModel;
use Modules\Attendance\Entities\AttendanceLog;
use App\Http\Controllers\HelperController;


class AttendnaceReportController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function attendance_report(Request $request)
    {
        /*  
            start_date ,end_date @required
            year @optional  if no  get_current 
            get_course_segments
            online and offline attendance_sessions  
    */
  

    $request->validate([
        'year' => 'exists:academic_years,id',
        'type' => 'exists:academic_types,id',
        'level' => 'exists:levels,id',
        'class' => 'exists:classes,id',
        'courses' => 'array|exists:courses,id',
        'segment' => 'exists:segments,id',
        'start_date' => 'date|required',
        'end_date' => 'date|required|after:start_date',        

    ]);

    $CS = GradeCategoryController::getCourseSegment($request);
    if(count($CS)<=0)
        return HelperController::api_response_format(404,null ,'No course segments available ');
    $courses_id = CourseSegment::whereIn('id',$CS)->pluck("course_id")->unique()->values();
    $bbb_ides = BigbluebuttonModel::whereIn('course_id',$courses_id)
                                        ->where('start_date','>=',$request->start_date)
                                        ->where('start_date','<=',$request->end_date)
                                        ->pluck('id');
    $sessions_ides = AttendanceSession::whereIn('course_id',$courses_id)
                                        ->where('start_date','>=',$request->start_date)
                                        ->where('start_date','<=',$request->end_date)
                                        ->pluck('id');

    $online_logs = AttendanceLog::whereIn('session_id',$bbb_ides)->where('type','online')->pluck('student_id');
    $offline_logs = AttendanceLog::whereIn('session_id',$sessions_ides)->where('type','offline')->pluck('student_id');
    $daily_logs = AttendanceLog::where('attendnace_type','daily')
                                        ->where('taken_at','>=',$request->start_date)
                                        ->where('taken_at','<=',$request->end_date)
                                        ->pluck('student_id');
    

    }

    
}
