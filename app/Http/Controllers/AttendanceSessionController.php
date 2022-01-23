<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\AttendanceSession;
use App\Attendance;
use App\GradeCategory;
use Carbon\Carbon;
use App\UserGrader;
use App\SessionLog;
use App\Events\TakeAttendanceEvent;
use Auth;

class AttendanceSessionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:attendance/add-session'],   ['only' => ['store']]);
        $this->middleware(['permission:attendance/get-sessions'],   ['only' => ['index','show']]);
        $this->middleware(['permission:attendance/delete-session'],   ['only' => ['destroy']]);
        $this->middleware(['permission:attendance/edit-session'],   ['only' => ['update']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            'attendance_id' => 'exists:attendances,id',
            'class_id' => 'exists:classes,id',
            'start_date' => 'date',
            'from' => 'date_format:H:i',
            'to' => 'date_format:H:i|after:from',
            'current' => 'in:month,week,day', //current
            'filter' => 'integer|between:1,12'
        ]);
        $attendanceSession=AttendanceSession::where('id', '!=', null);

        if(isset($request->attendance_id))
            $attendanceSession->where('attendance_id',$request->attendance_id);

        if(isset($request->class_id))
            $attendanceSession->where('class_id',$request->class_id);

        if(isset($request->start_date))
            $attendanceSession->where('start_date','>=', $request->start_date);

        if(isset($request->filter))
            $attendanceSession->whereMonth('start_date', $request->filter);

        // dd(Carbon::now()->format('m'));
        if(isset($request->current))
        {
            if($request->current == 'day')
                $attendanceSession->whereDay('start_date', Carbon::now()->format('j'));

            if($request->current == 'week')
                // from saterday to friday
                $attendanceSession->where('start_date', '>=', Carbon::now()->startOfWeek()->subDay(2))
                ->where('start_date', '<=', Carbon::now()->endOfWeek()->subDay(2));

            if($request->current == 'month')
                $attendanceSession->whereMonth('start_date', Carbon::now()->format('m'));
        }

        if(isset($request->from))
            $attendanceSession->where('from','>=', $request->from);

        if(isset($request->to))
            $attendanceSession->where('to','<', $request->to);

        return HelperController::api_response_format(200 , $attendanceSession->with('class','attendance.courses')->get()->paginate(HelperController::GetPaginate($request)) , __('messages.attendance_session.list'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'attendance_id' => 'required|exists:attendances,id',
            'class_id' => 'required|exists:classes,id',
            'course_id' => 'required|exists:courses,id',
            'repeated' => 'required|in:0,1',
            'sessions' => 'required_if:repeated,==,1|array',
            'start_date' => 'required|date',
            'sessions.*.day' => 'in:SA,SU,MO,TU,TH,friday|required_if:repeated,==,1',
            'sessions.*.from' => 'required|date',
            'sessions.*.to' => 'required|date|after:sessions.*.from',
            'repeated_until' => 'required_if:repeated,==,1|date'
        ]);
        $weekMap = ['SU','MO','TU','WE','TH','FR','SA'];
        $attendance=Attendance::where('id',$request->attendance_id)
                ->whereDate('start_date', '<=', $request->start_date)
                ->whereDate('end_date', '>=', $request->start_date)
                ->first();
        if(!isset($attendance))
            return HelperController::api_response_format(200 , null , __('messages.attendance_session.cannot_add'));

        if($request->repeated == 1)
        {
            foreach($request->sessions as $session)
            {
                if(array_search($session['day'],$weekMap) < carbon::parse($request->start_date)->dayOfWeek )
                    $attendancestart=(carbon::parse($request->start_date)->subDay(
                        Carbon::parse($request->start_date)->dayOfWeek - array_search($session['day'],$weekMap))->addDays(7));

                if(array_search($session['day'],$weekMap) >= carbon::parse($request->start_date)->dayOfWeek )
                $attendancestart=(carbon::parse($request->start_date)->addDays(
                    array_search($session['day'],$weekMap) - Carbon::parse($request->start_date)->dayOfWeek));

                if($attendancestart > Carbon::parse($request->repeated_until) )
                    return HelperController::api_response_format(200 , null , __('messages.attendance_session.wrong_day'));

                while($attendancestart <= Carbon::parse($request->repeated_until)){
                    $attendance=AttendanceSession::firstOrCreate([
                        'name' => $request->name,
                        'attendance_id' => $request->attendance_id,
                        'class_id' => $request->class_id,
                        'course_id' => $request->course_id,
                        'start_date' => $attendancestart,
                        'from' => Carbon::parse($session['from'])->format('H:i'),
                        'to' => Carbon::parse($session['to'])->format('H:i'),
                        'created_by' => Auth::id()
                    ]);
                    $attendancestart=$attendancestart->addDays(7);                   
                }   
            }
        }      
        else
        {
            $attendance=AttendanceSession::firstOrCreate([
                'name' => $request->name,
                'attendance_id' => $request->attendance_id,
                'class_id' => $request->class_id,
                'start_date' => $request->start_date,
                'from' => null,
                'to' => null,
                'created_by' => Auth::id()
            ]);
        } 

        return HelperController::api_response_format(200 , null , __('messages.attendance_session.add'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $attendanceSession=AttendanceSession::find($id);
        return HelperController::api_response_format(200 , $attendanceSession , null);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'string',
            'attendance_id' => 'exists:attendances,id',
            'class_id' => 'exists:classes,id',
            'from' => 'date',
            'to' => 'date|after:from',
        ]);

        $attendanceSession=AttendanceSession::find($id);

        $attendanceSession->update([
            'name' => ($request->name) ? $request->name : $attendanceSession->name,
            'attendance_id' => ($request->attendance_id) ? $request->attendance_id : $attendanceSession->attendance_id,
            'class_id' => ($request->class_id) ? $request->class_id : $attendanceSession->class_id,
            'start_date' => ($request->start_date) ? $request->start_date : $attendanceSession->start_date,
            'from' => ($request->from) ? $request->from : $attendanceSession->from,
            'to' => ($request->to) ? $request->to : $attendanceSession->to,
        ]);

        return HelperController::api_response_format(200 , null , __('messages.attendance_session.update'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $attendanceSession=AttendanceSession::find($id);
        $attendanceSession->delete();

        return HelperController::api_response_format(200 , null , __('messages.attendance_session.delete'));
    }

    public function takeAttendance(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:attendance_sessions,id',
            'user_id' => 'required|array',
            'user_id.*.status' => 'required|string',
            'user_id.*.id' => 'required|exists:users,id',
        ]);

        $session=AttendanceSession::find($request->session_id);
        $gradeCat=GradeCategory::where('instance_type','Attendance')->where('instance_id',$session->attendance_id)
                    ->where('type','item')->first();

        foreach($request->user_id as $user)
        {
            SessionLog::updateOrCreate([
                'session_id' => $request->session_id,
                'user_id' => $user['id'],
                'taken_by' => Auth::id()
            ],[
                'status' => $user['status'],
            ]);

            $allSessionsOfUser=AttendanceSession::where('attendance_id',$session->attendance_id)->pluck('id');
            $sessionsPresent= SessionLog::whereIn('session_id',$allSessionsOfUser)->where('status','Present')->count();
            $sessionsLateExcuse= SessionLog::whereIn('session_id',$allSessionsOfUser)->whereIn('status',['Excuse','Late'])->count();

            // dd($allSessionsOfUser);
            $gardeOfSessions=$sessionsPresent+($sessionsLateExcuse *2);
            $grader = UserGrader::updateOrCreate(
                ['item_id'=>$gradeCat->id, 'item_type' => 'item', 'user_id' => $user['id']],
                ['grade' =>  ($gardeOfSessions * $gradeCat->max)/100 , 'percentage' => ((($gardeOfSessions * $gradeCat->max)/100)*100)/20 ]
            );
            // event(new TakeAttendanceEvent($user['id']));
        }

        return HelperController::api_response_format(200 , null , __('messages.attendance_session.taken'));
    }

    public function LogsAttendance(Request $request)
    {
        $all=SessionLog::with('user')->get();
        return HelperController::api_response_format(200 , $all,null);
    }
}
