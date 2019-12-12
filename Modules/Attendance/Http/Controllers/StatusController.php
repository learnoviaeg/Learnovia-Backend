<?php

namespace Modules\Attendance\Http\Controllers;
use Modules\Attendance\Entities\AttendanceStatus;
use App\Http\Controllers\HelperController;
use Modules\Attendance\Entities\AttendanceLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use App\Enroll;
use App\CourseSegment;
class StatusController extends Controller
{
    
    public function Add(Request $request)
    {
        $request->validate([
            'attendance_id' => 'required|exists:attendances,id',
            'letter'        =>'required|string',
            'descrption'    =>'required|string',
            'grade'         =>'required|integer',
            'visible'       =>'boolean',
        ]);
        $Data=[
            'attendance_id' => $request->attendance_id,
            'letter'=> $request->letter,
            'descrption' => $request->descrption,
            'grade' =>$request->grade,
        ];
        if(isset($request->visible)) {
            $Data['visible'] = $request->visible;
        }
        $Status=AttendanceStatus::create($Data);

        return HelperController::api_response_format(200,$Status,'Status Created Successfully');
    }

    public function Update(Request $request)
    {
        $request->validate([
            'id'            => 'required|exists:attendance_statuses,id',
            'attendance_id' => 'exists:attendances,id',
            'letter'        =>'string',
            'descrption'    =>'string',
            'grade'         =>'integer',
            'visible'       =>'boolean',
        ]);
        $status=AttendanceStatus::find($request->id);
        if($request->filled('attendance_id')) {
            $status->attendance_id = $request->attendance_id;
        }
        if($request->filled('letter')) {
            $status->letter = $request->letter;
        }
        if($request->filled('descrption')) {
            $status->descrption = $request->descrption;
        }
        if($request->filled('visible')) {
            $status->visible = $request->visible;
        }
        $status->save();
        return HelperController::api_response_format(200,$status,'Status Updated Successfully');
    }
    public function Delete(Request $request)
    {
        $request->validate([
            'id'            => 'required|exists:attendance_statuses,id',
        ]);
        $status=AttendanceStatus::find($request->id);
        $status->delete();
        return HelperController::api_response_format(201,'Status Deleted Successfully');
    }

    public function GetStatus(Request $request)
    {
        $request->validate([
            'session_id'  => 'nullable|exists:attendance_sessions,id',
        ]);

        $user_id[]= Auth::id();
        if (Auth::user()->can('site/course/teacher')) {
            $Course_Segment = Enroll::where('user_id',$user_id)->pluck('course_segment');
            $Course_Segments = CourseSegment::whereIn('id',$Course_Segment)->where('is_active','1')->pluck('id');
            $Students= (Enroll::whereIn('course_segment',$Course_Segments)->pluck('user_id'))->unique();
            $user_id= $Students;
        }
       
        $logs = AttendanceLog::whereIn('student_id',$user_id)->with('status')->get();
        if($request->filled('session_id'))
             $logs = AttendanceLog::whereIn('student_id',$user_id)->where('session_id',$request->session_id)->with('status')->get();
        foreach($logs as $log){
           $st[$log->status->letter]= $log->status->id;
        }
        $all_Status = array_unique($st);
        foreach($all_Status as $key => $Letter){
            if($request->filled('session_id'))
                 $count[$key][] = (AttendanceLog::whereIn('student_id',$user_id)->where('status_id',$Letter)->where('session_id',$request->session_id)->get())->count();
            else
                $count[$key][] = (AttendanceLog::whereIn('student_id',$user_id)->where('status_id',$Letter)->get())->count();
        }
        return $count;
    }
  
}
