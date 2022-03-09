<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\AttendanceSession;
use App\Attendance;
use App\GradeCategory;
use Carbon\Carbon;
use App\Exports\AttendanceLogsExport;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\UserGrader;
use App\SessionLog;
use App\WorkingDay;
use App\Events\TakeAttendanceEvent;
use Auth;
use App\Repositories\ChainRepositoryInterface;

class AttendanceSessionController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
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
            'start_date' => 'date',
            'from' => 'date_format:H:i',
            'to' => 'date_format:H:i|after:from',
            'current' => 'in:month,week,day', //current
            'filter' => 'integer|between:1,12',
            'attendance_type' => 'in:Per Session,Daily',
            'search' => 'string'
        ]);
        $attendanceSession=AttendanceSession::where('id', '!=', null);

        if(isset($request->search))
            $attendanceSession->where('name', 'LIKE' , "%$request->search%");

        if(isset($request->attendance_id))
            $attendanceSession->where('attendance_id',$request->attendance_id);

        $enrolls = $this->chain->getEnrollsByManyChain($request)->where('user_id',Auth::id());
        $classes=$enrolls->pluck('group')->unique();
        $attendanceSession->whereIn('class_id',$classes);

        if(isset($request->start_date))
            $attendanceSession->where('start_date','>=', $request->start_date);

        if(isset($request->filter))
            $attendanceSession->whereMonth('start_date', $request->filter);

        if(isset($request->current))
        {
            if($request->current == 'day')
                $attendanceSession->whereDay('start_date', Carbon::now()->format('j'))->whereMonth('start_date',Carbon::now()->format('m'));

            if($request->current == 'week'){
                // from saterday to friday
                if(Carbon::now()->format('l') == 'Saturday')
                    $attendanceSession->where('start_date', '>=', Carbon::now()->addDay(7))
                        ->where('start_date', '<=', Carbon::now()->addDay(7));

                else
                    for($i=1;$i<=7;$i++)
                    {
                        $day=Carbon::now()->subDay($i)->format('l');
                        if($day == 'Saturday')
                            $attendanceSession->where('start_date', '>=', Carbon::now()->subDay($i))
                                ->where('start_date', '<=', Carbon::now()->subDay($i)->addDay(7));
                    }
            }

            if($request->current == 'month')
                $attendanceSession->whereMonth('start_date', Carbon::now()->format('m'));
        }

        if(isset($request->from))
            $attendanceSession->where('from','>=', $request->from);

        if(isset($request->to))
            $attendanceSession->where('to','<', $request->to);

        $callback = function ($qu) use ($request) {
            if(isset($request->attendance_type))
                $qu->where('attendance_type',$request->attendance_type);
        };

        return HelperController::api_response_format(200 , $attendanceSession->whereHas('attendance', $callback)
            ->with(['class','attendance.courses','attendance'=>$callback])->get()
            ->paginate(HelperController::GetPaginate($request)) , __('messages.attendance_session.list'));
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
            'class_id' => 'exists:classes,id',
            'course_id' => 'required|exists:courses,id',
            'repeated' => 'required|in:0,1',
            'sessions' => 'required_if:repeated,==,1|array',
            'start_date' => 'required|date',
            // 'sessions.*.day' => 'in:SA,SU,MO,TU,WE,TH,FR|required_if:repeated,==,1',
            'sessions.*.from' => 'required|date',
            'sessions.*.to' => 'required|date|after:sessions.*.from',
            'repeated_until' => 'required_if:repeated,==,1|date',
        ]);
        $weekMap = ['Sunday','Monday','Tuesday','Wendesday','Thuresday','Friday','Saturday'];
        $attendance=Attendance::find($request->attendance_id);

        if(Carbon::parse($request->start_date) < Carbon::parse($attendance->start_date))
            return HelperController::api_response_format(400 , null , __('messages.attendance_session.invalid_start_date').$attendance->start_date .','.$attendance->end_date);

        if($request->repeated == 1)
        {
            $repeated_until=$request->repeated_until;
            if(Carbon::parse($request->repeated_until) > Carbon::parse($attendance->end_date))
                return HelperController::api_response_format(400 , null , __('messages.attendance_session.invalid_end_date').$attendance->start_date .','.$attendance->end_date);

            foreach($request->sessions as $session)
            {
                if($attendance->attendance_type == 'Per Session')
                {
                    $request->validate([
                        'class_id' => 'required|exists:classes,id',
                        'sessions.*.day' => 'in:Sunday,Monday,Tuesday,Wendesday,Thuresday,Friday,Saturday|required_if:repeated,==,1',
                    ]);

                    if(array_search($session['day'],$weekMap) < carbon::parse($request->start_date)->dayOfWeek )
                        $attendancestart=(carbon::parse($request->start_date)->subDay(
                            Carbon::parse($request->start_date)->dayOfWeek - array_search($session['day'],$weekMap))->addDays(7));
    
                    if(array_search($session['day'],$weekMap) >= carbon::parse($request->start_date)->dayOfWeek )
                        $attendancestart=(carbon::parse($request->start_date)->addDays(
                            array_search($session['day'],$weekMap) - Carbon::parse($request->start_date)->dayOfWeek));
        
                    while($attendancestart <= Carbon::parse($repeated_until)){
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
                else
                {
                    $request->validate([
                        'included_days' => 'required|array',
                        'included_days.*' => 'exists:working_days,id'
                    ]);

                    foreach(WorkingDay::whereIn('id',$request->included_days)->pluck('name') as $day)
                    {
                        if($day->status == 0)
                            continue;

                        if(array_search($day->day,$weekMap) < carbon::parse($request->start_date)->dayOfWeek )
                            $attendancestart=(carbon::parse($request->start_date)->subDay(
                                Carbon::parse($request->start_date)->dayOfWeek - array_search($day->day,$weekMap))->addDays(7));
    
                        if(array_search($day->day,$weekMap) >= carbon::parse($request->start_date)->dayOfWeek )
                            $attendancestart=(carbon::parse($request->start_date)->addDays(
                                array_search($day->day,$weekMap) - Carbon::parse($request->start_date)->dayOfWeek));

                        while($attendancestart <= Carbon::parse($repeated_until)){
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
            }
        }      
        else
        {
            $attendance=AttendanceSession::firstOrCreate([
                'name' => $request->name,
                'attendance_id' => $request->attendance_id,
                'class_id' => $request->class_id,
                'start_date' => $request->start_date,
                'from' => Carbon::parse($request->start_date)->format('H:i'),
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
        $attendanceSession=AttendanceSession::whereId($id)->with('class.level','attendance')->first();
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

    public function deleteAll(Request $request)
    {
        $request->validate([
            'ids' => 'array',
            'ids.*' => 'integer',
        ]);
        $attendanceSession=AttendanceSession::whereIn('id',$request->ids)->delete();

        return HelperController::api_response_format(200 , null , __('messages.attendance_session.delete_all'));
    }

    public function takeAttendance(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:attendance_sessions,id',
            'users' => 'required|array',
            'users.*.status' => 'required|string|in:Present,Late,Excuse,Absent',
            'users.*.id' => 'required|exists:users,id',
        ]);

        $session=AttendanceSession::find($request->session_id);
        $gradeCat=GradeCategory::where('instance_type','Attendance')->where('instance_id',$session->attendance_id)
                    ->where('type','item')->first();

        foreach($request->users as $user)
        {
            SessionLog::updateOrCreate([
                'session_id' => $request->session_id,
                'user_id' => $user['id'],
                'taken_by' => Auth::id()
            ],[
                'status' => $user['status'],
            ]);

            $session->taken=1;
            $session->save();

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

    public function LogsAttendance(Request $request,$export=0)
    {
        $request->validate([
            'session_id' => 'required|exists:attendance_sessions,id'
        ]);
        $all=SessionLog::where('session_id',$request->session_id)->with('user','session')->get();
        if($export == 1)
            return $all;
        
        return HelperController::api_response_format(200 , $all,null);
    }

    public function exportLogs(Request $request)
    {
        $allLogs=self::LogsAttendance($request,1);
        $file = Excel::store(new AttendanceLogsExport($allLogs), 'AttendanceLogs.xlsx','public');
        $file = url(Storage::url('AttendanceLogs.xlsx'));
        return HelperController::api_response_format(201,$file, __('messages.success.link_to_file'));
    }

    public function CountStatus(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:attendance_sessions,id'
        ]);

        $all=SessionLog::where('session_id',$request->session_id)->get();
        $attendees_object['Total']['count'] = $all->count();

        $attendees_object['Present']['count'] = $all->where('status','Present')->count();
        $attendees_object['Absent']['count'] =  $all->where('status','Absent')->count();
        $attendees_object['Late']['count'] =  $all->where('status','Late')->count();
        $attendees_object['Excuse']['count'] =  $all->where('status','Excuse')->count();

        $attendees_object['Present']['precentage'] = round((($attendees_object['Present']['count']/$attendees_object['Total']['count'])*100),2);
        $attendees_object['Absent']['precentage'] =  round((($attendees_object['Absent']['count']/$attendees_object['Total']['count'])*100),2);
        $attendees_object['Late']['precentage'] =  round((($attendees_object['Late']['count']/$attendees_object['Total']['count'])*100),2);
        $attendees_object['Excuse']['precentage'] =  round((($attendees_object['Excuse']['count']/$attendees_object['Total']['count'])*100),2);

        return HelperController::api_response_format(201,$attendees_object, 'Counts');
    }
}
