<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AttendanceSessionController extends Controller
{
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
            'from' => 'date',
            'to' => 'date|after:start_date',
        ]);
        $attendanceSession=AttendanceSession::where('id', '!=', null);

        if(isset($request->attendance_id))
            $attendanceSession->where('attendance_id',$request->attendance_id);

        if(isset($request->class_id))
            $attendanceSession->where('class_id',$request->class_id);

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
            'sessions' => 'array',
            'sessions.*.from' => 'required|date',
            'sessions.*.to' => 'required|date|after:from',
            'sessions.*.day' => 'required|in:saterday, sunday, monday, tuesday, thuresday, friday', //|required_if:repeated,==,1
            'repeat_until' => 'required_if:repeated,==,1|date'
        ]);

        foreach($request->sessions as $session)
        {
                    $attendance=AttendanceSession::firstOrCreate([
                        'name' => $request->name,
                        'attendance_id' => $request->attendance_id,
                        'class_id' => $request->class_id,
                        'from' => 'required|date',
                        'to' => 'required|date|after:start_date',
                        'created_by' => Auth::id()
                    ]);
        }

        return HelperController::api_response_format(200 , null , __('messages.attendance.add'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
