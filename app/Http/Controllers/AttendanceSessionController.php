<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\AttendanceSession;
use Carbon\Carbon;
use Auth;

class AttendanceSessionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:attendance/add-session'],   ['only' => ['store']]);
        $this->middleware(['permission:attendance/get-session'],   ['only' => ['index','show']]);
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
        ]);
        $attendanceSession=AttendanceSession::where('id', '!=', null);

        if(isset($request->attendance_id))
            $attendanceSession->where('attendance_id',$request->attendance_id);

        if(isset($request->class_id))
            $attendanceSession->where('class_id',$request->class_id);

        if(isset($request->start_date))
            $attendanceSession->where('start_date','>=', $request->start_date);

        if(isset($request->from))
            $attendanceSession->where('from','>=', $request->from);

        if(isset($request->to))
            $attendanceSession->where('to','<', $request->to);

        return HelperController::api_response_format(200 , $attendanceSession->get()->paginate(HelperController::GetPaginate($request)) , __('messages.attendance_session.list'));
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
            'repeated' => 'required|in:0,1',
            'sessions' => 'required|array',
            'start_date' => 'required|date',
            'sessions.*.day' => 'in:SA,SU,MO,TU,TH,friday|required_if:repeated,==,1',
            'sessions.*.from' => 'required|date_format:H:i',
            'sessions.*.to' => 'required|date_format:H:i|after:sessions.*.from',
            'repeated_until' => 'required_if:repeated,==,1|date'
        ]);
        $weekMap = ['SU','MO','TU','WE','TH','FR','SA'];
        foreach($request->sessions as $session)
        {
            if(isset($request->repeated_until))
            {
                if(array_search($session['day'],$weekMap) < carbon::parse($request->start_date)->dayOfWeek )
                    $attendancestart=(carbon::parse($request->start_date)->subDay(
                        Carbon::parse($request->start_date)->dayOfWeek - array_search($session['day'],$weekMap))->addDays(7));

                if(array_search($session['day'],$weekMap) >= carbon::parse($request->start_date)->dayOfWeek )
                $attendancestart=(carbon::parse($request->start_date)->addDays(
                    array_search($session['day'],$weekMap) - Carbon::parse($request->start_date)->dayOfWeek));

                $end=$attendancestart->diffInSeconds($session['to']);
                while($attendancestart <= Carbon::parse($request->repeated_until)){
                    $attendance=AttendanceSession::firstOrCreate([
                        'name' => $request->name,
                        'attendance_id' => $request->attendance_id,
                        'class_id' => $request->class_id,
                        'start_date' => $attendancestart,
                        'from' => $session['from'],
                        'to' => $session['to'],
                        'created_by' => Auth::id()
                    ]);
                    $attendancestart=$attendancestart->addDays(7);
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

        return HelperController::api_response_format(200 , null , __('messages.attendance.update'));
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
}
